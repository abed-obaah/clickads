<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:8|confirmed',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //     ]);

    //     // Generate OTP
    //     $otp = rand(100000, 999999);
    //     $user->otp = $otp;
    //     $user->otp_expires_at = now()->addMinutes(10);
    //     $user->save();

    //     // Send OTP email
    //     Mail::to($user->email)->send(new OtpMail($user, $otp));

    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'User registered successfully. OTP sent to email.',
    //         'user' => $user,
    //         'access_token' => $token,
    //         'token_type' => 'Bearer'
    //     ], 201);
    // }


    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'balance' => 0.00, // Add this line to set default balance
    ]);

    // Generate OTP
    $otp = rand(100000, 999999);
    $user->otp = $otp;
    $user->otp_expires_at = now()->addMinutes(10);
    $user->save();

    // Send OTP email
    Mail::to($user->email)->send(new OtpMail($user, $otp));

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'status' => 'success',
        'message' => 'User registered successfully. OTP sent to email.',
        'user' => $user,
        'access_token' => $token,
        'token_type' => 'Bearer'
    ], 201);
}

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_verified) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please verify your email with OTP first'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }
}