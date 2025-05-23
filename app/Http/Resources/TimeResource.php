<?php


namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'first_name' => $this->employee->first_name,
            'last_name' => $this->employee->last_name,
            'days' => $this->days->pluck('day_name'),
            'start_time' => Carbon::createFromFormat('H:i:s', $this->start_time)->format('H:i A'),
            'end_time' => Carbon::createFromFormat('H:i:s', $this->end_time)->format('H:i A'),
        ];
    }
}
