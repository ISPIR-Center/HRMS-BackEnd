<?php

namespace App\Http\Controllers\API;

use App\Models\Ipcr;
use App\Models\IpcrPeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class IpcrSubmit extends Controller
{
    public function storeAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_no' => 'required|exists:employees,employee_no', 
                'ipcr_period_type' => 'required|string|exists:ipcr_periods,ipcr_period_type',
                'ipcr_type' => 'required|string|exists:ipcr_periods,ipcr_type',
                'numerical_rating' => 'required|numeric|min:0|max:5',
                'file' => 'nullable|file|mimes:pdf,docx,doc,jpeg,png|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $employee_no = $request->employee_no;

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

            if (Ipcr::where('employee_no', $employee_no)->where('ipcr_period_id', $ipcrPeriod->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee has already submitted an IPCR for this period.'
                ], 409);
            }

            $adjectivalRating = $this->getAdjectivalRating($request->numerical_rating);
            $filePath = $request->file('file') ? $request->file('file')->store('ipcr_files', 'public') : null;

            $ipcr = Ipcr::create([
                'employee_no' => $employee_no, 
                'ipcr_period_id' => $ipcrPeriod->id,
                'numerical_rating' => $request->numerical_rating,
                'adjectival_rating' => $adjectivalRating,
                'submitted_date' => now(),
                'submitted_by' => $request->user()->employee_no, // Admin's employee_no
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

    public function storeEmployee(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ipcr_period_type' => 'required|string|exists:ipcr_periods,ipcr_period_type',
                'ipcr_type' => 'required|string|exists:ipcr_periods,ipcr_type',
                'numerical_rating' => 'required|numeric|min:0|max:5',
                'file' => 'nullable|file|mimes:pdf,docx,doc,jpeg,png|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $employee_no = auth()->user()->employee_no;

            $ipcrPeriod = IpcrPeriod::where('active_flag', true)
                ->where('ipcr_type', $request->ipcr_type)
                ->where('ipcr_period_type', $request->ipcr_period_type)
                ->first();

            if (!$ipcrPeriod) {
                return response()->json(['success' => false, 'message' => 'No active IPCR period found for the selected type.'], 403);
            }

            if (Ipcr::where('employee_no', $employee_no)->where('ipcr_period_id', $ipcrPeriod->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'You have already submitted an IPCR for this period.'], 409);
            }

            $adjectivalRating = $this->getAdjectivalRating($request->numerical_rating);
            $filePath = $request->file('file') ? $request->file('file')->store('ipcr_files', 'public') : null;

            return response()->json(['success' => true, 'message' => 'IPCR submitted successfully.'], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong.', 'error' => $e->getMessage()], 500);
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
