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
            'login' => ['nullable','string'],
            'email' => ['nullable','string'],
            'password' => ['required','string'],
            'device_name' => ['nullable','string','max:100'],
            'expected_role' => ['nullable','in:buyer,merchant,courier,admin'],
        ]);

        $login = trim((string) ($data['login'] ?? $data['email'] ?? ''));
        if ($login === '') throw ValidationException::withMessages(['login' => ['Email atau username wajib diisi.']]);

        $user = User::where('is_active', true)
            ->where(fn ($q) => $q->where('email', $login)->orWhere('username', $login))
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => ['Email/username atau password tidak sesuai.']]);
        }
        if (! empty($data['expected_role']) && $data['expected_role'] !== $user->role && $user->role !== 'admin') {
            throw ValidationException::withMessages(['login' => ['Akun ini tidak memiliki peran yang sesuai dengan aplikasi.']]);
        }

        $deviceName = $data['device_name'] ?? 'IAS Marketplace Android';
        $user->tokens()->where('name', $deviceName)->delete();
        $token = $user->createToken($deviceName, [$user->role])->plainTextToken;
        $publicUser = $user->only(['id','name','username','email','phone','role']);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $token,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $publicUser,
            'data' => ['token'=>$token,'access_token'=>$token,'token_type'=>'Bearer','user'=>$publicUser],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['success'=>true,'data'=>['user'=>$request->user()]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['success'=>true,'message'=>'Logout berhasil.']);
    }
}
