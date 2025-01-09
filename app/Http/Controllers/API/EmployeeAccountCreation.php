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
            //Check if the employee already exists by employee_no
            $existingEmployee = Employee::where('employee_no', $request->employee_no)->first();
    
            if ($existingEmployee) {
                // Check if a user account already exists for this employee
                if (User::where('employee_no', $existingEmployee->employee_no)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User account already exists for this employee.',
                    ], 409);
                }
    
                // Validate only user-related fields
                $validatedData = $request->validate([
                    'email_address' => 'required|email',
                    'password' => 'required|string|min:8',
                    'first_name' => 'nullable|string|max:255',
                    'last_name' => 'nullable|string|max:255',
                ]);
    
                // Email matches the existing employee
                if ($existingEmployee->email_address !== $request->email_address) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email address does not match the existing employee record.',
                    ], 422);
                }
            } else {
                // As a new employee
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
    
            // Create a new user
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

}
