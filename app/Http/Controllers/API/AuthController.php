<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

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
                'employee_no' => 'required|string',
                'password' => 'required|string|min:8',
            ]);

            $user = User::where('employee_no', $validatedData['employee_no'])->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
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
                // Token Expiration
                'expires_in' => now()->addHours(2)->timestamp, // Token expires in 2 hours
                'user' => [
                    'id' => $user->id,
                    'employee_no' => $user->employee_no,
                    'role' => $user->role,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong.'], 500);
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



   // public function login(Request $request)
    // {
    //     try {
    //         // Validate input
    //         $validatedData = $request->validate([
    //             'employee_no' => 'required|string',
    //             'password' => 'required|string|min:8',
    //         ]);

    //         // Trim password to avoid extra spaces
    //         $validatedData['password'] = trim($validatedData['password']);

    //         // Log request
    //         Log::info('Login Attempt:', [
    //             'employee_no' => $validatedData['employee_no'],
    //             'ip' => $request->ip(),
    //             'user_agent' => $request->header('User-Agent'),
    //         ]);

    //         // Find user
    //         $user = User::where('employee_no', $validatedData['employee_no'])->first();

    //         if (!$user) {
    //             Log::warning('Login Failed: User not found', ['employee_no' => $validatedData['employee_no']]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'User not found.',
    //             ], 404);
    //         }

    //         // Check password
    //         if (!Hash::check($validatedData['password'], $user->password)) {
    //             Log::warning('Login Failed: Incorrect password', ['employee_no' => $validatedData['employee_no']]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Password Dont match.',
    //             ], 401);
    //         }

    //         // Generate authentication token
    //         // $token = $user->createToken('auth-token')->plainTextToken;
    //         $token = $user->createToken('auth-token', [$user->role])->plainTextToken;


    //         // Log successful login
    //         Log::info('Login Successful', ['employee_no' => $validatedData['employee_no']]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Login successful.',
    //             'token' => $token,
    //             'user' => [
    //                 'id' => $user->id,
    //                 'employee_no' => $user->employee_no,
    //                 'role' => $user->role,
    //             ],
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         Log::error('Login Validation Failed', ['errors' => $e->errors()]);
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed.',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Throwable $e) {
    //         Log::error('Login Error', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong.',
    //         ], 500);
    //     }
    // }