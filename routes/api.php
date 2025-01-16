<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CreateAccountsController;
use App\Http\Controllers\API\IpcrPeriodController;
use App\Http\Controllers\API\EmployeeProfileController;
use App\Http\Controllers\API\IpcrSubmission;



Route::post('/login', [AuthController::class, 'login']);
Route::post('/public-account/create', [CreateAccountsController::class, 'CreateAccount']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/admin-account/create', [CreateAccountsController::class, 'AdminCreateAccount'])->middleware('admin');

    Route::controller(EmployeeProfileController::class)->middleware('admin')->group(function () { 
        Route::post('/profile/create', 'CreateProfile');
        Route::get('/profile/list',  'ViewList');
        Route::get('/profile/view/{employee_no}',  'ViewProfile');
        Route::put('/profile/update/{employee_no}',  'UpdateProfile'); 
        
    });

    Route::controller(EmployeeProfileController::class)->middleware('employee')->group(function () { 
        Route::get('/employees/profile/view/{employee_no}',  'ViewProfile');
        Route::put('/employees/profile/update/{employee_no}',  'UpdateEmployee');
    });

    
    Route::controller(IpcrPeriodController::class)->middleware('admin')->group(function () { 
        Route::post('/ipcr-periods/create', 'CreateIpcrPeriod');
        Route::get('/ipcr-periods/viewlist', 'ListIpcrPeriod');
        Route::get('/ipcr-periods/{id}', 'GetIpcrPeriod');
        Route::delete('/ipcr-periods/{id}', 'DeleteIpcrPeriod');

    });

    Route::controller(IpcrSubmission::class)->group(function () { 
        Route::post('/admin-ipcr/submit', 'AdminSubmit')->middleware('admin');
        Route::put('/status-validation/update/{id}',  'IpcrStatusValidation')->middleware('admin');
        Route::post('/employee-ipcr/submit',  'EmployeeIpcrSubmit')->middleware('employee');
        Route::get('/ipcr-list/view',  'IpcrList');
        
    });


    Route::post('/logout', [AuthController::class, 'logout']);
});

