<?php

namespace App\Http\Controllers\API;

use App\Models\Ipcr;
use App\Models\User;
use App\Models\IpcrPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;



class IpcrSubmission extends Controller
{
    public function AdminSubmit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_no' => 'required|exists:employees,employee_no',
                'ipcr_period_id' => 'required|exists:ipcr_periods,id',
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
            $ipcrPeriod = IpcrPeriod::find($request->ipcr_period_id);

            $existingSubmission = Ipcr::where('employee_no', $employee_no)
                ->whereHas('period', function ($query) use ($request) {
                    $query->where('id', '!=', $request->ipcr_period_id)
                        ->where('ipcr_period_type', '!=', IpcrPeriod::find($request->ipcr_period_id)->ipcr_period_type);
                })
                ->exists();

            if ($existingSubmission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee has already submitted an IPCR for a different period type.'
                ], 409);
            }

            // If submitting "Accomplished", check if there is a "Target" submission in the same period type
            if ($ipcrPeriod->ipcr_type === 'Accomplished') {
                $existingTargetSubmission = Ipcr::where('employee_no', $employee_no)
                    ->whereHas('period', function ($query) use ($ipcrPeriod) {
                        $query->where('ipcr_period_type', $ipcrPeriod->ipcr_period_type) // Same period type
                            ->where('ipcr_type', 'Target'); // Ensure Target type
                    })
                    ->exists();
            
                if (!$existingTargetSubmission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot submit Accomplished because no Target submission exists for the same IPCR period type.'
                    ], 409);
                }
            }

            $duplicateSubmission = Ipcr::where('employee_no', $employee_no)
                ->where('ipcr_period_id', $ipcrPeriod->id)
                ->whereHas('period', function ($query) use ($ipcrPeriod) {
                    $query->where('ipcr_type', $ipcrPeriod->ipcr_type); 
                })
                ->exists();

            if ($duplicateSubmission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee has already submitted an IPCR for this period and type.'
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
                'ipcr_period_id' => 'required|exists:ipcr_periods,id',
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

            $user = auth()->user();
            $employee_no = $user->employee_no;

            $ipcrPeriod = IpcrPeriod::find($request->ipcr_period_id);

            if (!$ipcrPeriod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid IPCR period.'
                ], 404);
            }

            if ($ipcrPeriod->ipcr_type === 'Accomplished') {
                $existingTargetSubmission = Ipcr::where('employee_no', $employee_no)
                    ->whereHas('period', function ($query) use ($ipcrPeriod) {
                        $query->where('ipcr_period_type', $ipcrPeriod->ipcr_period_type)
                            ->where('ipcr_type', 'Target');
                    })
                    ->exists();

                if (!$existingTargetSubmission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot submit Accomplished because no Target submission exists for the same IPCR period type.'
                    ], 409);
                }
            }

            $duplicateSubmission = Ipcr::where('employee_no', $employee_no)
                ->where('ipcr_period_id', $ipcrPeriod->id)
                ->whereHas('period', function ($query) use ($ipcrPeriod) {
                    $query->where('ipcr_type', $ipcrPeriod->ipcr_type);
                })
                ->exists();

            if ($duplicateSubmission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee has already submitted an IPCR for this period and type.'
                ], 409);
            }

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
                'submitted_by' => $user->id,
                'validated_by' => $user->id,  
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

    private function getAdjectivalRating($rating)
     {
         if ($rating >= 4.5) return 'Outstanding';
         if ($rating >= 3.5) return 'Very Satisfactory';
         if ($rating >= 2.5) return 'Satisfactory';
         if ($rating >= 1.5) return 'Unsatisfactory';
         return 'Poor';
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

    // public function IpcrList(Request $request)
    // {
    //      try {
    //          $user = Auth::user();
 
    //          if ($user->role === 'admin') {
    //              $ipcrs = Ipcr::with(['employee', 'submittedBy', 'validatedBy', 'period'])->get();
    //          } else {
    //              $ipcrs = Ipcr::where('employee_no', $user->employee_no)
    //                  ->with(['period'])
    //                  ->get();
    //          }


    //          $formattedIpcrs = $ipcrs->map(function ($ipcr) {
    //             return [
               
    //                 'id' => $ipcr->id,
    //                 'employee_no' => $ipcr->employee_no,
    //                 'employee_name' => optional($ipcr->employee, fn($e) => $e->first_name . ' ' . $e->last_name) ?? 'Null',
    //                 // 'employee_name' => optional($ipcr->employee) ? $ipcr->employee->first_name . ' ' . $ipcr->employee->last_name : 'N/A',
    //                 'numerical_rating' => $ipcr->numerical_rating,
    //                 'adjectival_rating' => $ipcr->adjectival_rating,
    //                 'submitted_date' => $ipcr->submitted_date,
    //                 'validated_date' => $ipcr->validated_date,
    //                 // classes from model submittedBy validatedBy
    //                 // 'submitted_by' => optional($ipcr->submittedBy?->employee)->first_name ?? 'N/A',
    //                 'submitted_by' => optional($ipcr->submittedBy)->employee_no ?? 'N/A',
    //                 'validated_by' => optional($ipcr->validatedBy)->employee_no ?? 'N/A',
    //                 'file_path' => $ipcr->file_path,
    //                 'status' => $ipcr->status,
    //                 'ipcr_period' => [
    //                     'type' => optional($ipcr->period)->ipcr_type,
    //                     'start_date' => optional($ipcr->period)->start_month_year,
    //                     'end_date' => optional($ipcr->period)->end_month_year,
    //                 ],
    //             ];
    //         });
    //          return response()->json([
    //              'success' => true,
    //              'message' => 'IPCR records retrieved successfully.',
    //             //  'data' => $ipcrs
    //             'data'=> $formattedIpcrs
    //          ], 200);
    //      } catch (\Exception $e) {
    //          return response()->json([
    //              'success' => false,
    //              'message' => 'Something went wrong.',
    //              'error' => $e->getMessage()
    //          ], 500);
    //      }
    // }

    public function IpcrList(Request $request)
    {
        try {
           $perPage = $request->get('per_page', 20);

            $user = Auth::user();

            if ($user->role === 'admin') {
                $ipcrs = Ipcr::with([
                    'employee:id,first_name,last_name', 
                    'submittedBy:id,employee_no', 
                    'validatedBy:id,employee_no', 
                    'period:id,ipcr_type,start_month_year,end_month_year'
                ])->paginate($perPage);
            } else {
                $ipcrs = Ipcr::where('employee_no', $user->employee_no)
                    ->with(['period:id,ipcr_type,start_month_year,end_month_year'])
                    ->paginate($perPage);
            }

            $formattedIpcrs = $ipcrs->map(function ($ipcr) {
                return [
                    'id' => $ipcr->id,
                    'employee_no' => $ipcr->employee_no,
                    'employee_name' => optional($ipcr->employee, fn($e) => $e->first_name . ' ' . $e->last_name) ?? 'Null',
                    'numerical_rating' => $ipcr->numerical_rating,
                    'adjectival_rating' => $ipcr->adjectival_rating,
                    'submitted_date' => $ipcr->submitted_date,
                    'validated_date' => $ipcr->validated_date,
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
                'data' => $formattedIpcrs,
                'pagination' => [
                'current_page' => $ipcrs->currentPage(),
                'per_page' => $ipcrs->perPage(),
                'total' => $ipcrs->total(),
                'next_page_url' => $ipcrs->nextPageUrl(),
                'prev_page_url' => $ipcrs->previousPageUrl(),
                'last_page' => $ipcrs->lastPage(),
                'to' => $ipcrs->lastItem(),
            ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('IPCR List error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
























































// Treat 1st part as first name, last part as last name, and the rest as middle name
// public function AdminSubmit(Request $request)
// {
//     try {
//         $validator = Validator::make($request->all(), [
//             'full_name' => 'required|string|max:255', // New field for full name
//             'ipcr_period_id' => 'required|exists:ipcr_periods,id',
//             'numerical_rating' => $request->ipcr_type === 'Accomplished' ? 'required|numeric|min:0|max:5' : 'nullable|numeric|min:0|max:5',
//             'file' => 'nullable|file|mimes:pdf,docx,doc,jpeg,png|max:10240',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Validation failed',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         // Split the full name into first and last name
//         $nameParts = explode(' ', $request->full_name);
//         $first_name = $nameParts[0];  // The first part is the first name
//         $last_name = array_pop($nameParts); // The last part is the last name
//         $middle_name = implode(' ', $nameParts); // Any remaining parts are treated as the middle name

//         // Find employee by first and last name
//         $employee = Employee::where('first_name', $first_name)
//                             ->where('last_name', $last_name)
//                             ->first();

//         if (!$employee) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Employee not found.'
//             ], 404);
//         }

//         $employee_no = $employee->employee_no; // Get employee_no from the found employee

//         $ipcrPeriod = IpcrPeriod::find($request->ipcr_period_id);

//         // Check for existing submission in a different period type
//         $existingSubmission = Ipcr::where('employee_no', $employee_no)
//             ->whereHas('period', function ($query) use ($request) {
//                 $query->where('id', '!=', $request->ipcr_period_id)
//                     ->where('ipcr_period_type', '!=', IpcrPeriod::find($request->ipcr_period_id)->ipcr_period_type);
//             })
//             ->exists();

//         if ($existingSubmission) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Employee has already submitted an IPCR for a different period type.'
//             ], 409);
//         }

//         // If submitting "Accomplished", check if there is a "Target" submission in the same period type
//         if ($ipcrPeriod->ipcr_type === 'Accomplished') {
//             $existingTargetSubmission = Ipcr::where('employee_no', $employee_no)
//                 ->whereHas('period', function ($query) use ($ipcrPeriod) {
//                     $query->where('ipcr_period_type', $ipcrPeriod->ipcr_period_type) // Same period type
//                         ->where('ipcr_type', 'Target'); // Ensure Target type
//                 })
//                 ->exists();

//             if (!$existingTargetSubmission) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Cannot submit Accomplished because no Target submission exists for the same IPCR period type.'
//                 ], 409);
//             }
//         }

//         // Check for duplicate submission
//         $duplicateSubmission = Ipcr::where('employee_no', $employee_no)
//             ->where('ipcr_period_id', $ipcrPeriod->id)
//             ->whereHas('period', function ($query) use ($ipcrPeriod) {
//                 $query->where('ipcr_type', $ipcrPeriod->ipcr_type);
//             })
//             ->exists();

//         if ($duplicateSubmission) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Employee has already submitted an IPCR for this period and type.'
//             ], 409);
//         }

//         // Get the user who is submitting the IPCR
//         $submittedByUser = User::where('employee_no', $request->user()->employee_no)->first();
//         $submittedById = $submittedByUser ? $submittedByUser->id : null;

//         // Get the adjectival rating based on the numerical rating
//         $adjectivalRating = null;
//         if ($request->numerical_rating !== null) {
//             $adjectivalRating = $this->getAdjectivalRating($request->numerical_rating);
//         }

//         // Store the uploaded file, if any
//         $filePath = $request->file('file') ? $request->file('file')->store('ipcr_files', 'public') : null;

//         // Create the IPCR submission
//         $ipcr = Ipcr::create([
//             'employee_no' => $employee_no,
//             'ipcr_period_id' => $ipcrPeriod->id,
//             'numerical_rating' => $request->numerical_rating,
//             'adjectival_rating' => $adjectivalRating,
//             'submitted_date' => now(),
//             'submitted_by' => $submittedById,
//             'validated_by' => $submittedById,
//             'validated_date' => now(),
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



// SUBMITTING BY NAME
// public function AdminSubmit(Request $request)
//     {
//         try {
//             $validator = Validator::make($request->all(), [
//                 'full_name' => 'required|string|max:255', // New field for full name
//                 'ipcr_period_id' => 'required|exists:ipcr_periods,id',
//                 'numerical_rating' => $request->ipcr_type === 'Accomplished' ? 'required|numeric|min:0|max:5' : 'nullable|numeric|min:0|max:5',
//                 'file' => 'nullable|file|mimes:pdf,docx,doc,jpeg,png|max:10240',
//             ]);

//             if ($validator->fails()) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Validation failed',
//                     'errors' => $validator->errors()
//                 ], 422);
//             }

//             // Split the full name into first and last name
//             $nameParts = explode(' ', $request->full_name, 2);
//             if (count($nameParts) < 2) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Please provide both first name and last name.'
//                 ], 422);
//             }

//             $first_name = $nameParts[0];
//             $last_name = $nameParts[1];

//             // Find employee by first and last name
//             $employee = Employee::where('first_name', $first_name)
//                                 ->where('last_name', $last_name)
//                                 ->first();

//             if (!$employee) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Employee not found.'
//                 ], 404);
//             }

//             $employee_no = $employee->employee_no; // Get employee_no from the found employee

//             $ipcrPeriod = IpcrPeriod::find($request->ipcr_period_id);

//             // Check for existing submission in a different period type
//             $existingSubmission = Ipcr::where('employee_no', $employee_no)
//                 ->whereHas('period', function ($query) use ($request) {
//                     $query->where('id', '!=', $request->ipcr_period_id)
//                         ->where('ipcr_period_type', '!=', IpcrPeriod::find($request->ipcr_period_id)->ipcr_period_type);
//                 })
//                 ->exists();

//             if ($existingSubmission) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Employee has already submitted an IPCR for a different period type.'
//                 ], 409);
//             }

//             // If submitting "Accomplished", check if there is a "Target" submission in the same period type
//             if ($ipcrPeriod->ipcr_type === 'Accomplished') {
//                 $existingTargetSubmission = Ipcr::where('employee_no', $employee_no)
//                     ->whereHas('period', function ($query) use ($ipcrPeriod) {
//                         $query->where('ipcr_period_type', $ipcrPeriod->ipcr_period_type) // Same period type
//                             ->where('ipcr_type', 'Target'); // Ensure Target type
//                     })
//                     ->exists();

//                 if (!$existingTargetSubmission) {
//                     return response()->json([
//                         'success' => false,
//                         'message' => 'Cannot submit Accomplished because no Target submission exists for the same IPCR period type.'
//                     ], 409);
//                 }
//             }

//             // Check for duplicate submission
//             $duplicateSubmission = Ipcr::where('employee_no', $employee_no)
//                 ->where('ipcr_period_id', $ipcrPeriod->id)
//                 ->whereHas('period', function ($query) use ($ipcrPeriod) {
//                     $query->where('ipcr_type', $ipcrPeriod->ipcr_type);
//                 })
//                 ->exists();

//             if ($duplicateSubmission) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Employee has already submitted an IPCR for this period and type.'
//                 ], 409);
//             }

//             // Get the user who is submitting the IPCR
//             $submittedByUser = User::where('employee_no', $request->user()->employee_no)->first();
//             $submittedById = $submittedByUser ? $submittedByUser->id : null;

//             // Get the adjectival rating based on the numerical rating
//             $adjectivalRating = null;
//             if ($request->numerical_rating !== null) {
//                 $adjectivalRating = $this->getAdjectivalRating($request->numerical_rating);
//             }

//             // Store the uploaded file, if any
//             $filePath = $request->file('file') ? $request->file('file')->store('ipcr_files', 'public') : null;

//             // Create the IPCR submission
//             $ipcr = Ipcr::create([
//                 'employee_no' => $employee_no,
//                 'ipcr_period_id' => $ipcrPeriod->id,
//                 'numerical_rating' => $request->numerical_rating,
//                 'adjectival_rating' => $adjectivalRating,
//                 'submitted_date' => now(),
//                 'submitted_by' => $submittedById,
//                 'validated_by' => $submittedById,
//                 'validated_date' => now(),
//                 'file_path' => $filePath,
//             ]);

//             return response()->json([
//                 'success' => true,
//                 'message' => 'IPCR submitted successfully.',
//                 'data' => $ipcr
//             ], 201);
//         } catch (\Exception $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Something went wrong.',
//                 'error' => $e->getMessage()
//             ], 500);
//         }
//     }

