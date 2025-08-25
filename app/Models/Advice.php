<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Advice extends Model
{
    use HasTranslations;
    public $translatable = ['advice'];
    protected $guarded=[];
    protected $table = 'advices';
    protected $hidden = ['appointment_id','created_at','updated_at'];
    public function appointment(){
        return $this->belongsTo(Appointment::class);
    }
}
