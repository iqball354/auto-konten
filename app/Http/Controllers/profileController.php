<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ProfileService;
use Throwable;

class profileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService)
    {
    }

    // ================================================================
    // ShowProfile
    // GET /profile
    // Tampilkan profil user + akun sosial terhubung + info subscription
    // ================================================================

    public function ShowProfile()
    {
        try {
            $data = $this->profileService->getProfilePageData((int) Auth::id());

            return view('profile', $data);
        } catch (Throwable $e) {
            Log::error('ShowProfile gagal.', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('profile')->with('error', 'Gagal memuat halaman profil. Silakan coba lagi.');
        }
    }

    // ================================================================
    // UpdateProfile
    // POST /profile/update
    // Update nama, email, dan avatar
    // ================================================================

    public function UpdateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'qris_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        try {
            $this->profileService->updateProfile($request, (int) $user->id);

            return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('UpdateProfile gagal.', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('profile')->with('error', 'Gagal memperbarui profil. Silakan coba lagi.');
        }
    }

    // ================================================================
    // UpdatePassword
    // POST /profile/password
    // Ganti password — verifikasi password lama sebelum update
    // ================================================================

    public function UpdatePassword(Request $request)
    {
        $request->validate([
            'password_lama' => 'required|string',
            'password_baru' => 'required|string|min:8|confirmed', // butuh field password_baru_confirmation
        ]);

        try {
            $this->profileService->updatePassword($request, (int) Auth::id());

            return redirect()->route('profile')->with('success', 'Password berhasil diubah.');
        } catch (Throwable $e) {
            Log::error('UpdatePassword gagal.', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('profile')->with('error', $e->getMessage());
        }
    }
}