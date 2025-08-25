<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorProfileRequest extends FormRequest
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
                'certificate'            => 'nullable|string|max:255',
                'medical_license_number' => 'nullable|string|max:255',
                'bio'                    => 'nullable|string',
                'years_of_experience'    => 'nullable|integer|min:0',
                'image'                  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'qualifications'         => 'nullable|array',
                'qualifications.*'       => 'string|max:255', // كل عنصر في المصفوفة يجب أن يكون نصاً

        ];
    }
}
