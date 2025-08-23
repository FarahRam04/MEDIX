<?php

namespace App\Http\Controllers\Dashboard;

use App\HelperFunctions;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAndEmployee\AddEmployeeRequest;
use App\Http\Requests\AdminAndEmployee\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stichoza\GoogleTranslate\GoogleTranslate;
class EmployeeController extends Controller
{
    use HelperFunctions;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::query()->get();

        return response()->json([
            'employees' => $employees
        ],200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddEmployeeRequest $request)
    {
        if (!in_array($request->role, ['doctor', 'receptionist'])) {   //doesn't matter because there is a drop_down list to choice a role
            return response()->json(['message' => 'This is an invalid role'], 422);
        }
        $tempPassword = Str::random(8);

        $data = $request->validated();
        $data['password'] = bcrypt($tempPassword);

        $employee = Employee::create($data);
        $employee->assignRole($request->role);
        if ($employee->role === 'doctor') {
            $doctor=$employee->doctor()->create();
        }

        $fullName = $request->first_name . ' ' . $request->last_name;

        Mail::raw("Hello $fullName,
            Your account has been successfully created in the MEDIX Clinic.
            Here are your temporary login credentials:
            Temporary Password: $tempPassword
            Please log in using your email and this password.
            Best regards,
            System Administration",
            function ($message) use ($request, $fullName) {
            $message->to($request->email)->subject("Your Login Details - $fullName");
        });
        $tr=new GoogleTranslate();

        $tr->setSource('en');
        $tr->setTarget('ar');

        $first_name_en=$request->first_name;
        $last_name_en=$request->last_name;

        $first_name_ar=$tr->translate($first_name_en);
        $last_name_ar=$tr->translate($last_name_en);

        $employee->setTranslation('first_name','en',$first_name_en);
        $employee->setTranslation('first_name','ar',$first_name_ar);

        $employee->setTranslation('last_name','en',$last_name_en);
        $employee->setTranslation('last_name','ar',$last_name_ar);


        $employee->save();

        return response()->json([
            'message' => 'Employee added successfully,and login details sent via email.',
            'role' => $employee->getRoleNames()->first(),
            'employee' => $employee,
        ],200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    { $employee = Employee::query()->findOrFail($id);

        return response()->json([
            'employee' => $employee
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request,  $id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }
        $employee->update($request->validated());

        if ($request->has('role')) {
            $employee->syncRoles([$request->role]);
        }

        return response()->json([
            'message' => 'Employee updated successfully',
            'employee' => $employee,
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $employee = Employee::findOrFail($id);

        // الحصول على وقت العمل المرتبط
        $time = $employee->time;

        if ($time) {
            $time->days()->detach(); // فك الربط مع الأيام
            $time->delete();         // حذف وقت العمل
        }

        $employee->delete(); // حذف الموظف

        return response()->json([
            'message' => 'Employee and related time deleted successfully.',
            'employee'=> $employee
            ],200);
    }



}
