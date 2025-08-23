<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory,HasTranslations;

    public $translatable = ['name'];

    public $casts=[
        'name'=>'array'
    ];
    protected $hidden = ['created_at','updated_at'];
    protected $guarded = [];

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }
    public function appointments(){
        return $this->hasMany(Appointment::class);
    }
}
