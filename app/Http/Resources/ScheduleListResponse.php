<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
class ScheduleListResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = Config::get('app.timezone', 'Asia/Kolkata');
        return [
            'id' => $this->id,
            'playlist_id' => $this->playlist_id,
            'playlist_name' => $this->playlist->name ?? 'N/A',
            'schedule_date' => Carbon::parse($this->schedule_date, 'UTC')->setTimezone($timezone)->toDateString(),
            'start_time' => Carbon::parse($this->start_time, 'UTC')->setTimezone($timezone)->format('h:i A'),
            'end_time' => Carbon::parse($this->end_time, 'UTC')->setTimezone($timezone)->format('h:i A'),
        ];
    }
}
