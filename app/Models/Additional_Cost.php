<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Additional_Cost extends Model
{
    protected $guarded= [];

    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
}
