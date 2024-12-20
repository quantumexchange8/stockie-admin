<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->group(function(){
    Route::post('login', 'login');
    Route::post('logout', 'logout');
});

Route::controller(DashboardController::class)->group(function(){
    Route::get('getClockInTime', 'getClockInTime');
    Route::get('getTodayAttendance', 'getTodayAttendance');
    Route::get('getAllAttendances', 'getAllAttendances');
    Route::get('getAllPromotions', 'getAllPromotions');
});


