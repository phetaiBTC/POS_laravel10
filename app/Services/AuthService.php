<?php

namespace App\Services;

use App\interfaces\AuthInterface;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;


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
            'phone' => 'required|string',
            'avatar' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $user = $this->authRepository->register($request);
        $user->sendEmailVerificationNotification();
        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully. Please check your email to verify.'
        ], 201);
    }
    public function profile($id)
    {
        return $this->authRepository->profile($id);
    }
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }
    public function sendPasswordResetLink($request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['error' => __($status)], 500);
    }
    public function resetPassword($request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // ค้นหา Token จาก email
        // $resetData = PasswordResetToken::where('email', $request->email)->first();
        $resetData = $this->authRepository->selectPassword($request->email);

        // ตรวจสอบ Token โดยใช้ Hash::check()
        if (!$resetData || !Hash::check($request->token, $resetData->token)) {
            return response()->json(['error' => 'Invalid token.'], 400);
        }

        // รีเซ็ตรหัสผ่าน
        $status = Password::reset(
            [
                'email' => $request->email,
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation,
                'token' => $request->token
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // ลบ Token ออกจาก Database
            // PasswordResetToken::where('email', $request->email)->delete();
            $this->authRepository->deletePassword($request);
            return response()->json(['message' => 'Password has been successfully reset.']);
        }

        return response()->json(['error' => 'Invalid token or email.'], 500);
    }
    public function changePassword($request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return $this->authRepository->changePassword($request);
    }
}
