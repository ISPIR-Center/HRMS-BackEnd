<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CreateAccount;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\IpcrController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\IpcrPeriodController;
use App\Http\Controllers\API\EmployeeAccountCreation;
use App\Http\Controllers\API\IpcrSubmit;

Route::post('/create-account', [EmployeeAccountCreation::class, 'createAccount']);
Route::post('/login', [AuthController::class, 'login']);


Route::post('/ipcr', [IpcrController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('admin')->group(function () {
        Route::post('/register', [CreateAccount::class, 'createAccount']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        
        Route::post('/ipcr-periods', [IpcrPeriodController::class, 'store']);
        Route::post('/ipcr/admin', [IpcrController::class, 'storeAdmin']);

        Route::get('/user', [AuthController::class, 'user']);
    });


    Route::middleware('employee')->group(function () {
        Route::post('/ipcr/employee', [IpcrController::class, 'storeEmployee']);
    });
});

       






Route::prefix('public')->group(function () {
    
    Route::post('/ipcr/admin', [IpcrSubmit::class, 'storeAdmin']);  // Admin submits IPCR for any employee
    Route::post('/ipcr/employee', [IpcrSubmit::class, 'storeEmployee']);  // Employee submits IPCR for themselves
});






// FOR IPCRSUBMIT SEPERATE ROUTE
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout']);

//     Route::middleware('admin')->group(function () {
//         Route::post('/ipcr/admin', [IpcrController::class, 'storeAdmin']);  // Admin submits IPCR for any employee
//     });

//     Route::middleware('employee')->group(function () {
//         Route::post('/ipcr/employee', [IpcrController::class, 'storeEmployee']);  // Employee submits IPCR for themselves
//     });
// });
















// RoleBaseMiddleWare
// Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
//     Route::get('/admin-dashboard', [AdminController::class, 'index']);
// });