<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    protected $guarded=[];
    public function times()
    {
        return $this->belongsToMany(Time::class);
    }
}
