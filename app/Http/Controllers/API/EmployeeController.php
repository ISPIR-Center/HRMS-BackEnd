<?php

namespace App\Http\Controllers\API;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate incoming request
            $validatedData = $request->validate([
                'employment_type_id' => 'required|exists:employment_types,id',
                'classification_id' => 'required|exists:employee_classifications,id',
                'office_id' => 'required|exists:offices,id',

                'employee_no' => 'required|string|unique:employees,employee_no',

                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'suffix' => 'nullable|string|max:10',
                'email_address' => 'required|email|unique:employees,email_address',
                'mobile_no' => 'nullable|string|max:20',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|string|in:Male,Female',
                'google_scholar_link' => 'nullable|url',
            ]);

            // Create a new employee
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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
