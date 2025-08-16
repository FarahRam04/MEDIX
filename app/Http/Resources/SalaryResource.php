<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'month'=>$this->month,
            'year'=>$this->year,
            'salary_period' => "{$this->month} / {$this->year}", // يمكننا دمج الحقول
            'status' => $this->status,
            'employee' => [
                'id' => $this->employee->id,
                'first_name' => $this->employee->first_name,
                'last_name' => $this->employee->last_name,
                'role' => $this->employee->role
            ],
            'financials' => [
                'base_salary' => (float) $this->base_salary,
                'leave_deduction' => (float) $this->leave_deduction,
                'penalty_deduction' => (float) $this->penalty_deduction,
                'final_salary' => (float) $this->final_salary,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
