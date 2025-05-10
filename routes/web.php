<?php

use App\Http\Controllers\Auth\AdminAndEmployeeAuth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/admin/login-form', function () {
    return view('admin.login');
});
Route::post('/admin/login', [AdminAndEmployeeAuth::class, 'login'])->name('admin.login');


Route::middleware(['auth:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return 'مرحباً بك في لوحة تحكم الأدمن';
    })->name('admin.dashboard');
});

Route::middleware(['auth:employee'])->group(function () {
    Route::get('/employee/dashboard', function () {
        return 'مرحباً بك في لوحة تحكم employee';
    })->name('employee.dashboard');
});

