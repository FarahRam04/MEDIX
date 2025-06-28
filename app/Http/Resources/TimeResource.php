<?php


namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
//            'first_name' => $this->employee->first_name,
//            'last_name' => $this->employee->last_name,
            'id'=>$this->id,
            'days' => $this->days->pluck('day_name'),
            'start_time' => Carbon::createFromFormat('H:i:s', $this->start_time)->format('g:i A'),
            'end_time' => Carbon::createFromFormat('H:i:s', $this->end_time)->format('g:i A'),
        ];
    }
}
