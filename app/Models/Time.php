<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    protected $guarded= [];
    public function days()
    {
        return $this->belongsToMany(Day::class);
    }
    public function employee(){
        return $this->belongsTo(Employee::class);
    }
}
