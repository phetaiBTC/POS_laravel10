<?php

namespace App\Services;

use App\interfaces\AuthInterface;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

class AuthService
{
    protected $authRepository;
    public function __construct(AuthInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }
    public function login($request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $checkEmail = $this->authRepository->login($request);
        if (!$checkEmail) {
            return response()->json(['error' => 'User not found.'], 404);
        }
        if (!$checkEmail->hasVerifiedEmail()) {
            return response()->json(['error' => 'Email not verified.'], 403);
        }
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return response()->json(['token' => $token]);
    }
    public function register($request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        $user = $this->authRepository->register($request);
        $user->sendEmailVerificationNotification();
        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully. Please check your email to verify.'
        ], 201);
    }
    public function profile()
    {
        return $this->authRepository->profile();
    }
    public function logout()
    {
        return $this->authRepository->logout();
    }
    public function sendPasswordResetLink(string $email)
    {
        return $this->authRepository->sendPasswordResetLink($email);
    }
    public function resetPassword($request)
    {
        return $this->authRepository->resetPassword($request);
    }
}
