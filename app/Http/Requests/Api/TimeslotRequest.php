<?php

namespace App\Http\Requests\Api;

use App\Models\Region;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TimeslotRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
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

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(
            $validator->errors(),
            422
        ));
    }
}
