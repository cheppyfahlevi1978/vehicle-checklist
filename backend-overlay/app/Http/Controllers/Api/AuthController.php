<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $login = trim((string) ($data['login'] ?? $data['email'] ?? ''));
        if ($login === '') {
            throw ValidationException::withMessages(['login' => ['Email atau username wajib diisi.']]);
        }

        $user = User::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('email', $login)->orWhere('username', $login))
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => ['Email/username atau password tidak sesuai.']]);
        }

        $deviceName = $data['device_name'] ?? 'WA-BOT Android';
        $user->tokens()->where('name', $deviceName)->delete();
        $token = $user->createToken($deviceName, ['mobile'])->plainTextToken;
        $publicUser = $user->only(['id', 'name', 'email', 'username', 'role']);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $token,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $publicUser,
            'data' => [
                'token' => $token,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $publicUser,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['user' => $request->user()]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }
}
