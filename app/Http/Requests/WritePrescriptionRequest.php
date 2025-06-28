<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WritePrescriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [

            'medications' => 'sometimes|required|array|min:1',
            'medications.*' => 'required|array',
            'medications.*.name' => 'required|string',
            'medications.*.dosage' => 'required|string',
            'medications.*.duration' => 'required|string',

            'lab_tests' => 'sometimes|required|array|min:1',
            'lab_tests.*' => 'string',

            'surgeries' => 'sometimes|required|array|min:1',
            'surgeries.*' => 'string',

            'advices' => 'sometimes|required|array|min:1',
            'advices.*' => 'string',
        ];

    }
}
