<?php

namespace App\Console\Commands;

use App\Models\SosialAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugInsightFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insight:debug';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Debug insight:fetch query issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line("\n=== DEBUGGING INSIGHT:FETCH ===\n");

        // 1. Check raw database data
        $this->info("1️⃣  Raw Database Query:");
        $rawData = DB::select("SELECT id, username, platform, is_active, deleted_at FROM sosial_accounts");
        foreach ($rawData as $account) {
            $this->line("  ID: {$account->id}, Username: {$account->username}, Platform: {$account->platform}, is_active: {$account->is_active} (type: " . gettype($account->is_active) . "), deleted_at: {$account->deleted_at}");
        }

        // 2. Count all accounts
        $this->info("\n2️⃣  Counts:");
        $totalCount = SosialAccount::count();
        $this->line("  Total accounts: {$totalCount}");

        // 3. Count with where is_active = true
        $activeTrue = SosialAccount::where('is_active', true)->count();
        $this->line("  where(is_active, true): {$activeTrue}");

        // 4. Count with where is_active = 1
        $activeOne = SosialAccount::where('is_active', 1)->count();
        $this->line("  where(is_active, 1): {$activeOne}");

        // 5. Count with whereRaw
        $activeRaw = SosialAccount::whereRaw('is_active = 1')->count();
        $this->line("  whereRaw('is_active = 1'): {$activeRaw}");

        // 6. Count not deleted
        $notDeletedCount = SosialAccount::whereNull('deleted_at')->count();
        $this->line("  whereNull(deleted_at): {$notDeletedCount}");

        // 7. Try the full query from command
        $this->info("\n3️⃣  Full Query (like in command):");
        $fullQuery = SosialAccount::where('is_active', true)
            ->whereNull('deleted_at')
            ->whereIn('platform', ['facebook', 'instagram']);
        $fullCount = $fullQuery->count();
        $this->line("  where(is_active, true)->whereNull(deleted_at)->whereIn(platform, [...]): {$fullCount}");

        // 8. Show the query SQL
        $this->info("\n4️⃣  SQL Query:");
        $sql = $fullQuery->toSql();
        $bindings = $fullQuery->getBindings();
        $this->line("  SQL: {$sql}");
        $this->line("  Bindings: " . json_encode($bindings));

        // 9. Try with account ID
        if ($fullCount > 0) {
            $this->info("\n5️⃣  With account ID = 1:");
            $withIdCount = SosialAccount::where('is_active', true)
                ->whereNull('deleted_at')
                ->whereIn('platform', ['facebook', 'instagram'])
                ->where('id', 1)
                ->count();
            $this->line("  Count with id=1: {$withIdCount}");

            // Get the actual account data
            $account = SosialAccount::find(1);
            if ($account) {
                $this->line("\n  Account ID 1 data:");
                $this->line("    username: {$account->username}");
                $this->line("    platform: {$account->platform}");
                $this->line("    is_active: {$account->is_active} (type: " . gettype($account->is_active) . ")");
                $this->line("    deleted_at: {$account->deleted_at}");
                $this->line("    page_id: {$account->page_id}");
                $this->line("    platform_user_id: {$account->platform_user_id}");
                $this->line("    access_token: " . (empty($account->access_token) ? "EMPTY" : "SET"));
            } else {
                $this->warn("  Account ID 1 NOT FOUND!");
            }
        }

        $this->info("\n=== END DEBUG ===\n");
        return self::SUCCESS;
    }
}
