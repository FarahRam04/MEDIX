<?php

namespace App;

use App\Models\Offer;

trait HelperFunctions
{
    public function getTotalOfferPrice($offer_id ,$request_type_id,bool $with_medical_report)
    {
        $offer = Offer::findOrFail($offer_id);
        $basePrice=0;
        if ($request_type_id===1){
            $basePrice = 50000;
        } elseif ($request_type_id == 2) {
            $basePrice = 25000;
        }
        if ($with_medical_report){
            $basePrice+=20000;
        }
        $discount = $basePrice * ($offer->discount_cash  / 100);
        return $basePrice - $discount;

    }

    public function getTotalPriceWithoutOffer($request_type_id,bool $with_medical_report)
    {
        $basePrice=0;
        if ($request_type_id===1){
            $basePrice = 50000;
        }elseif ($request_type_id==2) {
            $basePrice = 25000;
        }
        if ($with_medical_report){
            $basePrice+=20000;
        }
        return $basePrice;
    }

    public function getSpecialists():array
    {
         return [
            1 => 'General Practitioner',
            2 => 'Cardiologist',
            3 => 'Dermatologist',
            4 => 'Gastroenterologist',
            5 => 'neurologist',
            6 => 'pediatrician',
            7 =>  'ophthalmologist'
        ];
    }

    function convertToArabicNumbers($number) {
        $western = ['0','1','2','3','4','5','6','7','8','9'];
        $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        return  str_replace($western, $arabic, $number);
    }


}
