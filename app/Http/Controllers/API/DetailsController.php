<?php

namespace App\Http\Controllers\API;

use App\Models\Office;
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
}
