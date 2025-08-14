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
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (empty($this->all())) {
                return;
            }
            // نحصل على بيانات الإجازة الحالية والموظف المرتبط بها
            $vacationToUpdate = $this->vacation;
            $employee = $vacationToUpdate->employee;
            // نستخدم البيانات الجديدة إذا كانت موجودة، وإلا نستخدم القديمة
            $startDay = $this->start_day ?? $vacationToUpdate->start_day;
            $endDay = $this->end_day ?? $vacationToUpdate->end_day;
            $days = $this->days ?? $vacationToUpdate->days;

            if ($this->has('days') || $this->has('start_day') || $this->has('end_day')) {
                $start = Carbon::parse($startDay);
                $end = Carbon::parse($endDay);
                $calculatedDays = $start->diffInDays($end) + 1;

                if ($days != $calculatedDays) {
                    $validator->errors()->add(
                        'days',
                        'عدد الأيام المدخل (' . $days . ') لا يتطابق مع الفترة المحددة. الصحيح هو: ' . $calculatedDays
                    );
                    return;}}

            // --- الشرط 1: لا يمكن تعديل إجازة قديمة (منتهية أو ملغاة) ---
            if (in_array($vacationToUpdate->status, ['expired', 'cancelled']) && $this->status !== 'active') {
                $validator->errors()->add('status', 'لا يمكن تعديل إجازة منتهية أو ملغاة.');
                return;
            }
            // --- الشرط 2: التحقق من تداخل الإجازة المعدلة مع إجازات أخرى ---
            $isOverlapping = Vacation::where('employee_id', $employee->id)
                ->where('id', '!=', $vacationToUpdate->id) // تجاهل الإجازة نفسها التي نعدلها
                ->where('status', '!=', 'cancelled')
                ->where('start_day', '<=', $endDay)
                ->where('end_day', '>=', $startDay)
                ->exists();

            if ($isOverlapping) {
                $validator->errors()->add('start_day', 'فترة الإجازة المعدلة تتداخل مع إجازة أخرى.');
            }
            // --- الشرط 3: التحقق من رصيد الإجازات بعد التعديل ---
            // نحصل على الرصيد الحالي للإجازات
            $usedDays = $employee->vacations()
                ->where('id', '!=', $vacationToUpdate->id) // استثناء الإجازة الحالية من المجموع
                ->whereIn('status', ['active', 'expired'])
                ->sum('days');

            // نحدد الحد الأقصى
            $maxDays = ($employee->role === 'doctor') ? 40 : 14;

            // الرصيد الإجمالي بعد التعديل سيكون: الرصيد القديم + الأيام الجديدة
            $totalAfterUpdate = $usedDays + $days;

            if ($totalAfterUpdate > $maxDays) {
                $validator->errors()->add('days', 'رصيد الإجازات سيتجاوز الحد المسموح به بعد هذا التعديل.');
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
