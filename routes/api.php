<?php

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CreateAccountsController;
use App\Http\Controllers\API\DetailsController;
use App\Http\Controllers\API\IpcrPeriodController;
use App\Http\Controllers\API\EmployeeProfileController;
use App\Http\Controllers\API\IpcrSubmission;


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [AuthController::class, 'login']);
Route::post('/public-account/create', [CreateAccountsController::class, 'CreateAccount']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/admin-account/create', [CreateAccountsController::class, 'AdminCreateAccount'])->middleware('admin');
    
    Route::controller(DetailsController::class)->group(function () { 
        Route::get('/classification-type/dropdown',  'getEmployeeClassifications');
        Route::get('/employment-type/dropdown',  'getEmploymentTypes');
        Route::get('/offices/dropdown',  'getOffices');

        Route::get('/ipcr-periods/active-flags/dropdown',  'getIpcrPeriods');
        Route::post('/employee-names/autosuggestions/input-search',  'AutoSuggestEmployee')->middleware('admin');
    });

    Route::controller(EmployeeProfileController::class)->middleware('admin')->group(function () { 
        Route::post('/profile/create', 'CreateProfile');
        Route::get('/profile/list',  'ViewList');
        Route::get('/profile/view/{employee_no}',  'ViewProfile');
        Route::put('/profile/update/{employee_no}',  'UpdateProfile'); 

    });

    Route::controller(EmployeeProfileController::class)->middleware('employee')->group(function () { 
        Route::get('/employees/profile/view/{employee_no}',  'ViewEmployeeProfile');
        Route::put('/employees/profile/update/{employee_no}',  'UpdateEmployee');
    });

    
    Route::controller(IpcrPeriodController::class)->middleware('admin')->group(function () { 
        Route::post('/ipcr-periods/create', 'CreateIpcrPeriod');
        Route::get('/ipcr-periods/viewlist', 'ListIpcrPeriod');
        Route::get('/ipcr-periods/get/{id}', 'GetIpcrPeriod');
        Route::delete('/ipcr-periods/delete/{id}', 'DeleteIpcrPeriod');

    });

    Route::controller(IpcrSubmission::class)->group(function () { 
        Route::post('/admin-ipcr/submit', 'AdminSubmit')->middleware('admin');
        Route::get('/status-validation/view/{id}',  'GetIpcr')->middleware('admin');
        Route::put('/status-validation/update/{id}',  'IpcrStatusValidation')->middleware('admin');
        Route::patch('/status-validation/validation/{id}',  'ValidatedSubmission')->middleware('admin');
        Route::delete('/admin-ipcr/delete/{id}',  'DeleteIpcrRecord')->middleware('admin');


        Route::post('/employee-ipcr/submit',  'EmployeeIpcrSubmit')->middleware('employee');
        // List of IPCR Admin and User Funtion
        Route::get('/ipcr-list/view',  'IpcrList');
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});

