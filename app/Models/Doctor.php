<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\URL;

class Doctor extends Model
{
    use HasRoles,HasApiTokens;

    protected $appends=['image_url'];
    public function getImageUrlAttribute()
    {
        return URL::to('storage/'.$this->attributes['image']);
    }
    protected $guarded=[];
    protected $hidden=['image'];
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

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }




}
