<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Medication extends Model
{
    use HasTranslations;

    public $translatable = ['name','type','dosage','frequency','duration','note'];
    protected $guarded=[];
    protected $hidden=['appointment_id','created_at','updated_at'];

    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
}
