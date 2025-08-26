<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardAppointmentResource extends JsonResource
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
            'date' => $this->date,
            'medical_status' => $this->status,
            'type' => $this->type,
            'with_medical_report' => (bool) $this->with_medical_report,
            'financial_status' => $this->payment_status ? 'paid' : 'unpaid',
            'total_price' => $this->final_total_price,
            'slot' => [
                'id' => $this->whenLoaded('slot', $this->slot->id),
                'time' => $this->whenLoaded('slot', $this->slot->start_time),
            ],
            'doctor' => [
                'id' => $this->whenLoaded('doctor', $this->doctor->id),
                'name' => $this->whenLoaded('doctor', $this->doctor->employee->first_name . ' ' . $this->doctor->employee->last_name),
            ],
            'department' => [
                'id' => $this->whenLoaded('department', $this->department->id),
                'name' => $this->whenLoaded('department', $this->department->name),
            ],
            'patient' => [
                'id' => $this->whenLoaded('patient', $this->patient->id),
                'name' => $this->whenLoaded('patient', $this->patient->user->first_name . ' ' . $this->patient->user->last_name),
            ],
        ];
    }
}
