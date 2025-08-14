<?php

namespace App\Http\Requests;

use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVacationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_day' => 'sometimes|date',
            'end_day' => 'sometimes|date|after_or_equal:start_day',
            'days' => 'sometimes|required|integer|min:1',
            'paid' => 'sometimes|boolean',
            'deduction' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string',
            'status' => 'in:active,expired,cancelled',
        ];
    }
}
