<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SosialAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AkunTerhubungService
{
    public function getActiveAccounts(int $userId): Collection
    {
        return cache()->remember(
            'user_' . $userId . '_active_accounts',
            now()->addHours(1),
            fn () => SosialAccount::where('user_id', $userId)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function getAccountsForUser(int $userId): Collection
    {
        return SosialAccount::forUser($userId)
            ->notDeleted()
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param array{platform:string, platform_user_id:string, username:?string, access_token:string} $data
     */
    public function addAccount(int $userId, array $data): SosialAccount
    {
        try {
            return DB::transaction(function () use ($userId, $data): SosialAccount {
                $exists = SosialAccount::forUserPlatformUserId(
                    $userId,
                    $data['platform'],
                    $data['platform_user_id']
                )
                    ->exists();

                if ($exists) {
                    throw new \RuntimeException('Akun ini sudah pernah dihubungkan.');
                }

                $account = SosialAccount::create([
                    'user_id'          => $userId,
                    'platform'         => $data['platform'],
                    'platform_user_id' => $data['platform_user_id'],
                    'username'         => $data['username'],
                    'access_token'     => encrypt($data['access_token']),
                    'token_expires_at' => now()->addDays(60),
                    'is_active'        => 1,
                    'deleted_at'       => null,
                ]);

                Notification::create([
                    'user_id' => $userId,
                    'type'    => 'account_connected',
                    'title'   => 'Akun berhasil terhubung',
                    'message' => ucfirst($data['platform']) . ' account "' . $account->display_name . '" sudah terhubung dan aktif.',
                    'data'    => [
                        'platform' => $data['platform'],
                        'name'     => $account->display_name,
                    ],
                ]);

                return $account;
            });
        } catch (Throwable $e) {
            if ($e instanceof \RuntimeException && $e->getMessage() === 'Akun ini sudah pernah dihubungkan.') {
                throw $e;
            }

            Log::error('Tambah akun terhubung gagal di service.', [
                'user_id' => $userId,
                'platform' => $data['platform'] ?? null,
                'platform_user_id' => $data['platform_user_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Gagal menambahkan akun. Silakan coba lagi.');
        }
    }

    public function deleteAccount(int $userId, int $accountId): string
    {
        try {
            return DB::transaction(function () use ($userId, $accountId): string {
                $account = SosialAccount::forUser($userId)
                    ->notDeleted()
                    ->where('id', $accountId)
                    ->firstOrFail();

                $displayName = $account->display_name;

                $account->update([
                    'is_active'  => 0,
                    'deleted_at' => now(),
                ]);

                return $displayName;
            });
        } catch (Throwable $e) {
            Log::error('Hapus akun terhubung gagal di service.', [
                'user_id' => $userId,
                'akun_id' => $accountId,
                'error'   => $e->getMessage(),
            ]);

            throw new \RuntimeException('Gagal memutuskan akun.');
        }
    }

    /**
     * @return array{status:array<string, mixed>, account_name:string}
     */
    public function checkStatus(int $userId, int $accountId, MetaService $meta): array
    {
        try {
            $account = SosialAccount::forUser($userId)
                ->notDeleted()
                ->where('id', $accountId)
                ->firstOrFail();

            $status = $meta->checkTokenStatus($account);

            DB::transaction(function () use ($account, $status): void {
                $account->update([
                    'is_active'        => !empty($status['is_valid']) ? 1 : 0,
                    'token_expires_at' => $status['expires_at'] ?? $account->token_expires_at,
                ]);
            });

            return [
                'status' => $status,
                'account_name' => $account->display_name,
            ];
        } catch (Throwable $e) {
            Log::error('Cek status akun terhubung gagal di service.', [
                'user_id' => $userId,
                'akun_id' => $accountId,
                'error'   => $e->getMessage(),
            ]);

            throw new \RuntimeException('Gagal cek status token.');
        }
    }
}