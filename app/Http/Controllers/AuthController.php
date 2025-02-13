<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use App\Models\PasswordResetToken;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->sendEmailVerificationNotification();
        $token = JWTAuth::fromUser($user);

        // return response()->json(['user' => $user, 'token' => $token]);
        return response()->json(['message' => 'User registered successfully. Please check your email to verify.']);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $checkEmail = User::where('email', $request->email)->first();
        if (!$checkEmail->hasVerifiedEmail()) {
            return response()->json(['error' => 'Email not verified.'], 403);
        }
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = auth()->user();
        return response()->json(['token' => $token, 'user' => $user]);
    }


    public function profile()
    {
        $token = auth()->user();
        $user= User::find($token->id);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'roles' => $user->getRoleNames(), // Fetch roles
            'permissions' => $user->getAllPermissions()->pluck('name'), // Fetch permissions
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Successfully logged out']);
    }
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['error' => __($status)], 500);
    }

    public function resetPassword(Request $request)
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
        $resetData = PasswordResetToken::where('email', $request->email)->first();

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
            PasswordResetToken::where('email', $request->email)->delete();
            return response()->json(['message' => 'Password has been successfully reset.']);
        }

        return response()->json(['error' => 'Invalid token or email.'], 500);
    }
}
