<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded= [];

    protected $casts = [
        'sent_at' => 'datetime',
        'is_read' => 'boolean',
        'is_sent' => 'boolean',
    ];

    // ربط الإشعار بالمستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
