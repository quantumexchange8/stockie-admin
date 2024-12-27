<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\PromotionController;
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
});

Route::controller(AttendanceController::class)->group(function(){
    Route::get('clock_in_time', 'getClockInTime');
    Route::get('today_attendance', 'getTodayAttendance');
    Route::get('attendances', 'getAllAttendances');
});

Route::controller(PromotionController::class)->group(function(){
    Route::get('promotions', 'getAllPromotions');
    Route::get('promotions/most_recent_promotions', 'getMostRecentPromotions');
    Route::get('promotions/promotion_details/{id}', 'getPromotionDetails');
});


