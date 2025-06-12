<?php

namespace App\Console\Commands;

use App\Events\NowPlayingEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\PlaylistSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PlayScheduledPlaylists extends Command
{
    protected $signature = 'run:scheduled-playlists';
    protected $description = 'Play scheduled playlists';

    public function handle()
    {
        $timezone = config('app.timezone', 'Asia/Kolkata');
        $now = now()->setTimezone($timezone);

        $schedules = PlaylistSchedule::with(['playlist.songs'])
            ->whereDate('start_time', $now->toDateString())
            ->get();

        $hasActiveSchedule = false;

        foreach ($schedules as $schedule) {
            $startTime = Carbon::parse($schedule->start_time, 'UTC')->setTimezone($timezone);
            $endTime   = Carbon::parse($schedule->end_time, 'UTC')->setTimezone($timezone);


            $this->info("🔁 Running scheduled playlists at {$now} — {$startTime} to {$endTime} for schedule ID {$schedule->id}");

            if ($now->lt($startTime) || $now->gte($endTime)) {
                Log::debug("⏩ Skipping schedule ID {$schedule->id} start: {$startTime} end: {$endTime} — not active now");
                continue;
            }

            $hasActiveSchedule = true;

            $playlist = $schedule->playlist;
            if (!$playlist || $playlist->songs->isEmpty()) {
                Log::warning("⚠️ No songs in playlist for schedule ID {$schedule->id}");
                continue;
            }

            $elapsedSinceStart = $startTime->diffInSeconds($now);
            $songs = $playlist->songs;
            $durations = [];
            $totalDuration = 0;

            foreach ($songs as $song) {
                $seconds = $this->parseDurationToSeconds($song->length);
                if ($seconds === null || $seconds <= 0) {
                    Log::error("❌ Invalid song duration for ID {$song->id}: '{$song->length}'");
                    continue;
                }
                $durations[] = $seconds;
                $totalDuration += $seconds;
            }

            if ($totalDuration === 0) {
                Log::warning("⚠️ Playlist ID {$playlist->id} has total duration 0");
                continue;
            }

            $loopTime = $elapsedSinceStart % $totalDuration;
            $position = 0;

            foreach ($songs as $index => $song) {
                $duration = $durations[$index];

                if ($loopTime < $position + $duration) {
                    $offset = $loopTime - $position;

                    Log::info("🎵 Now playing: {$song->title}", [
                        'file_url' => $song->file_url,
                        'offset' => $offset,
                    ]);

                    event(new NowPlayingEvent($song, $offset));

                    Cache::put('now_playing', [
                        'title' => $song->title,
                        'file_url' => $song->hasMedia('audio') ? $song->getFirstMediaUrl('audio') : null,
                        'started_at' => now()->subSeconds($offset)->timestamp,
                        'duration' => $this->parseDurationToSeconds($song->length),
                        'is_live' => true
                    ]);

                    break;
                }

                $position += $duration;
            }
        }

        // 🛑 Clear cache if no schedule is active
        if (!$hasActiveSchedule) {
            Cache::forget('now_playing');
            Log::info("🛑 All schedules ended — now_playing cache cleared");
        }
    }

    private function parseDurationToSeconds(string $duration): ?int
    {
        $duration = trim($duration);

        if (preg_match('/^(\d+):(\d{2})$/', $duration, $matches)) {
            return ((int) $matches[1]) * 60 + (int) $matches[2];
        }

        if (is_numeric($duration)) {
            return (int) $duration;
        }

        return null;
    }
}
