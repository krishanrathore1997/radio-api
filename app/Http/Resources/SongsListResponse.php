<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SongsListResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
                'id' => $this->id,
                'title' => $this->title,
                'artist' => $this->artist ?? null,
                'album' => $this->album ?? null,
                'length' => $this->length ?? null,
                'bpm' => $this->bpm ?? null,
                'year' => $this->year ?? null,
                'file_url' => $this->getSongUrl(),
                'cover_image' =>  url("/api/songs/{$this->id}/cover_image"),
        ];
    }

}
