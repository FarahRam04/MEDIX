<?php

use App\Http\Controllers\Api\CodeCheckEmailController;
use App\Http\Controllers\Api\CodeCheckWhatsappController;
use App\Http\Controllers\Api\ForgetPasswordWhatsappController;
use App\Http\Controllers\Api\ForgotPasswordEmailController;
use App\Http\Controllers\Api\ResetPasswordEmailController;
use App\Http\Controllers\Api\ResetPasswordWhatsappController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BookingPage;
use App\Http\Controllers\Dashboard\AdminAndEmployeeAuth;
use App\Http\Controllers\Dashboard\DepartmentController;
use App\Http\Controllers\Dashboard\DoctorController;
use App\Http\Controllers\Dashboard\EmployeeController;
use App\Http\Controllers\Dashboard\TimeController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\User\PatientController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserControllerAuth;
use App\Http\Controllers\firebasee\NotificationController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//rotes without middleware
///User Auth
Route::post('/user_register',[UserControllerAuth::class,'register']);
Route::post('/user_login',[UserControllerAuth::class,'login']);


Route::get('/departments',[BookingPage::class, 'departments']);//get all departments
Route::get('/default_days',[BookingPage::class, 'getNextFiveDays']);//get default days
Route::get('/default_times',[BookingPage::class, 'getSlotsByRange']);//default morning and afternoon times
Route::get('/department/{id}',[BookingPage::class, 'getDepartmentAvailability']);
Route::get('/availableSlotsByShift',[BookingPage::class, 'getShiftSlotsWithDoctor']);
Route::get('/doctors/{id}',[DoctorController::class, 'show']);///////////this need a resource to design the response///////////////
Route::get('/appointments/{id}/can_cancel',[AppointmentController::class, 'canCancelAppointment']);//get an appointment details
Route::get('/appointments/{id}',[AppointmentController::class, 'show']);//get an appointment details



//routes only for users
Route::middleware(['auth:sanctum','is_user'])->group(function () {
    Route::post('/user_logout',[UserControllerAuth::class,'logout']);
    Route::post('/upload_image',[UserControllerAuth::class,'uploadImage']);
    Route::post('/appointments',[PatientController::class, 'store']);//add a patient and Book an appointment
    Route::put('/appointments/{id}',[PatientController::class, 'update']);
    Route::get('/patient/appointments',[AppointmentController::class, 'getPatientAppointments']);
    Route::delete('/appointments/{id}',[AppointmentController::class, 'destroy']);


});
//Admin ,doctor and receptionist login

Route::post('/login', [AdminAndEmployeeAuth::class, 'login']);//you can hide anything in Employee or Admin Model

//routs only for admins
Route::middleware(['auth:sanctum','is_admin'])->group(function () {
    Route::get('/employees',[EmployeeController::class, 'index']);
    Route::post('/add_employee',[EmployeeController::class, 'store']);//add employee
    Route::delete('/employees/{id}',[EmployeeController::class, 'destroy']);

    Route::get('/users',[UserController::class, 'index']);//get all users
    Route::get('/doctors',[DoctorController::class, 'index']);//get all doctors with all relationships


    Route::get('/departments/doctors',[DepartmentController::class, 'index']);//get all departments with doctors
    Route::post('/departments/create',[DepartmentController::class, 'store']);//add a department
    Route::put('/departments/{id}',[DepartmentController::class, 'update']);
    Route::delete('/departments/{id}',[DepartmentController::class, 'destroy']);//delete a department

    Route::get('/working_details',[TimeController::class, 'index']);
    Route::post('/working_details',[TimeController::class, 'store']);
    Route::put('/working_details/{time}',[TimeController::class, 'update']);
    Route::delete('/working_details/{id}',[TimeController::class, 'destroy']);

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
Route::post('password/code/check/email', CodeCheckEmailController::class);
Route::post('password/reset/email', ResetPasswordEmailController::class);
Route::post('password/send/whatsapp',ForgetPasswordWhatsappController::class);
Route::post('password/code/check/whatsapp', CodeCheckWhatsappController::class);
Route::post('password/reset/whatsapp', ResetPasswordWhatsappController::class);

Route::post('/send-notification', [NotificationController::class, 'send']);




