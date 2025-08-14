<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VacationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'start_day' => $this->start_day,
            'end_day' => $this->end_day,
            'days_count' => $this->days,
            'paid' => $this->paid,
            'deduction' => $this->deduction,
            'reason' => $this->reason,
            'status' => $this->status,
            'employee' => [
                'id' => $this->employee->id,
                'name' => $this->employee->first_name . ' ' . $this->employee->last_name,
                'email' => $this->employee->email,
            ],
        ];
    }
}
