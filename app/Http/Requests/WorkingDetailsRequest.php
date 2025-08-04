<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkingDetailsRequest extends FormRequest
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
            'employee_id' => 'required|exists:employees,id|unique:times,employee_id',
            'start_time' => 'required|date_format:g:i A',
            'end_time' => 'required|date_format:g:i A|after:start_time',
            'days' => 'required|array|min:1',
            'days.*' => 'string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'
        ];
    }
}
