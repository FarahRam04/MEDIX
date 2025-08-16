<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreVacationRequest extends FormRequest
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
            // --- التحقق من تطابق عدد الأيام ---
            $start = Carbon::parse($this->start_day);
            $end = Carbon::parse($this->end_day);
            $calculatedDays = $start->diffInDays($end) + 1; // +1 لتضمين يوم البداية

            // نقارن الأيام المحسوبة مع الأيام المطلوبة
            if ($this->days != $calculatedDays) {
                $validator->errors()->add(
                    'days',
                    'عدد الأيام المدخل لا يتطابق مع الفترة المحددة بين تاريخ البداية والنهاية,الصحيح هو'.$calculatedDays
                );
                return; // نتوقف هنا لأن البيانات الأساسية غير متناسقة
            }


            $employeeId = $this->employee_id;
            $employee = Employee::with('vacations')->find($employeeId);

            if (!$employee) {
                $validator->errors()->add('employee_id', 'الموظف غير موجود.');
                return;
            }

            $role = $employee->role;
            $maxDays = match ($role) {
                'doctor' => 40,
                'receptionist' => 14,
                default => 0,
            };

            $usedDays = $employee->vacations()
                ->whereIn('status', ['active', 'expired'])
                ->sum('days');

            $totalAfterRequest = $usedDays + $this->days; // نستخدم الأيام من الطلب

            if ($totalAfterRequest > $maxDays) {
                $validator->errors()->add('days', 'عدد أيام الإجازة المطلوبة يتجاوز الحد المسموح به لهذا الموظف.');
            }
            $startDay = $this->start_day;
            $endDay = $this->end_day;


            $isOverlapping = Vacation::where('employee_id', $employeeId)
                ->where('status', '!=', 'cancelled') // تجاهل الإجازات الملغاة
                ->where('start_day', '<=', $endDay)   // بداية الإجازة القديمة يجب أن تكون قبل أو في نفس يوم نهاية الإجازة الجديدة
                ->where('end_day', '>=', $startDay)     // نهاية الإجازة القديمة يجب أن تكون بعد أو في نفس يوم بداية الإجازة الجديدة
                ->exists(); // هل يوجد أي سجل يطابق هذه الشروط؟

            if ($isOverlapping) {
                $validator->errors()->add(
                    'start_day', // يمكن ربط الخطأ بـ start_day أو end_day
                    'توجد إجازة أخرى مسجلة لهذا الموظف تتداخل مع هذه الفترة.'
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
            'start_day' => 'required|date|after_or_equal:today',
            'end_day' => 'required|date|after_or_equal:start_day',
            'days' => 'required|integer|min:1|max:365',
            'paid' => 'boolean',
            'deduction' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string',
            'status' => 'in:active,expired,cancelled',
        ];
    }
}
