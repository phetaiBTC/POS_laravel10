<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\PasswordResetToken;
use App\interfaces\AuthInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Facades\JWTAuth;


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
    public function profile($id)
    {
        $user = User::find($id);
        return $user;
    }
    public function selectPassword($email)
    {
        $resetData = PasswordResetToken::where('email', $email)->first();
        return $resetData;
    }
    public function deletePassword($email)
    {
        PasswordResetToken::where('email', $email)->delete();
    }
    public function changePassword($request)
    {
        $user = User::find(auth()->user()->id);
        if (Hash::check($request->current_password, $user->password)) {
            $user->password = Hash::make($request->new_password);
            $user->save();
            return response()->json(['message' => 'Password changed successfully.']);
        } else {    
            return response()->json(['error' => 'Current password is incorrect.']);
        }
    }
}
