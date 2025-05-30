<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Doctor extends Model
{
    use HasRoles,HasApiTokens;
    protected $guarded=[];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(){
        return $this->belongsTo(Department::class);
    }
    public function appointments(){
        return $this->hasMany(Appointment::class);
    }

    public function patients()
    {
        return$this->hasManyThrough(Patient::class,Appointment::class);
    }

    public function availableSlots()
    {
        return $this->belongsToMany(AvailableSlot::class);
    }




}
