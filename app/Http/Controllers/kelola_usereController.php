<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\KelolaUserService;
use RuntimeException;
use Throwable;

class kelola_usereController extends Controller
{
    public function __construct(private readonly KelolaUserService $kelolaUserService)
    {
    }

    public function ShowUser()
    {
        $data = $this->kelolaUserService->getUserManagementDashboard();

        return view('kelola_user', $data);
    }

    public function UpdateUser(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'is_active' => 'nullable|boolean'
        ]);

        $validated = array_filter($validated, fn($value) => $value !== null);

        try {
            $this->kelolaUserService->updateUser((int) $id, $validated);

            return redirect()->route('kelola_user')->with('success', 'User updated successfully');
        } catch (RuntimeException $e) {
            Log::warning('Update user workflow failed.', [
                'user_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('kelola_user')->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Unexpected error while updating user.', [
                'user_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('kelola_user')->withInput()->with('error', 'Terjadi kesalahan saat mengupdate user.');
        }
    }

    public function VerifyGmail($id)
    {
        try {
            $this->kelolaUserService->verifyGmail((int) $id);

            return redirect()->route('kelola_user')->with('success', 'Email user berhasil diverifikasi.');
        } catch (RuntimeException $e) {
            Log::warning('Verify gmail workflow failed.', [
                'user_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('kelola_user')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Unexpected error while verifying gmail.', [
                'user_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('kelola_user')->with('error', 'Terjadi kesalahan saat verifikasi email user.');
        }
    }

    public function DeleteSocialAccount($id)
    {
        try {
            $this->kelolaUserService->deleteSocialAccount((int) $id);

            return redirect()->route('kelola_user')->with('success', 'Akun sosial nonaktif berhasil dihapus.');
        } catch (RuntimeException $e) {
            Log::warning('Delete social account workflow failed.', [
                'account_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('kelola_user')->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Unexpected error while deleting social account.', [
                'account_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('kelola_user')->with('error', 'Terjadi kesalahan saat menghapus akun sosial.');
        }
    }

}