<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PaymentService
{
    private const STATUS_LABELS = [
        'pending' => 'Order Created',
        'waiting' => 'Verification',
        'success' => 'Settlement',
        'rejected' => 'Rejected',
    ];

    public function getQrisPageData(int $userId): array
    {
        $existingOrder = Order::where('user_id', $userId)
            ->whereIn('status', ['pending', 'waiting'])
            ->latest()
            ->first();

        $latestOrder = Order::where('user_id', $userId)
            ->latest()
            ->first();

        $orders = Order::where('user_id', $userId)
            ->latest()
            ->paginate(8);

        $settings = PaymentSetting::qrisSettings();
        $qrisImage = $this->normalizeQrisImage($settings['qrisCode'] ?? null);

        return [
            'price' => $settings['qrisNominal'] ?? 0,
            'existingOrder' => $existingOrder,
            'latestOrder' => $latestOrder,
            'orders' => $orders,
            'statusLabels' => self::STATUS_LABELS,
            'qrisName' => $settings['qrisName'] ?? '',
            'qrisCatatan' => $settings['qrisCatatan'] ?? '',
            'qrisImage' => $qrisImage,
        ];
    }

    public function createOrder(int $userId): void
    {
        try {
            $existing = Order::where('user_id', $userId)
                ->whereIn('status', ['pending', 'waiting'])
                ->first();

            if ($existing) {
                throw new RuntimeException('Kamu masih punya order yang belum selesai.');
            }

            $nominal = (int) PaymentSetting::get('qris_nominal', 0);

            if ($nominal <= 0) {
                throw new RuntimeException('Nominal QRIS belum diset di database.');
            }

            Order::create([
                'order_id' => 'ORDER-' . uniqid(),
                'user_id' => $userId,
                'total_price' => $nominal,
                'status' => 'pending',
            ]);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Create payment order error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal membuat order pembayaran.');
        }
    }

    public function uploadProof(int $userId, string $orderId, Request $request): void
    {
        try {
            $order = Order::where('order_id', $orderId)
                ->where('user_id', $userId)
                ->firstOrFail();

            if (!in_array($order->status, ['pending', 'waiting'], true)) {
                throw new RuntimeException('Order ini sudah tidak bisa diupdate.');
            }

            if ($order->bukti_pembayaran) {
                Storage::disk('public')->delete($order->bukti_pembayaran);
            }

            $path = $request->file('bukti_pembayaran')->store('bukti_pembayaran', 'public');

            $order->update([
                'bukti_pembayaran' => $path,
                'status' => 'waiting',
            ]);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Upload payment proof error', [
                'user_id' => $userId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal mengupload bukti pembayaran.');
        }
    }

    public function confirmPayment(string $orderId): void
    {
        try {
            DB::transaction(function () use ($orderId) {
                $order = Order::where('order_id', $orderId)->firstOrFail();

                $order->update([
                    'status' => 'success',
                    'confirmed_at' => now(),
                ]);

                $now = now();
                $subscription = Subscription::where('user_id', $order->user_id)
                    ->latest()
                    ->first();

                $isActive = $subscription
                    && $subscription->status === 'active'
                    && $subscription->expired_at
                    && $subscription->expired_at->isFuture();

                $baseStart = $isActive ? ($subscription->started_at ?? $now) : $now;
                $baseExpiry = $isActive ? $subscription->expired_at : $now;
                $expiresAt = $baseExpiry->copy()->addDays(30);

                if ($subscription) {
                    $subscription->update([
                        'plan' => $subscription->plan ?? 'basic',
                        'status' => 'active',
                        'started_at' => $baseStart,
                        'expired_at' => $expiresAt,
                    ]);
                } else {
                    Subscription::create([
                        'user_id' => $order->user_id,
                        'plan' => 'basic',
                        'status' => 'active',
                        'started_at' => $baseStart,
                        'expired_at' => $expiresAt,
                    ]);
                }

                User::where('id', $order->user_id)->update(['is_active' => true]);

                Notification::create([
                    'user_id' => $order->user_id,
                    'type' => 'payment_confirmed',
                    'title' => 'Pembayaran berhasil dikonfirmasi',
                    'message' => 'Pembayaran Anda untuk order ' . $order->order_id . ' sudah dikonfirmasi admin. Akses akun Anda kini aktif.',
                    'data' => [
                        'order_id' => $order->order_id,
                        'status' => 'success',
                    ],
                ]);

                cache()->forget("user_{$order->user_id}_unread_count");
            });
        } catch (Throwable $e) {
            Log::error('Confirm payment error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal mengonfirmasi pembayaran.');
        }
    }

    public function rejectPayment(string $orderId, string $catatan): void
    {
        try {
            $order = Order::where('order_id', $orderId)->firstOrFail();

            $order->update([
                'status' => 'rejected',
                'catatan' => $catatan,
            ]);
        } catch (Throwable $e) {
            Log::error('Reject payment error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Gagal menolak pembayaran.');
        }
    }

    public function getHistoryData(int $userId, ?string $status = null): array
    {
        $allowedStatuses = ['pending', 'waiting', 'success', 'rejected'];
        $selectedStatus = strtolower((string) $status);

        if (!in_array($selectedStatus, $allowedStatuses, true)) {
            $selectedStatus = '';
        }

        $ordersQuery = Order::where('user_id', $userId);

        if ($selectedStatus !== '') {
            $ordersQuery->where('status', $selectedStatus);
        }

        $orders = $ordersQuery->latest()->paginate(10);

        $latestOrder = Order::where('user_id', $userId)
            ->latest()
            ->first();

        return [
            'orders' => $orders,
            'latestOrder' => $latestOrder,
            'allowedStatuses' => $allowedStatuses,
            'selectedStatus' => $selectedStatus,
            'statusLabels' => self::STATUS_LABELS,
        ];
    }

    public function getLatestStatus(int $userId): array
    {
        $latestOrder = Order::where('user_id', $userId)
            ->latest()
            ->first();

        if (!$latestOrder) {
            return ['has_order' => false];
        }

        $status = strtolower((string) $latestOrder->status);

        return [
            'has_order' => true,
            'order_id' => $latestOrder->order_id,
            'status' => $status,
            'status_label' => self::STATUS_LABELS[$status] ?? ucfirst($status),
        ];
    }

    public function getAdminOrders()
    {
        return Order::with('user')
            ->whereIn('status', ['waiting', 'pending', 'rejected', 'success'])
            ->latest()
            ->paginate(12);
    }

    private function normalizeQrisImage(mixed $qrisCode): ?string
    {
        if (empty($qrisCode)) {
            return null;
        }

        if (str_starts_with($qrisCode, 'http://') || str_starts_with($qrisCode, 'https://') || str_starts_with($qrisCode, 'data:image/')) {
            return $qrisCode;
        }

        return asset('storage/' . ltrim((string) $qrisCode, '/'));
    }
}
