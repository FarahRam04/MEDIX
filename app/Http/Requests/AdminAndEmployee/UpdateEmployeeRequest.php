<?php

namespace App\Http\Requests\AdminAndEmployee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
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
        $employeeId = $this->route('id');
        return [
            'first_name' => 'sometimes|required|string',
            'last_name'  => 'sometimes|required|string',
            'email'      => 'sometimes|required|email|unique:employees,email,'. $employeeId,
            'salary'     => 'sometimes|required|numeric',
            'role'       => 'sometimes|required|string|in:doctor,receptionist',
        ];
    }
}


