<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Appointment extends Model
{
    use HasApiTokens;
    protected $guarded=[];

    public function doctor(){
        return $this->belongsTo(Doctor::class);
    }

    public function patient(){
        return $this->belongsTo(Patient::class);
    }

    public function slot()
    {
        return $this->belongsTo(AvailableSlot::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function medications()
    {
        return $this->hasMany(Medication::class);
    }

    public function labTests(){
        return $this->hasMany(LabTest::class);
    }
    public function surgeries()
    {
        return $this->hasMany(Surgery::class);
    }
    public function advices()
    {
        return $this->hasMany(Advice::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
