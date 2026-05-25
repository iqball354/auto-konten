@extends('layout.main')

@section('title', 'Kelola User')

@section('content')
    <style>
        .ku-head {
            margin-bottom: 14px;
        }

        .ku-head h1 {
            margin: 0;
            font-size: clamp(30px, 4vw, 42px);
            color: #eef4ff;
            font-weight: 700;
        }

        .ku-head p {
            margin: 6px 0 0;
            color: #90a4cc;
        }

        .ku-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .ku-card,
        .ku-table-wrap {
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.95), rgba(10, 18, 31, 0.95));
            border: 1px solid rgba(162, 183, 255, 0.12);
            border-radius: 14px;
        }

        .ku-card {
            padding: 12px;
        }

        .ku-card p {
            margin: 0;
            color: #89a0cb;
            font-size: 12px;
        }

        .ku-card b {
            display: block;
            margin-top: 6px;
            color: #eef4ff;
            font-size: 28px;
        }

        .ku-filter {
            padding: 12px;
            display: grid;
            grid-template-columns: 1fr 180px;
            gap: 8px;
            border-bottom: 1px solid rgba(87, 111, 164, 0.18);
        }

        .ku-filter input,
        .ku-filter select {
            width: 100%;
            border-radius: 9px;
            border: 1px solid rgba(150, 175, 230, 0.2);
            background: rgba(21, 32, 52, 0.78);
            color: #e7efff;
            padding: 9px 10px;
            font-family: inherit;
        }

        .ku-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ku-table th,
        .ku-table td {
            padding: 11px 12px;
            border-bottom: 1px solid rgba(87, 111, 164, 0.18);
            color: #dce6fb;
            font-size: 13px;
            text-align: left;
            vertical-align: middle;
        }

        .ku-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #7992bf;
        }

        .ku-badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            border: 1px solid;
            font-weight: 700;
        }

        .ku-badge.active { color: #a4ffd7; border-color: rgba(102, 223, 176, 0.45); background: rgba(39, 112, 85, 0.28); }
        .ku-badge.off { color: #ffb7af; border-color: rgba(255, 144, 136, 0.45); background: rgba(120, 56, 52, 0.28); }
        .ku-badge.admin { color: #ffccaa; border-color: rgba(255, 186, 128, 0.45); background: rgba(118, 75, 36, 0.28); }
        .ku-badge.user { color: #aecdff; border-color: rgba(134, 171, 255, 0.45); background: rgba(49, 77, 134, 0.28); }
        .ku-badge.verified { color: #a4ffd7; border-color: rgba(102, 223, 176, 0.45); background: rgba(39, 112, 85, 0.28); }
        .ku-badge.unverified { color: #ffd7a8; border-color: rgba(255, 196, 117, 0.45); background: rgba(119, 86, 39, 0.28); }

        .ku-form {
            display: grid;
            grid-template-columns: 1fr 1fr 150px 130px;
            gap: 6px;
            align-items: center;
        }

        .ku-form input,
        .ku-form select {
            width: 100%;
            border-radius: 8px;
            border: 1px solid rgba(150, 175, 230, 0.2);
            background: rgba(21, 32, 52, 0.78);
            color: #e7efff;
            padding: 7px 9px;
            font-family: inherit;
            font-size: 12px;
        }

        .ku-btn {
            border-radius: 8px;
            border: 1px solid rgba(133, 168, 255, 0.28);
            background: rgba(37, 54, 87, 0.86);
            color: #dce9ff;
            padding: 7px 10px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .ku-btn.verify {
            border-color: rgba(103, 221, 176, 0.35);
            background: rgba(40, 109, 84, 0.35);
            color: #a5ffd8;
            margin-top: 6px;
        }

        .ku-btn.verify:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        @media (max-width: 1100px) {
            .ku-stats {
                grid-template-columns: 1fr;
            }

            .ku-filter {
                grid-template-columns: 1fr;
            }

            .ku-form {
                grid-template-columns: 1fr;
            }
        }

        /* Styles untuk Social Accounts Section */
        .sa-head {
            margin: 20px 0 14px;
        }

        .sa-head h2 {
            margin: 0;
            font-size: clamp(24px, 3vw, 32px);
            color: #eef4ff;
            font-weight: 600;
        }

        .sa-head p {
            margin: 4px 0 0;
            color: #90a4cc;
            font-size: 13px;
        }

        .sa-table-wrap {
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.95), rgba(10, 18, 31, 0.95));
            border: 1px solid rgba(162, 183, 255, 0.12);
            border-radius: 14px;
            overflow: hidden;
        }

        .sa-table-note {
            margin: 0;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(87, 111, 164, 0.18);
            font-size: 11px;
            color: #8ea3cc;
            background: rgba(24, 35, 56, 0.38);
        }

        .sa-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .sa-table th,
        .sa-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(87, 111, 164, 0.18);
            color: #dce6fb;
            font-size: 12px;
            text-align: left;
            vertical-align: top;
        }

        .sa-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #7992bf;
            white-space: nowrap;
        }

        .sa-user-name {
            margin: 0;
            color: #eef4ff;
            font-size: 13px;
            font-weight: 600;
        }

        .sa-user-email {
            margin: 2px 0 0;
            color: #8ea3cc;
            font-size: 11px;
        }

        .sa-token {
            max-width: 300px;
            word-break: break-all;
            color: #ffd7a8;
            font-family: 'Courier New', monospace;
            font-size: 11px;
        }

        .sa-token-cell {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .sa-token-toggle {
            width: fit-content;
            padding: 4px 8px;
            font-size: 10px;
        }

        .sa-delete-btn {
            margin-top: 6px;
            border-color: rgba(255, 133, 125, 0.38);
            background: rgba(113, 45, 42, 0.3);
            color: #ffb4ac;
        }

        .sa-delete-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .sa-id {
            word-break: break-all;
            color: #aecdff;
            font-family: 'Courier New', monospace;
            font-size: 11px;
        }

        .sa-platform {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .sa-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sa-status.active {
            background: rgba(39, 112, 85, 0.28);
            color: #a4ffd7;
            border: 1px solid rgba(102, 223, 176, 0.45);
        }

        .sa-status.inactive {
            background: rgba(120, 56, 52, 0.28);
            color: #ffb7af;
            border: 1px solid rgba(255, 144, 136, 0.45);
        }

        .sa-empty {
            text-align: center;
            padding: 40px 20px;
            color: #8ca0c6;
        }

        .sa-empty p {
            margin: 0;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .sa-table {
                min-width: 980px;
            }
        }

        .or-head {
            margin: 20px 0 14px;
        }

        .or-head h2 {
            margin: 0;
            font-size: clamp(24px, 3vw, 32px);
            color: #eef4ff;
            font-weight: 600;
        }

        .or-head p {
            margin: 4px 0 0;
            color: #90a4cc;
            font-size: 13px;
        }

        .or-table-wrap {
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.95), rgba(10, 18, 31, 0.95));
            border: 1px solid rgba(162, 183, 255, 0.12);
            border-radius: 14px;
            overflow: hidden;
        }

        .or-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        .or-table th,
        .or-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(87, 111, 164, 0.18);
            color: #dce6fb;
            font-size: 12px;
            text-align: left;
            vertical-align: top;
        }

        .or-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #7992bf;
            white-space: nowrap;
        }

        .or-token {
            max-width: 280px;
            word-break: break-all;
            color: #ffd7a8;
            font-family: 'Courier New', monospace;
            font-size: 11px;
        }

        .or-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            border: 1px solid;
            font-weight: 700;
        }

        .or-status.pending {
            color: #ffd7a8;
            border-color: rgba(255, 196, 117, 0.45);
            background: rgba(119, 86, 39, 0.28);
        }

        .or-status.pages-ready,
        .or-status.approved {
            color: #a4ffd7;
            border-color: rgba(102, 223, 176, 0.45);
            background: rgba(39, 112, 85, 0.28);
        }

        .or-status.other {
            color: #aecdff;
            border-color: rgba(134, 171, 255, 0.45);
            background: rgba(49, 77, 134, 0.28);
        }

        .pa-head {
            margin: 20px 0 14px;
        }

        .pa-head h2 {
            margin: 0;
            font-size: clamp(24px, 3vw, 32px);
            color: #eef4ff;
            font-weight: 600;
        }

        .pa-head p {
            margin: 4px 0 0;
            color: #90a4cc;
            font-size: 13px;
        }

        .pa-table-wrap {
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.95), rgba(10, 18, 31, 0.95));
            border: 1px solid rgba(162, 183, 255, 0.12);
            border-radius: 14px;
            overflow: hidden;
        }

        .pa-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        .pa-table th,
        .pa-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(87, 111, 164, 0.18);
            color: #dce6fb;
            font-size: 12px;
            text-align: left;
            vertical-align: top;
        }

        .pa-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #7992bf;
            white-space: nowrap;
        }

        .pa-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            border: 1px solid;
            font-weight: 700;
        }

        .pa-status.pending {
            color: #ffd7a8;
            border-color: rgba(255, 196, 117, 0.45);
            background: rgba(119, 86, 39, 0.28);
        }

        .pa-status.waiting {
            color: #aecdff;
            border-color: rgba(134, 171, 255, 0.45);
            background: rgba(49, 77, 134, 0.28);
        }

        .pa-status.success {
            color: #a4ffd7;
            border-color: rgba(102, 223, 176, 0.45);
            background: rgba(39, 112, 85, 0.28);
        }

        .pa-status.rejected {
            color: #ffb7af;
            border-color: rgba(255, 144, 136, 0.45);
            background: rgba(120, 56, 52, 0.28);
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

        .pa-note {
            max-width: 240px;
            word-break: break-word;
            color: #ffb8b1;
        }

        .pa-actions {
            display: grid;
            gap: 6px;
            min-width: 220px;
        }

        .pa-reject-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 6px;
        }

        .pa-reject-form input {
            border-radius: 8px;
            border: 1px solid rgba(150, 175, 230, 0.2);
            background: rgba(21, 32, 52, 0.78);
            color: #e7efff;
            padding: 7px 9px;
            font-family: inherit;
            font-size: 12px;
        }

        .ku-btn.approve {
            border-color: rgba(103, 221, 176, 0.35);
            background: rgba(40, 109, 84, 0.35);
            color: #a5ffd8;
        }

        .ku-btn.reject {
            border-color: rgba(255, 133, 125, 0.38);
            background: rgba(113, 45, 42, 0.3);
            color: #ffb4ac;
        }

        .ku-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 120;
            background: rgba(4, 10, 20, 0.62);
        }

        .ku-modal-backdrop.show {
            display: block;
        }

        .ku-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 130;
            margin: auto;
            width: min(460px, calc(100% - 28px));
            height: fit-content;
            padding: 16px;
            border-radius: 14px;
            border: 1px solid rgba(162, 183, 255, 0.2);
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.98), rgba(10, 18, 31, 0.98));
        }

        .ku-modal.show {
            display: block;
        }

        .ku-modal p {
            color: #9bb1da;
            margin: 6px 0;
            font-size: 12px;
        }

        .ku-modal .ku-foot {
            margin-top: 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
    </style>

    <section>
        <div class="ku-head">
            <h1>Kelola User</h1>
            <p>Lihat daftar user, ubah profil dasar, dan atur status aktif akun.</p>
        </div>

        @if(session('success'))
            <div style="border:1px solid rgba(103,221,176,.35);background:rgba(40,109,84,.35);color:#a5ffd8;border-radius:10px;padding:10px 12px;margin-bottom:12px;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="border:1px solid rgba(255,164,157,.35);background:rgba(119,53,50,.35);color:#ffb8b1;border-radius:10px;padding:10px 12px;margin-bottom:12px;">
                {{ session('error') }}
            </div>
        @endif

        <div class="ku-stats">
            <article class="ku-card">
                <p>Total User</p>
                <b>{{ $totalUser }}</b>
            </article>
            <article class="ku-card">
                <p>User Aktif</p>
                <b>{{ $userActive }}</b>
            </article>
            <article class="ku-card">
                <p>User Nonaktif</p>
                <b>{{ $userNonactive }}</b>
            </article>
        </div>

        <article class="ku-table-wrap">
            <div class="ku-filter">
                <input type="text" id="user-search" placeholder="Cari nama atau email...">
                <select id="user-status-filter">
                    <option value="all">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="off">Nonaktif</option>
                </select>
            </div>

            <div style="overflow:auto;">
                <table class="ku-table" id="user-table">
                    <thead>
                        <tr>
                            <th>Peran</th>
                            <th>Detail User</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $effectiveActive = $user->effective_active ?? $user->is_active;
                            @endphp
                            <tr data-user-row data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}" data-status="{{ $effectiveActive ? 'active' : 'off' }}">
                                <td>
                                    <span class="ku-badge {{ $user->role === 'admin' ? 'admin' : 'user' }}">{{ $user->role }}</span>
                                </td>
                                <td>
                                    <div>
                                        <b style="display:block;color:#edf4ff">{{ $user->name }}</b>
                                        <small style="color:#8ea3cc">{{ $user->email }}</small>
                                        <small style="display:block;margin-top:4px;">
                                            <span class="ku-badge {{ $user->email_verified_at ? 'verified' : 'unverified' }}">
                                                {{ $user->email_verified_at ? 'gmail verified' : 'gmail belum verified' }}
                                            </span>
                                        </small>
                                        <small style="display:block;color:#7389b3">Gabung {{ \Carbon\Carbon::parse($user->created_at)->format('d M Y') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="ku-badge {{ $effectiveActive ? 'active' : 'off' }}">{{ $effectiveActive ? 'aktif' : 'nonaktif' }}</span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('kelola_user.update', $user->id) }}" class="ku-form" data-user-form>
                                        @csrf
                                        <input type="text" name="name" value="{{ $user->name }}" placeholder="Nama">
                                        <input type="email" name="email" value="{{ $user->email }}" placeholder="Email">
                                        <select name="is_active" data-current-status="{{ $effectiveActive ? 'active' : 'off' }}">
                                            <option value="1" {{ $effectiveActive ? 'selected' : '' }}>Aktif</option>
                                            <option value="0" {{ !$effectiveActive ? 'selected' : '' }}>Nonaktif</option>
                                        </select>
                                        <button type="submit" class="ku-btn">Simpan</button>
                                    </form>

                                    <form method="POST" action="{{ route('kelola_user.verify-gmail', $user->id) }}">
                                        @csrf
                                        <button type="submit" class="ku-btn verify" {{ $user->email_verified_at ? 'disabled' : '' }}>
                                            {{ $user->email_verified_at ? 'Gmail Sudah Verified' : 'Verifikasi Gmail' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center;color:#8ca0c6;">Tidak ada data user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section>
        <div class="pa-head">
            <h2>Konfirmasi Pembayaran User</h2>
            <p>Tabel ini menampilkan order pembayaran dari backend dan aksi konfirmasi/tolak oleh admin.</p>
        </div>

        <article class="pa-table-wrap">
            <div style="overflow:auto;">
                <table class="pa-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th>Bukti</th>
                            <th>Catatan</th>
                            <th>Dibuat</th>
                            <th>Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($paymentOrders ?? collect()) as $order)
                            @php
                                $orderStatus = strtolower((string) $order->status);
                                $canModerate = in_array($orderStatus, ['pending', 'waiting', 'rejected'], true);
                            @endphp
                            <tr data-filter-row data-name="{{ strtolower($order->user->name ?? '') }}" data-email="{{ strtolower($order->user->email ?? '') }}">
                                <td><span class="sa-id">{{ $order->order_id }}</span></td>
                                <td>
                                    <p class="sa-user-name">{{ $order->user->name ?? 'N/A' }}</p>
                                    <p class="sa-user-email">{{ $order->user->email ?? '-' }}</p>
                                </td>
                                <td>Rp {{ number_format((float) $order->total_price, 0, ',', '.') }}</td>
                                <td>
                                    <span class="pa-status {{ $orderStatus }}">{{ ucfirst($orderStatus) }}</span>
                                </td>
                                <td>
                                    @if($order->bukti_pembayaran)
                                        <a href="{{ asset('storage/' . $order->bukti_pembayaran) }}" target="_blank" rel="noopener noreferrer" class="pa-proof-link">
                                            bukti tf
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($order->catatan)
                                        <div class="pa-note">{{ $order->catatan }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i') : '-' }}</td>
                                <td>
                                    @if($canModerate)
                                        <div class="pa-actions">
                                            <form method="POST" action="{{ route('payment.konfirmasi', $order->order_id) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="ku-btn approve"
                                                    onclick="return confirm('Konfirmasi pembayaran ini?')"
                                                >
                                                    Konfirmasi
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('payment.tolak', $order->order_id) }}" class="pa-reject-form">
                                                @csrf
                                                <input type="text" name="catatan" placeholder="Alasan penolakan" required>
                                                <button
                                                    type="submit"
                                                    class="ku-btn reject"
                                                    onclick="return confirm('Tolak pembayaran ini?')"
                                                >
                                                    Tolak
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span style="color:#8ea3cc;">Tidak ada aksi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;color:#8ca0c6;">Belum ada order pembayaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <!-- Social Accounts Section -->
    <section>
        <div class="sa-head">
            <h2>User & Akun Sosial Media Terhubung</h2>
            <p>Pantau user dan akun media sosial yang sudah terhubung dengan aplikasi.</p>
        </div>

        @php
            $usersWithAccounts = $users->filter(function($user) {
                return $user->socialAccounts->count() > 0;
            });
            $socialAccountRows = $usersWithAccounts->flatMap(function($user) {
                return $user->socialAccounts->map(function($account) use ($user) {
                    return [
                        'user' => $user,
                        'account' => $account,
                    ];
                });
            });
        @endphp

        @if($socialAccountRows->count() > 0)
            <article class="sa-table-wrap">
                <p class="sa-table-note">Daftar ini menampilkan data akun sosial dari backend termasuk access token yang tersimpan.</p>
                <div style="overflow:auto;">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Platform</th>
                                <th>Username</th>
                                <th>Platform User ID</th>
                                <th>Page ID</th>
                                <th>Access Token</th>
                                <th>Status</th>
                                <th>Token Expired</th>
                                <th>Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($socialAccountRows as $row)
                                @php
                                    $user = $row['user'];
                                    $account = $row['account'];
                                    $accessToken = 'N/A';
                                    $maskedToken = 'N/A';
                                    $tokenPlaceholder = $account->platform === 'facebook' && $account->platform_user_id === 'pending_meta_token'
                                        ? 'Menunggu token admin'
                                        : 'N/A';
                                    $isPendingMetaToken = $account->platform === 'facebook' && $account->platform_user_id === 'pending_meta_token';

                                    if (!empty($account->access_token)) {
                                        try {
                                            $accessToken = decrypt($account->access_token);
                                        } catch (\Throwable $e) {
                                            $accessToken = $account->access_token;
                                        }

                                        if ($accessToken === '__PENDING_META_TOKEN__') {
                                            $accessToken = 'N/A';
                                        }

                                        $tokenLength = strlen((string) $accessToken);
                                        if ($tokenLength <= 12) {
                                            $visiblePart = substr((string) $accessToken, -4);
                                            $maskedToken = str_repeat('*', max(0, $tokenLength - 4)) . $visiblePart;
                                        } else {
                                            $maskedToken = substr((string) $accessToken, 0, 8) . '...' . substr((string) $accessToken, -6);
                                        }
                                    }
                                @endphp
                                <tr data-filter-row data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}">
                                    <td>
                                        <p class="sa-user-name">{{ $user->name }}</p>
                                        <p class="sa-user-email">{{ $user->email }}</p>
                                    </td>
                                    <td>
                                        <span class="sa-platform">
                                            <i class="fab fa-{{ strtolower($account->platform) }}"></i>
                                            {{ ucfirst($account->platform) }}
                                        </span>
                                    </td>
                                    <td>{{ $account->username ?: 'N/A' }}</td>
                                    <td>
                                        <span class="sa-id">
                                            {{ $isPendingMetaToken ? 'PENDING TOKEN' : ($account->platform_user_id ?: 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="sa-id">
                                            {{ $isPendingMetaToken ? 'Menunggu page' : ($account->page_id ?: 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="sa-token-cell">
                                            <div
                                                class="sa-token"
                                                data-token
                                                data-token-masked="{{ $maskedToken }}"
                                                data-token-full="{{ $accessToken !== 'N/A' ? e($accessToken) : '' }}"
                                            >{{ $accessToken !== 'N/A' ? $maskedToken : $tokenPlaceholder }}</div>
                                            @if($accessToken !== 'N/A')
                                                <button type="button" class="ku-btn sa-token-toggle" data-token-toggle data-state="masked">Show</button>
                                            @endif
                                        </div>
                                        @if($isPendingMetaToken)
                                            <div style="margin-top:6px;color:#ffd9a8;font-size:11px;">
                                                Token manual dari user sudah tersimpan, menunggu pemilihan Facebook Page.
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="sa-status {{ $isPendingMetaToken ? 'inactive' : ($account->is_active ? 'active' : 'inactive') }}">
                                            <i class="fas fa-circle" style="font-size: 6px;"></i>
                                            {{ $isPendingMetaToken ? 'Pending Page' : ($account->is_active ? 'Aktif' : 'Nonaktif') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($account->token_expires_at)
                                            <span style="color: {{ \Carbon\Carbon::parse($account->token_expires_at)->isPast() ? '#ffb7af' : '#a4ffd7' }};">
                                                {{ \Carbon\Carbon::parse($account->token_expires_at)->format('d M Y, H:i') }}
                                            </span>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $account->created_at ? \Carbon\Carbon::parse($account->created_at)->format('d M Y, H:i') : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        @else
            <div style="background: linear-gradient(180deg, rgba(16, 25, 42, 0.95), rgba(10, 18, 31, 0.95)); border: 1px solid rgba(162, 183, 255, 0.12); border-radius: 14px;">
                <div class="sa-empty">
                    <i class="fas fa-link" style="font-size: 32px; margin-bottom: 12px; opacity: 0.5; display: block;"></i>
                    <p>Belum ada user yang menghubungkan akun sosial media mereka.</p>
                </div>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            (function () {
                var searchInput = document.getElementById('user-search');
                var statusFilter = document.getElementById('user-status-filter');
                var userRows = document.querySelectorAll('[data-user-row]');
                var integratedRows = document.querySelectorAll('[data-filter-row]');
                var tokenButtons = document.querySelectorAll('[data-token-toggle]');

                function runFilter() {
                    var keyword = (searchInput.value || '').toLowerCase().trim();
                    var status = statusFilter.value;

                    userRows.forEach(function (row) {
                        var name = row.getAttribute('data-name') || '';
                        var email = row.getAttribute('data-email') || '';
                        var rowStatus = row.getAttribute('data-status') || 'all';

                        var matchKeyword = !keyword || name.includes(keyword) || email.includes(keyword);
                        var matchStatus = status === 'all' || rowStatus === status;

                        row.style.display = (matchKeyword && matchStatus) ? '' : 'none';
                    });

                    integratedRows.forEach(function (row) {
                        var name = row.getAttribute('data-name') || '';
                        var email = row.getAttribute('data-email') || '';
                        var matchKeyword = !keyword || name.includes(keyword) || email.includes(keyword);

                        row.style.display = matchKeyword ? '' : 'none';
                    });
                }

                if (searchInput) searchInput.addEventListener('input', runFilter);
                if (statusFilter) statusFilter.addEventListener('change', runFilter);

                tokenButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        var tokenEl = button.parentElement.querySelector('[data-token]');
                        if (!tokenEl) return;

                        var masked = tokenEl.getAttribute('data-token-masked') || 'N/A';
                        var full = tokenEl.getAttribute('data-token-full') || 'N/A';
                        var state = button.getAttribute('data-state') || 'masked';

                        if (state === 'masked') {
                            tokenEl.textContent = full;
                            button.textContent = 'Hide';
                            button.setAttribute('data-state', 'full');
                        } else {
                            tokenEl.textContent = masked;
                            button.textContent = 'Show';
                            button.setAttribute('data-state', 'masked');
                        }
                    });
                });

                document.querySelectorAll('[data-user-form]').forEach(function (form) {
                    var statusSelect = form.querySelector('select[name="is_active"]');
                    var currentStatus = statusSelect ? statusSelect.getAttribute('data-current-status') : 'off';

                    form.addEventListener('submit', function (event) {
                        if (!statusSelect) return;
                        var nextValue = statusSelect.value;

                        if (currentStatus !== 'active' && nextValue === '1') {
                            var proceed = confirm('User belum melakukan pembayaran. Jangan aktifkan secara manual jika belum ada validasi pembayaran. Lanjutkan?');
                            if (!proceed) {
                                event.preventDefault();
                            }
                        }
                    });
                });
            })();
        </script>
    @endpush

@endsection
