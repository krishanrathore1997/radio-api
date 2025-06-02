<?php

namespace App\Traits;

use getID3;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

trait SongMethods
{
    /**
     * Extract metadata from an audio file (UploadedFile or file path).
     *
     * @param \Illuminate\Http\UploadedFile|string $file
     * @return array
     */
    public function getMetaData($file): array
    {
        $meta = [];

        try {
            $getID3 = new getID3;

            // Determine path
            $path = $file instanceof UploadedFile ? $file->getPathname() : (string) $file;

            if (!file_exists($path)) {
                return [];
            }

            $info = $getID3->analyze($path);

            $meta['title']    = $info['tags']['id3v2']['title'][0] ?? null;
            $meta['artist']   = $info['tags']['id3v2']['artist'][0] ?? null;
            $meta['album']    = $info['tags']['id3v2']['album'][0] ?? null;
            $meta['year']     = $info['tags']['id3v2']['year'][0] ?? null;
            $meta['duration'] = $info['playtime_string'] ?? null;

            // Cover image (if available)
            if (!empty($info['comments']['picture'][0]['data'])) {
                $meta['cover'] = [
                    'data'       => $info['comments']['picture'][0]['data'],
                    'image_mime' => $info['comments']['picture'][0]['image_mime'],
                ];
            }

        } catch (\Throwable $e) {
            Log::error('Metadata extraction failed: ' . $e->getMessage());
            return [];
        }

        return $meta;
    }
}
