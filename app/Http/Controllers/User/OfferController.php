<?php

namespace App\Http\Controllers\User;

use App\HelperFunctions;
use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    use HelperFunctions;
    public function offers()
    {
        $offers = Offer::all();

        $offers->transform(function ($offer) {
            $offer->image = url($offer->image); // تحويل المسار إلى رابط كامل
            return $offer;
        });

        return response()->json($offers, 200);
    }


    public function offerPrice(Request $request){
        $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'selected_service.request_type_id' => 'nullable|in:1,2',
            'selected_service.with_medical_report' => 'nullable|boolean',
        ]);
        $offer = Offer::findOrFail($request->offer_id);

        $finalPrice=0;
        $offerPoints=0;
        if ($offer->payment_method === 'cash') {
            $finalPrice=$this->getTotalOfferPrice($offer->id,$request->selected_service['request_type_id'],$request->selected_service['with_medical_report']);
        }
        elseif($offer->payment_method === 'points') {
            $offerPoints=$offer->points_required;
        }

        return response()->json([
            'price' =>$finalPrice=== 0? $offerPoints:round($finalPrice),
            'currency' => $finalPrice===0 ?'Points' :'SYP',
        ]);
    }


}
