<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/patient_login',[AuthController::class,'login']);
Route::post('/patient_register',[AuthController::class,'register']);
Route::post('/patient_logout',[AuthController::class,'logout'])->middleware('auth:sanctum');
