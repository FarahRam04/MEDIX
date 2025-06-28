<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advice extends Model
{
    protected $guarded=[];
    protected $table = 'advices';
    protected $hidden = ['appointment_id','created_at','updated_at'];
    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
}
