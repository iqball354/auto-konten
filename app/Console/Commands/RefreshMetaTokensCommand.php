<?php

namespace App\Console\Commands;

use App\Jobs\RefreshTokenJob;
use App\Models\SosialAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshMetaTokensCommand extends Command
{
    protected $signature = 'meta:refresh-tokens';

    protected $description = 'Cek token Meta harian dan jadwalkan refresh untuk token yang akan expired dalam 7 hari';

    public function handle(): int
    {
        Log::info('Scheduler meta:refresh-tokens started.');

        $accounts = SosialAccount::query()
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->whereIn('platform', ['facebook', 'instagram'])
            ->get();

        if ($accounts->isEmpty()) {
            $this->info('Tidak ada akun aktif untuk diperiksa tokennya.');
            Log::info('Scheduler meta:refresh-tokens: no active accounts.');
            return self::SUCCESS;
        }

        $dispatched = 0;

        foreach ($accounts as $account) {
            RefreshTokenJob::dispatch((int) $account->id)->onQueue('heavy');
            $dispatched++;

            Log::info('Scheduler meta:refresh-tokens dispatched refresh job.', [
                'account_id' => $account->id,
                'user_id'    => $account->user_id,
                'platform'   => $account->platform,
            ]);
        }

        $this->info("Selesai. dispatched={$dispatched} job refresh token.");
        Log::info('Scheduler meta:refresh-tokens finished.', [
            'dispatched' => $dispatched,
        ]);

        return self::SUCCESS;
    }
}
