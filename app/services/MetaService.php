<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\SosialAccount;

class MetaService
{
    private string $appId;
    private string $appSecret;
    private string $redirectUri;
    private string $apiVersion = 'v25.0';

    public function __construct()
    {
        $this->appId       = config('services.meta.app_id');
        $this->appSecret   = config('services.meta.app_secret');
        $this->redirectUri = config('services.meta.redirect_uri');
    }

    // ================================================================
    // getLoginUrl — URL OAuth Meta (full/basic scope)
    // ================================================================

    public function getLoginUrl(bool $basicMode = false, ?string $state = null, ?string $redirectUri = null, bool $forceReauth = false): string
    {
        $fullScopes = [
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_posts',
            'pages_manage_metadata',
            'instagram_basic',
            'instagram_content_publish',
            'business_management',
        ];

        $basicScopes = [
            'pages_show_list',
            'pages_read_engagement',
            'instagram_basic',
            'business_management',
        ];

        $scope = implode(',', $basicMode ? $basicScopes : $fullScopes);

        $targetRedirectUri = $redirectUri ?: $this->redirectUri;

        $query = [
            'client_id'     => $this->appId,
            'redirect_uri'  => $targetRedirectUri,
            'scope'         => $scope,
            'response_type' => 'code',
            'state'         => $state,
        ];

        if ($forceReauth) {
            $query['auth_type'] = 'reauthenticate';
            $query['auth_nonce'] = Str::random(32);
        }

        return 'https://www.facebook.com/' . $this->apiVersion . '/dialog/oauth?' . http_build_query($query);
    }

    // ================================================================
    // getLongLivedToken — code → short-lived → long-lived
    // ================================================================

    public function exchangeCodeToShortLivedToken(string $code, ?string $redirectUri = null): string
    {
        $targetRedirectUri = $redirectUri ?: $this->redirectUri;

        // Step 1 — short-lived token
        $shortRes = Http::get("https://graph.facebook.com/{$this->apiVersion}/oauth/access_token", [
            'client_id'     => $this->appId,
            'redirect_uri'  => $targetRedirectUri,
            'client_secret' => $this->appSecret,
            'code'          => $code,
        ]);

        if ($shortRes->failed() || !$shortRes->json('access_token')) {
            throw new \Exception(
                'Gagal mendapatkan short-lived token: ' . ($shortRes->json('error.message') ?? $shortRes->body())
            );
        }

        return $shortRes->json('access_token');
    }

    public function exchangeShortToLongLivedToken(string $shortToken): string
    {

        // Step 2 — tukar ke long-lived token
        $longRes = Http::get("https://graph.facebook.com/{$this->apiVersion}/oauth/access_token", [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $this->appId,
            'client_secret'     => $this->appSecret,
            'fb_exchange_token' => $shortToken,
        ]);

        if ($longRes->failed() || !$longRes->json('access_token')) {
            throw new \Exception(
                'Gagal menukar ke long-lived token: ' . ($longRes->json('error.message') ?? $longRes->body())
            );
        }

        return $longRes->json('access_token');
    }

    public function getLongLivedToken(string $code, ?string $redirectUri = null): string
    {
        $shortToken = $this->exchangeCodeToShortLivedToken($code, $redirectUri);
        return $this->exchangeShortToLongLivedToken($shortToken);
    }

    // ================================================================
    // refreshLongLivedToken — refresh token long-lived yang akan expired
    // ================================================================

    public function refreshLongLivedToken(string $currentToken): array
    {
        $refreshRes = Http::get("https://graph.facebook.com/{$this->apiVersion}/oauth/access_token", [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $this->appId,
            'client_secret'     => $this->appSecret,
            'fb_exchange_token' => $currentToken,
        ]);

        if ($refreshRes->failed() || !$refreshRes->json('access_token')) {
            throw new \Exception(
                'Gagal refresh long-lived token: ' . ($refreshRes->json('error.message') ?? $refreshRes->body())
            );
        }

        $newToken = (string) $refreshRes->json('access_token');
        $expiresIn = (int) $refreshRes->json('expires_in', 60 * 24 * 60 * 60);

        return [
            'access_token' => $newToken,
            'expires_in'   => $expiresIn,
            'expires_at'   => now()->addSeconds($expiresIn)->toDateTimeString(),
        ];
    }

    // ================================================================
    // getFacebookPages — Ambil daftar Page dari user token
    // ================================================================

    public function getFacebookPages(string $longToken): array
    {
        $fields = 'id,name,access_token,tasks,instagram_business_account{id,username},connected_instagram_account{id,username}';
        $rawPages = [];
        $errors = [];
        $profileName = null;
        $profileId = null;

        // Primary endpoint.
        $rawPages = $this->fetchGraphCollection(
            "https://graph.facebook.com/{$this->apiVersion}/me/accounts",
            [
                'fields'       => $fields,
                'access_token' => $longToken,
                'limit'        => 200,
            ],
            '/me/accounts',
            $errors
        );

        // Alternate format used in some account setups.
        if (empty($rawPages)) {
            $meWithAccountsRes = Http::get("https://graph.facebook.com/{$this->apiVersion}/me", [
                'fields'       => 'id,name,accounts{id,name,access_token,tasks,instagram_business_account{id,username},connected_instagram_account{id,username}}',
                'access_token' => $longToken,
            ]);

            if ($meWithAccountsRes->failed()) {
                $errors[] = '/me?fields=accounts: ' . ($meWithAccountsRes->json('error.message') ?? $meWithAccountsRes->body());
            } else {
                $rawPages = (array) $meWithAccountsRes->json('accounts.data', []);
            }
        }

        // Fallback endpoint for some portfolio/business setups.
        if (empty($rawPages)) {
            $profileRes = Http::get("https://graph.facebook.com/{$this->apiVersion}/me", [
                'fields'       => 'id,name',
                'access_token' => $longToken,
            ]);

            if ($profileRes->failed() || empty($profileRes->json('id'))) {
                $errors[] = '/me: ' . ($profileRes->json('error.message') ?? $profileRes->body());
            } else {
                $fbUserId = (string) $profileRes->json('id');
                $profileId = $fbUserId;
                $profileName = (string) ($profileRes->json('name') ?? '');

                $rawPages = $this->fetchGraphCollection(
                    "https://graph.facebook.com/{$this->apiVersion}/{$fbUserId}/accounts",
                    [
                        'fields'       => $fields,
                        'access_token' => $longToken,
                        'limit'        => 200,
                    ],
                    '/{user-id}/accounts',
                    $errors
                );
            }
        }

        if (empty($rawPages)) {
            $rawPages = $this->getPagesFromBusinessPortfolios($longToken, $errors);
        }

        if (empty($rawPages)) {
            $scopeInfo = $this->describeTokenScopes($longToken);
            $scopeText = empty($scopeInfo['granted'])
                ? '-'
                : implode(', ', $scopeInfo['granted']);

            if (!empty($scopeInfo['missing'])) {
                $scopeText .= ' | missing: ' . implode(', ', $scopeInfo['missing']);
            }

            if (empty($profileId) || empty($profileName)) {
                $profileFallback = Http::get("https://graph.facebook.com/{$this->apiVersion}/me", [
                    'fields'       => 'id,name',
                    'access_token' => $longToken,
                ]);

                if (!$profileFallback->failed()) {
                    $profileId = $profileId ?: $profileFallback->json('id');
                    $profileName = $profileName ?: $profileFallback->json('name');
                }
            }

            $accountText = trim((string) ($profileName ?: ''));
            if ($profileId) {
                $accountText .= ($accountText ? ' ' : '') . '(id: ' . $profileId . ')';
            }

            $suffix = empty($errors)
                ? ''
                : ' Detail API: ' . implode(' | ', $errors);

            throw new \Exception(
                'Tidak ada Facebook Page yang ditemukan untuk akun ini. Akun terbaca: ' . ($accountText ?: '-') . '. Scope token: ' . $scopeText . '. Pastikan akun login punya akses penuh ke Page (bukan hanya akses business asset).' . $suffix
            );
        }

        $pages = collect($rawPages)
            ->filter(fn ($page) => !empty($page['id']) && !empty($page['access_token']))
            ->map(function ($page) {
                $igAccount = $page['instagram_business_account'] ?? ($page['connected_instagram_account'] ?? null);

                return [
                    'id'           => (string) $page['id'],
                    'name'         => (string) ($page['name'] ?? 'Untitled Page'),
                    'access_token' => (string) $page['access_token'],
                    'instagram_business_account' => [
                        'id'       => $igAccount['id'] ?? null,
                        'username' => $igAccount['username'] ?? null,
                    ],
                ];
            })
            ->values()
            ->all();

        if (empty($pages)) {
            $pageNames = collect($rawPages)
                ->pluck('name')
                ->filter()
                ->implode(', ');

            Log::warning('Meta getFacebookPages: page ditemukan tanpa access_token.', [
                'page_count_raw' => count($rawPages),
                'page_names'     => $pageNames,
            ]);

            throw new \Exception(
                'Page ditemukan tetapi access token Page tidak tersedia. Biasanya karena izin akun ke Page belum Full Control atau app belum mendapat permission page yang diperlukan. Page terdeteksi: ' . ($pageNames ?: '-')
            );
        }

        return $pages;
    }

    private function getPagesFromBusinessPortfolios(string $longToken, array &$errors): array
    {
        $businesses = $this->fetchGraphCollection(
            "https://graph.facebook.com/{$this->apiVersion}/me/businesses",
            [
                'fields'       => 'id,name',
                'access_token' => $longToken,
                'limit'        => 200,
            ],
            '/me/businesses',
            $errors
        );

        if (empty($businesses)) {
            $errors[] = '/me/businesses: tidak ada bisnis yang terhubung ke akun ini.';
            return [];
        }

        $pagesById = [];
        foreach ($businesses as $biz) {
            $bizId = (string) ($biz['id'] ?? '');
            if ($bizId === '') {
                continue;
            }

            foreach (['owned_pages', 'client_pages'] as $edge) {
                $edgeItems = $this->fetchGraphCollection(
                    "https://graph.facebook.com/{$this->apiVersion}/{$bizId}/{$edge}",
                    [
                        'fields'       => 'id,name,instagram_business_account{id,username},connected_instagram_account{id,username}',
                        'access_token' => $longToken,
                        'limit'        => 200,
                    ],
                    "/{$bizId}/{$edge}",
                    $errors
                );

                foreach ($edgeItems as $item) {
                    $pageId = (string) ($item['id'] ?? '');
                    if ($pageId === '') {
                        continue;
                    }

                    if (!isset($pagesById[$pageId])) {
                        $pagesById[$pageId] = [
                            'id' => $pageId,
                            'name' => (string) ($item['name'] ?? 'Untitled Page'),
                        ];
                    }
                }
            }
        }

        if (empty($pagesById)) {
            return [];
        }

        $result = [];
        foreach ($pagesById as $pageId => $seed) {
            $pageRes = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$pageId}", [
                'fields'       => 'id,name,access_token,tasks,instagram_business_account{id,username},connected_instagram_account{id,username}',
                'access_token' => $longToken,
            ]);

            if ($pageRes->failed()) {
                $errors[] = "/{$pageId}: " . ($pageRes->json('error.message') ?? $pageRes->body());
                $result[] = $seed;
                continue;
            }

            $result[] = [
                'id' => (string) ($pageRes->json('id') ?? $seed['id']),
                'name' => (string) ($pageRes->json('name') ?? $seed['name']),
                'access_token' => $pageRes->json('access_token'),
                'tasks' => $pageRes->json('tasks', []),
                'instagram_business_account' => $pageRes->json('instagram_business_account') ?: $pageRes->json('connected_instagram_account'),
            ];
        }

        return $result;
    }

    private function fetchGraphCollection(string $url, array $params, string $label, array &$errors): array
    {
        $items = [];
        $nextUrl = $url;
        $nextParams = $params;
        $guard = 0;

        while ($nextUrl && $guard < 20) {
            $guard++;

            $response = Http::get($nextUrl, $nextParams);
            if ($response->failed()) {
                $errors[] = $label . ': ' . ($response->json('error.message') ?? $response->body());
                break;
            }

            $chunk = (array) $response->json('data', []);
            if (!empty($chunk)) {
                $items = array_merge($items, $chunk);
            }

            $nextUrl = (string) ($response->json('paging.next') ?? '');
            if ($nextUrl === '') {
                break;
            }

            // Paging next sudah mengandung semua query params.
            $nextParams = [];
        }

        return $items;
    }

    private function describeTokenScopes(string $token): array
    {
        $required = [
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_posts',
            'pages_manage_metadata',
            'instagram_basic',
            'instagram_content_publish',
            'business_management',
        ];

        try {
            $debugRes = Http::get("https://graph.facebook.com/{$this->apiVersion}/debug_token", [
                'input_token'  => $token,
                'access_token' => $this->appId . '|' . $this->appSecret,
            ]);

            if ($debugRes->failed()) {
                return [
                    'granted' => [],
                    'missing' => $required,
                ];
            }

            $granted = array_values(array_unique((array) $debugRes->json('data.scopes', [])));
            $missing = array_values(array_diff($required, $granted));

            return [
                'granted' => $granted,
                'missing' => $missing,
            ];
        } catch (\Throwable $e) {
            return [
                'granted' => [],
                'missing' => $required,
            ];
        }
    }

    // ================================================================
    // saveFacebookPage — upsert konfigurasi akun page terpilih
    // ================================================================

    public function saveFacebookPage(int $userId, array $selectedPage): void
    {
        $pendingAccount = SosialAccount::where('user_id', $userId)
            ->where('platform', 'facebook')
            ->where('platform_user_id', 'pending_meta_token')
            ->whereNull('deleted_at')
            ->first();

        if ($pendingAccount) {
            $pendingAccount->update([
                'platform_user_id' => $selectedPage['id'],
                'username'         => $selectedPage['name'],
                'page_id'          => $selectedPage['id'],
                'access_token'     => encrypt($selectedPage['access_token']),
                'token_expires_at' => now()->addDays(60),
                'is_active'        => 1,
                'deleted_at'       => null,
            ]);
        } else {
            SosialAccount::updateOrCreate(
                [
                    'user_id'          => $userId,
                    'platform'         => 'facebook',
                    'platform_user_id' => $selectedPage['id'],
                ],
                [
                    'username'         => $selectedPage['name'],
                    'page_id'          => $selectedPage['id'],
                    'access_token'     => encrypt($selectedPage['access_token']),
                    'token_expires_at' => now()->addDays(60),
                    'is_active'        => 1,
                    'deleted_at'       => null,
                ]
            );
        }

        $igSource = $selectedPage['instagram_business_account'] ?? ($selectedPage['connected_instagram_account'] ?? null);
        $igId = $igSource['id'] ?? null;
        $igUsername = $igSource['username'] ?? null;

        // Some login flows return page data without instagram_business_account.
        // Do one extra lookup with page token before deciding that IG is not connected.
        if (empty($igId) && !empty($selectedPage['id']) && !empty($selectedPage['access_token'])) {
            try {
                $igLookup = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$selectedPage['id']}", [
                    'fields'       => 'instagram_business_account{id,username},connected_instagram_account{id,username}',
                    'access_token' => $selectedPage['access_token'],
                ]);

                if ($igLookup->successful()) {
                    $igId = $igLookup->json('instagram_business_account.id')
                        ?: $igLookup->json('connected_instagram_account.id');
                    $igUsername = $igLookup->json('instagram_business_account.username')
                        ?: $igLookup->json('connected_instagram_account.username');
                }
            } catch (\Throwable $e) {
                Log::warning('Meta saveFacebookPage: lookup instagram_business_account gagal.', [
                    'user_id' => $userId,
                    'page_id' => $selectedPage['id'] ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        if (!empty($igId)) {
            SosialAccount::updateOrCreate(
                [
                    'user_id'          => $userId,
                    'platform'         => 'instagram',
                    'platform_user_id' => $igId,
                ],
                [
                    'username'         => $igUsername,
                    'page_id'          => $selectedPage['id'],
                    'access_token'     => encrypt($selectedPage['access_token']),
                    'token_expires_at' => now()->addDays(60),
                    'is_active'        => 1,
                    'deleted_at'       => null,
                ]
            );
        } else {
            Log::info('Meta saveFacebookPage: tidak ada Instagram Business Account yang terhubung ke page.', [
                'user_id' => $userId,
                'page_id' => $selectedPage['id'] ?? null,
                'page_name' => $selectedPage['name'] ?? null,
            ]);
        }
    }

    // ================================================================
    // getAccounts — Fetch FB Page + Instagram Business Account
    // ================================================================

    public function getAccounts(string $longToken): array
    {
        $userProfile = Http::get("https://graph.facebook.com/{$this->apiVersion}/me", [
            'fields'       => 'id,name',
            'access_token' => $longToken,
        ]);

        if ($userProfile->failed() || empty($userProfile->json('id'))) {
            throw new \Exception(
                'Gagal mengambil profil user: ' . ($userProfile->json('error.message') ?? $userProfile->body())
            );
        }

        $fbUserId = $userProfile->json('id');
        $fbName   = $userProfile->json('name');

        $accounts = [];

        // Akun Facebook utama (user token)
        $accounts[] = [
            'platform'         => 'facebook',
            'platform_user_id' => $fbUserId,
            'username'         => $fbName ?? null,
            'page_id'          => null,
            'access_token'     => $longToken,
        ];

        // Fetch daftar Page & IG Business
        $pagesRes = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$fbUserId}/accounts", [
            'fields'       => 'id,name,access_token,instagram_business_account',
            'access_token' => $longToken,
        ]);

        if ($pagesRes->failed()) {
            throw new \Exception(
                'Gagal mengambil daftar Page: ' . ($pagesRes->json('error.message') ?? $pagesRes->body())
            );
        }

        $pages = $pagesRes->json('data', []);

        if (empty($pages)) {
            throw new \Exception('Tidak ada Facebook Page yang ditemukan untuk akun ini.');
        }

        foreach ($pages as $page) {
            // Tiap Page punya access_token sendiri — gunakan itu, bukan longToken user
            $pageToken = $page['access_token'] ?? $longToken;

            $accounts[] = [
                'platform'         => 'facebook',
                'platform_user_id' => $page['id'],
                'username'         => $page['name'] ?? null,
                'page_id'          => $page['id'],
                'access_token'     => $pageToken,
            ];

            // Jika ada Instagram Business Account terhubung
            if (!empty($page['instagram_business_account']['id'])) {
                $igId = $page['instagram_business_account']['id'];

                $igProfile = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$igId}", [
                    'fields'       => 'id,username',
                    'access_token' => $pageToken,
                ]);

                $accounts[] = [
                    'platform'         => 'instagram',
                    'platform_user_id' => $igId,
                    'username'         => $igProfile->json('username') ?? null,
                    'page_id'          => $page['id'], // relasi ke FB Page induknya
                    'access_token'     => $pageToken,
                ];
            }
        }

        return $accounts;
    }

    // ================================================================
    // storeAccounts — Simpan ke tabel sosial_accounts dengan encrypt
    // ================================================================

    public function storeAccounts(int $userId, array $accounts): void
    {
        foreach ($accounts as $account) {
            SosialAccount::updateOrCreate(
                [
                    'user_id'          => $userId,
                    'platform'         => $account['platform'],
                    'platform_user_id' => $account['platform_user_id'],
                ],
                [
                    'username'         => $account['username'],
                    'page_id'          => $account['page_id'] ?? null,
                    'access_token'     => encrypt($account['access_token']),
                    'token_expires_at' => now()->addDays(60),
                    'is_active'        => 1,
                    'deleted_at'       => null,
                ]
            );
        }
    }

    // ================================================================
    // checkTokenStatus — Debug token ke Meta API
    // ================================================================

    public function checkTokenStatus(SosialAccount $akun): array
    {
        try {
            $token = decrypt($akun->access_token);

            $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/debug_token", [
                'input_token'  => $token,
                'access_token' => $this->appId . '|' . $this->appSecret,
            ]);

            if ($response->failed()) {
                throw new \Exception('Facebook debug_token API error: ' . $response->body());
            }

            $data      = $response->json('data', []);
            $isValid   = $data['is_valid'] ?? false;
            $expiresAt = isset($data['expires_at']) && $data['expires_at'] > 0
                ? \Carbon\Carbon::createFromTimestamp($data['expires_at'])->toDateTimeString()
                : null;

            return [
                'is_valid'     => $isValid,
                'expires_at'   => $expiresAt,
                'token_status' => $this->resolveTokenStatus($isValid, $expiresAt),
                'scopes'       => $data['scopes'] ?? [],
                'platform'     => $akun->platform,
                'username'     => $akun->username,
            ];

        } catch (\Exception $e) {
            \Log::error('MetaService::checkTokenStatus failed', [
                'akun_id' => $akun->id ?? null,
                'platform' => $akun->platform ?? null,
                'username' => $akun->username ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'is_valid'     => false,
                'expires_at'   => null,
                'token_status' => 'error',
                'scopes'       => [],
                'platform'     => $akun->platform,
                'username'     => $akun->username,
            ];
        }
    }

    // ================================================================
    // PRIVATE — resolveTokenStatus
    // ================================================================

    private function resolveTokenStatus(bool $isValid, ?string $expiresAt): string
    {
        if (!$isValid) return 'expired';
        if (!$expiresAt) return 'valid';

        $diff = now()->diffInDays(\Carbon\Carbon::parse($expiresAt), false);

        if ($diff < 0)  return 'expired';
        if ($diff <= 7) return 'akan_expired';

        return 'valid';
    }
}