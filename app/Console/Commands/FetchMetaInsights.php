<?php

namespace App\Console\Commands;

use App\Models\SosialAccount;
use App\Models\SosialPost;
use App\Models\PostLog;
use App\Services\MetaInsightService;
use App\Services\MovingAverageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchMetaInsights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insight:fetch {--account-id= : Fetch insight for specific account ID}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Fetch post insights from Meta Graph API for active accounts and save to database';

    /**
     * Execute the console command.
     */
    public function handle(MetaInsightService $metaService, MovingAverageService $avgService): int
    {
        Log::info('Scheduler insight:fetch started');
        $this->info('Starting insight fetch from Meta Graph API...');

        try {
            // Fetch active accounts
            $query = SosialAccount::where('is_active', true)
                ->whereNull('deleted_at')
                ->whereIn('platform', ['facebook', 'instagram']);

            if ($this->option('account-id')) {
                $accountId = $this->option('account-id');
                
                // Validate that account exists
                $accountExists = SosialAccount::where('id', $accountId)->exists();
                if (!$accountExists) {
                    $this->error("❌ Account ID {$accountId} not found!");
                    
                    // Show available accounts
                    $availableAccounts = SosialAccount::select('id', 'username', 'platform', 'is_active')
                        ->get();
                    
                    if ($availableAccounts->isNotEmpty()) {
                        $this->info("\n📌 Available accounts:");
                        foreach ($availableAccounts as $acc) {
                            $status = $acc->is_active ? '✓ Active' : '✗ Inactive';
                            $this->line("   ID: {$acc->id}, Username: {$acc->username}, Platform: {$acc->platform} ({$status})");
                        }
                        $this->info("\nUsage: php artisan insight:fetch --account-id=<CORRECT_ID>\n");
                    } else {
                        $this->warn("No accounts found in database!");
                    }
                    
                    return self::FAILURE;
                }
                
                $query->where('id', $accountId);
            }

            $accounts = $query->get();

            if ($accounts->isEmpty()) {
                $this->info('❌ No active accounts found.');
                Log::info('Scheduler insight:fetch: no active accounts found');
                
                // Debug info
                $allAccounts = SosialAccount::count();
                $activeAccounts = SosialAccount::where('is_active', true)->count();
                $notDeletedAccounts = SosialAccount::whereNull('deleted_at')->count();
                
                $this->info("\n📊 Debug Stats:");
                $this->line("   Total accounts: {$allAccounts}");
                $this->line("   Active accounts: {$activeAccounts}");
                $this->line("   Not deleted: {$notDeletedAccounts}");
                
                if ($allAccounts > 0) {
                    $this->info("\n💡 Available accounts:");
                    $allAvailable = SosialAccount::select('id', 'username', 'platform', 'is_active', 'deleted_at')->get();
                    foreach ($allAvailable as $acc) {
                        $status = $acc->is_active ? '✓ Active' : '✗ Inactive';
                        $deleted = $acc->deleted_at ? ' (DELETED)' : '';
                        $this->line("   ID: {$acc->id}, {$acc->username} ({$acc->platform}) {$status}{$deleted}");
                    }
                }
                
                Log::info("Debug - Total: {$allAccounts}, Active: {$activeAccounts}, NotDeleted: {$notDeletedAccounts}");
                
                return self::SUCCESS;
            }

            $totalAccounts = 0;
            $totalPosts = 0;
            $totalInsights = 0;
            $errors = 0;

            foreach ($accounts as $account) {
                try {
                    $totalAccounts++;
                    $this->info("Processing account: {$account->username} ({$account->platform})");

                    // Get access token
                    $pageId = $account->page_id ?: $account->platform_user_id;
                    $accessToken = $account->access_token;

                    if (!$pageId || !$accessToken) {
                        $this->warn("Skipping {$account->username}: missing page_id or token");
                        continue;
                    }

                    // Fetch successful post logs from last 30 days for this account user and platform
                    $postLogs = PostLog::where('status', 'success')
                        ->whereNotNull('platform_post_id')
                        ->where('executed_at', '>=', now()->subDays(30))
                        ->whereHas('post', function ($q) use ($account) {
                            $q->where('user_id', $account->user_id)
                                ->whereJsonContains('platform_targets', strtolower((string) $account->platform));
                        })
                        ->get();

                    if ($postLogs->isEmpty()) {
                        $this->info("  No published posts found for {$account->username} in last 30 days");
                        continue;
                    }

                    $this->info("  Found {$postLogs->count()} published posts to fetch insights for");

                    // Process each post log
                    foreach ($postLogs as $postLog) {
                        try {
                            $platformPostId = $postLog->platform_post_id;

                            // Validate post ID format before fetching insights
                            if (!$this->isValidPostIdFormat($platformPostId, (string) $account->platform)) {
                                $this->warn("  ⚠ Skipping post {$platformPostId}: Invalid post ID format for {$account->platform}");
                                Log::info('FetchMetaInsights: Skipped invalid post ID format', [
                                    'platform_post_id' => $platformPostId,
                                    'platform' => $account->platform,
                                    'reason' => 'Invalid format (likely video/media ID, not post ID)',
                                ]);
                                continue;
                            }

                            // Fetch insights from Meta API
                            $insights = $metaService->getPostInsightSafe($platformPostId, $accessToken, (string) $account->platform);

                            if (empty($insights)) {
                                continue;
                            }

                            // Extract hour and day of week from post execution time
                            $hour = $postLog->executed_at->hour;
                            $dayOfWeek = $postLog->executed_at->dayOfWeek;

                            // Save to database
                            $metaService->savePostInsight(
                                $platformPostId,
                                $account->id,
                                $insights,
                                $hour,
                                $dayOfWeek
                            );

                            $totalInsights++;
                            $totalPosts++;

                        } catch (\Exception $e) {
                            $this->warn("  Error fetching insight for post log {$postLog->id}: {$e->getMessage()}");
                            Log::error("FetchMetaInsights: Error processing post log {$postLog->id}", [
                                'account_id' => $account->id,
                                'exception' => $e
                            ]);
                            $errors++;
                            continue;
                        }
                    }

                    // Clear cache for this account to force recalculation
                    $avgService->clearCache($account->id);
                    $this->info("  ✓ Processed {$totalPosts} posts, saved {$totalInsights} insights");

                } catch (\Exception $e) {
                    $this->error("Error processing account {$account->username}: {$e->getMessage()}");
                    Log::error("FetchMetaInsights: Error processing account {$account->id}", [
                        'exception' => $e
                    ]);
                    $errors++;
                    continue;
                }
            }

            $this->info("\n✓ Insight fetch completed!");
            $this->info("Summary: {$totalAccounts} accounts, {$totalPosts} posts, {$totalInsights} insights saved, {$errors} errors");

            Log::info("Scheduler insight:fetch completed", [
                'total_accounts' => $totalAccounts,
                'total_posts' => $totalPosts,
                'total_insights' => $totalInsights,
                'errors' => $errors,
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Fatal error in insight:fetch: {$e->getMessage()}");
            Log::error("FetchMetaInsights: Fatal error", ['exception' => $e]);
            return self::FAILURE;
        }
    }

    /**
     * Validate if post ID format is correct for the platform.
     * 
     * Facebook: Must contain underscore (PAGE_ID_POST_ID format)
     *           - Videos sometimes return just media ID, which won't work for insights
     * Instagram: Can be any ID (IG_MEDIA_ID format is fine)
     */
    private function isValidPostIdFormat(string $platformPostId, string $platform): bool
    {
        if (strtolower($platform) === 'facebook') {
            // Facebook post insights require PAGE_ID_POST_ID format (contains underscore)
            // If ID is just digits (video/media ID), it won't work
            return str_contains($platformPostId, '_');
        }

        // Instagram media IDs are just numbers, always valid
        return true;
    }
}

