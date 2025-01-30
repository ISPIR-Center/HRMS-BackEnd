<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Office;
use App\Models\Employee;
use App\Models\IpcrPeriod;
use Illuminate\Http\Request;
use App\Models\EmploymentType;
use App\Http\Controllers\Controller;
use App\Models\EmployeeClassification;

class DetailsController extends Controller
{
    // Employee Classifications Dropdown
    public function getEmployeeClassifications()
    {
        $classifications = EmployeeClassification::all();
        return response()->json([
            'success' => true,
            'data' => $classifications,
        ], 200);
    }

    // Employment Types Dropdown
    public function getEmploymentTypes()
    {
        $employmentTypes = EmploymentType::all();

       

        return response()->json([
            'success' => true,
            'data' => $employmentTypes,
        ], 200);
    }

    // Offices Dropdown
    public function getOffices()
    {
        $offices = Office::all();


        return response()->json([
            'success' => true,
            "data" => $offices,
        ], 200);
    }

    // IPCR Periods Dropdown
    public function getIpcrPeriods()
    {
        try {
            $ipcrPeriods = IpcrPeriod::where('active_flag', true)
                ->orderBy('start_month_year', 'desc')
                ->get();

            $formattedPeriods = $ipcrPeriods->map(function ($period) {

                $startMonth = Carbon::parse($period->start_month_year)->format('F-Y'); 
                $endMonth = Carbon::parse($period->end_month_year)->format('F-Y'); 
                
                return [
                    'id' => $period->id, 
                    // 'name' => $period->ipcr_period_type . ' - ' . $period->ipcr_type,
                    'ipcr_period_type' => $period->ipcr_period_type,
                    'ipcr_type' => $period->ipcr_type, 
                    'period' => $startMonth . ' - ' . $endMonth,

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


