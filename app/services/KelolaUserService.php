<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\SosialAccount;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class KelolaUserService
{
    /**
     * @return array{users: Collection<int, User>, totalUser: int, userActive: int, userNonactive: int, paymentOrders: Collection<int, Order>}
     */
    public function getUserManagementDashboard(): array
    {
        $users = User::with(['socialAccounts', 'subscriptions'])
            ->get();

        $subscriptions = Subscription::whereIn('user_id', $users->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id');

        $users->each(function (User $user) use ($subscriptions): void {
            $latestSubscription = $subscriptions->get($user->id)?->first();

            $effectiveActive = false;
            if (($user->role ?? null) === 'admin') {
                $effectiveActive = true;
            } elseif ($latestSubscription && $latestSubscription->isCurrentlyActive()) {
                $effectiveActive = true;
            }

            if ($user->is_active !== $effectiveActive) {
                $user->forceFill(['is_active' => $effectiveActive])->save();
            }

            $user->setAttribute('effective_active', $effectiveActive);
        });

        $stats = $this->getUserStatistics();

        return [
            'users' => $users,
            'totalUser' => $stats['totalUser'],
            'userActive' => $stats['userActive'],
            'userNonactive' => $stats['userNonactive'],
            'paymentOrders' => $this->getRecentPaymentOrders(),
        ];
    }

    /**
     * @return array{totalUser: int, userActive: int, userNonactive: int}
     */
    public function getUserStatistics(): array
    {
        return [
            'totalUser' => User::query()->count(),
            'userActive' => User::query()->active()->count(),
            'userNonactive' => User::query()->inactive()->count(),
        ];
    }

    /**
     * @return Collection<int, Order>
     */
    public function getRecentPaymentOrders(): Collection
    {
        return Order::with('user:id,name,email')
            ->whereIn('status', ['pending', 'waiting', 'rejected', 'success'])
            ->latest()
            ->limit(50)
            ->get();
    }

    public function updateUser(int $userId, array $payload): User
    {
        try {
            return DB::transaction(function () use ($userId, $payload): User {
                $user = User::query()->findOrFail($userId);

                if ($payload === []) {
                    throw new RuntimeException('Tidak ada data yang dapat diperbarui.');
                }

                $user->update($payload);

                return $user->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Failed to update user.', [
                'user_id' => $userId,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal memperbarui data user.', 0, $e);
        }
    }

    public function verifyGmail(int $userId): void
    {
        try {
            DB::transaction(function () use ($userId): void {
                $user = User::query()->findOrFail($userId);

                if ($user->email_verified_at) {
                    throw new RuntimeException('Email user sudah terverifikasi sebelumnya.');
                }

                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();

                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'gmail_verified',
                    'title' => 'Gmail berhasil diverifikasi',
                    'message' => 'Admin telah memverifikasi Gmail Anda. Akun Anda sekarang sudah terkonfirmasi.',
                    'data' => [
                        'verified_by' => auth()->id(),
                    ],
                ]);
            });

            cache()->forget("user_{$userId}_unread_count");
        } catch (Throwable $e) {
            Log::error('Failed to verify gmail.', [
                'user_id' => $userId,
                'verified_by' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal memverifikasi email user.', 0, $e);
        }
    }

    public function deleteSocialAccount(int $accountId): void
    {
        try {
            DB::transaction(function () use ($accountId): void {
                $account = SosialAccount::query()->find($accountId);

                if (!$account) {
                    throw new RuntimeException('Akun sosial tidak ditemukan.');
                }

                if ($account->deleted_at) {
                    throw new RuntimeException('Akun sosial ini sudah dihapus sebelumnya.');
                }

                if ($account->is_active) {
                    throw new RuntimeException('Akun sosial masih aktif, nonaktifkan dulu sebelum dihapus.');
                }

                $account->update([
                    'deleted_at' => now(),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Failed to delete social account.', [
                'account_id' => $accountId,
                'deleted_by' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal menghapus akun sosial.', 0, $e);
        }
    }
}
