<?php

namespace App\Http\Controllers\API;

use App\Models\IpcrPeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class IpcrPeriodController extends Controller
{
    public function CreateIpcrPeriod(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'start_month_year' => 'required|date',
                'end_month_year' => 'required|date|after_or_equal:start_month_year',
                'ipcr_period_type' => 'required|string|max:255',
                'ipcr_type' => 'required|string|in:Target,Accomplished',
            ]);

            $currentDate = now()->toDateString(); 

            IpcrPeriod::where('end_month_year', '<', $currentDate)->update(['active_flag' => false]);

            $validatedData['active_flag'] = ($currentDate >= $validatedData['start_month_year'] && $currentDate <= $validatedData['end_month_year']);

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
    

    public function ListIpcrPeriod()
    {
        try {
            $ipcrPeriods = IpcrPeriod::orderBy('start_month_year', 'desc')->get();
            
            // Format the dates before returning
            $formattedPeriods = $ipcrPeriods->map(function ($period) {
                $startMonth = Carbon::parse($period->start_month_year)->format('F'); // Full month name
                $endMonth = Carbon::parse($period->end_month_year)->format('F'); // Full month name
                $year = Carbon::parse($period->start_month_year)->format('Y'); // Year

                return [
                    'id' => $period->id,
                    'period' => "$startMonth - $endMonth $year",
                    'ipcr_period_type' => $period->ipcr_period_type,
                    'ipcr_type' => $period->ipcr_type,
                    'active_flag' => $period->active_flag,
                    // 'created_at' => Carbon::parse($period->created_at)->format('Y-m-d H:i:s'),
                    // 'updated_at' => Carbon::parse($period->updated_at)->format('Y-m-d H:i:s'),
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


    public function GetIpcrPeriod($id)
    {
        try {
            $ipcrPeriod = IpcrPeriod::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $ipcrPeriod
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'IPCR period not found.'
            ], 404);
        }
    }

    public function DeleteIpcrPeriod($id)
    {
        try {
            $ipcrPeriod = IpcrPeriod::findOrFail($id);
            $ipcrPeriod->delete();

            return response()->json([
                'success' => true,
                'message' => 'IPCR period deleted successfully.'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'IPCR period not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
