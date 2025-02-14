<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\PasswordResetToken;
use App\interfaces\AuthInterface;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthInterface
{
    public function register($request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        return $user;
    }
    public function login($request)
    {
        $user = User::where('email', $request->email)->first();
        return $user;
    }
    public function profile()
    {
        $user = auth()->user();
        return $user;
    }
    public function logout()
    {
        auth()->logout();
    }
    public function forgotPassword($request)
    {
        $user = User::where('email', $request->email)->first();
        return $user;
    }
    public function resetPassword($request)
    {
        $user = User::where('email', $request->email)->first();
        return $user;
    }
}
