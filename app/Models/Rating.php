<?php

namespace App\Models;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $guarded=[];

    function getInitialWeight($years)
    {
        if ($years <= 1) return 3;
        if ($years <= 4) return 4;
        if ($years <= 9) return 6;
        if ($years <= 15) return 8;
        return 10;
    }


    protected static function booted()
    {
        static::created(function ($rating) {
            $doctor = $rating->doctor;

            $totalRatings = $doctor->ratings()->count();
            $batchesDone = $doctor->batches_count ?? 0;

            // تحقق إذا لازم نحسب دفعة جديدة
            if ($totalRatings >= ($batchesDone + 1) * 15) {

                // جيب التقييمات الجديدة فقط (الدفعة الحالية)
                $newBatch = $doctor->ratings()
                    ->skip($batchesDone * 15)
                    ->take(15)
                    ->pluck('value');

                $batchAverage = $newBatch->avg();

                // احسب التقييم الجديد
                $initialWeight = (new static)->getInitialWeight($doctor->years_of_experience);
                $totalWeight = $batchesDone + $initialWeight;

                $newRatio = (($doctor->ratio * $totalWeight) + $batchAverage) / ($totalWeight + 1);

                // حدّث التقييم وعدد الدفعات
                $doctor->ratio = round($newRatio, 2);
                $doctor->batches_count = $batchesDone + 1;
                $doctor->save();
            }
        });
    }


    public function doctor(){
        return $this->belongsTo(doctor::class);
    }
}
