<?php

use App\Http\Controllers\Api\CodeCheckEmailController;
use App\Http\Controllers\Api\CodeCheckWhatsappController;
use App\Http\Controllers\Api\ForgetPasswordWhatsappController;
use App\Http\Controllers\Api\ForgotPasswordEmailController;
use App\Http\Controllers\Api\ResetPasswordEmailController;
use App\Http\Controllers\Api\ResetPasswordWhatsappController;
use App\Http\Controllers\Dashboard\AAppointmentController;
use App\Http\Controllers\Dashboard\AdminAndEmployeeAuth;
use App\Http\Controllers\Dashboard\DepartmentController;
use App\Http\Controllers\Dashboard\DoctorController;
use App\Http\Controllers\Dashboard\EmployeeController;
use App\Http\Controllers\Dashboard\OOfferController;
use App\Http\Controllers\Dashboard\PPatientController;
use App\Http\Controllers\Dashboard\SalaryController;
use App\Http\Controllers\Dashboard\TimeController;
use App\Http\Controllers\Dashboard\VacationController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\User\AppointmentController;
use App\Http\Controllers\User\BookingPage;
use App\Http\Controllers\User\OfferController;
use App\Http\Controllers\User\PatientController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserControllerAuth;
use App\Http\Controllers\WhatsAppController;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//rotes without middleware
///User Auth
Route::post('/user_register',[UserControllerAuth::class,'register']);
Route::post('/user_login',[UserControllerAuth::class,'login']);

//routes for all
Route::get('/doctor/profile/{id}',[DoctorController::class,'getDoctorProfileDashboard']);

Route::get('/offers',[OfferController::class,'offers']);
Route::get('/offer/{id}',[BookingPage::class,'offerDays']);
Route::get('/offer_price',[OfferController::class,'offerPrice']);

Route::get('/doctors',[DoctorController::class, 'index']);
Route::get('/doctors/{id}',[DoctorController::class, 'show']);
Route::get('/departments_all',[DepartmentController::class,'getAllDep']);

Route::get('/departments',[BookingPage::class, 'departments']);//get all departments
Route::get('/default_days',[BookingPage::class, 'getNextFiveDays']);//get default days
Route::get('/default_times',[BookingPage::class, 'getSlotsByRange']);//default morning and afternoon times
Route::get('/department/{id}',[BookingPage::class, 'getDepartmentAvailability']);
Route::get('/availableSlotsByShift',[BookingPage::class, 'getShiftSlotsWithDoctor']);
Route::get('/appointments/{id}/can_cancel',[AppointmentController::class, 'canCancelAppointment']);
Route::get('/appointments/{id}',[AppointmentController::class, 'show']);//get an appointment details

Route::put('/prescription/{id}',[DoctorController::class, 'updatePrescription']);
Route::get('/most_rated_doctors',[DoctorController::class, 'getMostRatedDoctors']);
Route::get('/top5',[DoctorController::class, 'getTop5Doctors']);
Route::get('/doctors/{id}/profile',[DoctorController::class, 'getDoctorProfile']);

Route::get('/doctors/department/{id}',[DoctorController::class,'getDoctorsRelatedToDepartment']);
Route::get('/appointment/{id}/bill',[AppointmentController::class,'testBill']);

Route::get('/days/doctor/{id}',[BookingPage::class, 'getDaysRelatedToDoctor']);

//routes only for users
Route::middleware(['auth:sanctum','is_user'])->group(function () {
    Route::post('/user_logout',[UserControllerAuth::class,'logout']);
    Route::post('/upload_image',[UserControllerAuth::class,'uploadImage']);
    Route::post('/appointments',[PatientController::class, 'store']);//add a patient and Book an appointment
    Route::put('/appointments/{id}',[PatientController::class, 'update']);
    Route::get('/patient/appointments',[AppointmentController::class, 'getPatientAppointments']);
    Route::delete('/appointments/{id}',[AppointmentController::class, 'destroy']);
    Route::get('/prescription/{id}',[DoctorController::class, 'getPrescription']);
    Route::post('/doctors/rate',[DoctorController::class, 'rate']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/points',[UserController::class, 'getPoints']);
    Route::post('/refresh_token',[UserControllerAuth::class,'refreshToken']);
    Route::get('/user/profile',[UserController::class,'getUserProfile']);
    Route::put('/user/profile',[UserController::class,'updateUserProfile']);
    Route::put('/user/email',[EmailController::class,'updateEmail_V_Code']);
    Route::put('/user/password',[UserController::class,'updatePassword']);
    Route::get('/user/bills',[AppointmentController::class, 'getUserBills']);
    Route::get('/user/bill/{id}',[AppointmentController::class, 'getBillDetails']);
    Route::get('/medical_report/appointment/{id}',[AppointmentController::class, 'getMedicalReport']);
    Route::get('/vital_signs',[UserController::class, 'getVitalSigns']);



});
//Admin ,doctor and receptionist login

Route::post('/login', [AdminAndEmployeeAuth::class, 'login']);//
Route::post('/logout', [AdminAndEmployeeAuth::class, 'logout'])->middleware('auth:sanctum');
//you can hide anything in Employee or Admin Model
///////////////////////////////////////////////////////////////////////////////////////
//routs only for admins
Route::middleware(['auth:sanctum','is_admin'])->group(function () {

    Route::controller(EmployeeController::class)->group(function () {
        Route::get('/employees', 'index');
        Route::post('/add_employee', 'store');
        Route::get('/employees/show/{id}', 'show');
        Route::Put('/employee/update/{id}', 'update');
        Route::delete('/employee/destroy/{id}', 'destroy');
    });

    Route::put('/doctors/{id}/assign-details', [DoctorController::class, 'assignDepartmentAndSpecialty']);


    Route::controller(TimeController::class)->group(function (){
        Route::get('/working_details', 'index');
        Route::post('/working_details', 'store');
        Route::put('/working_details/{id}','update');
        Route::delete('/working_details/{id}/delete', 'destroy');
        Route::get('/working_details/{id}/show', 'show');
    });

    Route::controller(DepartmentController::class)->group(function (){
        Route::post('/departments/create', 'store');//add a department

        Route::put('/departments/{id}', 'update');
        Route::delete('/departments/{id}', 'destroy');//delete a department
        Route::get('/departments/{id}/show', 'show');
    });

    Route::controller(VacationController::class)->group(function (){
        Route::post('/vacations/create', 'store');
        Route::put('/vacations/{id}', 'update');
        Route::delete('/vacations/{id}/delete', 'destroy');
        Route::get('/vacations/{id}/show', 'show');
    });

    Route::controller(SalaryController::class)->group(function (){
        Route::get('/salaries', 'index');
        Route::post('/salaries/create', 'store');
        Route::put('/salaries/{id}', 'update');
        Route::delete('/salaries/{id}/delete', 'destroy');
        Route::get('/salaries/{id}/show', 'show');
    });
    Route::controller(OOfferController::class)->group(function (){
        Route::post('/dashboard/offers/create', 'store');
        Route::put('/dashboard/offers/{id}', 'update');
        Route::delete('dashboard/offers/{id}/delete', 'destroy');
        Route::get('/dashboard/offers/{id}/show', 'show');
    });

});
////////////////////////////////////////////////////////////////////////////////////////////
Route::middleware(['auth:sanctum', 'is_admin_or_receptionist'])->group(function () {
    Route::get('/dashboard/appointments', [AAppointmentController::class, 'index']);
    Route::get('/vacations', [VacationController::class,'index']);
    Route::get('/users',[UserController::class, 'index']);//get all users

});
/////////////////////////////////////////////////////////////////////////////////////////////
Route::middleware(['auth:sanctum','is_admin_or_receptionist_or_doctor'])->group(function () {
    Route::get('/dashboard/offers',[OOfferController::class,'index']);
    Route::get('/departments/doctors',[ DepartmentController::class,'index']);
    Route::get('/dashboard/appointment/{id}/show', [AAppointmentController::class, 'show']);
    Route::get('/patients/index', [PPatientController::class, 'index']);//done
    Route::get('/patients/{id}/show', [PPatientController::class, 'show']);
    Route::post('/appointments/{id}/confirm-payment', [AAppointmentController::class, 'confirmPayment']);

});


//////////////////////////////////////////////////////////////////////////////////////////////
//routes only for doctors

Route::middleware(['auth:sanctum','is_doctor'])->group(function () {
    Route::post('/update_profile',[DoctorController::class, 'update']);
    Route::post('/prescription/{id}',[DoctorController::class, 'writePrescription']);
    Route::post('/appointments/{id}/upload-report', [DoctorController::class, 'uploadMedicalReport']);
    Route::get('/my-schedule', [AAppointmentController::class, 'getDoctorSchedule']);
    Route::put('/patients/{id}/vitals', [PPatientController::class, 'updateVitals']);
    Route::get('/my-visits', [DoctorController::class, 'getDoctorVisits']);
});
//////////////////////////////////////////////////////////////////////////////////////////////////

//routes only for reception
Route::middleware(['auth:sanctum','is_receptionist'])->group(function () {
    Route::post('/dashboard/patients/register', [PPatientController::class, 'registerPatient']);
    Route::get('/dashboard/patients/search', [PPatientController::class, 'search']);
    //reservations crud----------->
    Route::post('/dashboard/appointment/create', [AAppointmentController::class, 'store']);
    Route::put('/dashboard/appointment/{id}/update', [AAppointmentController::class, 'update']);
    Route::delete('/dashboard/appointment/{id}/delete', [AAppointmentController::class, 'destroy']);
/////عرض اطباء لقسم معين
    Route::get('/dashboard/doctors-by-department', [AAppointmentController::class, 'getDoctorsByDepartment']);


});
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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


Route::post('/refresh_token',[UserControllerAuth::class,'refreshToken'])->middleware('auth:sanctum');
Route::post('/firebase/send', [NotificationService::class, 'send']);


////////////
Route::get('/num',[DoctorController::class,'num']);
