<?php

namespace App\Console\Commands;

use App\Events\NowPlayingEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\PlaylistSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PlayScheduledPlaylists extends Command
{
    protected $signature = 'run:scheduled-playlists';
    protected $description = 'Play scheduled playlists';

    public function handle()
    {
        $now = now(); // UTC
        $today = $now->toDateString();

        $schedules = PlaylistSchedule::with(['playlist.songs'])
            ->where('schedule_date', $today)
            ->get();

        foreach ($schedules as $schedule) {
            $startTime = Carbon::parse("{$schedule->schedule_date} {$schedule->start_time}");
            $endTime = Carbon::parse("{$schedule->schedule_date} {$schedule->end_time}");

            if ($now->lt($startTime) || $now->gte($endTime)) {
                Log::debug("⏩ Skipping schedule ID {$schedule->id} — Not active now");
                continue;
            }

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
                    Log::error("Invalid song duration for ID {$song->id}: '{$song->length}'");
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
                    ], now()->addSeconds(10));

                    break;
                }

                $position += $duration;
            }
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
