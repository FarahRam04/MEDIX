<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    /** @use HasFactory<\Database\Factories\DoctorFactory> */
    use HasFactory;

    use HasFactory;

    protected $guarded = [];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class);
    }
}
