<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->user->first_name . ' ' . $this->user->last_name,
            'email' => $this->user->email,
            'age' => $this->user->birth_date,

            'vitals' => [
                'heart_rate'   => $this->heart_rate,
                'blood_group'  => $this->blood_group,
                'temperature'  => $this->temperature,
                'weight'       => $this->weight,
                'height'       => $this->height,
                'pressure'     => $this->pressure,
                'blood_sugar'  => $this->blood_sugar,
            ]];
    }
}
