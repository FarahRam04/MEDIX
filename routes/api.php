<?php

use App\Http\Controllers\Dashboard\AdminAndEmployeeAuth;
use App\Http\Controllers\Dashboard\AdminAndEmployeeController;
use App\Http\Controllers\Dashboard\DoctorController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserControllerAuth;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\EmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

///User Auth
Route::post('/user_register',[UserControllerAuth::class,'register']);
Route::post('/user_login',[UserControllerAuth::class,'login']);
Route::middleware(['auth:sanctum','is_user'])->group(function () {
    Route::post('/user_logout',[UserControllerAuth::class,'logout']);
    Route::post('/upload_image',[UserControllerAuth::class,'uploadImage']);
});
//Admin ,doctor and receptionist login

Route::post('/login', [AdminAndEmployeeAuth::class, 'login']);//you can hide anything in Employee or Admin Model

//routs only for admins
Route::middleware(['auth:sanctum','is_admin'])->group(function () {
    Route::post('/add_employee',[AdminAndEmployeeController::class, 'addEmployee']);
    Route::get('/users',[UserController::class, 'index']);//get all users
    Route::get('/doctors',[DoctorController::class, 'index']);//get all doctors
});

//routs only for employees
Route::middleware(['auth:sanctum','is_employee'])->group(function () {
    Route::get('/employee-only', function () {
        return response()->json(['message' => 'Welcome Employee']);
    });
});

//WhatsApp
Route::post('/send-code',[WhatsAppController::class, 'code']);//send whatsapp verification code
Route::post('/verify-code',[WhatsAppController::class, 'verify']);


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



//Route::get('/send-whatsapp',[WhatsAppController::class,'sendTestMessage']);
Route::post('/send-email', [EmailController::class, 'sendCode']);
Route::post('/verify-email', [EmailController::class, 'verifyCode']);
