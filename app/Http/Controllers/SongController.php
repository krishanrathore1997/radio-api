<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSongsRequest;
use App\Http\Resources\SongsListResponse;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SongController extends Controller
{
    public function store(StoreSongsRequest $request)
    {
        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return $this->validationError('No valid audio file uploaded.');
        }
        $file = $request->file('file');
        $meta = (new Song)->getMetaData($file);

        if (!$meta) {
            return $this->validationError('Could not extract metadata from this file.');
        }

        if ($this->isDuplicate($meta)) {
            return $this->validationError('This song already exists in the system.');
        }

        $song = $this->createFromMeta($request, $meta);
        $song->addMedia($file)->toMediaCollection('audio');

        return response()->json([
            'message' => 'Song uploaded successfully.',
        ]);
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $song = Song::find($id);

        if (!$song) {
            return response()->json([
                'status' => false,
                'message' => 'Song not found.'
            ], 404);
        }

        $song->clearMediaCollection('audio');
        $song->delete();

        return response()->json([
            'status' => true,
            'message' => 'Song deleted successfully.'
        ]);
    }

    public function list()
    {
        $songs = Song::with('media')
            ->when(request('category_id'), function ($query) {
                $query->where('category_id', request('category_id'));
            })
            ->when(request('search'), function ($query) {
                $search = request('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('artist', 'like', "%{$search}%")
                        ->orWhere('album', 'like', "%{$search}%")
                        ->orWhere('length', 'like', "%{$search}%");
                });
            })
            ->get();

            return response()->json([
                'status' => true,
                'songs' => SongsListResponse::collection($songs),
                'message' => 'Song list fetched successfully.'
            ], 200, [], JSON_UNESCAPED_SLASHES);

    }

    public function coverImage($id)
    {
        $song = Song::findOrFail($id);
        $media = $song->getFirstMedia('audio');

        if (!$media || !$media->getPath()) {
            return response()->noContent();
        }

        $meta = $song->getMetaData($media->getPath());

        if (!isset($meta['cover']['data'], $meta['cover']['image_mime'])) {
            return response()->noContent();
        }

        return response($meta['cover']['data'])
            ->header('Content-Type', $meta['cover']['image_mime']);
    }

    // ========== Helper Methods ==========

    private function isDuplicate(array $meta): bool
    {
        return Song::where('title', $meta['title'] ?? null)
            ->where('artist', $meta['artist'] ?? null)
            ->exists();
    }

    private function createFromMeta(StoreSongsRequest $request, array $meta): Song
    {
        return Song::create([
            'category_id' => $request->input('category_id'),
            'title'       => $meta['title'] ?? 'Unknown Title',
            'artist'      => $meta['artist'] ?? 'Unknown Artist',
            'album'       => $meta['album'] ?? null,
            'length'      => $meta['duration'] ?? null,
            'bpm'         => $request->input('bpm'),
            'year'        => $meta['year'] ?? null,
        ]);
    }

    private function formatSong(Song $song): ?array
    {
        $media = $song->getFirstMedia('audio');
        if (!$media || !$media->getPath()) {
            return null;
        }

        return [
            'id'          => $song->id,
            'title'       => $song->title,
            'artist'      => $song->artist,
            'album'       => $song->album,
            'length'      => $song->length,
            'bpm'         => $song->bpm,
            'year'        => $song->year,
            'file_url'    => $media->getFullUrl(),
            'cover_image' => url("/api/songs/{$song->id}/cover_image"),
        ];
    }

    private function validationError(string $message)
    {
        throw ValidationException::withMessages(['file' => [$message]]);
    }
}
