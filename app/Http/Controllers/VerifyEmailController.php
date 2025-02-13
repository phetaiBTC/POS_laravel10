<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use App\Models\User;

class VerifyEmailController extends Controller
{
    public function verify(Request $request)
    {
        $user = $request->route('id');

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }
        if (! hash_equals((string) $request->route('hash'), sha1($user->email))) {
            return response()->json(['error' => 'Invalid verification link.'], 403);
        }

        $user->email_verified_at = now();
        $user->save();

        event(new Verified($user));

        return response()->json(['message' => 'Email successfully verified.'], 200);
        // return response()->json(['message' => $user], 200);
    }

    public function resend(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email resent.']);
    }
}
