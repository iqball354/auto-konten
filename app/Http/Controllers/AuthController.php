<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class AuthController extends Controller
{
    public function ShowLogin()
    {
        return view('login');
    }
    public function Login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->has('remember');

        if (Auth::attempt($request->only('email', 'password'), $remember)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }
        return back()->with('error', 'Email atau password salah');
    }   

    public function Showregister()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = new \App\Models\User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = hash::make($request->password);
        $user->role = 'user';
        $user->is_active = false;
        $user->save();

        return redirect()->route('login')->with('success', 'Account berhasil dibuat');

    }


    public function Logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function apiLogin(Request $request)
{
    $traceId = $this->makeTraceId();

    try {
        $credentials = $request->only('email','password');

        if (!$token = auth()->attempt($credentials)) {
            return $this->apiError('Email atau password salah', 401, [
                'code' => 'INVALID_CREDENTIALS',
                'trace_id' => $traceId,
            ]);
        }

        return $this->apiSuccess('Login berhasil', [
            'token' => $token,
            'type' => 'Bearer',
            'user' => auth()->user(),
        ]);
    } catch (\Throwable $e) {
        Log::error('apiLogin gagal.', [
            'trace_id' => $traceId,
            'error' => $e->getMessage(),
        ]);

        return $this->apiError('Login gagal diproses.', 500, [
            'code' => 'LOGIN_FAILED',
            'details' => $e->getMessage(),
            'trace_id' => $traceId,
        ]);
    }
}
    public function apiRegister(Request $request)
    {
    $traceId = $this->makeTraceId();

    try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        $user = new \App\Models\User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = 'user';
        $user->is_active = false;
        $user->save();

        return $this->apiSuccess('Register berhasil', [
            'user' => $user,
        ]);
    } catch (\Throwable $e) {
        Log::error('apiRegister gagal.', [
            'trace_id' => $traceId,
            'email' => $request->email,
            'error' => $e->getMessage(),
        ]);

        return $this->apiError('Register gagal diproses.', 500, [
            'code' => 'REGISTER_FAILED',
            'details' => $e->getMessage(),
            'trace_id' => $traceId,
        ]);
    }
    }
    public function apiLogout()
{
    $traceId = $this->makeTraceId();

    try {
        auth()->logout();

        return $this->apiSuccess('Logout berhasil');
    } catch (\Throwable $e) {
        Log::error('apiLogout gagal.', [
            'trace_id' => $traceId,
            'error' => $e->getMessage(),
        ]);

        return $this->apiError('Logout gagal diproses.', 500, [
            'code' => 'LOGOUT_FAILED',
            'details' => $e->getMessage(),
            'trace_id' => $traceId,
        ]);
    }
}

    private function apiSuccess(string $message, array $data = [], int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'error' => null,
        ], $status);
    }

    private function apiError(string $message, int $status = 422, array $error = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'error' => [
                'code' => $error['code'] ?? 'API_ERROR',
                'details' => $error['details'] ?? null,
                'trace_id' => $error['trace_id'] ?? $this->makeTraceId(),
            ],
        ], $status);
    }

    private function makeTraceId(): string
    {
        return (string) Str::uuid();
    }
}
