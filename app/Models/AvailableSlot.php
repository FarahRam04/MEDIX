<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailableSlot extends Model
{
    protected $guarded=[];

    public function doctor(){
        return $this->belongsToMany(Doctor::class);
    }

    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
}
