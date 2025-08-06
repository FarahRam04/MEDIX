<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;

class OfferController extends Controller
{
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
            'selected_service.request_type_id' => 'required|in:1,2',
            'selected_service.with_medical_report' => 'required|boolean',
        ]);
        $offer = Offer::findOrFail($request->offer_id);

        // السعر الأساسي حسب نوع الطلب
        if ($request->selected_service['request_type_id'] == 1) {
            $basePrice = 50000;
        } elseif ($request->selected_service['request_type_id'] == 2) {
            $basePrice = 25000;
        } else {
            return response()->json(['error' => 'نوع الطلب غير صالح','request_type_id'=>$request->request_type_id], 400);
        }

        // إذا كان بدو تقرير طبي أضف 20 ألف
        if ($request->selected_service['with_medical_report']) {
            $basePrice += 20000;
        }

        // تطبق الخصم إذا العرض نوعه cash
        if ($offer->payment_method === 'cash' && $offer->discount_cash) {
            $discount = $basePrice * ($offer->discount_cash  / 100);
            $finalPrice = $basePrice - $discount;
        } else {
            $finalPrice = $basePrice;
        }

        return response()->json([
            'price' => round($finalPrice),
            'currency' => 'SYP',
        ]);
    }


}
