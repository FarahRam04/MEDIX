<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
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
            'doctor_id' => 'sometimes|required|exists:doctors,id',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'payment_method' => 'sometimes|required|in:cash,points',
            'discount_cash' => 'required_if:payment_method,cash|nullable|numeric|min:0',
            'points_required' => 'required_if:payment_method,points|nullable|integer|min:0',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'offer_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('offers')->where(function ($query) {
                    return $query->where('doctor_id', $this->doctor_id)
                        ->where('id','!=',$this->id)
                        ->where('start_date', $this->start_date)
                        ->where('end_date', $this->end_date);
                })
            ],
        ];
    }
}
