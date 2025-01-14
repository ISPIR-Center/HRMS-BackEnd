<?php

namespace App\Http\Controllers\API;

use App\Models\Ipcr;
use App\Models\Employee;
use App\Models\IpcrPeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IpcrController extends Controller
{
    public function store(Request $request)
    {
        try {
         
            $validator = Validator::make($request->all(), [
                'employee_no' => 'required|exists:employees,employee_no',
                'ipcr_period_type' => 'required|string|exists:ipcr_periods,ipcr_period_type',
                'ipcr_type' => 'required|string|exists:ipcr_periods,ipcr_type',
                'numerical_rating' => 'required|numeric|min:0|max:5',
                'file' => 'nullable|file|mimes:pdf,docx,doc,jpeg,png|max:10240', // Validates file type and size

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
    
            $ipcrPeriod = IpcrPeriod::where('active_flag', true)
            ->where('ipcr_type', $request->ipcr_type)
            ->where('ipcr_period_type', $request->ipcr_period_type)
            ->first();

            if (!$ipcrPeriod) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active IPCR period found for the selected type.'
                ], 403);
            }
            //Prevent multiple submissions in the same period (even for different types)
            $existingIpcr = Ipcr::where('employee_no', $request->employee_no)
                ->whereIn('ipcr_period_id', function ($query) {
                    $query->select('id')->from('ipcr_periods')->where('active_flag', true);
                })
                ->first();
                
            if ($existingIpcr) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already submitted an IPCR for this period, regardless of type.'
                ], 409);
            }

            $adjectivalRating = $this->getAdjectivalRating($request->numerical_rating);
            $filePath = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filePath = $file->store('ipcr_files', 'public');
            }

            $ipcr = Ipcr::create([
                'employee_no' => $request->employee_no,
                'ipcr_period_id' => $ipcrPeriod->id,
                'numerical_rating' => $request->numerical_rating,
                'adjectival_rating' => $adjectivalRating,
                'submitted_date' => now(),
                'validated_date' => null,
                'validated_by' => null, 
                'submitted_by' => auth()->user()->employee_no,
                'file_path' => $filePath, 
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IPCR submitted successfully.',
                'data' => $ipcr
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getAdjectivalRating($rating)
    {
        if ($rating >= 4.5) return 'Outstanding';
        if ($rating >= 3.5) return 'Very Satisfactory';
        if ($rating >= 2.5) return 'Satisfactory';
        if ($rating >= 1.5) return 'Unsatisfactory';
        return 'Poor';
    }
}












// SINGLE FUNCTION ROUTES
//SINGLE FUNCTION FOR IPCR NOTH ADMIN AND EMPLOYEE
// public function store(Request $request)
// {
//     try {
//         // Validation rules
//         $validator = Validator::make($request->all(), [
//             'employee_no' => 'required_if:role,admin|exists:employees,employee_no', // Admin must provide employee_no
//             'ipcr_period_type' => 'required|string|exists:ipcr_periods,ipcr_period_type',
//             'ipcr_type' => 'required|string|exists:ipcr_periods,ipcr_type',
//             'numerical_rating' => 'required|numeric|min:0|max:5',
//             'file' => 'nullable|file|mimes:pdf,docx,doc,jpeg,png|max:10240', // Validates file type and size
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Validation failed',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         // Get the authenticated user
//         $user = auth()->user();

//         // Determine the employee_no
//         if ($user->role === 'admin') {
//             // Admin can submit for any employee, use the provided employee_no
//             $employee_no = $request->employee_no;
//         } else {
//             // Employee can only submit for themselves, use the authenticated user's employee_no
//             $employee_no = $user->employee_no;
//         }

//         // Find the IPCR period
//         $ipcrPeriod = IpcrPeriod::where('active_flag', true)
//             ->where('ipcr_type', $request->ipcr_type)
//             ->where('ipcr_period_type', $request->ipcr_period_type)
//             ->first();

//         if (!$ipcrPeriod) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'No active IPCR period found for the selected type.'
//             ], 403);
//         }

//         // Prevent multiple submissions in the same period (even for different types)
//         $existingIpcr = Ipcr::where('employee_no', $employee_no)
//             ->whereIn('ipcr_period_id', function ($query) {
//                 $query->select('id')->from('ipcr_periods')->where('active_flag', true);
//             })
//             ->first();

//         if ($existingIpcr) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'You have already submitted an IPCR for this period, regardless of type.'
//             ], 409);
//         }

//         $adjectivalRating = $this->getAdjectivalRating($request->numerical_rating);
//         $filePath = null;

//         if ($request->hasFile('file')) {
//             $file = $request->file('file');
//             $filePath = $file->store('ipcr_files', 'public');
//         }

//         $ipcr = Ipcr::create([
//             'employee_no' => $employee_no, // Use the employee_no from either the admin or employee
//             'ipcr_period_id' => $ipcrPeriod->id,
//             'numerical_rating' => $request->numerical_rating,
//             'adjectival_rating' => $adjectivalRating,
//             'submitted_date' => now(),
//             'validated_date' => null,
//             'validated_by' => null,
//             'submitted_by' => $employee_no, // This ensures the submission is linked to the correct user
//             'file_path' => $filePath,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'IPCR submitted successfully.',
//             'data' => $ipcr
//         ], 201);

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Something went wrong.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }
