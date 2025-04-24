<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Patient extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory,HasApiTokens;

    protected $guarded = [];
    public function doctors(){
        return $this->belongsToMany(Doctor::class);
    }

    protected $hidden = ['password'];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

}
