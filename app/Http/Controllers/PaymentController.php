<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function showQris()
    {
        return view('payment.qris', $this->paymentService->getQrisPageData((int) auth()->id()));
    }

    // User buat order baru
    public function createOrder(Request $request)
    {
        try {
            $this->paymentService->createOrder((int) auth()->id());

            return redirect()->route('payment.qris')
                ->with('success', 'Order berhasil dibuat! Silakan scan QR dan upload bukti pembayaran.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Create payment order failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal membuat order pembayaran.');
        }
    }

    public function uploadBukti(Request $request, $orderId)
    {
        $request->validate([
            'bukti_pembayaran' => ['required', 'image', 'max:2048'],
        ]);

        try {
            $this->paymentService->uploadProof((int) auth()->id(), (string) $orderId, $request);

            return back()->with('success', 'Bukti pembayaran berhasil diupload! Menunggu konfirmasi admin.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Upload payment proof failed', [
                'user_id' => auth()->id(),
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal mengupload bukti pembayaran.');
        }
    }

    public function konfirmasi($orderId)
    {
        try {
            $this->paymentService->confirmPayment((string) $orderId);

            return back()->with('success', 'Pembayaran berhasil dikonfirmasi!');
        } catch (\Throwable $e) {
            Log::error('Confirm payment failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal mengonfirmasi pembayaran.');
        }
    }

    public function tolak(Request $request, $orderId)
    {
        $request->validate([
            'catatan' => ['required', 'string'],
        ]);

        try {
            $this->paymentService->rejectPayment((string) $orderId, (string) $request->catatan);

            return back()->with('success', 'Pembayaran ditolak.');
        } catch (\Throwable $e) {
            Log::error('Reject payment failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menolak pembayaran.');
        }
    }

    public function history(Request $request)
    {
        return view('payment.history', $this->paymentService->getHistoryData((int) auth()->id(), $request->query('status')));
    }

    public function latestStatus()
    {
        return response()->json($this->paymentService->getLatestStatus((int) auth()->id()));
    }

    public function adminIndex()
    {
        $orders = $this->paymentService->getAdminOrders();

        return view('payment.admin', compact('orders'));
    }
}