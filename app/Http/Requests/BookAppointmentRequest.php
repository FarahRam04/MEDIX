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
            'user_id' => 'required|exists:users,id',
            'slot_id' => 'required|exists:available_slots,id',
            'type' => 'required|in:check_up,follow_up',
            'specialization' => 'required|string',
            'status' => 'required|in:pending,completed',
            'check_up_price' => 'required|numeric',
            'lab_tests' => 'sometimes|boolean',
            'total_price' => 'required|numeric',
            'payment_status' => 'required|boolean',
        ];
    }
}
