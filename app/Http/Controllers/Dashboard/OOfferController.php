<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfferRequest;
use App\Http\Requests\UpdateOfferRequest;
use App\Http\Resources\OfferResource;
use App\Models\Doctor;
use App\Models\Offer;
use App\Models\Time;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OOfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $offers=Offer::with('doctor.employee', 'doctor.department')->get();
        return OfferResource::collection($offers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfferRequest $request)
    {
        $validatedData = $request->validated();
        $doctorData = $this->getDoctorRelatedData($validatedData['doctor_id']);
        if (isset($doctorData['error'])) {
            return response()->json(['message' => $doctorData['error']], 422);
        }
        $validatedData = array_merge($validatedData, $doctorData);
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('offers', 'public');
            $validatedData['image'] = $imagePath;
        }

        $offer = Offer::create($validatedData);
        $offer->load('doctor.employee', 'doctor.department');
        return response()->json([
            'message' => '!تم إنشاء العرض بنجاح',
            'offer' => new OfferResource($offer)
        ], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $offer= Offer::with('doctor.employee','doctor.department')->find($id);
        if(!$offer){
            return response()->json(['message'=>'Offer not found.'],404);
        }
        return response()->json(['message'=>'offer and its doctors.',
            'offer'=>new OfferResource($offer)],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfferRequest $request, string $id)
    {
        $offer=Offer::find($id);
        if(!$offer){
            return response()->json(['message'=>'Offer not found!']);
        }
        $validatedData = $request->validated();
        if ($request->hasFile('image')) {
            if ($offer->image) {
                Storage::disk('public')->delete($offer->image);
            }
            $imagePath = $request->file('image')->store('offers', 'public');
            $validatedData['image'] = $imagePath;
        }
        if (isset($validatedData['doctor_id'])) {
            $doctorData = $this->getDoctorRelatedData($validatedData['doctor_id']);
            if (isset($doctorData['error'])) {
                return response()->json(['message' => $doctorData['error']], 422);
            }
            $validatedData = array_merge($validatedData, $doctorData);
        }
        $offer->update($validatedData);
        $offer->load('doctor.employee', 'doctor.department');
        return response()->json([
            'message' => '!تم تعديل العرض بنجاح',
            'offer' => new OfferResource($offer)
        ], 200); // 200 OK
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        $offer= Offer::with('doctor.employee','doctor.department')->find($id);
//        if(!$offer){
//            return response()->json(['message'=>'Offer not found.'],404);
//        }
//        $offerDelete=new OfferResource($offer);
//        $offer->delete();
//        return response()->json(['message'=>'Offer deleted successfully!',
//        'Deleted Offer' => $offerDelete],200);
    }


    private function getDoctorRelatedData(int $doctorId): array
    {
        $doctor = Doctor::find($doctorId);
        if (!$doctor) {
            return ['error' => 'doctor not found'];
        }
        $doctorTime = Time::where('employee_id', $doctor->employee_id)->first();
        if (!$doctorTime) {
            return ['error' => ' الطبيب المختار ليس لديه أوقات دوام مسجلة'];
        }

        $startTime = $doctorTime->start_time;
        $shift = null;

        if ($startTime === '09:00:00') {
            $shift = 'morning';
        } elseif ($startTime === '14:00:00') {
            $shift = 'afternoon';
        }

        if (is_null($shift)) {
            return ['error' => 'خطأ: وقت دوام الطبيب المسجل (' . $startTime . ') لا يطابق أي شيفت معروف.'];
        }
        return [
            'shift' => $shift,
            'department_id' => $doctor->department_id,
        ];
    }

}
