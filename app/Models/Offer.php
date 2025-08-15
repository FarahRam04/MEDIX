<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $guarded=[];
    protected $hidden = ['start_date','end_date','points_required','discount_cash','created_at','updated_at'];
    public function appointments(){
        return $this->hasMany(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
