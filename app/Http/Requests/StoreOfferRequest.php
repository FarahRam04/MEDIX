<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferRequest extends FormRequest
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
            'doctor_id' => 'required|exists:doctors,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'payment_method' => 'required|in:cash,points',
            'discount_cash' => 'required_if:payment_method,cash|nullable|numeric|min:0',
            'points_required' => 'required_if:payment_method,points|nullable|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'offer_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('offers')->where(function ($query) {
                    return $query->where('doctor_id', $this->doctor_id)
                        ->where('start_date', $this->start_date)
                        ->where('end_date', $this->end_date)
                        ->where('payment_method',$this->payment_method);
                }),
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'offer_name.unique' => 'لا يمكن إضافة هذا العرض لأنه موجود بالفعل بنفس التفاصيل (نفس الطبيب ونفس الفترة الزمنية).',
        ];
    }
}
