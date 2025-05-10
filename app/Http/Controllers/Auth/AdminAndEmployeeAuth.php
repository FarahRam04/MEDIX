<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAndEmployee\AddEmployeeRequest;
use App\Http\Requests\AdminAndEmployee\LoginAdminRequest;
use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAndEmployeeAuth extends Controller
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
    public function store(Request $request)
    {
        //
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
    public function login(Request $request)

    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            $admin = Auth::guard('admin')->user();
            $token = $admin->createToken('auth_token')->plainTextToken;
            return response()->json([
                'status' => true,
                'user_type' => $admin->getRoleNames()->first(),
                'user' => $admin,
                'token' => $token,
            ])->withCookie(cookie('laravel_session', session()->getId(),60));

        }

        if (Auth::guard('employee')->attempt($credentials)) {
            $employee = Auth::guard('employee')->user();
            $token = $employee->createToken('auth_token')->plainTextToken;
            return response()->json([
                'status' => true,
                'user_type' => $employee->getRoleNames()->first(),
                'user' => $employee,
                'token' => $token,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid credentials',
        ], 401)->withCookie(cookie('laravel_session', session()->getId(),60));
    }

    public function addEmployee(AddEmployeeRequest $request)
    {
        if (!in_array($request->role, ['doctor', 'receptionist'])) {   //doesnt matter because there is a drop_down list to choice a role
            return response()->json(['message' => 'This is an invalid role'], 422);
        }

            $employee = Employee::create($request->validated());
            $employee->assignRole($request->role);

            return response()->json([
                'message' => 'Employee added successfully',
                'role' => $employee->getRoleNames()->first(),
            ]);
        }





//    public function login(Request $request)
//    {
//        $credentials = $request->only('email', 'password');
//
//        if (Auth::guard('admin')->attempt($credentials)) {
//            $admin = Auth::guard('admin')->user();
//            return redirect()->intended('/admin/dashboard');
//        }
//
//        if (Auth::guard('employee')->attempt($credentials)) {
//            $employee = Auth::guard('employee')->user();
//            return redirect()->intended('/employee/dashboard');
//        }
//
//        return back()->withErrors([
//            'email' => 'بيانات الدخول غير صحيحة.',
//        ])->onlyInput('email');
//    }


}
