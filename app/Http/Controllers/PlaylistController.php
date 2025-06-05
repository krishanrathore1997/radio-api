<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaylistRequest;
use App\Http\Resources\PlayListResponse;
use App\Models\Playlist;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
 public function store(PlaylistRequest $request)
{
    try {
        $playlist = Playlist::create([
            'name' => $request->name,
        ]);

        if ($playlist && $request->song_ids) {
            $playlist->songs()->sync($request->song_ids);
        }

        return response()->json([
            'message' => 'Playlist created successfully!',
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Failed to create playlist!',
            'error' => $th->getMessage(),
        ], 500);
    }
}
public function update(PlaylistRequest $request, $id)
{
    try {
        $playlist = Playlist::findOrFail($id);

        $playlist->update([
            'name' => $request->name,
        ]);

        if ($request->song_ids) {
            $playlist->songs()->sync($request->song_ids);
        }

        return response()->json([
            'message' => 'Playlist updated successfully!',
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Failed to update playlist!',
            'error' => $th->getMessage(),
        ], 500);
    }
}
public function show($id)
{
    try {
        $playlist = Playlist::with('songs')->findOrFail($id);

        return response()->json([
            'playlist' => $playlist,
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Failed to fetch playlist!',
            'error' => $th->getMessage(),
        ], 404);
    }
}

public function delete($id)
{
    try {
        $playlist = Playlist::findOrFail($id);
        $playlist->delete();

        return response()->json([
            'message' => 'Playlist deleted successfully!',
        ], 200);
    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Failed to delete playlist!',
            'error' => $th->getMessage(),
        ], 500);
    }

}

public function list()
{
    try {
        $playlists = Playlist::with('songs')->get();

        return response()->json([
            'playlists' => PlayListResponse::collection($playlists),
            'message' => 'Playlists fetched successfully!',
        ], 200);
    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Failed to fetch playlists!',
            'error' => $th->getMessage(),
        ], 500);
    }
    }
}
