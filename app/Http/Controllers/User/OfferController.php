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
        foreach ($offers as $offer) {
            $offer->image = url('storage/'. $offer->image);
        }
        return response()->json($offers, 200);
    }


    public function offerPrice(Request $request)
    {
        $request->validate([
            'offer_id' => 'nullable|exists:offers,id',
            'selected_service.request_type_id' => 'required|in:1,2',
            'selected_service.with_medical_report' => 'required|boolean',
        ]);
        $locale=app()->getLocale();

        if ($request->offer_id !== null) {
            $offer = Offer::findOrFail($request->offer_id);
            $finalPrice = 0;
            $offerPoints = 0;
            if ($offer->payment_method === 'cash') {
                $finalPrice = $this->getTotalOfferPrice($offer->id, $request->selected_service['request_type_id'], $request->selected_service['with_medical_report']);
            } elseif ($offer->payment_method === 'points') {
                $offerPoints = $offer->points_required;
            }
            if ($offerPoints > 0 && $offerPoints <=10) {
                $points='نقاط';
            }else{
                $points='نقطة';
            }
            return response()->json([
                'price' => $finalPrice === 0 ? $offerPoints : round($finalPrice),
                'currency' => $finalPrice === 0 ? ($locale=== 'en'?'Points':$points) : ($locale=== 'en'?'SYP':'ليرة سورية'),
            ]);

        } else {
            $Price = $this->getTotalPriceWithoutOffer($request->selected_service['request_type_id'], $request->selected_service['with_medical_report']);
            return response()->json([
                'price' => $Price,
                'currency' => $locale=== 'en'?'SYP':'ليرة سورية',
            ]);

        }

    }
}
