<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    protected $guarded=[];
    protected $hidden=['appointment_id','created_at','updated_at'];
    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
}
