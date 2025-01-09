<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class EmployeeAccountCreation extends Controller
{
    public function createAccount(Request $request)
    {
        try {
            // Step 1: Check if the employee already exists by employee_no
            $existingEmployee = Employee::where('employee_no', $request->employee_no)->first();
    
            if ($existingEmployee) {
                // If employee exists, ensure no user account exists yet
                if (User::where('employee_no', $existingEmployee->employee_no)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User account already exists for this employee.',
                    ], 409); // 409 Conflict
                }
    
                // Validate only user-related fields (skip employee uniqueness check)
                $validatedData = $request->validate([
                    'email_address' => 'required|email',
                    'password' => 'required|string|min:8',
                    'first_name' => 'nullable|string|max:255',
                    'last_name' => 'nullable|string|max:255',
                ]);
    
                // Ensure the provided email matches the existing employee record
                if ($existingEmployee->email_address !== $request->email_address) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email address does not match the existing employee record.',
                    ], 422);
                }
                    // $updatedFields = [];
                    // if (!empty($request->first_name) && $existingEmployee->first_name !== $request->first_name) {
                    //     $updatedFields['first_name'] = $request->first_name;
                    // }
                    // if (!empty($request->last_name) && $existingEmployee->last_name !== $request->last_name) {
                    //     $updatedFields['last_name'] = $request->last_name;
                    // }
        
                    // if (!empty($updatedFields)) {
                    //     $existingEmployee->update($updatedFields);
                    // }
            } else {
                // Validate as a new employee
                $validatedData = $request->validate([
                    'employee_no' => 'required|unique:employees,employee_no',
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email_address' => 'required|email|unique:employees,email_address',
                    'password' => 'required|string|min:8',
                ]);
    
                // Create a new employee
                $existingEmployee = Employee::create([
                    'employee_no' => $validatedData['employee_no'],
                    'first_name' => $validatedData['first_name'],
                    'last_name' => $validatedData['last_name'],
                    'email_address' => $validatedData['email_address'],
                ]);
            }
    
            // Step 2: Create a new user
            $user = User::create([
                'employee_no' => $existingEmployee->employee_no,
                'role' => 'employee',
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

    // public function createAccount(Request $request)
    //     {
    //         // Validate input data with proper JSON error response
    //         $validator = Validator::make($request->all(), [
    //             'employee_no' => 'required|unique:employees,employee_no|unique:users,employee_no',
    //             'first_name' => 'required|string|max:255',
    //             'last_name' => 'required|string|max:255',
    //             'email_address' => 'required|email|unique:employees,email_address',
    //             'password' => 'required|string|min:8',
    //         ]);

    //         // If validation fails, return JSON response
    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation failed',
    //                 'errors' => $validator->errors()
    //             ], 422); // 422 Unprocessable Entity
    //         }

    //         try {
    //             // Step 1: Check if the employee already exists
    //             $existingEmployee = Employee::where('employee_no', $request->employee_no)->first();

    //             if (!$existingEmployee) {
    //                 // Create new employee if not exists
    //                 $existingEmployee = Employee::create([
    //                     'employee_no' => $request->employee_no,
    //                     'first_name' => $request->first_name,
    //                     'last_name' => $request->last_name,
    //                     'email_address' => $request->email_address,
    //                 ]);
    //             }

    //             // Step 2: Check if user already exists
    //             $existingUser = User::where('employee_no', $existingEmployee->employee_no)->first();
    //             if ($existingUser) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'User account already exists for this employee.'
    //                 ], 409); // 409 Conflict
    //             }

    //             // Step 3: Create new user
    //             $user = User::create([
    //                 'employee_no' => $existingEmployee->employee_no,
    //                 'role' => 'employee',
    //                 'password' => Hash::make($request->password),
    //             ]);

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'User account created successfully.',
    //                 'data' => $user
    //             ], 201);

    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Something went wrong.',
    //                 'error' => $e->getMessage()
    //             ], 500);
    //         }
    //     }
}
