<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAndEmployee\AddEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddEmployeeRequest $request)
    {
        if (!in_array($request->role, ['doctor', 'receptionist'])) {   //doesn't matter because there is a drop_down list to choice a role
            return response()->json(['message' => 'This is an invalid role'], 422);
        }

        $employee = Employee::create($request->validated());
        $employee->assignRole($request->role);
        if ($employee->role === 'doctor') {
            $doctor=$employee->doctor()->create();
        }

        return response()->json([
            'message' => 'Employee added successfully',
            'role' => $employee->getRoleNames()->first(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }



}
