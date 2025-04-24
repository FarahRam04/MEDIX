<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatentFactory> */
    use HasFactory;

    protected $guarded = [];
    public function doctors(){
        return $this->belongsToMany(Doctor::class);
    }
}
