<?php

namespace App\Http\Requests\Api;

use App\Rules\ActiveRegion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'region' => ['required', new ActiveRegion],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(
            $validator->errors(), 422)
        );
    }
}
