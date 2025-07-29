<?php

namespace App\Http\Requests\Api;

use App\Models\ScheduleTemplate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class BookingCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) {
                    $scheduleTemplate = ScheduleTemplate::query()->find($this->input('schedule_template_id'));
                    $timezone = $scheduleTemplate?->venue?->inRegion?->timezone ?? config('app.timezone');

                    $date = Carbon::createFromFormat('Y-m-d', $value, $timezone);
                    $today = Carbon::now($timezone)->startOfDay();
                    $maxDate = Carbon::now($timezone)->addDays(config('app.max_reservation_days', 30));

                    if ($date->lt($today)) {
                        $fail('The date must not be in the past.');
                    }

                    if ($date->gt($maxDate)) {
                        $maxDays = config('app.max_reservation_days', 30);
                        $fail("We only show availability for the next {$maxDays} days. Please select a date within this range.");
                    }
                },
            ],
            'schedule_template_id' => ['required', 'integer'],
            'guest_count' => ['required', 'integer'],
            'vip_code' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.date_format' => 'The date must be in YYYY-MM-DD format.',
            'date.after_or_equal' => 'The date must not be in the past.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(
            $validator->errors(), 422)
        );
    }
}
