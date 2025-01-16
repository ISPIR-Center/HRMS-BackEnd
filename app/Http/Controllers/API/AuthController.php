<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{   
    public function login(Request $request)
    {
        try {
            $key = 'login-attempts:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json(['success' => false, 'message' => 'Too many login attempts. Try again later.'], 429);
            }
            RateLimiter::hit($key, 60);

            $validatedData = $request->validate([
                'email_address' => 'required|string|email',
                'password' => 'required|string|min:8',
            ]);

            $employee = Employee::where('email_address', $validatedData['email_address'])->first();
            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
            }

            $user = User::where('employee_no', $employee->employee_no)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User account not found.'], 404);
            }

            if (!Hash::check($validatedData['password'], $user->password)) {
                return response()->json(['success' => false, 'message' => 'Password does not match.'], 401);
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth-token', [$user->role])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'token' => $token,
                'expires_in' => now()->addHours(2)->timestamp,
                'user' => [
                    'id' => $user->id,
                    'employee_no' => $user->employee_no,
                    'email_address' => $employee->email_address, 
                    'role' => $user->role,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong.', 'error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['success' => true, 'message' => 'Logged out successfully.'], 200);
    }
}








// Luma, employee_no ang username 
 // public function login(Request $request)
    // {
    //     try {
    //         $key = 'login-attempts:' . $request->ip();
    //         if (RateLimiter::tooManyAttempts($key, 5)) {
    //             return response()->json(['success' => false, 'message' => 'Too many login attempts. Try again later.'], 429);
    //         }
    //         RateLimiter::hit($key, 60);

    //         $validatedData = $request->validate([
    //             'employee_no' => 'required|string',
    //             'password' => 'required|string|min:8',
    //         ]);

    //         $user = User::where('employee_no', $validatedData['employee_no'])->first();
    //         if (!$user) {
    //             return response()->json(['success' => false, 'message' => 'User not found.'], 404);
    //         }

    //         if (!Hash::check($validatedData['password'], $user->password)) {
    //             return response()->json(['success' => false, 'message' => 'Password does not match.'], 401);
    //         }

    //         $user->tokens()->delete();
    //         $token = $user->createToken('auth-token', [$user->role])->plainTextToken;

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Login successful.',
    //             'token' => $token,
    //             // Token Expiration
    //             'expires_in' => now()->addHours(2)->timestamp, // Token expires in 2 hours
    //             'user' => [
    //                 'id' => $user->id,
    //                 'employee_no' => $user->employee_no,
    //                 'role' => $user->role,
    //             ],
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
    //     } catch (\Throwable $e) {
    //         return response()->json(['success' => false, 'message' => 'Something went wrong.'], 500);
    //     }
    // }
    // public function logout(Request $request)
    // {
    //     $request->user()->tokens->each(function ($token) {
    //         $token->delete();
    //     });

    //     return response()->json(['success' => true, 'message' => 'Logged out successfully.'], 200);
    // }