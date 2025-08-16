<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AppointmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'shift' => ($this->slot_id >= 1 && $this->slot_id <= 8) ? 'morning' : 'afternoon',
            'status' => ucfirst($this->status),
            'doctor_name' => $this->doctor && $this->doctor->employee
                ? $this->doctor->employee->first_name . ' ' . $this->doctor->employee->last_name
                : 'Unknown',
            'doctor_image' => $this->doctor?->image_url,
            'department' => $this->doctor?->department?->name,
            'date_time' => Carbon::parse($this->date . ' ' . optional($this->slot)->start_time)->format('Y-m-d\TH:i:s'),
            'request_type_id' => $this->type === 'check_up' ? 1 : 2,
            'with_medical_report' => (bool) $this->with_medical_report,
        ];
    }
}
