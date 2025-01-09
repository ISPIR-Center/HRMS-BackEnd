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

            $ipcr = Ipcr::create([
                'employee_no' => $request->employee_no,
                'ipcr_period_id' => $ipcrPeriod->id,
                'numerical_rating' => $request->numerical_rating,
                'adjectival_rating' => $adjectivalRating,
                'submitted_date' => now(),
                'validated_date' => null,
                'validated_by' => null, 
                // 'submitted_by' => auth()->id(), // Uncomment when authentication is added
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












// Code for Validating the IPCR (Admin Only)
// public function validateIpcr($id)
// {
//     try {
//         $ipcr = Ipcr::findOrFail($id);

//         // Check if the user is an admin (You need to implement role checking)
//         if (!auth()->user()->is_admin) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Only admins can validate IPCR records.'
//             ], 403);
//         }

//         // Update validation details
//         $ipcr->update([
//             'validated_date' => now(),
//             'validated_by' => auth()->id(),
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'IPCR validated successfully.',
//             'data' => $ipcr
//         ], 200);

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Something went wrong.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }
