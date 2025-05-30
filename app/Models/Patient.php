<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Patient extends Model
{
    use HasRoles,HasApiTokens;
    protected $guarded=[];
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function doctors()
    {
        return $this->hasManyThrough(Doctor::class, Appointment::class);
    }

    public function appointments(){
        return $this->hasMany(Appointment::class);
    }





}
