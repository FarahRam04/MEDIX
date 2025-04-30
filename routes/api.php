<?php

use App\Http\Controllers\Auth\AdminAuth;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\DoctorAuth;
use App\Http\Controllers\GetThings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Illuminate\Session\Middleware\StartSession;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

///User Auth
Route::post('/user_register',[AuthController::class,'register']);
Route::post('/user_login',[AuthController::class,'login']);
Route::post('/user_logout',[AuthController::class,'logout'])->middleware('auth:sanctum');

//Doctor Auth(sanctum)
Route::post('/doctor_register',[DoctorAuth::class,'register']);
Route::post('/doctor_login',[DoctorAuth::class,'login']);
Route::post('/doctor_logout',[DoctorAuth::class,'logout'])->middleware('auth:doctor');

//Admin Auth (session)
Route::middleware([
    'api',
    StartSession::class, // شغل ميدلوير الجلسة هون
])->group(function () {
    Route::post('/admin_login', [AdminAuth::class, 'login']);
    Route::post('/admin_logout', [AdminAuth::class, 'logout'])->middleware('auth:admin');
});

//Get Departments
Route::get('/departments',[GetThings::class,'getDepartments']);
