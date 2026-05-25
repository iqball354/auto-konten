<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FacebookOAuthService;
use App\Services\MetaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FacebookOAuthController extends Controller
{
    public function __construct(
        private readonly FacebookOAuthService $oauthService,
        private readonly MetaService $metaService
    ) {
    }

    /**
     * GET /api/facebook/connect-url
     * Generate OAuth login URL for user
     */
    public function getConnectUrl(Request $request)
    {
        $request->validate([
            'redirect_uri' => 'required|url',
            'basic_mode'   => 'nullable|boolean',
        ]);

        $userId = auth('api')->id();

        if (!$userId) {
            return $this->apiError('Unauthenticated.', 401, [
                'code' => 'UNAUTHENTICATED',
            ]);
        }

        try {
            $state = Str::random(40);
            $cacheKey = "meta_oauth_state:{$userId}:{$state}";

            Cache::put($cacheKey, [
                'redirect_uri' => $request->redirect_uri,
            ], now()->addMinutes(10));

            $authUrl = $this->metaService->getLoginUrl(
                (bool) $request->boolean('basic_mode', false),
                $state,
                $request->redirect_uri
            );

            return $this->apiSuccess('OAuth URL berhasil dibuat.', [
                'auth_url'   => $authUrl,
                'state'      => $state,
                'expires_in' => 600,
            ]);
        } catch (\Throwable $e) {
            return $this->apiError('Gagal membuat OAuth URL.', 500, [
                'code'    => 'CONNECT_URL_FAILED',
                'details' => $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /api/facebook/callback
     * Handle OAuth callback with code exchange
     */
    public function handleCallback(Request $request)
    {
        $request->validate([
            'code'         => 'required|string',
            'redirect_uri' => 'required|url',
            'state'        => 'required|string|min:20',
            'approval_mode'=> 'nullable|in:auto,admin',
        ]);

        $userId = auth('api')->id();

        if (!$userId) {
            return $this->apiError('Unauthenticated.', 401, [
                'code' => 'UNAUTHENTICATED',
            ]);
        }

        $stateKey = "meta_oauth_state:{$userId}:{$request->state}";
        $stateData = Cache::get($stateKey);

        if (!$stateData || ($stateData['redirect_uri'] ?? null) !== $request->redirect_uri) {
            return $this->apiError('State OAuth tidak valid atau sudah expired.', 422, [
                'code' => 'INVALID_STATE',
            ]);
        }

        try {
            $result = $this->oauthService->apiCallback(
                $userId,
                (string) $request->code,
                (string) $request->redirect_uri,
                (string) $request->state,
                (string) $request->input('approval_mode', 'auto')
            );

            Cache::forget($stateKey);

            if ($result['mode'] === 'admin') {
                return $this->apiSuccess('Permintaan OAuth dikirim ke admin untuk diproses.', [
                    'request_id'    => $result['request_id'],
                    'approval_mode' => 'admin',
                    'status'        => $result['status'],
                ], 202);
            }

            return $this->apiSuccess('Daftar page berhasil diambil.', [
                'pages' => $result['pages'],
                'state' => $result['state'],
            ]);
        } catch (\Exception $e) {
            return $this->apiError('Gagal menghubungkan akun.', 422, [
                'code'    => 'META_CALLBACK_FAILED',
                'details' => $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /api/facebook/save-page
     * Save selected page to user's account
     */
    public function savePage(Request $request)
    {
        $request->validate([
            'page_id'   => 'required|string',
            'state'     => 'required|string|min:20',
        ]);

        $userId = auth('api')->id();

        if (!$userId) {
            return $this->apiError('Unauthenticated.', 401, [
                'code' => 'UNAUTHENTICATED',
            ]);
        }

        $pendingKey = "meta_pending_pages:{$userId}:{$request->state}";
        $pendingPages = Cache::get($pendingKey, []);

        try {
            $result = $this->oauthService->apiSaveFacebookPage($userId, (string) $request->page_id, (string) $request->state, $pendingPages);

            Cache::forget($pendingKey);
            Cache::forget("meta_oauth_state:{$userId}:{$request->state}");

            return $this->apiSuccess('Facebook Page berhasil disimpan.', $result);
        } catch (\Throwable $e) {
            return $this->apiError('Gagal menyimpan Facebook Page.', 500, [
                'code'    => 'SAVE_PAGE_FAILED',
                'details' => $e->getMessage(),
            ]);
        }
    }

    protected function apiSuccess(string $message, array $data = [], int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'error'   => null,
        ], $status);
    }

    protected function apiError(string $message, int $status = 422, array $error = [])
    {
        $traceId = $error['trace_id'] ?? $this->makeTraceId();

        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'error'   => [
                'code'     => $error['code'] ?? 'API_ERROR',
                'details'  => $error['details'] ?? null,
                'trace_id' => $traceId,
            ],
        ], $status);
    }

    private function makeTraceId(): string
    {
        return (string) Str::uuid();
    }
}
