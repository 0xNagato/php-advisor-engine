<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use libphonenumber\PhoneNumberType;
use Propaganistas\LaravelPhone\Rules\Phone;

class BookingUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                (new Phone)
                    ->country(config('app.countries'))
                    ->type(PhoneNumberType::MOBILE)
                    ->lenient(),
            ],
            'email' => ['nullable', 'email'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'bookingUrl' => ['required', 'url'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(
            $validator->errors(), 422)
        );
    }

    public function messages(): array
    {
        return [
            'phone' => 'The phone field must be a valid phone number.',
        ];
    }
}
