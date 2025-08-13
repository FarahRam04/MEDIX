<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Time;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OffersSeeder extends Seeder
{
    public function run()
    {
        $offers = [
            ['image'=>'storage/offers/offer1.jpg', 'department_id'=>2, 'payment_method'=>'cash', 'discount_cash'=>50, 'points_required'=>null],
            ['image'=>'storage/offers/offer2.jpg', 'department_id'=>6, 'payment_method'=>'points', 'discount_cash'=>null, 'points_required'=>350],
            ['image'=>'storage/offers/offer3.jpg', 'department_id'=>5, 'payment_method'=>'cash', 'discount_cash'=>25, 'points_required'=>null],
            ['image'=>'storage/offers/offer4.jpg', 'department_id'=>3, 'payment_method'=>'points', 'discount_cash'=>null, 'points_required'=>250],
        ];

        $startDate = Carbon::now()->addDay(); // بكرا
        $endDate = Carbon::now()->addDays(12); // بعد 12 يوم

        $insertData = [];

        foreach ($offers as $offer) {
            // جلب دكتور عشوائي من القسم
            $doctor = Doctor::where('department_id', $offer['department_id'])->inRandomOrder()->first();
            if (!$doctor) continue; // إذا ما في دكتور بالقسم نتجاوز

            // جلب الشيفت من جدول times المرتبط بالموظف
            $time = Time::where('employee_id', $doctor->employee_id)->first();
            $shift = null;
            if ($time) {
                $shift = $time->start_time == '09:00:00' ? 'morning' : ($time->start_time == '14:00:00' ? 'afternoon':null);
            }

            $insertData[] = [
                'image' => $offer['image'],
                'department_id' => $offer['department_id'],
                'doctor_id' => $doctor->id,
                'shift' => $shift,
                'payment_method' => $offer['payment_method'],
                'discount_cash' => $offer['discount_cash'],
                'points_required' => $offer['points_required'],
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        }

        // إدخال كل العروض دفعة واحدة
        DB::table('offers')->insert($insertData);
    }
}
