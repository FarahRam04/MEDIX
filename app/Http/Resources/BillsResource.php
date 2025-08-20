<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillsResource extends JsonResource
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
                'status' => $this->payment_status === 0 ? 'unpaid' : 'paid',
                'total_price' => $this->total_price,
                'currency' => 'SYP',
                'doctor_name' => $this->doctor->employee->first_name . ' ' . $this->doctor->employee->last_name,
                'department' => $this->department->name,
                'appointment_date_time' => Carbon::parse($this->date . ' ' . optional($this->slot)->start_time)->format('Y-m-d\TH:i:s')
            ];

    }
}
