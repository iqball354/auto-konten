<?php

namespace App\Services;

use App\Models\PostInsight;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MovingAverageService - Calculate moving averages and recommend best posting hours
 * Responsible for:
 * - Calculate Simple Moving Average (SMA)
 * - Calculate Weighted Moving Average (WMA)
 * - Determine best posting hours based on historical data
 * - Determine best posting days of week
 * - Cache recommendations for performance
 */
class MovingAverageService
{
    private const CACHE_PREFIX = 'insight_';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Calculate Simple Moving Average (SMA)
     * 
     * Example: SMA(7) = (D1 + D2 + ... + D7) / 7
     * 
     * @param array $data Array of numeric values
     * @param int $period Moving average period (default: 7)
     * @return array Array of SMA values (padded with nulls for initial values)
     */
    public function calculateSMA(array $data, int $period = 7): array
    {
        if (count($data) < $period) {
            return $data;
        }

        $sma = [];
        
        // Pad initial values with null (not enough data points)
        for ($i = 0; $i < $period - 1; $i++) {
            $sma[] = null;
        }

        // Calculate SMA for each window
        for ($i = $period - 1; $i < count($data); $i++) {
            $window = array_slice($data, $i - $period + 1, $period);
            $sma[] = array_sum($window) / $period;
        }

        return $sma;
    }

    /**
     * Calculate Weighted Moving Average (WMA)
     * 
     * More recent values are weighted more heavily
     * Example: WMA(3) for [10, 20, 30] = (10*1 + 20*2 + 30*3) / (1+2+3) = 23.33
     * 
     * @param array $data Array of numeric values
     * @param int $period Moving average period (default: 7)
     * @return array Array of WMA values (padded with nulls for initial values)
     */
    public function calculateWMA(array $data, int $period = 7): array
    {
        if (count($data) < $period) {
            return $data;
        }

        $wma = [];
        
        // Pad initial values with null
        for ($i = 0; $i < $period - 1; $i++) {
            $wma[] = null;
        }

        // Calculate WMA for each window
        for ($i = $period - 1; $i < count($data); $i++) {
            $window = array_slice($data, $i - $period + 1, $period);
            
            $weightedSum = 0;
            $weightSum = 0;
            
            foreach ($window as $index => $value) {
                $weight = $index + 1; // Weights: 1, 2, 3, ..., period
                $weightedSum += $value * $weight;
                $weightSum += $weight;
            }
            
            $wma[] = $weightedSum / $weightSum;
        }

        return $wma;
    }

    /**
     * Get best posting hours based on historical post insights
     * Combines SMA and WMA analysis for accuracy
     * 
     * @param int $socialAccountId Social account ID
     * @param int $period Analysis period in days (default: 7)
     * @return array Top 5 hours with engagement metrics
     */
    public function getBestPostingHours(int $socialAccountId, int $period = 7): array
    {
        $cacheKey = self::CACHE_PREFIX . "best_hours_{$socialAccountId}_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($socialAccountId, $period) {
            try {
                // Fetch insights for past N days, grouped by hour
                $insights = PostInsight::where('sosial_account_id', $socialAccountId)
                    ->where('recorded_at', '>=', now()->subDays($period))
                    ->whereNotNull('hour')
                    ->get();

                if ($insights->isEmpty()) {
                    Log::info("MovingAverageService::getBestPostingHours - No insights for account {$socialAccountId}");
                    return [];
                }

                // Aggregate by hour
                $hourlyData = [];
                for ($hour = 0; $hour < 24; $hour++) {
                    $hourInsights = $insights->filter(fn ($i) => $i->hour === $hour);
                    
                    if ($hourInsights->isEmpty()) {
                        $hourlyData[$hour] = 0;
                    } else {
                        // Calculate average engagement per hour
                        $totalEngagement = $hourInsights->sum(fn ($i) => 
                            ($i->metric_impressions ?? 0) +
                            ($i->metric_reach ?? 0) +
                            ($i->metric_engaged_users ?? 0) +
                            ($i->metric_clicks ?? 0)
                        );
                        
                        $hourlyData[$hour] = $totalEngagement / $hourInsights->count();
                    }
                }

                // Calculate SMA and WMA
                $dataArray = array_values($hourlyData);
                $sma = $this->calculateSMA($dataArray, min(3, count($dataArray)));
                $wma = $this->calculateWMA($dataArray, min(3, count($dataArray)));

                // Combine SMA + WMA (average both)
                $combined = [];
                foreach (array_keys($hourlyData) as $hour) {
                    $smaValue = $sma[$hour] ?? 0;
                    $wmaValue = $wma[$hour] ?? 0;
                    $combined[$hour] = ($smaValue + $wmaValue) / 2;
                }

                // Sort and get top 5
                arsort($combined);
                $topHours = array_slice($combined, 0, 5, true);

                $result = [];
                $rank = 1;
                foreach ($topHours as $hour => $score) {
                    $result[] = [
                        'hour' => (int) $hour,
                        'score' => round($score, 2),
                        'rank' => $rank++,
                        'raw_value' => $hourlyData[$hour],
                        'sma' => round($smaValue, 2),
                        'wma' => round($wmaValue, 2),
                    ];
                }

                return $result;
            } catch (\Exception $e) {
                Log::error("MovingAverageService::getBestPostingHours Error: {$e->getMessage()}", [
                    'social_account_id' => $socialAccountId,
                    'exception' => $e
                ]);
                return [];
            }
        });
    }

    /**
     * Get best posting days of week based on historical data
     * 
     * @param int $socialAccountId Social account ID
     * @return array Days sorted by engagement (0=Sunday, 6=Saturday)
     */
    public function getBestPostingDays(int $socialAccountId): array
    {
        $cacheKey = self::CACHE_PREFIX . "best_days_{$socialAccountId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($socialAccountId) {
            try {
                // Fetch insights for past 30 days, grouped by day of week
                $insights = PostInsight::where('sosial_account_id', $socialAccountId)
                    ->where('recorded_at', '>=', now()->subDays(30))
                    ->whereNotNull('day_of_week')
                    ->get();

                if ($insights->isEmpty()) {
                    return $this->getDefaultDays();
                }

                // Aggregate by day of week
                $dailyData = [];
                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                for ($day = 0; $day < 7; $day++) {
                    $dayInsights = $insights->filter(fn ($i) => $i->day_of_week === $day);
                    
                    if ($dayInsights->isEmpty()) {
                        $dailyData[$day] = 0;
                    } else {
                        $totalEngagement = $dayInsights->sum(fn ($i) => 
                            ($i->metric_impressions ?? 0) +
                            ($i->metric_reach ?? 0) +
                            ($i->metric_engaged_users ?? 0) +
                            ($i->metric_clicks ?? 0)
                        );
                        
                        $dailyData[$day] = $totalEngagement / $dayInsights->count();
                    }
                }

                // Sort and return top 3 days
                arsort($dailyData);
                $topDays = array_slice($dailyData, 0, 3, true);

                $result = [];
                $rank = 1;
                foreach ($topDays as $day => $score) {
                    $result[] = [
                        'day' => (int) $day,
                        'name' => $dayNames[$day],
                        'score' => round($score, 2),
                        'rank' => $rank++,
                    ];
                }

                return $result;
            } catch (\Exception $e) {
                Log::error("MovingAverageService::getBestPostingDays Error: {$e->getMessage()}", [
                    'social_account_id' => $socialAccountId,
                    'exception' => $e
                ]);
                return $this->getDefaultDays();
            }
        });
    }

    /**
     * Get default recommendation when insufficient data
     * Based on general social media best practices
     * 
     * @return array Default recommendation with top hours and days
     */
    public function getDefaultRecommendation(): array
    {
        return [
            'is_default' => true,
            'message' => 'Rekomendasi berbasis data standar industri. Terus posting untuk hasil lebih akurat!',
            'hours' => [
                ['hour' => 11, 'score' => 85, 'rank' => 1],
                ['hour' => 19, 'score' => 82, 'rank' => 2],
                ['hour' => 7, 'score' => 78, 'rank' => 3],
                ['hour' => 13, 'score' => 75, 'rank' => 4],
                ['hour' => 20, 'score' => 72, 'rank' => 5],
            ],
            'days' => [
                ['day' => 2, 'name' => 'Tuesday', 'score' => 88, 'rank' => 1],
                ['day' => 3, 'name' => 'Wednesday', 'score' => 85, 'rank' => 2],
                ['day' => 4, 'name' => 'Thursday', 'score' => 82, 'rank' => 3],
            ]
        ];
    }

    /**
     * Get default days when insufficient data
     * 
     * @return array Default best days
     */
    private function getDefaultDays(): array
    {
        return [
            ['day' => 2, 'name' => 'Tuesday', 'score' => 88, 'rank' => 1],
            ['day' => 3, 'name' => 'Wednesday', 'score' => 85, 'rank' => 2],
            ['day' => 4, 'name' => 'Thursday', 'score' => 82, 'rank' => 3],
        ];
    }

    /**
     * Clear cached recommendations for a specific account
     * Useful when new insights are saved
     * 
     * @param int $socialAccountId
     * @return void
     */
    public function clearCache(int $socialAccountId): void
    {
        Cache::forget(self::CACHE_PREFIX . "best_hours_{$socialAccountId}_7");
        Cache::forget(self::CACHE_PREFIX . "best_days_{$socialAccountId}");
        Log::info("MovingAverageService::clearCache - Cache cleared for account {$socialAccountId}");
    }
}
