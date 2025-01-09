<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\IpcrController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\IpcrPeriodController;
use App\Http\Controllers\API\EmployeeAccountCreation;


Route::post('/employees', [EmployeeController::class, 'store']);
Route::post('ipcr', [IpcrController::class, 'store']);
Route::post('/ipcr-periods', [IpcrPeriodController::class, 'store']);

// Users for Employee Acc.
Route::post('/create-account', [EmployeeAccountCreation::class, 'createAccount']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
