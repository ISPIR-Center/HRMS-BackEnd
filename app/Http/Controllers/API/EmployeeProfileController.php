<?php

namespace App\Http\Controllers\API;

use App\Models\Office;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmploymentType;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\EmployeeClassification;
use Illuminate\Validation\ValidationException;

class EmployeeProfileController extends Controller
{
    public function CreateProfile(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'employment_type_id' => 'required|exists:employment_types,id',
                'classification_id' => 'required|exists:employee_classifications,id',
                'office_id' => 'required|exists:offices,id',

                'employee_no' => 'required|string|unique:employees,employee_no',

                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'suffix' => 'nullable|string|max:10',
                'email_address' => 'nullable|email|unique:employees,email_address', 
                'mobile_no' => 'nullable|string|max:20',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|string|in:Male,Female',
                'google_scholar_link' => 'nullable|url',
            ]);

            $employee = Employee::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Created successfully',
                'data' => $employee
            ], 201);

        } 
        catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } 
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function ViewList(): JsonResponse
    {
        $employees = Employee::with(['employmentType', 'classification', 'office', 'ipcrs'])->get();

        return response()->json($employees);
    }


    public function ViewProfile($employee_no): JsonResponse
    {
        try {
            $employee = Employee::with(['employmentType', 'classification', 'office'])->findOrFail($employee_no);

            return response()->json($employee, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Employee not found', 'message' => $e->getMessage()], 404);
        }
    } 

    

    public function UpdateProfile(Request $request, $employee_no): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($employee_no);

            $validatedData = $request->validate([
                'employment_type_id' => 'nullable|exists:employment_types,id',
                'classification_id' => 'nullable|exists:employee_classifications,id',
                'office_id' => 'nullable|exists:offices,id',
                'suffix' => 'nullable|string',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email_address' => 'nullable|email|unique:employees,email_address,' . $employee_no . ',employee_no',
                'mobile_no' => 'nullable|string',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|in:Male,Female',
                'google_scholar_link' => 'nullable|url',
            ]);

            $employee->update($validatedData);

            if ($request->filled('employment_type_id')) {
                $employee->employmentType()->associate($request->employment_type_id);
            }
            if ($request->filled('classification_id')) {
                $employee->classification()->associate($request->classification_id);
            }
            if ($request->filled('office_id')) {
                $employee->office()->associate($request->office_id);
            }

            $employee->save(); 

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully',
                'data' => $employee->load(['employmentType', 'classification', 'office'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function UpdateEmployee(Request $request, $employee_no): JsonResponse
    {
        try {
            $authEmployee = auth()->user(); 
            
            if ($authEmployee->employee_no !== $employee_no) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only update your own profile'
                ], 403);
            }

            $employee = Employee::findOrFail($employee_no);

            $validatedData = $request->validate([
                'employment_type_id' => 'nullable|exists:employment_types,id',
                'classification_id' => 'nullable|exists:employee_classifications,id',
                'office_id' => 'nullable|exists:offices,id',
                'suffix' => 'nullable|string',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email_address' => 'required|email|unique:employees,email_address,' . $employee_no . ',employee_no',
                'mobile_no' => 'nullable|string',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|in:Male,Female',
                'google_scholar_link' => 'nullable|url',
            ]);

            $employee->update($validatedData);

            if ($request->filled('employment_type_id')) {
                $employee->employmentType()->associate($request->employment_type_id);
            }
            if ($request->filled('classification_id')) {
                $employee->classification()->associate($request->classification_id);
            }
            if ($request->filled('office_id')) {
                $employee->office()->associate($request->office_id);
            }
            $employee->save();

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully',
                'data' => $employee->load(['employmentType', 'classification', 'office'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getEmployeeClassifications()
    {
        $classifications = EmployeeClassification::all();

        return response()->json([
            'success' => true,
            'data' => $classifications,
        ], 200);
    }

    public function getEmploymentTypes()
    {
        $employmentTypes = EmploymentType::all();

        $types = $employmentTypes->map(function ($formatted) {
            return [ 
                'employment_type' => $formatted->employment_type,
            ];
        });

        return response()->json([
            'success' => true,
            // 'data' => $employmentTypes,
            'data' => $types,
        ], 200);
    }

    public function getOffices()
    {
        $offices = Office::all();

        return response()->json([
            'success' => true,
            'data' => $offices,
        ], 200);
    }
    
}












// public function updateEmployee(Request $request, $employee_no): JsonResponse
    // {
    //     try {
    //         $employee = Employee::findOrFail($employee_no);

    //         $validatedData = $request->validate([
    //             'employment_type_id' => 'nullable|exists:employment_types,id',
    //             'classification_id' => 'nullable|exists:employee_classifications,id',
    //             'office_id' => 'nullable|exists:offices,id',
    //             'suffix' => 'nullable|string',
    //             'first_name' => 'required|string|max:255',
    //             'middle_name' => 'nullable|string|max:255',
    //             'last_name' => 'required|string|max:255',
    //             'email_address' => 'required|email|unique:employees,email_address,' . $employee_no . ',employee_no',
    //             'mobile_no' => 'nullable|string',
    //             'birthdate' => 'nullable|date',
    //             'gender' => 'nullable|in:Male,Female',
    //             'google_scholar_link' => 'nullable|url',
    //         ]);

    //         $employee->update($validatedData);

    //         if ($request->has('employment_type_id')) {
    //             $employee->employmentType()->associate($request->employment_type_id);
    //         }

    //         if ($request->has('classification_id')) {
    //             $employee->classification()->associate($request->classification_id);
    //         }

    //         if ($request->has('office_id')) {
    //             $employee->office()->associate($request->office_id);
    //         }

    //         return response()->json($employee->load(['employmentType', 'classification', 'office']), 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
    //     }
    // }