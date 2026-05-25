<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use App\Models\PostInsight;

/**
 * MetaInsightService - Fetch and manage Meta Graph API insights
 * Responsible for:
 * - Fetching fans online per hour from Meta Graph API v19.0
 * - Fetching post insights (impressions, reach, engaged users, clicks)
 * - Saving insights to database
 */
class MetaInsightService
{
    private const API_VERSION = 'v19.0';
    private const REQUEST_TIMEOUT = 30;

    /**
     * Get fans online data per hour from Meta Graph API
     * 
     * @param string $pageId Meta page ID
     * @param string $accessToken User access token
     * @return array Array of hours with fan count data
     * @throws \Exception
     */
    public function getFansOnlinePerHour(string $pageId, string $accessToken): array
    {
        try {
            // FIXED: decrypt token before sending to Meta API
            $accessToken = $this->decryptToken($accessToken);

            $url = 'https://graph.facebook.com/' . self::API_VERSION . "/{$this->getMetaPageId($pageId)}/insights";
            
            $response = Http::timeout(self::REQUEST_TIMEOUT)
                ->withOptions(['verify' => config('services.meta.verify_ssl', true)])
                ->get($url, [
                    'metric' => 'page_fans_online_per_day',
                    'period' => 'day',
                    'access_token' => $accessToken,
                ])
                ->throw();

            $data = $response->json();

            if (!$data || !isset($data['data'])) {
                Log::warning("MetaInsightService::getFansOnlinePerHour - No data returned for page {$pageId}");
                return [];
            }

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error("MetaInsightService::getFansOnlinePerHour Error: {$e->getMessage()}", [
                'page_id' => $pageId,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Get post insights from Meta Graph API
     * Fetches: post_impressions, post_reach, post_engaged_users, post_clicks
     * 
     * @param string $postId Meta post ID
     * @param string $accessToken User access token
     * @return array Insights data
     * @throws \Exception
     */
    public function getPostInsight(string $postId, string $accessToken, string $platform = 'facebook'): array
    {
        try {
            // FIXED: decrypt token before sending to Meta API
            $accessToken = $this->decryptToken($accessToken);

            $metricConfig = $this->getValidMetrics($platform, $postId);
            $url = $metricConfig['url'];
            $metrics = $metricConfig['metrics'];

            $response = Http::timeout(self::REQUEST_TIMEOUT)
                ->withOptions(['verify' => config('services.meta.verify_ssl', true)])
                ->get($url, [
                    'metric' => implode(',', $metrics),
                    'access_token' => $accessToken,
                ])
                ->throw();

            $data = $response->json();

            if (!$data || !isset($data['data'])) {
                Log::warning("MetaInsightService::getPostInsight - No data returned for post {$postId}");
                return [];
            }

            // Transform response into key-value format
            $insights = [];
            foreach ($data['data'] ?? [] as $metric) {
                $insights[$metric['name']] = $metric['values'][0]['value'] ?? 0;
            }

            return $insights;
        } catch (\Exception $e) {
            Log::error("MetaInsightService::getPostInsight Error: {$e->getMessage()}", [
                'post_id' => $postId,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Safely get post insights with fallback metric strategy.
     * If main metrics fail with Meta error code 100 (invalid metric),
     * retry with alternative platform metrics. If all fail, return empty array.
     *
     * @param string $postId Meta post ID
     * @param string $accessToken User access token
     * @param string $platform facebook|instagram
     * @return array Insights data or empty array on failure
     */
    public function getPostInsightSafe(string $postId, string $accessToken, string $platform = 'facebook'): array
    {
        try {
            // FIXED: decrypt token before sending to Meta API
            $decryptedToken = $this->decryptToken($accessToken);
            Log::debug('MetaInsightService::getPostInsightSafe - Token decryption', [
                'post_id' => $postId,
                'token_length' => strlen($decryptedToken),
                'is_different' => $decryptedToken !== $accessToken,
            ]);

            $attempts = $this->buildMetricAttempts($platform, $postId);

            foreach ($attempts as $index => $attempt) {
                try {
                    $metricsString = implode(',', $attempt['metrics']);
                    Log::info('MetaInsightService::getPostInsightSafe - Attempting metrics fetch', [
                        'post_id' => $postId,
                        'platform' => $attempt['platform'],
                        'attempt' => $index + 1,
                        'url' => $attempt['url'],
                        'metrics_string' => $metricsString,
                        'metrics_count' => count($attempt['metrics']),
                    ]);

                    $response = Http::timeout(self::REQUEST_TIMEOUT)
                        ->withOptions(['verify' => config('services.meta.verify_ssl', true)])
                        ->get($attempt['url'], [
                            'metric' => $metricsString,
                            'access_token' => $decryptedToken,
                        ]);

                    Log::debug('MetaInsightService::getPostInsightSafe - HTTP response received', [
                        'post_id' => $postId,
                        'attempt' => $index + 1,
                        'status_code' => $response->status(),
                        'response_size' => strlen($response->body()),
                    ]);

                    $response->throw();

                    $data = $response->json();
                    Log::debug('MetaInsightService::getPostInsightSafe - Response parsed', [
                        'post_id' => $postId,
                        'attempt' => $index + 1,
                        'has_data_key' => isset($data['data']),
                        'data_count' => count($data['data'] ?? []),
                        'response_keys' => array_keys($data),
                    ]);

                    if (!$data || !isset($data['data'])) {
                        Log::warning('MetaInsightService::getPostInsightSafe - Empty data on attempt', [
                            'post_id' => $postId,
                            'platform' => $attempt['platform'],
                            'attempt' => $index + 1,
                            'full_response' => json_encode($data),
                        ]);
                        continue;
                    }

                    $insights = [];
                    foreach ($data['data'] ?? [] as $metric) {
                        $insights[$metric['name']] = $metric['values'][0]['value'] ?? 0;
                    }

                    Log::info('MetaInsightService::getPostInsightSafe - Successfully parsed insights', [
                        'post_id' => $postId,
                        'platform' => $attempt['platform'],
                        'attempt' => $index + 1,
                        'insights_count' => count($insights),
                        'insights' => $insights,
                    ]);

                    return $insights;
                } catch (\Exception $e) {
                    Log::warning('MetaInsightService::getPostInsightSafe - Attempt failed', [
                        'post_id' => $postId,
                        'platform' => $attempt['platform'],
                        'attempt' => $index + 1,
                        'exception_class' => get_class($e),
                        'message' => $e->getMessage(),
                        'is_invalid_metric_error' => $this->isInvalidMetricError($e),
                    ]);

                    if ($e instanceof RequestException && $e->response) {
                        Log::debug('MetaInsightService::getPostInsightSafe - HTTP error details', [
                            'post_id' => $postId,
                            'attempt' => $index + 1,
                            'status_code' => $e->response->status(),
                            'response_body' => $e->response->body(),
                            'response_json' => $e->response->json(),
                        ]);
                    }

                    if (!$this->isInvalidMetricError($e)) {
                        Log::error('MetaInsightService::getPostInsightSafe - Non-recoverable error, stopping retries', [
                            'post_id' => $postId,
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        return [];
                    }
                }
            }

            Log::warning('MetaInsightService::getPostInsightSafe - All attempts exhausted', [
                'post_id' => $postId,
                'platform' => $platform,
                'attempts_count' => count($attempts),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('MetaInsightService::getPostInsightSafe - Unexpected failure', [
                'post_id' => $postId,
                'platform' => $platform,
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Save post insights to database
     * 
     * @param string $postId Meta post ID
     * @param int $socialAccountId Social account ID
     * @param array $data Insight data to save
     * @param int|null $hour Hour of the day (0-23) when post was published
     * @param int|null $dayOfWeek Day of week (0-6, 0=Sunday)
     * @return PostInsight Saved model instance
     */
    public function savePostInsight(
        string $postId,
        int $socialAccountId,
        array $data,
        ?int $hour = null,
        ?int $dayOfWeek = null
    ): PostInsight {
        try {
            $metricAliases = [
                'metric_impressions' => ['post_impressions', 'impressions'],
                'metric_reach' => ['post_reach', 'post_impressions_unique', 'reach'],
                'metric_engaged_users' => ['post_engaged_users', 'engagement'],
                'metric_clicks' => ['post_clicks', 'saved'],
            ];

            $mappedMetrics = [];
            foreach ($metricAliases as $targetKey => $sourceKeys) {
                $mappedMetrics[$targetKey] = 0;

                foreach ($sourceKeys as $sourceKey) {
                    if (array_key_exists($sourceKey, $data)) {
                        $mappedMetrics[$targetKey] = (int) ($data[$sourceKey] ?? 0);
                        break;
                    }
                }
            }

            $insight = PostInsight::updateOrCreate(
                [
                    'post_id' => $postId,
                    'sosial_account_id' => $socialAccountId,
                ],
                [
                    'metric_impressions' => $mappedMetrics['metric_impressions'],
                    'metric_reach' => $mappedMetrics['metric_reach'],
                    'metric_engaged_users' => $mappedMetrics['metric_engaged_users'],
                    'metric_clicks' => $mappedMetrics['metric_clicks'],
                    'hour' => $hour,
                    'day_of_week' => $dayOfWeek,
                    'recorded_at' => now(),
                ]
            );

            Log::info("MetaInsightService::savePostInsight - Saved insight for post {$postId}");

            return $insight;
        } catch (\Exception $e) {
            Log::error("MetaInsightService::savePostInsight Error: {$e->getMessage()}", [
                'post_id' => $postId,
                'social_account_id' => $socialAccountId,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Resolve valid metrics and endpoint based on platform.
     * Defaults to facebook metrics when platform is unknown.
     *
     * @param string $platform facebook|instagram
     * @param string $postId Meta post ID
     * @return array{url:string,metrics:array<int,string>}
     */
    public function getValidMetrics(string $platform, string $postId): array
    {
        try {
            $normalized = strtolower(trim($platform));
            $url = 'https://graph.facebook.com/' . self::API_VERSION . "/{$postId}/insights";

            if ($normalized === 'instagram') {
                return [
                    'url' => $url,
                    'metrics' => ['impressions', 'reach', 'engagement', 'saved'],
                ];
            }

            return [
                'url' => $url,
                'metrics' => ['post_impressions', 'post_reach', 'post_engaged_users', 'post_clicks'],
            ];
        } catch (\Exception $e) {
            Log::warning('MetaInsightService::getValidMetrics - Failed to resolve metrics, using facebook default', [
                'platform' => $platform,
                'post_id' => $postId,
                'message' => $e->getMessage(),
            ]);

            return [
                'url' => 'https://graph.facebook.com/' . self::API_VERSION . "/{$postId}/insights",
                'metrics' => ['post_impressions', 'post_reach', 'post_engaged_users', 'post_clicks'],
            ];
        }
    }

    /**
     * Build ordered metric attempts for safe insight fetching.
     *
     * @param string $platform facebook|instagram
     * @param string $postId Meta post ID
     * @return array<int, array{platform:string,url:string,metrics:array<int,string>}>
     */
    private function buildMetricAttempts(string $platform, string $postId): array
    {
        $primary = $this->getValidMetrics($platform, $postId);
        $url = $primary['url'];
        $normalized = strtolower(trim($platform));

        if ($normalized === 'instagram') {
            return [
                [
                    'platform' => 'instagram',
                    'url' => $url,
                    'metrics' => ['impressions', 'reach', 'engagement'],
                ],
                [
                    'platform' => 'instagram',
                    'url' => $url,
                    'metrics' => ['impressions'],
                ],
            ];
        }

        // Facebook attempts with fallbacks for different post types
        return [
            // Primary: standard post insights
            [
                'platform' => 'facebook',
                'url' => $url,
                'metrics' => ['post_impressions', 'post_reach', 'post_engaged_users', 'post_clicks'],
            ],
            // Fallback 1: alternative names
            [
                'platform' => 'facebook',
                'url' => $url,
                'metrics' => ['post_impressions', 'post_impressions_unique', 'post_engaged_users'],
            ],
            // Fallback 2: simple metrics
            [
                'platform' => 'facebook',
                'url' => $url,
                'metrics' => ['post_impressions', 'post_reach'],
            ],
            // Fallback 3: just impressions
            [
                'platform' => 'facebook',
                'url' => $url,
                'metrics' => ['post_impressions'],
            ],
        ];
    }

    /**
     * Decrypt access token safely.
     * If token is already plain text, return it as-is.
     *
     * @param string $accessToken Encrypted or plain token
     * @return string Decrypted token or original token
     */
    private function decryptToken(string $accessToken): string
    {
        try {
            $decrypted = Crypt::decryptString($accessToken);
            return $this->normalizeTokenString($decrypted);
        } catch (\Exception $e) {
            Log::warning('MetaInsightService::decryptToken - Failed to decrypt token, using raw token', [
                'message' => $e->getMessage(),
            ]);

            return $this->normalizeTokenString($accessToken);
        }
    }

    /**
     * Normalize historical token formats (serialized/json/wrapped string)
     * into plain OAuth token expected by Meta API.
     */
    private function normalizeTokenString(string $token): string
    {
        $value = trim($token);

        // Handle serialized string format: s:205:"EAAB...";
        if (preg_match('/^(s|a|O|i|b|d):/', $value) === 1) {
            try {
                $unserialized = @unserialize($value);
                if (is_string($unserialized)) {
                    $value = trim($unserialized);
                }
            } catch (\Throwable $e) {
                Log::warning('MetaInsightService::normalizeTokenString - Failed unserialize', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Handle JSON wrappers: {"access_token":"EAAB..."}
        if (str_starts_with($value, '{') || str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($decoded)) {
                    if (!empty($decoded['access_token']) && is_string($decoded['access_token'])) {
                        $value = trim($decoded['access_token']);
                    } elseif (!empty($decoded[0]) && is_string($decoded[0])) {
                        $value = trim($decoded[0]);
                    }
                }
            }
        }

        return trim($value, " \t\n\r\0\x0B\"'");
    }

    /**
     * Check whether exception indicates invalid metric error from Meta API.
     *
     * @param \Exception $e
     * @return bool
     */
    private function isInvalidMetricError(\Exception $e): bool
    {
        try {
            if ($e instanceof RequestException && $e->response) {
                $error = $e->response->json('error');
                return (int) ($error['code'] ?? 0) === 100;
            }

            return false;
        } catch (\Exception $innerException) {
            Log::warning('MetaInsightService::isInvalidMetricError - Failed to parse error response', [
                'message' => $innerException->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Helper: Transform Meta page ID format
     * Instagram API uses numeric page IDs, Facebook uses FB- prefix format
     * 
     * @param string $pageId
     * @return string Formatted page ID
     */
    private function getMetaPageId(string $pageId): string
    {
        // If already numeric, return as-is
        if (is_numeric($pageId)) {
            return $pageId;
        }

        // Remove FB- prefix if present
        return str_replace('FB-', '', $pageId);
    }
}
