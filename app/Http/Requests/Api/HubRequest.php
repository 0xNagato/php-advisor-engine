<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class HubRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'guest_count' => ['required', 'integer', 'min:1'],
            'reservation_time' => ['required', 'date_format:H:i:s'],
            'timeslot_count' => ['sometimes', 'integer', 'min:1', 'max:10'],
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
