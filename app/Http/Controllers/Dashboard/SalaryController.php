<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalaryRequest;
use App\Http\Requests\UpdateSalaryRequest;
use App\Http\Resources\SalaryResource;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\Vacation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class SalaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $salary = Salary::with('employee')->get();
        return SalaryResource::collection($salary);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalaryRequest $request)
    {
        $validatedData = $request->validated();

        $employeeId = $validatedData['employee_id'];
        $month = $validatedData['month'];
        $year = $validatedData['year'];
        $penaltyDeduction = $validatedData['penalty_deduction'];
        $status = $validatedData['status'];

       $leaveDeduction = $this->calculateLeaveDeductionForPeriod($employeeId, $month, $year);
        $employee = Employee::find($employeeId);
        $baseSalary = $employee->salary;
        $penaltyDeduction =  $request->input('penalty_deduction', 0);

      if(!$employee){
           return response()->json(['message'=>'employee not found']);
       }
        $finalSalary = $baseSalary - $leaveDeduction - $penaltyDeduction;
        $salary = Salary::create([
            'employee_id' => $employeeId,
            'month' => $month,
            'year' => $year,
            'status'=>$status,
            'penalty_deduction' =>$penaltyDeduction,
            'base_salary' => $baseSalary,
            'leave_deduction' => $leaveDeduction,
            'final_salary' => $finalSalary,

        ]);
        $salary->load('employee');

        return response()->json([
            'message' => 'Salary created successfully!',
           'salary'=> new SalaryResource($salary)
        ], 201);
    }
    private function calculateLeaveDeductionForPeriod(int $employeeId, int $month, int $year): float
    {
        // 1. تحديد تاريخ بداية ونهاية الشهر المطلوب
        $startDateOfMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDateOfMonth = $startDateOfMonth->copy()->endOfMonth();

        $vacations = Vacation::where('employee_id', $employeeId)
            ->where(function ($query) use ($startDateOfMonth, $endDateOfMonth) {
                $query->where('start_day', '<=', $endDateOfMonth)
                    ->where('end_day', '>=', $startDateOfMonth);
            })
            ->get();

        $totalDeduction = 0;

        foreach ($vacations as $vacation) {
            if ($vacation->days <= 0 || $vacation->deduction <= 0) {
                continue;
            }
            $dailyDeduction = $vacation->deduction / $vacation->days;

            $vacationStart = Carbon::parse($vacation->start_day);
            $vacationEnd = Carbon::parse($vacation->end_day);

            $effectiveStart = $vacationStart->max($startDateOfMonth);
            $effectiveEnd = $vacationEnd->min($endDateOfMonth);
            $daysInMonth = $effectiveStart->diffInDays($effectiveEnd) + 1;
            $totalDeduction += ($daysInMonth * $dailyDeduction);
        }

        return $totalDeduction;
    }
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $salary= Salary::with('employee')->find($id);
        if(!$salary){
            return response()->json(['message'=>'Salary not found.'],404);
        }
        return response()->json(['message'=>'salary and its employee.',
            'salary'=>new SalaryResource($salary)],200);
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(UpdateSalaryRequest $request, string $id)
    {
        $salary = Salary::find($id);
        if (!$salary) {
            return response()->json(['message' => 'Salary not found!'], 404);
        }
        if ($salary->status === 'paid') {

            if ($request->has('penalty_deduction') || $request->has('status')) {
                return response()->json([
                    'message' => 'لا يمكن تعديل أي بيانات مالية أو تغيير حالة راتب تم دفعه بالفعل.'
                ], 403);
            }
        }

        $validatedData = $request->validated();
        $salary->update($validatedData);
        if ($request->has('penalty_deduction')) {
            $salary->final_salary = $salary->base_salary - $salary->leave_deduction - $validatedData['penalty_deduction'];
        }
        $salary->save();
        return response()->json([
            'message' => 'تم تحديث سجل الراتب بنجاح!',
            'salary' => new SalaryResource($salary)
        ]);
    }


    public function destroy(string $id)
    {
        $salary= Salary::with('employee')->find($id);
        if(!$salary){
            return response()->json(['message'=>'Salary not found.'],404);
        }
        if ($salary->status === 'paid') {
            return response()->json([
                'message' => 'لا يمكن حذف سجل راتب تم دفعه ً.'
            ], 403);
        }
        $deleteSalay=new SalaryResource($salary);
        $salary->delete();
        return response()->json([
            'message' => 'تم حذف سجل الراتب بنجاح وبشكل نهائي.',
            'deleted salary' => $deleteSalay
        ], 200);
    }

}
