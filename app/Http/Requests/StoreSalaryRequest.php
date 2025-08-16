<?php

namespace App\Http\Requests;

use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryRequest extends FormRequest
{


    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $employeeId = $this->input('employee_id');
            $month = $this->input('month');
            $year = $this->input('year');

            if (!$employeeId || !$month || !$year) {
                return;
            }

            $salaryExists = Salary::where('employee_id', $employeeId)
                ->where('month', $month)
                ->where('year', $year)
                ->exists();

            if ($salaryExists) {
                $validator->errors()->add(
                    'employee_id',
                    'هذا الراتب موجود مسبقا. اذا أردت تغييره قم بتعديل .'
                );
            }
                $currentYear = Carbon::now()->year;
                $currentMonth = Carbon::now()->month;

                if ((int)$year === $currentYear && (int)$month > $currentMonth) {
                    $validator->errors()->add(
                        'month',
                        'لا يمكن إنشاء راتب لشهر يتجاوز الشهر الحالي في السنة الحالية.'
                    );
                }

        });
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|in:' . date('Y'),
            'penalty_deduction' => 'required|numeric|min:0',
            'status' => 'in:unpaid,paid,processing'
        ];
    }
    public function messages(): array
    {
        return [
            'year.in' => 'لا يمكن تسجيل راتب الا في السنة الحالية فقط.',
        ];
    }
}
