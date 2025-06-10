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
class PlaylistScheduleController extends Controller
{
   public function store(StorePlaylistScheduleRequest $request)
{
    PlaylistSchedule::create([
        'playlist_id' => $request->playlist_id,
        'schedule_date' => $request->schedule_date,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
    ]);

    return response()->json(['message' => 'Playlist scheduled successfully.']);
}

public function update(UpdatePlaylistScheduleRequest $request, $id)
{
    $schedule = PlaylistSchedule::find($id);

    if (!$schedule) {
        return response()->json(['message' => 'Playlist schedule not found.'], 404);
    }

    $schedule->update([
        'playlist_id' => $request->playlist_id,
        'schedule_date' => $request->schedule_date,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
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
    $today = Carbon::now()->toDateString();

    $data = PlaylistSchedule::whereDate('schedule_date', $today)
        ->with('playlist.songs')
        ->first();

    if (!$data) {
        return response()->json([
            'message' => 'No playlist scheduled for today.',
            'playlist' => null,
        ], 404);
    }

    return response()->json([
    'message' => 'Playlist schedule fetched successfully!',
    'playlist' => new PlayListResponse($data->playlist),
    'start_time' => Carbon::parse($data->start)
        ->setTimezone('Asia/Kolkata')
        ->format('h:i A'),
    'end_time' => Carbon::parse($data->end)
        ->setTimezone('Asia/Kolkata')
        ->format('h:i A'),
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
        'start_time' => Carbon::parse($data->start)
            ->setTimezone('Asia/Kolkata')
            ->format('h:i A'),
        'end_time' => Carbon::parse($data->end)
            ->setTimezone('Asia/Kolkata')
            ->format('h:i A'),
    ], 200);
}

public function destroy($id)
{
    $schedule = PlaylistSchedule::find($id);

    if (!$schedule) {
        return response()->json(['message' => 'Playlist schedule not found.'], 404);
    }

    $schedule->delete();

    return response()->json(['message' => 'Playlist schedule deleted successfully.']);
}

public function nowPlaying(Request $request)
{
    // Step 1: Get IP address of the current user
    $ip = $request->ip();
    $now = Carbon::now();

    // Step 2: Get and filter existing viewers from cache
    $viewers = Cache::get('live_viewers', []);

    // Remove old/inactive IPs (last seen > 2 mins ago)
    $viewers = array_filter($viewers, function ($lastSeen) use ($now) {
        return Carbon::parse($lastSeen)->gt($now->subMinutes(2));
    });

    // Step 3: Update current user's IP
    $viewers[$ip] = $now->toDateTimeString();
    Cache::put('live_viewers', $viewers, now()->addMinutes(3));

    // Step 4: Get now playing data
    $nowPlaying = Cache::get('now_playing', [
        'title' => 'No song playing',
        'file_url' => null,
        'started_at' => null,
        'duration' => null
    ]);

    // Step 5: Return both now playing and active user count
    return response()->json([
        'now_playing' => $nowPlaying,
        'active_user_count' => count($viewers)
    ]);
}
}
