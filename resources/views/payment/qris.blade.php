@extends('layout.main')

@section('title', 'Pembayaran QRIS')

@section('content')
    <style>
        .qris-wrap {
            max-width: 860px;
            margin: 0 auto;
            display: grid;
            gap: 14px;
        }

        .qris-card {
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.95), rgba(10, 18, 31, 0.95));
            border: 1px solid rgba(162, 183, 255, 0.14);
            border-radius: 14px;
            padding: 16px;
            color: #e7efff;
        }

        .qris-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #f3f7ff;
        }

        .qris-card p {
            margin: 8px 0;
            color: #cddaf3;
        }

        .qris-card label {
            display: block;
            margin: 10px 0 6px;
            color: #b8c9ea;
            font-size: 13px;
        }

        .qris-card input[type="file"] {
            width: 100%;
            color: #d8e6ff;
            border: 1px solid rgba(133, 168, 255, 0.28);
            background: rgba(37, 54, 87, 0.86);
            border-radius: 10px;
            padding: 10px;
        }

        .btn-primary {
            margin-top: 12px;
            border-radius: 10px;
            border: 1px solid transparent;
            color: #11254e;
            background: linear-gradient(180deg, #9dc0ff, #709fff);
            padding: 10px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .alert-info,
        .alert-success,
        .alert-error {
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 600;
            margin-top: 8px;
        }

        .alert-info {
            background: rgba(69, 103, 173, 0.24);
            border: 1px solid rgba(126, 164, 255, 0.35);
            color: #cfe0ff;
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

        .badge-pending,
        .badge-waiting,
        .badge-success,
        .badge-rejected {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 9px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            border: 1px solid;
        }

        .badge-pending {
            color: #ffd9a8;
            border-color: rgba(255, 196, 117, 0.45);
            background: rgba(123, 88, 40, 0.28);
        }

        .badge-waiting {
            color: #b9cdff;
            border-color: rgba(149, 177, 240, 0.45);
            background: rgba(55, 81, 131, 0.28);
        }

        .badge-success {
            color: #a4ffd7;
            border-color: rgba(102, 223, 176, 0.45);
            background: rgba(39, 112, 85, 0.28);
        }

        .badge-rejected {
            color: #ffb7af;
            border-color: rgba(255, 144, 136, 0.45);
            background: rgba(120, 56, 52, 0.28);
        }

        .qris-divider-title {
            margin: 4px 0 0;
            font-size: 17px;
            color: #eef4ff;
            font-weight: 700;
        }

        .qris-summary-title {
            margin: 0 0 8px;
            color: #eef4ff;
            font-size: 20px;
        }

        .qris-summary-sub {
            margin: 0;
            color: #9db2da;
        }

        .ph-live {
            margin-top: 8px;
            border: 1px solid rgba(143, 167, 223, 0.2);
            border-radius: 12px;
            padding: 16px;
            background: radial-gradient(circle at 20% 0%, rgba(103, 149, 255, 0.16), transparent 40%), rgba(13, 20, 34, 0.9);
        }

        .ph-live-head {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .ph-live-head h3 {
            margin: 0;
            color: #ecf3ff;
            font-size: 24px;
            font-weight: 700;
        }

        .ph-live-head small {
            color: #8ea3cc;
            font-size: 12px;
        }

        .ph-live-badge {
            align-self: flex-start;
            border-radius: 999px;
            border: 1px solid rgba(126, 164, 255, 0.35);
            padding: 4px 9px;
            color: #bdd4ff;
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: rgba(67, 98, 167, 0.25);
            font-weight: 700;
        }

        .ph-stage-line {
            display: grid;
            grid-template-columns: repeat(5, minmax(120px, 1fr));
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        
        .pa-proof-link {
            display: inline-flex;
            align-items: center;
            border-radius: 8px;
            border: 1px solid rgba(133, 168, 255, 0.28);
            background: rgba(37, 54, 87, 0.86);
            color: #dce9ff;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .ph-stage {
            min-width: 120px;
            border: 1px solid rgba(143, 167, 223, 0.2);
            border-radius: 10px;
            padding: 10px;
            background: rgba(17, 28, 46, 0.7);
            text-align: center;
        }

        .ph-stage-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            margin: 0 auto 8px;
            border: 1px solid rgba(143, 167, 223, 0.3);
            background: rgba(26, 40, 64, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #b9ccf0;
            font-size: 16px;
            font-weight: 700;
        }

        .ph-stage-title {
            display: block;
            color: #dce7ff;
            font-size: 11px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .ph-stage-state {
            color: #8498bf;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .ph-stage.active {
            border-color: rgba(136, 176, 255, 0.8);
            background: linear-gradient(180deg, rgba(49, 78, 132, 0.78), rgba(29, 46, 78, 0.78));
            box-shadow: 0 0 0 1px rgba(145, 181, 255, 0.3), 0 0 22px rgba(93, 136, 240, 0.35);
        }

        .ph-stage.active .ph-stage-state {
            color: #d2e3ff;
        }

        .ph-stage.active .ph-stage-icon {
            background: rgba(104, 150, 255, 0.28);
            border-color: rgba(165, 197, 255, 0.6);
            box-shadow: 0 0 18px rgba(108, 158, 255, 0.5);
            color: #e8f1ff;
        }

        .ph-stage.done {
            border-color: rgba(102, 223, 176, 0.38);
            background: rgba(30, 77, 62, 0.45);
        }

        .ph-stage.done .ph-stage-state {
            color: #9ce9cc;
        }

        .ph-stage.done .ph-stage-icon {
            border-color: rgba(102, 223, 176, 0.52);
            background: rgba(37, 108, 82, 0.55);
            color: #d8ffee;
        }

        .ph-stage.failed {
            border-color: rgba(255, 146, 138, 0.52);
            background: rgba(120, 56, 52, 0.38);
        }

        .ph-stage.failed .ph-stage-icon {
            border-color: rgba(255, 146, 138, 0.52);
            background: rgba(137, 62, 57, 0.55);
            color: #ffd8d4;
        }

        .ph-stage.failed .ph-stage-state {
            color: #ffc1ba;
        }

        .qris-table-wrap {
            overflow: auto;
            border: 1px solid rgba(143, 167, 223, 0.2);
            border-radius: 10px;
            margin-top: 10px;
        }

        .qris-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }

        .qris-table th,
        .qris-table td {
            padding: 10px;
            border-bottom: 1px solid rgba(143, 167, 223, 0.15);
            color: #dce7ff;
            font-size: 13px;
            text-align: left;
        }

        .qris-table th {
            color: #9cb1d9;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: rgba(20, 30, 48, 0.65);
        }

        .qris-proof {
            width: 88px;
            border-radius: 8px;
            border: 1px solid rgba(133, 168, 255, 0.25);
            display: block;
        }

        .qris-note {
            margin: 0;
            color: #ffcfca;
            font-size: 12px;
        }

        .qris-empty {
            color: #9db2da;
            margin: 10px 0 2px;
        }

        .qris-paginate {
            margin-top: 10px;
        }

        .qris-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 14px;
            background: rgba(4, 10, 20, 0.72);
            backdrop-filter: blur(4px);
            z-index: 1300;
        }

        .qris-modal.show {
            display: flex;
        }

        .qris-modal-card {
            width: 100%;
            max-width: 560px;
            border-radius: 14px;
            border: 1px solid rgba(162, 183, 255, 0.24);
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.98), rgba(10, 18, 31, 0.98));
            padding: 16px;
            color: #e7efff;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45);
        }

        .qris-modal-head {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .qris-modal-head h4 {
            margin: 0;
            font-size: 18px;
            color: #f3f7ff;
        }

        .qris-modal-close {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(148, 175, 233, 0.3);
            background: rgba(26, 38, 62, 0.88);
            color: #d9e8ff;
            font-size: 16px;
            cursor: pointer;
        }

        .qris-modal-figure {
            text-align: center;
            margin-bottom: 12px;
        }

        .qris-modal-figure img {
            width: 250px;
            max-width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(149, 177, 240, 0.26);
            background: #ffffff;
        }

        .qris-terms {
            border: 1px solid rgba(143, 167, 223, 0.2);
            border-radius: 10px;
            background: rgba(17, 28, 46, 0.7);
            padding: 12px;
        }

        .qris-terms h5 {
            margin: 0 0 8px;
            color: #dce7ff;
            font-size: 14px;
        }

        .qris-terms ul {
            margin: 0;
            padding-left: 18px;
            color: #bfd0ef;
            font-size: 13px;
            display: grid;
            gap: 6px;
        }

        .qris-terms-ack {
            margin-top: 10px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: #cfe0ff;
            font-size: 12px;
        }

        .qris-modal-actions {
            margin-top: 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
    </style>

    @php
        $stageConfig = [
            ['key' => 'order-created', 'label' => 'Order Created', 'icon' => 'O'],
            ['key' => 'verification', 'label' => 'Verification', 'icon' => 'V'],
            ['key' => 'processing', 'label' => 'Processing', 'icon' => 'P'],
            ['key' => 'network-clearing', 'label' => 'Network Clearing', 'icon' => 'N'],
            ['key' => 'settlement', 'label' => 'Settlement', 'icon' => 'S'],
        ];

        $statusToStage = [
            'pending' => 'processing',
            'waiting' => 'network-clearing',
            'success' => 'settlement',
            'rejected' => 'verification',
        ];

        $flowIndexMap = [
            'order-created' => 0,
            'verification' => 1,
            'processing' => 2,
            'network-clearing' => 3,
            'settlement' => 4,
        ];

        $currentStatus = $latestOrder ? strtolower((string) $latestOrder->status) : null;
        $activeStageKey = $currentStatus ? ($statusToStage[$currentStatus] ?? null) : null;
        $activeIndex = $activeStageKey !== null ? ($flowIndexMap[$activeStageKey] ?? null) : null;
        $finishMessages = [
            'success' => ['Pembayaran berhasil.', 'Terima kasih, transaksi kamu sudah tercatat.'],
            'waiting' => ['Pembayaran sedang diverifikasi.', 'Bukti transfer sudah kami terima, tunggu konfirmasi admin.'],
            'pending' => ['Pembayaran menunggu bukti transfer.', 'Silakan upload bukti agar proses verifikasi dimulai.'],
            'rejected' => ['Pembayaran ditolak.', 'Silakan upload ulang bukti transfer sesuai catatan admin.'],
        ];
        $currentSummary = $finishMessages[$currentStatus] ?? ['Status pembayaran belum tersedia.', 'Lakukan transaksi untuk melihat progres pembayaran.'];
    @endphp

    <div class="qris-wrap">
        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        <div class="qris-card">
            <h3 class="qris-divider-title">Live Transaction Tracker</h3>

            <div class="ph-live">
                <div class="ph-live-head">
                    <div>
                        <h3>Live Transaction Tracker</h3>
                        <small id="live-status-text">
                            @if($latestOrder)
                                Tracking {{ $latestOrder->order_id }} • Status {{ $statusLabels[$currentStatus] ?? ucfirst($currentStatus) }}
                            @else
                                Belum ada transaksi untuk ditrack.
                            @endif
                        </small>
                    </div>
                    <span class="ph-live-badge">Live Monitoring</span>
                </div>

                <div class="ph-stage-line">
                    @foreach($stageConfig as $stage)
                        @php
                            $stageIndex = $flowIndexMap[$stage['key']];
                            $isActive = $activeStageKey === $stage['key'];
                            $isDone = $activeIndex !== null && $stageIndex < $activeIndex;
                            $isFailed = $currentStatus === 'rejected' && $stage['key'] === 'verification';
                        @endphp
                        <div class="ph-stage {{ $isActive ? 'active' : '' }} {{ $isDone ? 'done' : '' }} {{ $isFailed ? 'failed' : '' }}"
                             data-stage="{{ $stage['key'] }}">
                            <div class="ph-stage-icon">{{ $stage['icon'] }}</div>
                            <span class="ph-stage-title">{{ $stage['label'] }}</span>
                            <span class="ph-stage-state">
                                @if($isFailed)
                                    Ditolak
                                @elseif($isActive)
                                    Active Stage
                                @elseif($isDone)
                                    Completed
                                @else
                                    Pending
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Card 1: Scan QRIS --}}
<div class="qris-card">
    <h3>1. Scan QR Code Berikut</h3>
    <p>Nominal: <strong>Rp {{ number_format($price, 0, ',', '.') }}</strong></p>

    {{-- Ketentuan dipindah ke sini, sebelum tombol --}}
    <div class="qris-terms" style="margin-bottom: 14px;">
        <h5>Ketentuan Transaksi</h5>
        <ul>
            <li>Transfer sesuai nominal yang tertera agar verifikasi berjalan otomatis.</li>
            <li>Upload bukti pembayaran yang jelas setelah transfer selesai.</li>
            <li>Proses verifikasi dilakukan admin, mohon tunggu sampai status berubah.</li>
            @if(!empty($qrisCatatan))
                <li>{{ $qrisCatatan }}</li>
            @endif
        </ul>
        <label class="qris-terms-ack">
            <input type="checkbox" id="qrisAckCheckbox">
            <span>Saya memahami dan menyetujui ketentuan transaksi di atas.</span>
        </label>
    </div>

    {{-- Baris tombol: Tampilkan QRIS | Saya Sudah Bayar --}}
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn-primary" id="openQrisModalBtn" {{ empty($qrisImage) ? 'disabled' : '' }}>
            Tampilkan QRIS
        </button>

        @if(!$existingOrder)
            <form method="POST" action="{{ route('payment.create-order') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn-primary">Saya Sudah Bayar</button>
            </form>
        @endif
    </div>

    @if(empty($qrisImage))
        <div class="alert-error" style="margin-top: 12px;">
            Kode QRIS belum tersedia di database.
        </div>
    @endif

    <p style="font-size:12px; color:#9bb0da; text-align:center; margin-top:10px;">
        Bisa dibayar via GoPay, OVO, DANA, ShopeePay, m-Banking, dll
    </p>
</div>

{{-- Modal QRIS — sekarang hanya gambar + tutup, tanpa ketentuan --}}
<div class="qris-modal" id="qrisModal" aria-hidden="true">
    <div class="qris-modal-card" role="dialog" aria-modal="true" aria-labelledby="qrisModalTitle">
        <div class="qris-modal-head">
            <h4 id="qrisModalTitle">Pembayaran QRIS</h4>
            <button type="button" class="qris-modal-close" id="closeQrisModalBtn" aria-label="Tutup popup">x</button>
        </div>

        <p>Nominal transaksi: <strong>Rp {{ number_format($price, 0, ',', '.') }}</strong></p>

        @if(!empty($qrisImage))
            <div class="qris-modal-figure">
                <img src="{{ $qrisImage }}" alt="QRIS">
            </div>
        @endif

        <div class="qris-modal-actions">
            <button type="button" class="qris-modal-close" id="closeQrisModalBtnBottom">Tutup</button>
        </div>
    </div>
</div>

{{-- Card 2: Upload Bukti — tampil hanya jika order sudah ada & status perlu aksi --}}
@if($existingOrder)
<div class="qris-card">
    <h3>2. Upload Bukti Pembayaran</h3>
    <p>Order ID: <strong>{{ $existingOrder->order_id }}</strong></p>
    <p>Status:
        <span class="badge-{{ $existingOrder->status }}">
            {{ ucfirst($existingOrder->status) }}
        </span>
    </p>

    @if($existingOrder->status === 'pending')
        <form method="POST"
              action="{{ route('payment.upload-bukti', $existingOrder->order_id) }}"
              enctype="multipart/form-data">
            @csrf
            <label>Upload Bukti Transfer (screenshot)</label>
            <input type="file" name="bukti_pembayaran" accept="image/*" required>
            @error('bukti_pembayaran')
                <small style="color:#ffb7af">{{ $message }}</small>
            @enderror
            <button type="submit" class="btn-primary">Upload Bukti</button>
        </form>
    @elseif($existingOrder->status === 'waiting')
        <div class="alert-info">
            Bukti pembayaran sudah diterima. Menunggu konfirmasi admin.
        </div>
        @if($existingOrder->bukti_pembayaran)
            <img src="{{ asset('storage/' . $existingOrder->bukti_pembayaran) }}"
                 alt="Bukti" style="width:200px; margin-top:8px; border-radius:8px;">
        @endif
    @elseif($existingOrder->status === 'success')
        <div class="alert-success">
            Pembayaran berhasil dikonfirmasi.
        </div>
    @elseif($existingOrder->status === 'rejected')
        <div class="alert-error">
            Pembayaran ditolak. Alasan: {{ $existingOrder->catatan }}
        </div>
        <form method="POST"
              action="{{ route('payment.upload-bukti', $existingOrder->order_id) }}"
              enctype="multipart/form-data">
            @csrf
            <label>Upload Ulang Bukti</label>
            <input type="file" name="bukti_pembayaran" accept="image/*" required>
            <button type="submit" class="btn-primary">Upload Ulang</button>
        </form>
    @endif
</div>
@endif

        <div class="qris-card">
            <h3 class="qris-summary-title">Status Pembayaran Saat Ini</h3>
            <p class="qris-summary-sub">{{ $currentSummary[0] }}</p>
            <p class="qris-summary-sub">{{ $currentSummary[1] }}</p>
        </div>

        <div class="qris-card">
            <h3 class="qris-divider-title">Riwayat Pembayaran</h3>

            @if($orders->count())
                <div class="qris-table-wrap">
                    <table class="qris-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Bukti</th>
                                <th>Catatan Admin</th>
                                <th>Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                @php
                                    $orderStatusKey = strtolower((string) $order->status);
                                @endphp
                                <tr>
                                    <td>{{ $order->order_id }}</td>
                                    <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge-{{ $orderStatusKey }}">
                                            {{ $statusLabels[$orderStatusKey] ?? ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>
                                    @if($order->bukti_pembayaran)
                                        <a href="{{ asset('storage/' . $order->bukti_pembayaran) }}" target="_blank" rel="noopener noreferrer" class="pa-proof-link">
                                            liat bukti
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                    <td>
                                        @if($order->catatan)
                                            <p class="qris-note">{{ $order->catatan }}</p>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ optional($order->created_at)->format('d M Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="qris-paginate">{{ $orders->links() }}</div>
            @else
                <p class="qris-empty">Belum ada riwayat pembayaran.</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var statusText = document.getElementById('live-status-text');
            var stageElements = document.querySelectorAll('.ph-stage[data-stage]');
            var flow = ['order-created', 'verification', 'processing', 'network-clearing', 'settlement'];
            var pollerId = null;
            var isFetching = false;
            var statusToStage = {
                pending: 'processing',
                waiting: 'network-clearing',
                success: 'settlement',
                rejected: 'verification'
            };

            function updateTracker(status) {
                var currentStatus = (status || '').toLowerCase();
                var activeStage = statusToStage[currentStatus] || '';
                var activeIndex = flow.indexOf(activeStage);

                stageElements.forEach(function (stageElement) {
                    var stage = stageElement.getAttribute('data-stage');
                    var isActive = stage === activeStage;
                    var isDone = activeIndex !== -1 && flow.indexOf(stage) < activeIndex;
                    var isFailed = currentStatus === 'rejected' && stage === 'verification';

                    stageElement.classList.toggle('active', isActive);
                    stageElement.classList.toggle('done', isDone);
                    stageElement.classList.toggle('failed', isFailed);

                    var statusNode = stageElement.querySelector('.ph-stage-state');
                    if (statusNode) {
                        if (isFailed) {
                            statusNode.textContent = 'Ditolak';
                        } else if (isActive) {
                            statusNode.textContent = 'Active Stage';
                        } else if (isDone) {
                            statusNode.textContent = 'Completed';
                        } else {
                            statusNode.textContent = 'Pending';
                        }
                    }
                });
            }

            function fetchLatestStatus() {
                if (isFetching) {
                    return;
                }

                isFetching = true;
                fetch('{{ route('payment.latest-status') }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!statusText) {
                            return;
                        }

                        if (!data.has_order) {
                            statusText.textContent = 'Belum ada transaksi untuk ditrack.';
                            updateTracker('');
                            return;
                        }

                        statusText.textContent = 'Tracking ' + data.order_id + ' • Status ' + data.status_label;
                        updateTracker(data.status);
                    })
                    .catch(function () {
                        // Keep current state when polling fails.
                    })
                    .finally(function () {
                        isFetching = false;
                    });
            }

            function startPolling() {
                if (pollerId !== null) {
                    return;
                }

                fetchLatestStatus();
                pollerId = setInterval(fetchLatestStatus, 10000);
            }

            function stopPolling() {
                if (pollerId === null) {
                    return;
                }

                clearInterval(pollerId);
                pollerId = null;
            }

            function refreshPollingState() {
                var isVisible = document.visibilityState === 'visible';
                var isOnline = navigator.onLine;

                if (isVisible && isOnline) {
                    startPolling();
                } else {
                    stopPolling();
                }
            }

            refreshPollingState();

            document.addEventListener('visibilitychange', refreshPollingState);
            window.addEventListener('focus', refreshPollingState);
            window.addEventListener('blur', refreshPollingState);
            window.addEventListener('online', refreshPollingState);
            window.addEventListener('offline', refreshPollingState);

            var qrisModal = document.getElementById('qrisModal');
            var openQrisModalBtn = document.getElementById('openQrisModalBtn');
            var closeQrisModalBtn = document.getElementById('closeQrisModalBtn');
            var closeQrisModalBtnBottom = document.getElementById('closeQrisModalBtnBottom');

            function closeQrisModal() {
                if (!qrisModal) return;
                qrisModal.classList.remove('show');
                qrisModal.setAttribute('aria-hidden', 'true');
            }

            function openQrisModal() {
                if (!qrisModal || !openQrisModalBtn || openQrisModalBtn.disabled) return;
                qrisModal.classList.add('show');
                qrisModal.setAttribute('aria-hidden', 'false');
            }

            if (openQrisModalBtn) {
                openQrisModalBtn.addEventListener('click', openQrisModal);
            }

            if (closeQrisModalBtn) {
                closeQrisModalBtn.addEventListener('click', closeQrisModal);
            }

            if (closeQrisModalBtnBottom) {
                closeQrisModalBtnBottom.addEventListener('click', closeQrisModal);
            }

            if (qrisModal) {
                qrisModal.addEventListener('click', function (event) {
                    if (event.target === qrisModal) {
                        closeQrisModal();
                    }
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeQrisModal();
                }
            });
        });
    </script>
@endpush
