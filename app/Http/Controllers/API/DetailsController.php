<?php

namespace App\Http\Controllers\API;

use App\Models\Office;
use App\Models\Employee;
use App\Models\IpcrPeriod;
use Illuminate\Http\Request;
use App\Models\EmploymentType;
use App\Http\Controllers\Controller;
use App\Models\EmployeeClassification;

class DetailsController extends Controller
{
    public function getEmployeeClassifications()
    {
        $classifications = EmployeeClassification::all();

        $formattedClassifications = $classifications->map(function ($classification) {
            return [ 
                'id' => $classification->id,
                'classification' => $classification->classification,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedClassifications,
        ], 200);
    }

    public function getEmploymentTypes()
    {
        $employmentTypes = EmploymentType::all();

        $formattedEmploymentType = $employmentTypes->map(function ($employmentType) {
            return [ 
                'id' => $employmentType->id,
                'employment_type' => $employmentType->employment_type,
            ];
        });

        return response()->json([
            'success' => true,
            // 'data' => $employmentTypes,
            'data' => $formattedEmploymentType,
        ], 200);
    }

    public function getOffices()
    {
        $offices = Office::all();

        $formattedOffices = $offices->map(function ($office) {
            return [ 
                'id' => $office->id,
                'office_name' => $office->office_name,
                'office_description' => $office->office_description,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedOffices,
        ], 200);
    }


    public function getIpcrPeriods()
    {
        try {
            $ipcrPeriods = IpcrPeriod::where('active_flag', true)
                ->orderBy('start_month_year', 'desc')
                ->get();

            $formattedPeriods = $ipcrPeriods->map(function ($period) {
                return [
                    'id' => $period->id, 
                    'name' => $period->ipcr_period_type . ' - ' . $period->ipcr_type,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPeriods
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function AutoSuggestEmployee(Request $request)
    {
        try {
            $searchTerm = $request->input('search_name');
            
            $employees = Employee::where('first_name', 'like', "%{$searchTerm}%")
                                ->orWhere('last_name', 'like', "%{$searchTerm}%")
                                ->orWhere('middle_name', 'like', "%{$searchTerm}%")  
                                ->orWhere('employee_no', 'like', "%{$searchTerm}%")
                                ->select('employee_no','first_name', 'middle_name', 'last_name')
                                ->get();

            return response()->json([
                'success' => true,
                'data' => $employees
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

}
// public function AutoSuggestEmployee(Request $request)
// {
//     try {
//         $searchTerm = $request->input('search_name');
//         $employees = Employee::where('first_name', 'like', "%{$searchTerm}%")
//                             ->orWhere('last_name', 'like', "%{$searchTerm}%")
//                             ->select('first_name', 'last_name', 'employee_no')
//                             ->get();

//         return response()->json([
//             'success' => true,
//             'data' => $employees
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Something went wrong.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

