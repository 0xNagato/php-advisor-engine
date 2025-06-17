<?php

namespace App\Http\Requests\Api;

use App\Actions\Region\GetUserRegion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class CalendarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'guest_count' => ['required', 'integer', 'min:1'],
            'reservation_time' => [
                'required',
                'date_format:H:i:s',
                function ($attribute, $value, $fail) {
                    $date = $this->input('date');
                    if (! $date) {
                        return; // Date validation will fail separately
                    }

                    // Get the user's region for timezone
                    $region = GetUserRegion::run();

                    // Create Carbon instances for the reservation date/time and current time
                    $reservationDateTime = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $date.' '.$value,
                        $region->timezone
                    );

                    $currentTime = Carbon::now($region->timezone);
                    $minimumTime = $currentTime->copy()->addMinutes(30);

                    // Only apply this validation if the reservation is for today
                    if ($reservationDateTime->isSameDay($currentTime) && $reservationDateTime->lt($minimumTime)) {
                        $fail('The reservation time must be at least 30 minutes from now.');
                    }
                },
            ],
            'timeslot_count' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'cuisine' => ['sometimes', 'array'],
            'cuisine.*' => ['string'],
            'neighborhood' => ['sometimes', 'string'],
            'specialty' => ['sometimes', 'array'],
            'specialty.*' => ['string'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(
            $validator->errors(),
            422
        ));
    }
}
