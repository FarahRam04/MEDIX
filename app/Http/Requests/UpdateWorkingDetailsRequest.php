<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkingDetailsRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'start_time' => 'sometimes|date_format:g:i A',
            'end_time' => 'sometimes|date_format:g:i A|after:start_time',
            'days' => 'sometimes|array|min:1',
            'days.*' => 'required_with:days|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'
        ];
    }
}
