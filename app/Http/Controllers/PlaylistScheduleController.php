<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaylistScheduleRequest;
use App\Http\Resources\BrandListResponse;
use App\Http\Resources\PlayListResponse;
use App\Models\PlaylistSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
    ], 200);
}
}
