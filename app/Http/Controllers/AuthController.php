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
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function register(Request $request)
    {
        $service = $this->authService->register($request);
        return response()->json(['message' => $service]);
    }
    public function login(Request $request)
    {
        $service = $this->authService->login($request);
        if ($service instanceof JsonResponse) {
            return $service;
        }
        return response()->json($service);
    }
    public function profile()
    {
        $token = auth()->user();
        $user = User::find($token->id);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
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
