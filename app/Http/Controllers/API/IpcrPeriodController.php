<?php

namespace App\Http\Controllers\API;

use App\Models\IpcrPeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class IpcrPeriodController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'start_month_year' => 'required|date',
                'end_month_year' => 'required|date|after_or_equal:start_month_year',
                'ipcr_period_type' => 'required|string|max:255',
                'ipcr_type' => 'required|string|max:255',
                'active_flag' => 'nullable|boolean',
            ]);

            $validatedData['active_flag'] = $validatedData['active_flag'] ?? false;

            $existingPeriod = IpcrPeriod::where('ipcr_period_type', $request->ipcr_period_type)
                ->where('ipcr_type', $request->ipcr_type)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_month_year', [$request->start_month_year, $request->end_month_year])
                        ->orWhereBetween('end_month_year', [$request->start_month_year, $request->end_month_year]);
                })
                ->exists();

            if ($existingPeriod) {
                return response()->json([
                    'success' => false,
                    'message' => 'An IPCR period of the same type already exists within this date range.'
                ], 409);
            }

            // If active_flag is true, deactivate other active periods of the same type
            // if ($validatedData['active_flag']) {
            //     IpcrPeriod::where('active_flag', true)
            //         ->where('ipcr_period_type', $validatedData['ipcr_period_type'])
            //         ->update(['active_flag' => false]);
            // }

            if ($validatedData['active_flag']) {
                // Activate all periods with the same ipcr_period_type but different ipcr_type
                IpcrPeriod::where('ipcr_period_type', $validatedData['ipcr_period_type'])
                    ->update(['active_flag' => true]);
            }
            // Create new IPCR period
            $ipcrPeriod = IpcrPeriod::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'IPCR period created successfully.',
                'data' => $ipcrPeriod
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
