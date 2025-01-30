<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateAccountsController extends Controller
{
    // Create User Account for Employee Admin function
    public function AdminCreateAccount(Request $request)
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
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'role' => 'nullable|string|in:employee,admin', 
                ]);

                if ($existingEmployee->email_address && $existingEmployee->email_address !== $request->email_address) {
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
                    'role' => 'nullable|string|in:employee,admin', 
                ]);

                $existingEmployee = Employee::create([
                    'employee_no' => $validatedData['employee_no'],
                    'first_name' => $validatedData['first_name'],
                    'last_name' => $validatedData['last_name'],
                    'email_address' => $validatedData['email_address'],
                ]);
            }

            $role = $validatedData['role'] ?? 'employee';

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

    
    // Create account public function
    public function CreateAccount(Request $request)
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
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                ]);
    
                if ($existingEmployee->email_address && $existingEmployee->email_address !== $request->email_address) {
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
                ]);
    
                $existingEmployee = Employee::create([
                    'employee_no' => $validatedData['employee_no'],
                    'first_name' => $validatedData['first_name'],
                    'last_name' => $validatedData['last_name'],
                    'email_address' => $validatedData['email_address'],
                ]);
            }
    
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
