<?php

namespace App\Http\Controllers\User;

use App\HelperFunctions;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\BillsResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Google\Service\AdMob\App;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use HelperFunctions;

    public function getPatientAppointments(Request $request)
    {
        // تحقق من أن المستخدم الحالي هو مريض
        $user = auth()->user();
        $request->validate(['status'=>'required|in:pending,completed']);
        $status = $request->query('status');

        if (!$user->patient) {
            return response()->json([], 200);
        }

        $patient = $user->patient;
        $appointments = Appointment::with(['doctor.employee.user', 'slot'])
            ->where('patient_id', $patient->id)
            ->where('status', $status)
            ->orderBy('date', 'desc')
            ->get();



        return AppointmentResource::collection($appointments);
    }

    public function getUserBills(Request $request)
    {
        $user=auth()->user();
        $request->validate(['status'=>'required|in:unpaid,paid']);
        $status = $request->query('status') === 'unpaid' ? 0 : 1 ;

        if (!$user->patient) {
            return response()->json([], 200);
        }

        $appointments=Appointment::with(['doctor','department','slot'])
            ->where('patient_id',$user->patient->id)
            ->where('payment_status', $status)
            ->orderBy('id', 'asc')
            ->get();
        $allData=[];

        foreach ($appointments as $appointment) {
            $data=[
                'id' => $appointment->id,
                'status' => $appointment->payment_status === 0 ? 'unpaid' : 'paid',
                'total_price'=>$appointment->final_total_price,
                'currency' => 'SYP',
                'doctor_name' => $appointment->doctor->employee->first_name . ' ' . $appointment->doctor->employee->last_name,
                'department' => $appointment->department->name,
                'appointment_date_time' => Carbon::parse(
                    $appointment->date . ' ' . optional($appointment->slot)->start_time
                )->format('Y-m-d\TH:i:s')
            ];

            if ($appointment->offer && $appointment->offer->payment_method === 'points' && count($appointment->additional_costs)===0)
                continue;

            $total_price=$appointment->init_total_price;

            if ( count($appointment->additional_costs) > 0) {
                foreach ($appointment->additional_costs as $additional_cost) {
                    $total_price += $additional_cost->price;
                }
            }
                $appointment->final_total_price = $total_price;
                $appointment->update();

            $data['total_price']=$appointment->final_total_price;

            $allData[]=$data;


        }
        return response()->json($allData, 200);
    }

    public function getBillDetails($bill_id)//bill_id == appointment_id
    {
        $user = auth()->user();
        $appointment = Appointment::with('patient.user')
            ->where('id', $bill_id)
            ->where('patient_id',$user->patient->id)
            ->first();

        if (!$appointment) {
            return response()->json(['error'=>'Bill Not Found .'], 404);
        }
        $priceKey=$appointment->type === 'check_up' ? 'Check_Up Price': 'Follow_Up Price';
        $priceValue=$appointment->type === 'check_up' ?'50000 SYP' :'25000 SYP';

        $status=$appointment->payment_status === 0 ? 'Unpaid' : 'Paid';

        $payment_method='Cash';
        if ($appointment->offer && $appointment->offer->payment_method === 'points') {
            $payment_method='Points';
        }
        $data=[
            'Id'=> '# '.$appointment->id,
            'Status'=>$status,
            'Payment Date'=>$status=== 'Unpaid'?'----' :Carbon::today()->format('YFd'),
            'Payment Time'=>$status==='Unpaid'?'----': Carbon::now()->format('h:i A'),
            'Payment Method'=>$payment_method,
        ];

        $total_price=$appointment->final_total_price;
        if ($appointment->offer === null || $appointment->offer && $appointment->offer->payment_method === 'cash') {
            $data[$priceKey]=$priceValue;
            if ($appointment->with_medical_report) {
                $data['Medical Report Price'] = '20000 SYP';
            }
        }

        $additional_costs=$appointment->additional_costs;
        if ($additional_costs){
            foreach ($additional_costs as &$additional_cost){
                $additional_key=$additional_cost['title'].' Price';
                $additional_value=$additional_cost['price'];
                $data[$additional_key]=$additional_value.' SYP';
            }

        }

        if ($appointment->offer && $appointment->offer->payment_method === 'cash')
        {
            $before_dis= $appointment->type === 'check_up' ? 50000 : 25000;
            if ($appointment->with_medical_report ){
                $before_dis += 20000;
            }
            if ( count($appointment->additional_costs) > 0) {
                foreach ($appointment->additional_costs as $additional_cost) {
                    $before_dis += $additional_cost->price;
                }
            }

            $data['Total Price Before Discount ']=$before_dis;
        }
        $data['Total Price']=$total_price.' SYP';
        return response()->json($data);

    }
    public function show($id)
    {
        $appointment = Appointment::with(['slot', 'doctor']) // تأكد أن العلاقات معرفة
        ->findOrFail($id);

        $requestTypeId = match ($appointment->type) {
            'check_up' => 1,
            'follow_up' => 2,
            default => null
        };
        $data=
            ['department_id'      => $appointment->department_id,
            'doctor_id'           => $appointment->doctor_id,
            'request_type_id'     => $requestTypeId,
            'day'                 => $appointment->date,
            'time_id'             => $appointment->slot_id,
            'with_medical_report' => (bool) $appointment->with_medical_report,
            ];
        if ($appointment->offer_id ){
            $data['offer_id']=$appointment->offer_id;
        }
        return response()->json($data);
    }

    public function canCancelAppointment($id, AppointmentService $service)
    {
        $appointment = Appointment::findOrFail($id);

        if (!$service->canBeCancelledAndEdited($appointment)) {
            return response()->json([
                'can_cancel' => false,
            ], 403);
        }

        return response()->json([
            'can_cancel' => true,
        ]);
    }

    public function destroy($id,AppointmentService $service)
    {
        $appointment = Appointment::with('patient.user')->findOrFail($id);

        // تحقق أن المستخدم الحالي هو صاحب الموعد
        if ($appointment->patient->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not authorized to delete this appointment.'
            ], 403);
        }

        // تحقق من إمكانية الإلغاء (عن طريق الـ Service)
        if (!$service->canBeCancelledAndEdited($appointment)) {
            return response()->json([
                'can_cancel' => false,
            ], 403);
        }


        // تحقق من أن حالة الموعد Pending فقط
        if ($appointment->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending appointments can be cancelled.'
            ], 403);
        }

        $appointment->delete();

        return response()->json([
            'message' => 'Appointment cancelled successfully.'
        ]);
    }

    public function testBill($id)
    {
        $appointment = Appointment::with('patient.user')->findOrFail($id);
        return response()->json([
            'Id'=>$appointment->id,
            'Status'=>$appointment->payment_status === 0 ? 'Unpaid' : 'paid',
            'Payment Date'=>Carbon::today()->format('Y-m-d'),
            'Payment Time'=>Carbon::now()->format('H:i:s'),
            'Payment Method'=>$appointment->total_price === 0 ?'Points' : 'Cash',
            'Check_Up Price'=>'50000 SYP',
            'Medical Report Price'=>$appointment->with_medical_report ? '25000 SYP' : '0 SYP',
            'Total Price'=>$appointment->total_price,
        ]);
    }

}
