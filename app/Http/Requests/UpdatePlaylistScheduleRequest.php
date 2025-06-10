<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\PlaylistSchedule;

class UpdatePlaylistScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:playlist_schedules,id',
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
                $currentScheduleId = $this->route('playlist_schedule'); // assumes route model binding or id in URL

                $conflict = PlaylistSchedule::where('schedule_date', $scheduleDate)
                    ->where('playlist_id', $playlistId)
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<', $endTime)
                              ->where('end_time', '>', $startTime);
                    })
                    ->where('id', '!=', $currentScheduleId)
                    ->exists();

                if ($conflict) {
                    $validator->errors()->add('start_time', 'This playlist is already scheduled during the selected time range.');
                }
            }
        });
    }
}
