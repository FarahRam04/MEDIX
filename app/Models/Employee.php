<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Employee extends Authenticatable
{
    use HasRoles, HasApiTokens;

    protected $guard_name = 'employee';
    protected $guarded = [];
    protected $hidden = ['password','created_at','updated_at'];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }
    public function time(){
        return $this->hasOne(Time::class);
    }

}

