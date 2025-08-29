<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseData = (new DashboardAppointmentResource($this))->toArray($request);
        $visitDetails = [
            'prescription' => [
                'medications' => $this->whenLoaded('medications', function () {
                    return $this->medications->map(function ($med) {
                        return [
                            'id' => $med->id,
                            'name' => $med->name,
                            'type' => $med->type,
                            'dosage' => $med->dosage,
                            'frequency' => $med->frequency,
                            'duration' => $med->duration,
                            'note' => $med->note,
                        ];
                    });
                }),

                'lab_tests' => $this->whenLoaded('labTests', function () {
                    return $this->labTests->map(function ($test) {
                        return [
                            'id' => $test->id,
                            'name' => $test->name,
                        ];
                    });
                }),

                'surgeries' => $this->whenLoaded('surgeries', function () {
                    return $this->surgeries->map(function ($surgery) {
                        return [
                            'id' => $surgery->id,
                            'name' => $surgery->name,
                        ];
                    });
                }),


                'advices' => $this->whenLoaded('advices', function () {
                    return $this->advices->map(function ($advice) {
                        return [
                            'id' => $advice->id,
                            'advice' => $advice->advice,
                        ];
                    });
                }),
            ],

            'additional_costs' => $this->whenLoaded('additional_costs', function () {
                return $this->additional_costs->map(function ($cost) {
                    return [
                        'id' => $cost->id,
                        'title' => $cost->title,
                        'price' => $cost->price,
                    ];
                });
            }),

            'medical_report_url' => $this->medical_report_path ? url('storage/' . $this->medical_report_path) : null,
        ];

        // --- 3. دمج كل شيء في استجابة واحدة ---
        return array_merge($baseData, [
            'visit_details' => $visitDetails
        ]);

    }
}
