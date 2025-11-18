<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // register user
    public function register(Request $request)
    {
        try {

            $reqData = $request->data;

            $validated = Validator::make(
                $reqData,
                [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                    'password'  => ['required', 'string', 'min:8', 'confirmed']
                ],
                [
                    'name.required'  => 'Name is required.',
                    'email.required' => 'Email is required.',
                    'email.email'    => 'Please enter a valid email address.',
                    'email.unique'   => 'This email is already registered.',
                    'password.required'  => 'Password is required.',
                    'password.min'       => 'Password must be at least 8 characters.',
                    'password.confirmed' => 'Password and confirm password do not match.',
                ]
            );

            if ($validated->fails()) {
                return $this->error('Validation failed', 422, $validated->errors());
            }

            $data = $validated->validated();

            $user = User::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
            ]);

            // create sanctum token
            $token = $user->createToken('api-token')->plainTextToken;

            return $this->success('User registered successfully', [
                'user' => $user,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            Log::error('Register user error: ' . $e->getMessage());

            return $this->error('Something went wrong', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }


    // login 
    public function login(Request $request)
    {
        $reqData = $request->data;

        $validated = Validator::make(
            $reqData,
            [
                'email' => ['required', 'string', 'email'],
                'password'  => ['required', 'string', 'min:8']
            ],
            [
                'email.required' => 'Email is required.',
                'email.email'    => 'Please enter a valid email address.',
                'password.required'  => 'Password is required.',
                'password.min'       => 'Password must be at least 8 characters.',

            ]
        );

        if ($validated->fails()) {
            return $this->error('Validation failed', 422, $validated->errors());
        }

        $data = $validated->validated();

        // Attempt login
        if (!auth()->attempt([
            'email'    => $data['email'],
            'password' => $data['password']
        ])) {
            return $this->error('Invalid credentials', 401);
        }

        $user = auth()->user();

        // Create Sanctum Token
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success('Login successful', [
            'user'  => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
