<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
        public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'doctors' => $this->whenLoaded('doctors', function () {
                return $this->doctors->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'first_name' => optional($doctor->employee)->first_name,
                        'last_name' => optional($doctor->employee)->last_name,
                        'medical_license_number' => $doctor->medical_license_number,
                        // يمكنك إضافة الحقول الأخرى من جدول الأطباء هنا حسب حاجتك
                    ];
                });
            }),

        ];
    }

}
