<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class   BookAppointmentRequest extends FormRequest
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
            'department_id'    => 'required|exists:departments,id',
            'doctor_id'       => 'required|exists:employees,id',
            'request_type_id' => 'required|in:1,2',
            'date'            => 'required|date|after_or_equal:today',
            'slot_id'         => 'required|exists:available_slots,id',
            'with_medical_report'=>'boolean',
        ];
    }
}
