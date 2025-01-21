<?php

namespace App\Http\Controllers\API;

use App\Models\Ipcr;
use App\Models\IpcrPeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;



class IpcrSubmission extends Controller
{
    public function AdminSubmit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_no' => 'required|exists:employees,employee_no',
                'ipcr_period_type' => 'required|string|exists:ipcr_periods,ipcr_period_type',
                'ipcr_type' => [
                    'required',
                    'string',
                    Rule::exists('ipcr_periods', 'ipcr_type')->where(function ($query) use ($request) {
                        $query->where('ipcr_period_type', $request->ipcr_period_type)
                              ->where('active_flag', true);
                    })
                ],
                'numerical_rating' => $request->ipcr_type === 'Accomplished' ? 'required|numeric|min:0|max:5' : 'nullable|numeric|min:0|max:5',
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
                ->where('ipcr_period_type', $request->ipcr_period_type)
                ->where('start_month_year', '<=', now())
                ->where('end_month_year', '>=', now())
                ->where('ipcr_type', $request->ipcr_type)
                ->first();
    
            if (!$ipcrPeriod) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active IPCR period found for the selected type.'
                ], 403);
            }
    
            $existingSubmission = Ipcr::where('employee_no', $employee_no)
                ->whereHas('ipcrPeriod', function ($query) use ($request) {
                    $query->where('ipcr_period_type', $request->ipcr_period_type)
                          ->where('ipcr_type', $request->ipcr_type);
                })
                ->exists();
    
            if ($existingSubmission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee has already submitted an IPCR for this period type and IPCR type.'
                ], 409);
            }
    
            $existingTargetSubmission = Ipcr::where('employee_no', $employee_no)
                ->whereHas('ipcrPeriod', function ($query) {
                    $query->where('ipcr_type', 'Target');
                })
                ->exists();
    
            if ($existingTargetSubmission && $request->ipcr_type === 'Accomplished') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot submit Accomplished because a Target submission already exists in a different period type.'
                ], 409);
            }
    
            $submittedByUser = User::where('employee_no', $request->user()->employee_no)->first();
            $submittedById = $submittedByUser ? $submittedByUser->id : null;
    
            $adjectivalRating = null;
            if ($request->numerical_rating !== null) {
                $adjectivalRating = $this->getAdjectivalRating($request->numerical_rating);
            }
    
            $filePath = $request->file('file') ? $request->file('file')->store('ipcr_files', 'public') : null;

            $ipcr = Ipcr::create([
                'employee_no' => $employee_no,
                'ipcr_period_id' => $ipcrPeriod->id,
                'numerical_rating' => $request->numerical_rating,
                'adjectival_rating' => $adjectivalRating,
                'submitted_date' => now(),
                'submitted_by' => $submittedById,
                'validated_by' => $submittedById,  
                'validated_date' => now(),  
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


    public function EmployeeIpcrSubmit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ipcr_period_type' => 'required|string|exists:ipcr_periods,ipcr_period_type',
                'ipcr_type' => 'required|string|exists:ipcr_periods,ipcr_type',
                'numerical_rating' => $request->ipcr_type === 'Accomplished' ? 'required|numeric|min:0|max:5' : 'nullable|numeric|min:0|max:5',
                'file' => 'nullable|file|mimes:pdf,docx,doc,jpeg,png|max:10240',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
    
            $user = auth()->user();
            $employee_no = $user->employee_no;
    
            $ipcrPeriod = IpcrPeriod::where('active_flag', true)
                ->where('ipcr_period_type', $request->ipcr_period_type)
                ->where('ipcr_type', $request->ipcr_type)
                ->where('start_month_year', '<=', now())
                ->where('end_month_year', '>=', now())
                ->first();
    
            if (!$ipcrPeriod) {
                return response()->json(['success' => false, 'message' => 'No active IPCR period found for the selected type.'], 403);
            }
    
            $existingSubmission = Ipcr::where('employee_no', $employee_no)
                ->where('ipcr_period_id', $ipcrPeriod->id)
                ->exists();
    
            if ($existingSubmission) {
                $ipcr = Ipcr::where('employee_no', $employee_no)
                    ->where('ipcr_period_id', $ipcrPeriod->id)
                    ->first();
                $ipcr->numerical_rating = $request->numerical_rating;
                $ipcr->adjectival_rating = $this->getAdjectivalRating($request->numerical_rating);
                $ipcr->file_path = $request->file('file') ? $request->file('file')->store('ipcr_files', 'public') : $ipcr->file_path;
                $ipcr->submitted_date = now();
                $ipcr->save();
            } else {
                $adjectivalRating = $this->getAdjectivalRating($request->numerical_rating);

                $filePath = $request->file('file') ? $request->file('file')->store('ipcr_files', 'public') : null;

                $ipcr = Ipcr::create([
                    'employee_no' => $employee_no,
                    'ipcr_period_id' => $ipcrPeriod->id,
                    'numerical_rating' => $request->numerical_rating,
                    'adjectival_rating' => $adjectivalRating,
                    'submitted_date' => now(),
                    'submitted_by' => $user->id,
                    'file_path' => $filePath,
                ]);
            }
    
            return response()->json(['success' => true, 'message' => 'IPCR submitted successfully.', 'data' => $ipcr], 201);
    
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


    public function IpcrList(Request $request)
    {
         try {
             $user = Auth::user();
 
             if ($user->role === 'admin') {
                 $ipcrs = Ipcr::with(['employee', 'submittedBy', 'validatedBy', 'period'])->get();
             } else {
                 $ipcrs = Ipcr::where('employee_no', $user->employee_no)
                     ->with(['period'])
                     ->get();
             }


             $formattedIpcrs = $ipcrs->map(function ($ipcr) {
                return [
               
                    'id' => $ipcr->id,
                    'employee_no' => $ipcr->employee_no,
                    'employee_name' => optional($ipcr->employee, fn($e) => $e->first_name . ' ' . $e->last_name) ?? 'Null',
                    // 'employee_name' => optional($ipcr->employee) ? $ipcr->employee->first_name . ' ' . $ipcr->employee->last_name : 'N/A',
                    'numerical_rating' => $ipcr->numerical_rating,
                    'adjectival_rating' => $ipcr->adjectival_rating,
                    'submitted_date' => $ipcr->submitted_date,
                    // classes from model submittedBy validatedBy
                    // 'submitted_by' => optional($ipcr->submittedBy?->employee)->first_name ?? 'N/A',
                    'submitted_by' => optional($ipcr->submittedBy)->employee_no ?? 'N/A',
                    'validated_by' => optional($ipcr->validatedBy)->employee_no ?? 'N/A',
                    'file_path' => $ipcr->file_path,
                    'status' => $ipcr->status,
                    'ipcr_period' => [
                        'type' => optional($ipcr->period)->ipcr_type,
                        'start_date' => optional($ipcr->period)->start_month_year,
                        'end_date' => optional($ipcr->period)->end_month_year,
                    ],
                ];
            });
             return response()->json([
                 'success' => true,
                 'message' => 'IPCR records retrieved successfully.',
                //  'data' => $ipcrs
                'data'=> $formattedIpcrs
             ], 200);
         } catch (\Exception $e) {
             return response()->json([
                 'success' => false,
                 'message' => 'Something went wrong.',
                 'error' => $e->getMessage()
             ], 500);
         }
    }


    public function GetIpcr($id)
    {
        try {
            $ipcr = Ipcr::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $ipcr
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IPCR record not found.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function ValidatedSubmission(Request $request, $id)
    {
        try {
            $ipcr = Ipcr::findOrFail($id);

            if ($ipcr->status === 'Submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'This IPCR has already been validated.'
                ], 400);
            }
            $ipcr->status = 'Submitted';
            $ipcr->validated_by = auth()->id();
            $ipcr->validated_date = now();
            $ipcr->save();

            return response()->json([
                'success' => true,
                'message' => 'IPCR validated successfully.',
                'data' => $ipcr
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function IpcrStatusValidation(Request $request, $id)
    {
        $ipcr = Ipcr::findOrFail($id);
        try {
            $request->validate([
                'status' => 'required|in:Pending,Submitted',
            ]);

            $ipcr = Ipcr::findOrFail($id);
            $ipcr->status = $request->status;
            $ipcr->validated_by = auth()->id(); 
            $ipcr->validated_date = now(); 
            $ipcr->save();

            return response()->json([
                'success' => true,
                'message' => 'Validated !',
                'data' => $ipcr
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function DeleteIpcrRecord($id)
    {
        try {
            $ipcr = Ipcr::findOrFail($id);
            $ipcr->delete();

            return response()->json([
                'success' => true,
                'message' => 'Deleted !'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}























// REPLACE not Prevent
// public function storeAdmin(Request $request)
// {
//     try {
//         $validator = Validator::make($request->all(), [
//             'employee_no' => 'required|exists:employees,employee_no',
//             'ipcr_type' => 'required|string|exists:ipcr_periods,ipcr_type',
//             'numerical_rating' => 'required|numeric|min:0|max:5',
//             'file' => 'nullable|file|mimes:pdf,docx,doc,jpeg,png|max:10240',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Validation failed',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $employee_no = $request->employee_no;

//         // Get active IPCR period for the same period type, allowing different ipcr_types
//         $ipcrPeriod = IpcrPeriod::where('active_flag', true)
//             ->where('ipcr_period_type', 'Regular')  
//             ->where('start_month_year', '<=', now())
//             ->where('end_month_year', '>=', now())
//             ->where('ipcr_type', $request->ipcr_type) 
//             ->first();

//         if (!$ipcrPeriod) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'No active IPCR period found for the selected type.'
//             ], 403);
//         }

//         // Check if the employee already submitted an IPCR for this period & type
//         $existingIpcr = Ipcr::where('employee_no', $employee_no)
//             ->where('ipcr_period_id', $ipcrPeriod->id)
//             ->where('ipcr_type', $request->ipcr_type) // Ensure each type has its own record
//             ->first();

//         // Handle file upload if present
//         $filePath = $request->file('file') ? $request->file('file')->store('ipcr_files', 'public') : null;

//         if ($existingIpcr) {
//             // Update existing IPCR
//             $existingIpcr->numerical_rating = $request->numerical_rating;
//             $existingIpcr->adjectival_rating = $this->getAdjectivalRating($request->numerical_rating);
//             $existingIpcr->file_path = $filePath ?? $existingIpcr->file_path;
//             $existingIpcr->submitted_date = now();
//             $existingIpcr->submitted_by = $request->user()->id;

//             $existingIpcr->save();

//             return response()->json([
//                 'success' => true,
//                 'message' => 'IPCR updated successfully.',
//                 'data' => $existingIpcr
//             ], 200);
//         } else {
//             // Create new IPCR if none exists
//             $submittedById = $request->user()->id;

//             $ipcr = Ipcr::create([
//                 'employee_no' => $employee_no,
//                 'ipcr_period_id' => $ipcrPeriod->id,
//                 'ipcr_type' => $request->ipcr_type, // Ensure type is stored
//                 'numerical_rating' => $request->numerical_rating,
//                 'adjectival_rating' => $this->getAdjectivalRating($request->numerical_rating),
//                 'submitted_date' => now(),
//                 'submitted_by' => $submittedById,
//                 'file_path' => $filePath,
//             ]);

//             return response()->json([
//                 'success' => true,
//                 'message' => 'IPCR submitted successfully.',
//                 'data' => $ipcr
//             ], 201);
//         }

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Something went wrong.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

// public function storeAdmin(Request $request)
// {
//     try {
//         $validator = Validator::make($request->all(), [
//             'employee_no' => 'required|exists:employees,employee_no',
//             'ipcr_type' => 'required|string|exists:ipcr_periods,ipcr_type',
//             'numerical_rating' => 'required|numeric|min:0|max:5',
//             'file' => 'nullable|file|mimes:pdf,docx,doc,jpeg,png|max:10240',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Validation failed',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $employee_no = $request->employee_no;

//         // Get active IPCR period for the same period type (allow different ipcr_type)
//         $ipcrPeriod = IpcrPeriod::where('active_flag', true)
//             ->where('ipcr_period_type', function ($query) use ($employee_no) {
//                 // Fetch the period type of any existing record to match it
//                 $existingIpcr = Ipcr::where('employee_no', $employee_no)->first();
//                 if ($existingIpcr) {
//                     $query->where('ipcr_period_type', $existingIpcr->ipcrPeriod->ipcr_period_type);
//                 }
//             })
//             ->where('start_month_year', '<=', now())
//             ->where('end_month_year', '>=', now())
//             ->where('ipcr_type', $request->ipcr_type) 
//             ->first();

//         if (!$ipcrPeriod) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'No active IPCR period found for the selected type.'
//             ], 403);
//         }

//         // Check if an existing IPCR exists for this period & type
//         $existingIpcr = Ipcr::where('employee_no', $employee_no)
//             ->where('ipcr_period_id', $ipcrPeriod->id)
//             ->where('ipcr_type', $request->ipcr_type) // Ensuring each type can have its own record
//             ->first();

//         // Handle file upload
//         $filePath = $request->file('file') ? $request->file('file')->store('ipcr_files', 'public') : null;

//         if ($existingIpcr) {
//             // Update existing IPCR record
//             $existingIpcr->numerical_rating = $request->numerical_rating;
//             $existingIpcr->adjectival_rating = $this->getAdjectivalRating($request->numerical_rating);
//             $existingIpcr->file_path = $filePath ?? $existingIpcr->file_path;
//             $existingIpcr->submitted_date = now();
//             $existingIpcr->submitted_by = $request->user()->id;

//             $existingIpcr->save();

//             return response()->json([
//                 'success' => true,
//                 'message' => 'IPCR updated successfully.',
//                 'data' => $existingIpcr
//             ], 200);
//         } else {
//             // Create a new IPCR record if it doesn't exist
//             $submittedById = $request->user()->id;

//             $ipcr = Ipcr::create([
//                 'employee_no' => $employee_no,
//                 'ipcr_period_id' => $ipcrPeriod->id,
//                 'ipcr_type' => $request->ipcr_type, // Store type
//                 'numerical_rating' => $request->numerical_rating,
//                 'adjectival_rating' => $this->getAdjectivalRating($request->numerical_rating),
//                 'submitted_date' => now(),
//                 'submitted_by' => $submittedById,
//                 'file_path' => $filePath,
//             ]);

//             return response()->json([
//                 'success' => true,
//                 'message' => 'IPCR submitted successfully.',
//                 'data' => $ipcr
//             ], 201);
//         }

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Something went wrong.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }