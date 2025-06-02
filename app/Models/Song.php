<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SongMethods; // ✅ Import your trait
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Song extends Model implements HasMedia
{
    use SongMethods ,InteractsWithMedia;

    protected $table = 'songs';

    protected $fillable = ['category_id','playlist_id','title', 'artist', 'album', 'length','year','file', 'image','bpm'];

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class);
    }

    public function getSongUrl(): ?string
    {
        return $this->getFirstMediaUrl('audio');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('audio')->useDisk('public');
        // $this->addMediaCollection('cover')->useDisk('public')->maxFileSize(50 * 1024 * 1024);
    }
}
