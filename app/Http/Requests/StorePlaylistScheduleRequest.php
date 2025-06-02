<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\PlaylistSchedule;

class StorePlaylistScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'playlist_id' => 'required|exists:playlists,id',
            'schedule_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',

        ];
    }

    public function messages(): array
    {
        return [
            'playlist_id.required' => 'Playlist is required.',
            'playlist_id.exists' => 'The selected playlist does not exist.',
            'schedule_date.required' => 'Schedule date is required.',
            'schedule_date.date' => 'Schedule date must be a valid date.',
            'start_time.required' => 'Start time is required.',
            'start_time.date_format' => 'Start time must be in H:i format.',
            'end_time.required' => 'End time is required.',
            'end_time.date_format' => 'End time must be in H:i format.',
            'end_time.after' => 'End time must be after start time.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $playlistId = $this->input('playlist_id');
            $scheduleDate = $this->input('schedule_date');
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            if ($playlistId && $scheduleDate && $startTime && $endTime) {
                $conflict = PlaylistSchedule::where('schedule_date', $scheduleDate)
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->where(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<', $endTime)
                              ->where('end_time', '>', $startTime);
                        });
                    })
                    // Optional: only check for conflicts with the same playlist
                    ->where('playlist_id', $playlistId)
                    ->exists();

                if ($conflict) {
                    $validator->errors()->add('start_time', 'This playlist is already scheduled during the selected time range.');
                }
            }
        });
    }
}
