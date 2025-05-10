<?php

use App\Http\Controllers\Auth\AdminAndEmployeeAuth;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

///User Auth
Route::post('/user_register',[UserController::class,'register']);
Route::post('/user_login',[UserController::class,'login']);
Route::post('/user_logout',[UserController::class,'logout'])->middleware('auth:sanctum');

//Admin ,doctor and receptionist login

Route::post('/login', [AdminAndEmployeeAuth::class, 'login']);//you can hide anything in Employee or Admin Model

//routs only for admins
Route::middleware(['auth:sanctum','is_admin'])->group(function () {
    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Welcome Admin']);
    });

    Route::post('/add_employee',[AdminAndEmployeeAuth::class, 'addEmployee']);
});

//routs only for employees
Route::middleware(['auth:sanctum','is_employee'])->group(function () {
    Route::get('/employee-only', function () {
        return response()->json(['message' => 'Welcome Employee']);
    });




// صلاحيات حسب الدور
    Route::get('/doctor-area', function () {
        if (auth('employee')->user()->hasRole('doctor')) {
            return response()->json(['message' => 'Welcome Doctor']);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    });

    Route::get('/reception-area', function () {
        if (auth('employee')->user()->hasRole('receptionist')) {
            return response()->json(['message' => 'Welcome Receptionist']);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    });

});

//Get Departments
Route::get('/send-whatsapp',[WhatsAppController::class,'sendTestMessage']);

