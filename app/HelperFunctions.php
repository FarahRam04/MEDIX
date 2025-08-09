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
}
