<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\FacebookOauthRequest;
use App\Models\Notification;
use App\Models\SosialAccount;
use App\Services\MetaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookOAuthService
{
    public function __construct(private readonly MetaService $meta)
    {
    }

    /**
     * getPendingOauthRequests: Get all pending OAuth requests for admin
     */
    public function getPendingOauthRequests(): array
    {
        return FacebookOauthRequest::with('user:id,name,email')
            ->whereIn('status', ['pending', 'pages_ready'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'status'     => $item->status,
                    'user'       => [
                        'id'    => $item->user->id ?? null,
                        'name'  => $item->user->name ?? null,
                        'email' => $item->user->email ?? null,
                    ],
                    'created_at' => optional($item->created_at)->toDateTimeString(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * facebookCallback: Exchange auth code, store long-lived token, and fetch pages.
     */
    public function facebookCallback(int $userId, string $code, ?string $state): array
    {
        $shortToken = $this->meta->exchangeCodeToShortLivedToken($code, route('meta.callback'));
        $longToken = $this->meta->exchangeShortToLongLivedToken($shortToken);

        $this->upsertApiToken($userId, [
            'short_lived_token'  => encrypt($shortToken),
            'long_lived_token'   => encrypt($longToken),
            'token_refreshed_at' => now(),
            'oauth_state'        => $state,
            'oauth_redirect_uri' => route('meta.callback'),
        ]);

        SosialAccount::updateOrCreate(
            [
                'user_id'          => $userId,
                'platform'         => 'facebook',
                'platform_user_id' => 'pending_meta_token',
            ],
            [
                'username'         => 'Pending Facebook Page',
                'page_id'          => null,
                'access_token'     => encrypt($longToken),
                'token_expires_at' => now()->addDays(60),
                'is_active'        => 0,
                'deleted_at'       => null,
            ]
        );

        $pages = $this->meta->getFacebookPages($longToken);

        FacebookOauthRequest::create([
            'user_id'      => $userId,
            'state'        => $state,
            'redirect_uri' => route('meta.callback'),
            'status'       => 'pages_ready',
            'notes'        => 'OAuth callback success. Menunggu user pilih Facebook Page.',
        ]);

        return [
            'status' => 'pages_ready',
            'state' => $state,
            'pages' => $pages,
        ];
    }

    /**
     * apiCallback: Exchange code to token (auto or admin mode)
     */
    public function apiCallback(
        int $userId,
        string $code,
        string $redirectUri,
        string $state,
        string $approvalMode = 'auto'
    ): array {
        if ($approvalMode === 'admin') {
            $shortToken = $this->meta->exchangeCodeToShortLivedToken($code, $redirectUri);

            $this->upsertApiToken($userId, [
                'short_lived_token' => encrypt($shortToken),
                'oauth_state'       => $state,
                'oauth_redirect_uri'=> $redirectUri,
            ]);

            $pendingRequest = FacebookOauthRequest::create([
                'user_id'           => $userId,
                'state'             => $state,
                'redirect_uri'      => $redirectUri,
                'short_lived_token' => encrypt($shortToken),
                'status'            => 'pending',
            ]);

            return [
                'mode'       => 'admin',
                'request_id' => $pendingRequest->id,
                'status'     => $pendingRequest->status,
            ];
        }

        $longToken = $this->meta->getLongLivedToken($code, $redirectUri);

        $this->upsertApiToken($userId, [
            'long_lived_token'   => encrypt($longToken),
            'token_refreshed_at' => now(),
            'oauth_state'        => $state,
            'oauth_redirect_uri' => $redirectUri,
        ]);

        $pages = $this->meta->getFacebookPages($longToken);

        $pendingKey = "meta_pending_pages:{$userId}:{$state}";
        Cache::put($pendingKey, $pages, now()->addMinutes(15));

        return [
            'mode'  => 'auto',
            'pages' => $pages,
            'state' => $state,
        ];
    }

    /**
     * apiAdminFetchPages: Admin fetches pages from short token
     */
    public function apiAdminFetchPages(int $requestId, int $adminId): array
    {
        $oauthRequest = FacebookOauthRequest::find($requestId);

        if (!$oauthRequest) {
            throw new \RuntimeException('Permintaan OAuth tidak ditemukan.');
        }

        if (!$oauthRequest->short_lived_token) {
            throw new \RuntimeException('Short-lived token tidak tersedia di request ini.');
        }

        $shortToken = decrypt($oauthRequest->short_lived_token);
        $longToken = $this->meta->exchangeShortToLongLivedToken($shortToken);
        $pages = $this->meta->getFacebookPages($longToken);

        $this->upsertApiToken((int) $oauthRequest->user_id, [
            'short_lived_token' => encrypt($shortToken),
            'long_lived_token'  => encrypt($longToken),
            'token_refreshed_at'=> now(),
            'oauth_state'       => $oauthRequest->state,
            'oauth_redirect_uri'=> $oauthRequest->redirect_uri,
        ]);

        $oauthRequest->update([
            'status'           => 'pages_ready',
            'notes'            => 'Pages fetched by admin. Long-lived token tersimpan di api_tokens.',
        ]);

        return [
            'request_id' => $oauthRequest->id,
            'status'     => $oauthRequest->status,
            'pages'      => $pages,
        ];
    }

    /**
     * apiAdminApproveOauth: Admin approves and saves a page
     */
    public function apiAdminApproveOauth(int $requestId, string $pageId, int $adminId): array
    {
        $oauthRequest = FacebookOauthRequest::find($requestId);

        if (!$oauthRequest) {
            throw new \RuntimeException('Permintaan OAuth tidak ditemukan.');
        }

        $apiToken = ApiToken::where('user_id', (int) $oauthRequest->user_id)->first();

        if (!$apiToken || !$apiToken->long_lived_token) {
            throw new \RuntimeException('Long-lived token belum tersedia. Jalankan fetch pages dulu.');
        }

        $longToken = decrypt($apiToken->long_lived_token);
        $pages = $this->meta->getFacebookPages($longToken);
        $selectedPage = collect($pages)->firstWhere('id', $pageId);

        if (!$selectedPage) {
            throw new \RuntimeException('Page tidak ditemukan pada daftar akun user.');
        }

        $this->meta->saveFacebookPage((int) $oauthRequest->user_id, $selectedPage);

        $this->notifyAccountConnected(
            (int) $oauthRequest->user_id,
            'facebook',
            (string) ($selectedPage['name'] ?? $selectedPage['id'] ?? 'Facebook Page')
        );

        $oauthRequest->update([
            'status'       => 'approved',
            'approved_by'  => $adminId,
            'approved_at'  => now(),
            'notes'        => 'Approved and saved by admin',
        ]);

        return [
            'request_id' => $oauthRequest->id,
            'status'     => $oauthRequest->status,
            'page'       => [
                'id'   => $selectedPage['id'] ?? null,
                'name' => $selectedPage['name'] ?? null,
            ],
        ];
    }

    /**
     * SaveFacebookPage: Save selected page to account
     */
    public function SaveFacebookPage(int $userId, string $pageId, array $pendingPages): void
    {
        $selectedPage = collect($pendingPages)->firstWhere('id', $pageId);

        if (!$selectedPage) {
            throw new \RuntimeException('Page yang dipilih tidak ditemukan. Silakan ulangi proses koneksi Facebook.');
        }

        $this->meta->saveFacebookPage($userId, $selectedPage);

        FacebookOauthRequest::where('user_id', $userId)
            ->whereIn('status', ['pending_admin_token', 'pages_ready'])
            ->latest('id')
            ->limit(1)
            ->update([
                'status' => 'connected',
                'notes' => 'Page final sudah dipilih dan akun berhasil terhubung.',
            ]);

        $this->notifyAccountConnected(
            $userId,
            'facebook',
            (string) ($selectedPage['name'] ?? $selectedPage['id'] ?? 'Facebook Page')
        );
    }

    /**
     * apiSaveFacebookPage: API endpoint to save page
     */
    public function apiSaveFacebookPage(int $userId, string $pageId, string $state, array $pendingPages): array
    {
        $selectedPage = collect($pendingPages)->firstWhere('id', $pageId);

        if (!$selectedPage) {
            throw new \RuntimeException('Page tidak ditemukan atau sesi pemilihan sudah expired.');
        }

        $this->meta->saveFacebookPage($userId, $selectedPage);

        $this->notifyAccountConnected(
            $userId,
            'facebook',
            (string) ($selectedPage['name'] ?? $selectedPage['id'] ?? 'Facebook Page')
        );

        return [
            'page' => [
                'id'   => $selectedPage['id'] ?? null,
                'name' => $selectedPage['name'] ?? null,
            ],
        ];
    }

    /**
     * Private helpers
     */

    private function upsertApiToken(int $userId, array $attributes): void
    {
        ApiToken::updateOrCreate(
            ['user_id' => $userId],
            $attributes
        );
    }

    private function resolveStoredLongLivedToken(int $userId, FacebookOauthRequest $request): ?string
    {
        $apiToken = ApiToken::where('user_id', $userId)->first();

        if ($apiToken && !empty($apiToken->long_lived_token)) {
            try {
                return decrypt($apiToken->long_lived_token);
            } catch (\Throwable $e) {
                Log::warning('Decrypt long_lived_token di api_tokens gagal.', [
                    'user_id' => $userId,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        if (!empty($request->long_lived_token)) {
            try {
                return decrypt($request->long_lived_token);
            } catch (\Throwable $e) {
                Log::warning('Decrypt long_lived_token legacy di facebook_oauth_requests gagal.', [
                    'request_id' => $request->id,
                    'user_id'    => $userId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    private function notifyAccountConnected(int $userId, string $platform, string $accountName): void
    {
        Notification::create([
            'user_id' => $userId,
            'type'    => 'account_connected',
            'title'   => 'Akun berhasil terhubung',
            'message' => ucfirst($platform) . ' account "' . $accountName . '" sudah terhubung dan aktif.',
            'data'    => [
                'platform' => $platform,
                'name'     => $accountName,
            ],
        ]);
    }
}
