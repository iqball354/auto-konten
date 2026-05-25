<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\FacebookOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FacebookOAuthController extends Controller
{
    public function __construct(private readonly FacebookOAuthService $oauthService)
    {
    }

    /**
     * GET /api/admin/facebook/pending-requests
     * Get all pending OAuth requests for admin approval
     */
    public function getPendingRequests()
    {
        try {
            $requests = $this->oauthService->getPendingOauthRequests();

            return $this->apiSuccess('Daftar pending OAuth berhasil diambil.', [
                'requests' => $requests,
            ]);
        } catch (\Throwable $e) {
            return $this->apiError('Gagal memuat daftar pending OAuth.', 500, [
                'code'    => 'PENDING_REQUESTS_FAILED',
                'details' => $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /api/admin/facebook/pending-requests/{id}/pages
     * Admin fetches pages from short token for a pending request
     */
    public function fetchPages($requestId)
    {
        $admin = auth('api')->user();

        try {
            $result = $this->oauthService->apiAdminFetchPages((int) $requestId, (int) $admin->id);

            return $this->apiSuccess('Daftar page berhasil diambil oleh admin.', $result);
        } catch (\Exception $e) {
            return $this->apiError('Gagal memproses token oleh admin.', 422, [
                'code'    => 'ADMIN_TOKEN_EXCHANGE_FAILED',
                'details' => $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /api/admin/facebook/pending-requests/{id}/approve
     * Admin approves and saves a page for a pending request
     */
    public function approveRequest(Request $request, $requestId)
    {
        $request->validate([
            'page_id' => 'required|string',
        ]);

        $admin = auth('api')->user();

        try {
            $result = $this->oauthService->apiAdminApproveOauth((int) $requestId, (string) $request->page_id, (int) $admin->id);

            return $this->apiSuccess('Permintaan OAuth berhasil di-approve admin.', $result);
        } catch (\Exception $e) {
            return $this->apiError('Gagal menyimpan page saat approval admin.', 422, [
                'code'    => 'ADMIN_APPROVAL_FAILED',
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
