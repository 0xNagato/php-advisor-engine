<?php

namespace App\Http\Requests\Api;

use App\Actions\Region\GetUserRegion;
use App\Models\Region;
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
                function ($attribute, $value, $fail) {
                    // Accept "H:i" or "H:i:s"
                    if (! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
                        $fail('The reservation time must be in the format HH:MM or HH:MM:SS.');

                        return;
                    }

                    $date = $this->input('date');
                    if (! $date) {
                        return; // Date validation will fail separately
                    }

                    // Get the region for timezone - use the provided region or fall back to the user's region
                    $regionId = $this->input('region');
                    $region = $regionId ? Region::query()->where('id', $regionId)->first() : null;
                    $region = $region ?: GetUserRegion::run();

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
            'time_slot_offset' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'cuisine' => ['sometimes', 'array'],
            'cuisine.*' => ['string'],
            'neighborhood' => ['sometimes', 'string'],
            'specialty' => ['sometimes', 'array'],
            'specialty.*' => ['string'],
            'region' => [
                'sometimes',
                'string',
                function ($attribute, $value, $fail) {
                    // Validate that the region exists in the Region Sushi model
                    if ($value && ! Region::query()->where('id', $value)->exists()) {
                        $fail('The selected region is invalid.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reservation_time')) {
            $time = $this->input('reservation_time');
            if (preg_match('/^\d{2}:\d{2}$/', (string) $time)) {
                $this->merge([
                    'reservation_time' => $time.':00',
                ]);
            }
        }
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(
            $validator->errors(),
            422
        ));
    }
}
