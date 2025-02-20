<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Validator;
// use Tymon\JWTAuth\Facades\JWTAuth;
// use App\Models\User;
// use Illuminate\Support\Facades\Password;
// use App\Models\PasswordResetToken;
// use Spatie\Permission\Models\Role;
// use Spatie\Permission\Models\Permission;
use App\Services\AuthService;
// use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function register(Request $request)
    {
        $serviceRegister = $this->authService->register($request);
        return $serviceRegister;
    }
    public function login(Request $request)
    {
        $serviceLogin = $this->authService->login($request);
        return $serviceLogin;
    }
    public function profile()
    {
        $token = auth()->user();
        $user = $this->authService->profile($token->id);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->format('d-m-Y H:i:s') : null,
            'created_at' => $user->created_at->format('d-m-Y H:i:s'),
            'updated_at' => $user->updated_at->format('d-m-Y H:i:s'),
            'vendor_id' => $user->vendors ? $user->vendors->id : null,

            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
    public function logout()
    {
        $this->authService->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
    public function forgotPassword(Request $request)
    {
        return $this->authService->sendPasswordResetLink($request);
    }
    public function resetPassword(Request $request)
    {
        return $this->authService->resetPassword($request);
    }
    public function changePassword(Request $request)
    {
        return $this->authService->changePassword($request);
    }
}
