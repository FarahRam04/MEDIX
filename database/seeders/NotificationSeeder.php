<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
        public function run()
    {
        DB::table('notifications')->insert([
            [
                'user_id' => 1,
                'title' => 'تذكير بالموعد',
                'body' => 'موعدك غدًا الساعة 10 صباحًا.',
                'type' => 'reminders',
                'is_sent' => true,
                'sent_at' => now(),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2,
                'title' => 'وصفة طبية جديدة',
                'body' => 'تمت إضافة وصفة طبية جديدة إلى حسابك.',
                'type' => 'prescription',
                'is_sent' => true,
                'sent_at' => now(),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'title' => 'تقرير الفحوصات',
                'body' => 'تم رفع تقرير فحوصاتك على النظام.',
                'type' => 'report',
                'is_sent' => true,
                'sent_at' => now(),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 4,
                'title' => 'إلغاء الموعد',
                'body' => 'تم إلغاء موعدك المحجوز بتاريخ 15-08-2025.',
                'type' => 'cancellation',
                'is_sent' => true,
                'sent_at' => now(),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

}
