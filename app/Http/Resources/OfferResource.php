<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
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
            'offer_name' => $this->offer_name,
            'image_url' => url($this->image),
            'doctor' => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->employee->first_name . ' ' . $this->doctor->employee->last_name,
            ],
            'department' => [
                'id' => $this->doctor->department->id,
                'name' => $this->doctor->department->name,
            ],
            'shift' => $this->shift,
            'payment_details' => [
                'type' => $this->payment_method, // 'cash' or 'points'
                'value' => $this->payment_method === 'cash' ? (int)$this->discount_cash : (int)$this->points_required,
                'currency' => $this->payment_method === 'cash' ? 'SYP' : 'Points',
            ],
            'duration' => [
                'starts_on' => $this->start_date,
                'ends_on' => $this->end_date,
            ],

            'created_at' => $this->created_at->format('Y-m-d H:i:s'), // تنسيق تاريخ الإنشاء
        ];
    }
}
