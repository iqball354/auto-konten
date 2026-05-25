<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\SosialAccount;
use App\Services\MetaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(protected int $accountId)
    {
    }

    public function handle(MetaService $meta): void
    {
        $account = SosialAccount::whereKey($this->accountId)
            ->whereIn('platform', ['facebook', 'instagram'])
            ->whereNull('deleted_at')
            ->first();

        if (!$account) {
            Log::info('RefreshTokenJob: account not found, skip.', [
                'account_id' => $this->accountId,
            ]);

            return;
        }

        if ((int) $account->is_active !== 1) {
            Log::info('RefreshTokenJob: account inactive, skip.', [
                'account_id' => $account->id,
                'user_id' => $account->user_id,
            ]);

            return;
        }

        try {
            $status = $meta->checkTokenStatus($account);

            $account->update([
                'token_expires_at' => $status['expires_at'] ?? $account->token_expires_at,
            ]);

            if (!($status['is_valid'] ?? false)) {
                $this->deactivateAndNotify($account, 'Token Meta tidak valid atau izin telah dicabut.');
                return;
            }

            $expiresAt = $status['expires_at'] ?? null;
            if (!$expiresAt) {
                Log::info('RefreshTokenJob: token expiry not available, skip refresh.', [
                    'account_id' => $account->id,
                    'user_id' => $account->user_id,
                ]);

                return;
            }

            $daysLeft = now()->diffInDays($expiresAt, false);
            if ($daysLeft > 7) {
                Log::info('RefreshTokenJob: token still valid, refresh not needed.', [
                    'account_id' => $account->id,
                    'user_id' => $account->user_id,
                    'days_left' => $daysLeft,
                ]);

                return;
            }

            $currentToken = decrypt($account->access_token);
            // Attempt to refresh the token directly
            try {
                $refreshData = $meta->refreshLongLivedToken($currentToken);

                $account->update([
                    'access_token'     => encrypt($refreshData['access_token']),
                    'token_expires_at' => $refreshData['expires_at'],
                    'is_active'        => 1,
                ]);

                Log::info('RefreshTokenJob: token refreshed.', [
                    'account_id' => $account->id,
                    'user_id' => $account->user_id,
                    'platform' => $account->platform,
                ]);
                return;
            } catch (\Throwable $refreshException) {
                Log::warning('RefreshTokenJob: direct refresh failed, attempting re-discovery via getAccounts.', [
                    'account_id' => $account->id,
                    'error' => $refreshException->getMessage(),
                ]);
            }

            // Fallback: try to re-discover Page/IG tokens from the current token
            try {
                $accounts = $meta->getAccounts($currentToken);
                $meta->storeAccounts($account->user_id, $accounts);

                // Find matching account from returned list
                foreach ($accounts as $a) {
                    $matchPlatform = $a['platform'] ?? null;
                    $matchId = $a['platform_user_id'] ?? null;
                    if ($matchPlatform === $account->platform && ($matchId === $account->platform_user_id || ($a['page_id'] ?? null) === $account->page_id)) {
                        // Update stored access_token for this account record
                        SosialAccount::whereKey($account->id)->update([
                            'access_token' => encrypt($a['access_token'] ?? $currentToken),
                            'token_expires_at' => now()->addDays(60),
                            'is_active' => 1,
                        ]);

                        Log::info('RefreshTokenJob: account token re-discovered and updated via getAccounts.', [
                            'account_id' => $account->id,
                            'user_id' => $account->user_id,
                        ]);
                        return;
                    }
                }

                // If re-discovery yielded nothing useful, fall through to deactivation below
            } catch (\Throwable $e) {
                Log::warning('RefreshTokenJob: re-discovery via getAccounts failed.', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // If we reach here, both refresh and re-discovery failed
            throw new \RuntimeException('Refresh and re-discovery both failed for token.');
        } catch (\Throwable $e) {
            Log::warning('RefreshTokenJob: refresh failed, account will be deactivated.', [
                'account_id' => $account->id,
                'user_id' => $account->user_id,
                'platform' => $account->platform,
                'error' => $e->getMessage(),
            ]);

            $this->deactivateAndNotify($account, 'Token Meta gagal direfresh otomatis. Silakan hubungkan ulang akun.');
        }
    }

    private function deactivateAndNotify(SosialAccount $account, string $reason): void
    {
        $account->update([
            'is_active' => 0,
        ]);

        Notification::create([
            'user_id' => $account->user_id,
            'type' => 'token_expired',
            'title' => 'Akun sosial dinonaktifkan',
            'message' => ucfirst((string) $account->platform) . ' account "' . ($account->username ?? '-') . '" dinonaktifkan. ' . $reason,
            'is_read' => 0,
            'data' => [
                'sosial_account_id' => $account->id,
                'platform' => $account->platform,
                'reason' => $reason,
            ],
            'read_at' => null,
        ]);

        Log::info('RefreshTokenJob: account deactivated.', [
            'account_id' => $account->id,
            'user_id' => $account->user_id,
            'reason' => $reason,
        ]);
    }
}