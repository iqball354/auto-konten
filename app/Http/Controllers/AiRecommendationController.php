<?php

namespace App\Http\Controllers;

use App\Models\SosialAccount;
use App\Models\PostInsight;
use App\Services\MovingAverageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * AiRecommendationController - Handle AI-powered posting time recommendations
 */
class AiRecommendationController extends Controller
{
    public function __construct(
        private readonly MovingAverageService $movingAverageService
    ) {
    }

    /**
     * Show the AI recommendation page
     * 
     * GET /ai/recommendation
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $userId = auth()->id();
        
        // Get active social accounts for user
        $accounts = SosialAccount::where('user_id', $userId)
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->get();

        // Get selected account (default to first active account)
        $selectedAccountId = $request->get('account_id') ?? $accounts->first()?->id;
        $selectedAccount = $accounts->firstWhere('id', $selectedAccountId);

        return view('ai.recommendation', [
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
        ]);
    }

    /**
     * Get recommendation data as JSON
     * 
     * GET /ai/recommendation/data
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecommendation(Request $request)
    {
        try {
            $accountId = $request->get('account_id');
            $userId = auth()->id();

            if (!$accountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account ID is required',
                ], 422);
            }

            // Verify account ownership
            $account = SosialAccount::where('id', $accountId)
                ->where('user_id', $userId)
                ->first();

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found',
                ], 404);
            }

            if (!$account->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This account is not active',
                ], 422);
            }

            // Check if we have enough data
            $insightCount = PostInsight::where('sosial_account_id', $accountId)
                ->where('recorded_at', '>=', now()->subDays(30))
                ->count();

            $isDefault = $insightCount < 5; // Less than 5 posts = insufficient data

            if ($isDefault) {
                return response()->json([
                    'success' => true,
                    'is_default' => true,
                    ...$this->movingAverageService->getDefaultRecommendation(),
                ]);
            }

            // Get best hours
            $bestHours = $this->movingAverageService->getBestPostingHours($accountId);
            
            // Get best days
            $bestDays = $this->movingAverageService->getBestPostingDays($accountId);

            if (empty($bestHours) || empty($bestDays)) {
                return response()->json([
                    'success' => true,
                    'is_default' => true,
                    ...$this->movingAverageService->getDefaultRecommendation(),
                ]);
            }

            return response()->json([
                'success' => true,
                'is_default' => false,
                'message' => 'Rekomendasi berdasarkan analisis data akun Anda',
                'hours' => $bestHours,
                'days' => $bestDays,
                'insight_count' => $insightCount,
            ]);

        } catch (\Exception $e) {
            Log::error('AiRecommendationController::getRecommendation Error', [
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching recommendation data',
            ], 500);
        }
    }

    /**
     * Get chart data for visualization
     * 
     * GET /ai/recommendation/chart
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartData(Request $request)
    {
        try {
            $accountId = $request->get('account_id');
            $userId = auth()->id();

            if (!$accountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account ID is required',
                ], 422);
            }

            // Verify account ownership
            $account = SosialAccount::where('id', $accountId)
                ->where('user_id', $userId)
                ->first();

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found',
                ], 404);
            }

            // Get insights for past 7 days, grouped by hour
            $insights = PostInsight::where('sosial_account_id', $accountId)
                ->where('recorded_at', '>=', now()->subDays(7))
                ->whereNotNull('hour')
                ->get();

            if ($insights->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'labels' => [],
                    'rawData' => [],
                    'smaData' => [],
                    'wmaData' => [],
                ]);
            }

            // Aggregate by hour
            $hourlyAggregates = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $hourInsights = $insights->filter(fn ($i) => $i->hour === $hour);
                
                if ($hourInsights->isEmpty()) {
                    $hourlyAggregates[$hour] = 0;
                } else {
                    $totalEngagement = $hourInsights->sum(fn ($i) => 
                        ($i->metric_impressions ?? 0) +
                        ($i->metric_reach ?? 0) +
                        ($i->metric_engaged_users ?? 0) +
                        ($i->metric_clicks ?? 0)
                    );
                    
                    $hourlyAggregates[$hour] = round($totalEngagement / $hourInsights->count());
                }
            }

            // Calculate moving averages
            $dataArray = array_values($hourlyAggregates);
            $sma = $this->movingAverageService->calculateSMA($dataArray, 3);
            $wma = $this->movingAverageService->calculateWMA($dataArray, 3);

            // Format labels (hour format: 00:00, 01:00, etc)
            $labels = [];
            for ($i = 0; $i < 24; $i++) {
                $labels[] = sprintf('%02d:00', $i);
            }

            return response()->json([
                'success' => true,
                'labels' => $labels,
                'rawData' => array_values($hourlyAggregates),
                'smaData' => $sma,
                'wmaData' => $wma,
            ]);

        } catch (\Exception $e) {
            Log::error('AiRecommendationController::getChartData Error', [
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching chart data',
            ], 500);
        }
    }
}
