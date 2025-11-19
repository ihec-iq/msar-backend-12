<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Http\Resources\User\UserProfileResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {

        $user = User::where('email', $request->email)->where('active', true)->orWhere('user_name', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(
                [
                    'code' => '402',
                    'message' => "The provided credentials are incorrect.",
                ],
                401
            );
        }
        $token = $user->createToken($request->email)->plainTextToken;

        return response()->json(
            [
                'id' => $user->id,
                'name' => $user->name,
                'token' => $token,
            ],
            200
        );
    }

    public function me()
    {
        return new UserResource(Auth::user());
    }

    public function profile()
    {
        return new UserProfileResource(Auth::user());
    }

    public function ho_me()
    {
        // return Auth::user();
        return new UserResource(Auth::user());
    }

    public function logout()
    {
        return Auth::user();
    }
}
