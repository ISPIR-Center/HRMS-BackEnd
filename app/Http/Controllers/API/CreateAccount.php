<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateAccount extends Controller
{
    
    public function createAccount(Request $request)
    {
        try {
            $existingEmployee = Employee::where('employee_no', $request->employee_no)->first();

            if ($existingEmployee) {
                if (User::where('employee_no', $existingEmployee->employee_no)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User account already exists for this employee.',
                    ], 409);
                }

                $validatedData = $request->validate([
                    'email_address' => 'required|email',
                    'password' => 'required|string|min:8',
                    'first_name' => 'nullable|string|max:255',
                    'last_name' => 'nullable|string|max:255',
                    'role' => 'nullable|string',
                ]);

                if ($existingEmployee->email_address !== $request->email_address) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email address does not match the existing employee record.',
                    ], 422);
                }
            } else {
                $validatedData = $request->validate([
                    'employee_no' => 'required|unique:employees,employee_no',
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email_address' => 'required|email|unique:employees,email_address',
                    'password' => 'required|string|min:8',
                    'role' => 'nullable|string',
                ]);

                $existingEmployee = Employee::create([
                    'employee_no' => $validatedData['employee_no'],
                    'first_name' => $validatedData['first_name'],
                    'last_name' => $validatedData['last_name'],
                    'email_address' => $validatedData['email_address'],
                ]);
            }

            $role = $validatedData['role'] ?? 'employee';
            // Admin accounts can only be created by admins
            // if ($role === 'admin' && !Auth::check() || Auth::user()->role !== 'admin') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Only admins can create an admin account.',
            //     ], 403);
            // }

            $user = User::create([
                'employee_no' => $existingEmployee->employee_no,
                'role' => $role,
                'password' => Hash::make($validatedData['password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User account created successfully.',
                'data' => $user,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
}






//
    // public function createAccount(Request $request)
    // {
    //     try {
    //         // Check if the employee exists
    //         $employee = Employee::where('employee_no', $request->employee_no)->first();
            
    //         if ($employee) {
    //             // Ensure a user account does not already exist
    //             if (User::where('employee_no', $employee->employee_no)->exists()) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'User account already exists for this employee.',
    //                 ], 409);
    //             }
    //         } else {
    //             // Validate and create a new employee
    //             $validatedData = $request->validate([
    //                 'employee_no' => 'required|unique:employees,employee_no',
    //                 'first_name' => 'required|string|max:255',
    //                 'last_name' => 'required|string|max:255',
    //                 'email_address' => 'required|email|unique:employees,email_address',
    //                 'password' => 'required|string|min:8',
    //             ]);
                
    //             $employee = Employee::create([
    //                 'employee_no' => $validatedData['employee_no'],
    //                 'first_name' => $validatedData['first_name'],
    //                 'last_name' => $validatedData['last_name'],
    //                 'email_address' => $validatedData['email_address'],
    //             ]);
    //         }

    //         // Create the user account
    //         $user = User::create([
    //             'employee_no' => $employee->employee_no,
    //             'role' => $request->role ?? 'employee',
    //             'password' => Hash::make($request->password),
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'User account created successfully.',
    //             'data' => $user,
    //         ], 201);

    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed.',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }