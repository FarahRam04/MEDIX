<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offer;
use App\Models\Doctor;
use App\Models\Time;
use Carbon\Carbon;

class OffersSeeder extends Seeder
{
    public function run()
    {
        $doctors = Doctor::with('department', 'employee')->get();
        $paymentMethods = ['cash', 'points'];

        for ($i = 0; $i < 4; $i++) {
            $doctor = $doctors->random();

            // جلب التايم المرتبط بالدكتور
            $time = Time::where('employee_id', $doctor->employee->id ?? null)->first();
            $shift = 'unknown';

            if ($time && $time->start_time) {
                $startHour = Carbon::parse($time->start_time)->hour;

                if ($startHour == 9) {
                    $shift = 'morning';
                } elseif ($startHour == 14) {
                    $shift = 'afternoon';
                }
            }

            // توليد تاريخ البداية (اليوم أو بكرا)
            $startDate = rand(0, 1) === 0 ? Carbon::today() : Carbon::tomorrow();

            // توليد تاريخ الانتهاء (بعد 2 إلى 7 أيام)
            $endDate = (clone $startDate)->addDays(rand(2, 7));

            // اختيار نوع الدفع
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

            // إعداد الخصم أو النقاط حسب نوع الدفع
            $discountValue = $paymentMethod === 'cash' ? rand(10, 50) : null;
            $pointsRequired = $paymentMethod === 'points' ? rand(35, 70) : null;

            Offer::create([
                'image' => 'storage/offers/offer' .$i+1 . '.jpg',
                'department_id' => $doctor->department_id,
                'doctor_id' => $doctor->id,
                'shift' => $shift,
                'payment_method' => $paymentMethod,
                'discount_cash' => $discountValue,
                'points_required' => $pointsRequired,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        }
    }
}
