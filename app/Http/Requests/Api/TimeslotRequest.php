<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TimeslotRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
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
