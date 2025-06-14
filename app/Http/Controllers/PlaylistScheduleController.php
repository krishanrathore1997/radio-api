<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaylistScheduleRequest;
use App\Http\Requests\UpdatePlaylistScheduleRequest;
use App\Http\Resources\BrandListResponse;
use App\Http\Resources\PlayListResponse;
use App\Http\Resources\ScheduleListResponse;
use App\Models\PlaylistSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
class PlaylistScheduleController extends Controller
{
   public function store(StorePlaylistScheduleRequest $request)
{
 PlaylistSchedule::create([
    'playlist_id' => $request->playlist_id,
    'schedule_date' => $request->schedule_date,
    'start_time' => Carbon::parse($request->schedule_date . ' ' . $request->start_time),
    'end_time' => Carbon::parse($request->schedule_date . ' ' . $request->end_time),
]);

    return response()->json(['message' => 'Playlist scheduled successfully.']);
}

public function update(UpdatePlaylistScheduleRequest $request, $id)
{
    $schedule = PlaylistSchedule::find($id);

    if (!$schedule) {
        return response()->json(['message' => 'Playlist schedule not found.'], 404);
    }

    $nowPlaying = Cache::get('now_playing');
    if (
        $nowPlaying &&
        $schedule->playlist &&
        $schedule->playlist->songs->contains(function ($song) use ($nowPlaying) {
            return $song->title === $nowPlaying['title'];
        }) &&
        $request->playlist_id !== $schedule->playlist_id
    ) {
        Cache::forget('now_playing');
        \Log::info("🧹 now_playing cache cleared on playlist change for schedule ID: {$schedule->id}");
    }

    $schedule->update([
        'playlist_id' => $request->playlist_id,
        'schedule_date' => $request->schedule_date,
        'start_time' => Carbon::parse($request->schedule_date . ' ' . $request->start_time),
        'end_time' => Carbon::parse($request->schedule_date . ' ' . $request->end_time),
    ]);

    return response()->json(['message' => 'Playlist schedule updated successfully.']);
}


public function list()
{
    $data = PlaylistSchedule::with('playlist')->get();

    if ($data->isEmpty()) {
        return response()->json([
            'message' => 'No playlist schedules found.',
            'schedules' => [],
        ], 404);
    }
    return response()->json([
        'message' => 'Playlist schedules fetched successfully!',
        'list' => ScheduleListResponse::collection($data),
        'schedules' =>[],
    ], 200);
}

public function todaySchedule()
{
     $appTimezone = config('app.timezone', 'Asia/Kolkata');

    // Get "now" in UTC to match DB timestamps
    $todayUtc = Carbon::now('UTC')->toDateString();

    // Fetch today's schedule from DB (based on UTC date)
    $data = PlaylistSchedule::whereDate('schedule_date', $todayUtc)
        ->with('playlist.songs')
        ->first();

    if (!$data || !$data->playlist) {
        return response()->json([
            'message' => 'No playlist scheduled for today.',
            'playlist' => null,
            'start_time' => null,
            'end_time' => null,
        ], 404);
    }

    return response()->json([
    'message' => 'Playlist schedule fetched successfully!',
    'playlist' => new PlayListResponse($data->playlist),
    'start_time' => Carbon::parse($data->start_time, 'UTC')
        ->setTimezone($appTimezone)
        ->format('h:i A'), // 👈 12-hour time
    'end_time' => Carbon::parse($data->end_time, 'UTC')
        ->setTimezone($appTimezone)
        ->format('h:i A'), // 👈 12-hour time
], 200);
}
public function show($id)
{
    $data = PlaylistSchedule::with('playlist.songs')->find($id);

    if (!$data) {
        return response()->json([
            'message' => 'Playlist schedule not found.',
            'playlist' => null,
        ], 404);
    }

    return response()->json([
        'message' => 'Playlist schedule fetched successfully!',
        'playlist' => new PlayListResponse($data->playlist),
        'schedule_date' => Carbon::parse($data->schedule_date)
            ->setTimezone('Asia/Kolkata')
            ->format('Y-m-d'),
        'start_time' => Carbon::parse($data->start_time,'UTC')
            ->setTimezone('Asia/Kolkata')
            ->format('h:i A'),
        'end_time' => Carbon::parse($data->end_time,'UTC')
            ->setTimezone('Asia/Kolkata')
            ->format('h:i A'),
    ], 200);
}

public function destroy($id)
{
    $schedule = PlaylistSchedule::with('playlist.songs')->find($id);

    if (!$schedule) {
        return response()->json(['message' => 'Playlist schedule not found.'], 404);
    }

    $nowPlaying = Cache::get('now_playing');

    if (
        $nowPlaying &&
        $schedule->playlist &&
        $schedule->playlist->songs->contains(function ($song) use ($nowPlaying) {
            return $song->title === $nowPlaying['title'];
        })
    ) {
        Cache::forget('now_playing');
        \Log::info("🧹 now_playing cache cleared on schedule delete ID: {$schedule->id}");
    }

    $schedule->delete();

    return response()->json(['message' => 'Playlist schedule deleted successfully.']);
}
public function nowPlaying(Request $request)
{
    // Create a fingerprint using IP + User-Agent + Accept-Language
    $fingerprint = sha1(
        $request->ip() .
        $request->userAgent() .
        $request->header('Accept-Language', '')
    );

    $now = Carbon::now();
    $viewers = Cache::get('live_viewers', []);

    // Validate viewers array
    if (!is_array($viewers)) {
        $viewers = [];
    }

    // Filter inactive viewers (>2 minutes)
    $activeViewers = [];
    $count = 0;

    foreach ($viewers as $fp => $lastSeen) {
        try {
            $lastSeenTime = Carbon::parse($lastSeen);
            if ($now->diffInSeconds($lastSeenTime) <= 30) {
                $activeViewers[$fp] = $lastSeen;
                $count++;
            }
        } catch (\Exception $e) {
            // Ignore invalid entries
        }
    }

    // Update current fingerprint
    $activeViewers[$fingerprint] = $now->toDateTimeString();
    $count = count($activeViewers);

    // Update cache
    Cache::put('live_viewers', $activeViewers, now()->addSeconds(60));

    // Get now playing data
    $nowPlaying = Cache::get('now_playing', [
        'title' => 'No song playing',
        'file_url' => null,
        'started_at' => null,
        'duration' => null
    ]);

    return response()->json([
        'data' => $nowPlaying,
        'active_user_count' => $count,
        'fingerprint' => $fingerprint // For debugging
    ]);
}

}
