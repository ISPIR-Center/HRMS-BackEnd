<?php

namespace App\Http\Controllers\API;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
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
                'designation' => 'nullable|string|max:255', 
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
        try {
            $employees = Employee::with(['employmentType', 'classification', 'office', 'ipcrs'])->get();
    
            return response()->json([
                'success' => true,
                'data' => $employees,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employee list',
                'error' => $e->getMessage(),
            ], 500);
        }
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
                'employee_no' => 'required|string|unique:employees,employee_no,' . $employee_no . ',employee_no',
                'designation' => 'nullable|string|max:255', 
            ]);

            if ($request->filled('employee_no') && $request->employee_no !== $employee_no) {
                $employee->employee_no = $validatedData['employee_no'];
            }

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
                'data' => $employee->load(['employmentType', 'classification', 'office']),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
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

            $employee = Employee::where('employee_no', $employee_no)->firstOrFail();

            $validatedData = $request->validate([
                'employee_no' => 'required|string|unique:employees,employee_no,' . $employee->employee_no . ',employee_no',
                'employment_type_id' => 'nullable|exists:employment_types,id',
                'classification_id' => 'nullable|exists:employee_classifications,id',
                'office_id' => 'nullable|exists:offices,id',
                'suffix' => 'nullable|string',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email_address' => 'required|email|unique:employees,email_address,' . $employee->employee_no . ',employee_no',
                'mobile_no' => 'nullable|string',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|in:Male,Female',
                'google_scholar_link' => 'nullable|url',
                'designation' => 'nullable|string|max:255', 
            ]);

            if ($request->has('employee_no') && $employee->employee_no !== $validatedData['employee_no']) {
                if (Employee::where('employee_no', $validatedData['employee_no'])->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The new employee number is already taken.',
                    ], 409);
                }

                $employee->employee_no = $validatedData['employee_no'];
            }

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

    public function ViewEmployeeProfile($employee_no): JsonResponse
    {
        try {
            $employee = Employee::with(['employmentType', 'classification', 'office'])
                                ->where('employee_no', $employee_no) 
                                ->firstOrFail();

            if (auth()->user()->employee_no !== $employee_no) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You can only view your own profile.',
                ], 403);
            }

            return response()->json($employee, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Employee not found', 'message' => $e->getMessage()], 404);
        }
    }

}





// public function UpdateEmployee(Request $request, $employee_no): JsonResponse
// {
//     try {
//         $authEmployee = auth()->user(); 

//         // Check if the authenticated user is trying to update their own profile (User profile update)
//         if ($authEmployee->employee_no !== $employee_no) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Unauthorized: You can only update your own profile'
//             ], 403);
//         }

//         return $this->updateEmployeeProfile($request, $employee_no);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'error' => 'Something went wrong',
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }

// public function UpdateProfile(Request $request, $employee_no): JsonResponse
// {
//     try {
//         return $this->updateEmployeeProfile($request, $employee_no);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'error' => 'Something went wrong',
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }

// private function updateEmployeeProfile(Request $request, $employee_no): JsonResponse
// {
//     $employee = Employee::findOrFail($employee_no);

//     // Validate the incoming request data, including the employee_no
//     $validatedData = $request->validate([
//         'employee_no' => 'required|string|unique:employees,employee_no,' . $employee_no . ',employee_no',
//         'employment_type_id' => 'nullable|exists:employment_types,id',
//         'classification_id' => 'nullable|exists:employee_classifications,id',
//         'office_id' => 'nullable|exists:offices,id',
//         'suffix' => 'nullable|string',
//         'first_name' => 'required|string|max:255',
//         'middle_name' => 'nullable|string|max:255',
//         'last_name' => 'required|string|max:255',
//         'email_address' => 'nullable|email|unique:employees,email_address,' . $employee_no . ',employee_no',
//         'mobile_no' => 'nullable|string',
//         'birthdate' => 'nullable|date',
//         'gender' => 'nullable|in:Male,Female',
//         'google_scholar_link' => 'nullable|url',
//     ]);

//     // Update employee_no if provided and different
//     if ($request->filled('employee_no') && $request->employee_no !== $employee_no) {
//         // Ensure the new employee_no is unique
//         if (Employee::where('employee_no', $validatedData['employee_no'])->exists()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'The new employee number is already taken.',
//             ], 409);
//         }

//         // Update employee_no
//         $employee->employee_no = $validatedData['employee_no'];
//     }

//     // Update the employee record with validated data
//     $employee->update($validatedData);

//     // If related fields are provided, update relationships
//     if ($request->filled('employment_type_id')) {
//         $employee->employmentType()->associate($request->employment_type_id);
//     }
//     if ($request->filled('classification_id')) {
//         $employee->classification()->associate($request->classification_id);
//     }
//     if ($request->filled('office_id')) {
//         $employee->office()->associate($request->office_id);
//     }

//     // Save the updated employee
//     $employee->save();

//     return response()->json([
//         'success' => true,
//         'message' => 'Employee updated successfully',
//         'data' => $employee->load(['employmentType', 'classification', 'office']),
//     ], 200);
// }







