@extends('layout.main')

@section('title', 'Konfirmasi Pembayaran Admin')

@section('content')
    <style>
        .pa-wrap {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            gap: 12px;
        }

        .pa-head h1 {
            margin: 0;
            color: #eef4ff;
            font-size: clamp(26px, 4vw, 38px);
        }

        .pa-head p {
            margin: 6px 0 0;
            color: #90a4cc;
        }

        .order-card {
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.95), rgba(10, 18, 31, 0.95));
            border: 1px solid rgba(162, 183, 255, 0.12);
            border-radius: 14px;
            padding: 14px;
            color: #dce6fb;
        }

        .order-card p {
            margin: 6px 0;
        }

        .order-img {
            width: 220px;
            border-radius: 10px;
            border: 1px solid rgba(133, 168, 255, 0.22);
            margin: 8px 0;
            display: block;
        }

        .order-actions {
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .order-actions form {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }

        .input-note {
            border-radius: 8px;
            border: 1px solid rgba(150, 175, 230, 0.2);
            background: rgba(21, 32, 52, 0.78);
            color: #e7efff;
            padding: 8px 10px;
            min-width: 240px;
        }

        .btn-success,
        .btn-danger {
            border-radius: 8px;
            border: 1px solid transparent;
            font-weight: 700;
            padding: 8px 11px;
            cursor: pointer;
        }

        .btn-success {
            color: #0f3d2f;
            background: linear-gradient(180deg, #9cf2d3, #6edfb7);
        }

        .btn-danger {
            color: #4f1611;
            background: linear-gradient(180deg, #ffc3bc, #ff9f94);
        }

        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 9px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            border: 1px solid;
        }

        .badge.pending {
            color: #ffd9a8;
            border-color: rgba(255, 196, 117, 0.45);
            background: rgba(123, 88, 40, 0.28);
        }

        .badge.waiting {
            color: #b9cdff;
            border-color: rgba(149, 177, 240, 0.45);
            background: rgba(55, 81, 131, 0.28);
        }

        .badge.success {
            color: #a4ffd7;
            border-color: rgba(102, 223, 176, 0.45);
            background: rgba(39, 112, 85, 0.28);
        }

        .badge.rejected {
            color: #ffb7af;
            border-color: rgba(255, 144, 136, 0.45);
            background: rgba(120, 56, 52, 0.28);
        }

        .alert-success,
        .alert-error {
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(51, 130, 98, 0.3);
            border: 1px solid rgba(102, 223, 176, 0.45);
            color: #bbf5df;
        }

        .alert-error {
            background: rgba(153, 64, 58, 0.3);
            border: 1px solid rgba(255, 144, 136, 0.45);
            color: #ffd0cb;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }
    </style>

    <section class="pa-wrap">
        <div class="pa-head">
            <h1>Konfirmasi Pembayaran</h1>
            <p>Verifikasi bukti transfer QRIS dari pengguna.</p>
        </div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        @forelse($orders as $order)
            <div class="order-card">
                <p>Order ID: <strong>{{ $order->order_id }}</strong></p>
                <p>User: {{ optional($order->user)->name ?? '-' }} ({{ optional($order->user)->email ?? '-' }})</p>
                <p>Status: <span class="badge {{ $order->status }}">{{ ucfirst($order->status) }}</span></p>
                <p>Nominal: Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
                <p>Tanggal: {{ optional($order->created_at)->format('d M Y H:i') }}</p>

                @if($order->bukti_pembayaran)
                    <img src="{{ asset('storage/' . $order->bukti_pembayaran) }}"
                         alt="Bukti Pembayaran"
                         class="order-img">
                @endif

                @if(in_array($order->status, ['waiting', 'pending', 'rejected']))
                    <div class="order-actions">
                        <form method="POST" action="{{ route('payment.konfirmasi', $order->order_id) }}">
                            @csrf
                            <button type="submit" class="btn-success"
                                    onclick="return confirm('Konfirmasi pembayaran ini?')">
                                Konfirmasi
                            </button>
                        </form>

                        <form method="POST" action="{{ route('payment.tolak', $order->order_id) }}">
                            @csrf
                            <input type="text" name="catatan" class="input-note" placeholder="Alasan penolakan" required>
                            <button type="submit" class="btn-danger"
                                    onclick="return confirm('Tolak pembayaran ini?')">
                                Tolak
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="order-card">
                <p>Tidak ada order pembayaran saat ini.</p>
            </div>
        @endforelse

        <div class="pagination">{{ $orders->links() }}</div>
    </section>
@endsection
