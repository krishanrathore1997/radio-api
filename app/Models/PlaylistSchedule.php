<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaylistSchedule extends Model
{
    //
    protected $table = 'playlist_schedules';

    protected $fillable = ['playlist_id', 'start_time', 'end_time', 'schedule_date'];

    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }
}
