<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/', 'unique:users,phone'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'device_name' => ['required', 'string', 'max:60'],
        ]);
        $user = User::create(collect($data)->except('device_name')->all());

        return ApiResponse::ok(['token' => $user->createToken($data['device_name'])->plainTextToken, 'user' => MeController::present($user)], [], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:60'],
        ]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return ApiResponse::error('Email atau kata sandi salah.', 422, [['field' => 'email', 'message' => 'Email atau kata sandi salah.']]);
        }
        if ($user->status !== 'active') {
            return ApiResponse::error('Akun dinonaktifkan.', 403);
        }
        $user->forceFill(['last_login_at' => now()])->save();

        return ApiResponse::ok(['token' => $user->createToken($data['device_name'])->plainTextToken, 'user' => MeController::present($user)]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::ok(['logged_out' => true]);
    }
}
