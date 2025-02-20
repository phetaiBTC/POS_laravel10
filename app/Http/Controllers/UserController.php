<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    public function __construct() {
        $this->middleware('role:admin|vendor');
    }
    public function show($user)
    {
        $user = User::find($user);
        if(!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->format('d-m-Y H:i:s') : null,
            'created_at' => $user->created_at->format('d-m-Y H:i:s'),
            'updated_at' => $user->updated_at->format('d-m-Y H:i:s'),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function getall()
    {
        $users = User::all();
        $mapper = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->format('d-m-Y H:i:s') : null,
                'created_at' => $user->created_at->format('d-m-Y H:i:s'),
                'updated_at' => $user->updated_at->format('d-m-Y H:i:s'),
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ];
        });
        return response()->json(['users' => $mapper], 200,);
    }

    public function update(Request $request, User $user)
    {
        $Validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone'=>'required|string',
        ]);
        if ($Validator->fails()) {
            return response()->json($Validator->errors(), 422);
        }
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->save();
        return response()->json($user);
    }
    public function destroy($user)
    {
        $user = User::find($user);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
