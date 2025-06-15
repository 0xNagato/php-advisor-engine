<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CalendarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'guest_count' => ['required', 'integer', 'min:1'],
            'reservation_time' => ['required', 'date_format:H:i:s'],
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
            $validator->errors(), 422)
        );
    }
}
