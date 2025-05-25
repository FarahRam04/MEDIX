<?php

use App\Http\Controllers\Api\CodeCheckEmailController;
use App\Http\Controllers\Api\CodeCheckWhatsappController;
use App\Http\Controllers\Api\ForgetPasswordWhatsappController;
use App\Http\Controllers\Api\ForgotPasswordEmailController;
use App\Http\Controllers\Api\ResetPasswordEmailController;
use App\Http\Controllers\Api\ResetPasswordWhatsappController;
use App\Http\Controllers\Dashboard\AdminAndEmployeeAuth;
use App\Http\Controllers\Dashboard\DepartmentController;
use App\Http\Controllers\Dashboard\DoctorController;
use App\Http\Controllers\Dashboard\EmployeeController;
use App\Http\Controllers\Dashboard\TimeController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserControllerAuth;
use App\Http\Controllers\WhatsAppController;
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
    Route::post('/add_employee',[EmployeeController::class, 'store']);//add employee
    Route::get('/users',[UserController::class, 'index']);//get all users
    Route::get('/doctors',[DoctorController::class, 'index']);//get all doctors with all relationships

    Route::get('/departments',[DepartmentController::class, 'index']);//get all departments
    Route::post('/departments/create',[DepartmentController::class, 'store']);//add a department
    Route::put('/departments/{id}',[DepartmentController::class, 'update']);
    Route::delete('/departments/{id}',[DepartmentController::class, 'destroy']);//delete a department
    Route::post('/working_details',[TimeController::class, 'store']);
    Route::get('/working_details',[TimeController::class, 'index']);
});

//routs only for employees
Route::middleware(['auth:sanctum','is_employee'])->group(function () {
    Route::get('/employee-only', function () {
        return response()->json(['message' => 'Welcome Employee']);
    });
});

//routes only for doctors

Route::middleware(['auth:sanctum','is_doctor'])->group(function () {
    Route::post('/update_profile',[DoctorController::class, 'update']);
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
Route::post('password/email', ForgotPasswordEmailController::class);
Route::post('password/code/check', CodeCheckEmailController::class);
Route::post('password/reset', ResetPasswordEmailController::class);
Route::post('password/send/whatsapp',ForgetPasswordWhatsappController::class);
Route::post('password/code/check/whatsapp', CodeCheckWhatsappController::class);
Route::post('password/reset/whatsapp', ResetPasswordWhatsappController::class);




