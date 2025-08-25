<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\URL;
use Spatie\Translatable\HasTranslations;

class Doctor extends Model
{
    use HasRoles,HasApiTokens,HasTranslations;

    public $translatable = ['specialist','bio'];

    protected $attributes=[
        'initial_rating'=>'3',
        'final_rating'=>'3',
        'specialist'=>'"undefined"',
        'bio'=>'" "',
    ];

    protected $appends=['image_url'];
    public function getImageUrlAttribute()
    {
        return URL::to('storage/'.$this->attributes['image']);
    }
    protected $guarded=[];
    protected $hidden=['image'];

    // حساب التقييم المبدئي حسب سنوات الخبرة
    public static function getInitialRatingFromExperience(int $years): float
    {
        return match (true) {
            $years <= 1 => 3.0,
            $years <= 3 => 3.5,
            $years <= 5 => 4.0,
            $years <= 10 => 4.3
        };
    }


    // تحديث التقييم بعد وصول تقييم جديد
    public function applyRating(int $newRating): void
    {
        $this->rating_votes += 1;
        $this->rating_total += $newRating;

        $avgUserRating = $this->rating_total / $this->rating_votes;

        $initialWeight = 10; // وزن التقييم المبدئي

        $this->final_rating = (
                $avgUserRating * $this->rating_votes + $this->initial_rating * $initialWeight
            ) / ($this->rating_votes + $initialWeight);

        $this->save();
    }



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

    public function qualifications()
    {
        return $this->hasMany(Qualification::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }





}
