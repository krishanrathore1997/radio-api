<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    //
    protected $table = 'playlists';

    protected $fillable = ['name', 'description'];

    public function brands()
{
    return $this->belongsToMany(Brand::class);
}

public function songs()
{
    return $this->belongsToMany(Song::class)
                ->withPivot('order')
                ->withTimestamps()
                ->orderBy('pivot_order');
}


public function schedules()
{
    return $this->hasMany(PlaylistSchedule::class);
}

}
