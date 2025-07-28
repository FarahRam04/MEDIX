<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'title','body', 'data',];
    protected $casts = [
        'data' => 'array',    // يخزن JSON كـ array
        'read_at' => 'datetime'
    ];

    // ربط الإشعار بالمستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
