<?php

namespace App\Http\Requests\Api;

use App\Actions\Region\GetUserRegion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class HubRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $region = GetUserRegion::run();

                    $requestedDate = Carbon::parse($value, $region->timezone);
                    $maxDate = Carbon::now($region->timezone)->addDays(config('app.max_reservation_days', 30));

                    if ($requestedDate->gt($maxDate)) {
                        $maxDays = config('app.max_reservation_days', 30);
                        $fail("We only show availability for the next {$maxDays} days. Please select a date within this range.");
                    }
                },
            ],
            'guest_count' => ['required', 'integer', 'min:1'],
            'reservation_time' => ['required', 'date_format:H:i:s'],
            'timeslot_count' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'time_slot_offset' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'venue_id' => ['required', 'exists:venues,id'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(
            $validator->errors(), 422)
        );
    }
}
