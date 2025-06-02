<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

class NowPlayingEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $song;
    public $startOffset;
    public function __construct($song, $startOffset = 0)
    {
        $this->song = $song;
        $this->startOffset = $startOffset;
    }

    public function broadcastOn()
    {
        return new Channel('now-playing');
    }

    public function broadcastAs()
    {
        return 'NowPlayingEvent';
    }

    public function broadcastWith()
    {
        return [
            'title' => $this->song->title,
            'file_url' => $this->song->hasMedia('audio') ? $this->song->getFirstMediaUrl('audio') : null,
            'start_offset' => $this->startOffset,
        ];
    }
}

