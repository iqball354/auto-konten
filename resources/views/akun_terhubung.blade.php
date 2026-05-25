@extends('layout.main')

@section('title', 'Akun Terhubung')

@section('content')
    <style>
        .at-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }

        .at-title {
            margin: 0;
            font-size: clamp(30px, 4vw, 42px);
            color: #eef4ff;
            font-weight: 700;
        }

        .at-sub {
            margin: 6px 0 0;
            color: #90a4cc;
        }

        .at-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            border: 1px solid rgba(133, 168, 255, 0.28);
            color: #dce9ff;
            background: rgba(37, 54, 87, 0.86);
            font-weight: 600;
            cursor: pointer;
        }

        .at-banner,
        .at-table,
        .at-modal {
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.95), rgba(10, 18, 31, 0.95));
            border: 1px solid rgba(162, 183, 255, 0.12);
            border-radius: 14px;
        }

        .at-banner {
            padding: 14px 16px;
            margin-bottom: 14px;
            color: #9eb1d8;
        }

        .at-banner h4 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #d6e4ff;
        }

        .flash {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .flash.success {
            border: 1px solid rgba(103, 221, 176, 0.35);
            background: rgba(40, 109, 84, 0.35);
            color: #a5ffd8;
        }

        .flash.error {
            border: 1px solid rgba(255, 164, 157, 0.35);
            background: rgba(119, 53, 50, 0.35);
            color: #ffb8b1;
        }

        .at-table {
            overflow: hidden;
        }

        .at-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .at-table th,
        .at-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(87, 111, 164, 0.18);
            font-size: 13px;
            color: #dce6fb;
            text-align: left;
        }

        .at-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #7992bf;
        }

        .platform-pill,
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            border: 1px solid;
            font-weight: 700;
        }

        .pf-ig { color: #ffc0e0; border-color: rgba(243, 120, 184, 0.45); background: rgba(143, 52, 101, 0.28); }
        .pf-fb { color: #abc8ff; border-color: rgba(109, 157, 255, 0.45); background: rgba(44, 73, 140, 0.28); }

        .st-valid { color: #a4ffd7; border-color: rgba(102, 223, 176, 0.45); background: rgba(39, 112, 85, 0.28); }
        .st-soon { color: #ffd9a8; border-color: rgba(255, 196, 117, 0.45); background: rgba(123, 88, 40, 0.28); }
        .st-expired { color: #ffb7af; border-color: rgba(255, 144, 136, 0.45); background: rgba(120, 56, 52, 0.28); }
        .st-error { color: #ffd2f2; border-color: rgba(251, 166, 230, 0.45); background: rgba(119, 49, 101, 0.28); }

        .actions {
            display: inline-flex;
            gap: 8px;
        }

        .btn-mini {
            border: 1px solid rgba(145, 169, 223, 0.2);
            background: rgba(20, 30, 48, 0.74);
            color: #c8d8f8;
            border-radius: 8px;
            padding: 6px 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            font-family: inherit;
        }

        .btn-mini.danger {
            color: #ffb4ac;
            border-color: rgba(255, 133, 125, 0.38);
            background: rgba(113, 45, 42, 0.3);
        }

        .btn-mini:hover {
            opacity: 0.92;
        }

        .empty {
            text-align: center;
            padding: 28px;
            color: #8aa0c9;
        }

        .at-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 120;
            margin: auto;
            width: min(440px, calc(100% - 28px));
            height: fit-content;
            padding: 16px;
        }

        .at-modal.show {
            display: block;
        }

        .at-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 110;
            background: rgba(4, 10, 20, 0.62);
        }

        .at-modal-backdrop.show {
            display: block;
        }

        .at-field {
            margin-bottom: 10px;
        }

        .at-field label {
            display: block;
            margin-bottom: 6px;
            color: #9bb1da;
            font-size: 12px;
        }

        .at-field input,
        .at-field select {
            width: 100%;
            border-radius: 9px;
            border: 1px solid rgba(150, 175, 230, 0.2);
            background: rgba(21, 32, 52, 0.78);
            color: #e7efff;
            padding: 9px 10px;
            font-family: inherit;
        }

        .at-foot {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 10px;
        }

        .fb-page-list {
            display: grid;
            gap: 10px;
            max-height: 56vh;
            overflow-y: auto;
            margin-top: 8px;
            padding-right: 2px;
        }

        .fb-page-item {
            border: 1px solid rgba(150, 175, 230, 0.22);
            border-radius: 10px;
            padding: 10px;
            background: rgba(17, 29, 48, 0.62);
        }

        .fb-page-name {
            margin: 0;
            color: #e7efff;
            font-weight: 700;
        }

        .fb-page-meta {
            margin: 4px 0 8px;
            color: #9bb1da;
            font-size: 12px;
        }

        .fb-ig-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(243, 120, 184, 0.45);
            background: rgba(143, 52, 101, 0.2);
            color: #ffc0e0;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .fb-ig-miss {
            color: #ffd9a8;
            font-size: 12px;
            margin-bottom: 8px;
        }
    </style>

    <section>
        <div class="at-header">
            <div>
                <h1 class="at-title">Akun Terhubung</h1>
                <p class="at-sub">Kelola akun sosial yang terhubung untuk proses otomatisasi konten.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a class="at-action" href="{{ route('meta.redirect') }}">
                    <i class="fab fa-facebook"></i>
                    <span>Hubungkan Meta</span>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="flash success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flash error">{{ session('error') }}</div>
        @endif
        @error('platform') <div class="flash error">{{ $message }}</div> @enderror
        @error('platform_user_id') <div class="flash error">{{ $message }}</div> @enderror
        @error('access_token') <div class="flash error">{{ $message }}</div> @enderror
        @error('long_lived_token') <div class="flash error">{{ $message }}</div> @enderror

        <article class="at-banner">
            <h4>Info Kebijakan</h4>
            Integrasi akun mengikuti kebijakan Meta. Setelah login Meta berhasil, sistem akan mengambil long-lived token, menyimpan token terenkripsi, lalu menampilkan daftar Facebook Page untuk dipilih sebelum akun dinyatakan terhubung. Sistem menjalankan pengecekan token harian dan otomatis refresh token yang akan kedaluwarsa dalam 7 hari. Jika refresh gagal atau izin dicabut, akun akan dinonaktifkan otomatis dan notifikasi dikirim ke user.
        </article>

        <article class="at-table">
            <table>
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Username</th>
                        <th>ID Platform</th>
                        <th>Status Token</th>
                        <th>Kadaluarsa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sosial_accounts as $akun)
                        @php
                            $platformClass = $akun->platform === 'instagram' ? 'pf-ig' : 'pf-fb';
                            $tokenClass = $akun->token_status === 'valid'
                                ? 'st-valid'
                                : ($akun->token_status === 'akan_expired'
                                    ? 'st-soon'
                                    : ($akun->token_status === 'error' ? 'st-error' : 'st-expired'));
                            $tokenText = $akun->token_status === 'valid'
                                ? 'Valid'
                                : ($akun->token_status === 'akan_expired'
                                    ? 'Akan Expired'
                                    : ($akun->token_status === 'error' ? 'Error' : 'Expired'));
                        @endphp
                        <tr>
                            <td><span class="platform-pill {{ $platformClass }}">{{ ucfirst($akun->platform) }}</span></td>
                            <td>{{ $akun->username ?? '-' }}</td>
                            <td style="color:#8ca2cb">{{ $akun->platform_user_id }}</td>
                            <td>
                                <span class="status-pill {{ $tokenClass }}" id="status-pill-{{ $akun->id }}">{{ $tokenText }}</span>
                            </td>
                            <td style="color:#8ca2cb">{{ optional($akun->token_expires_at)->format('d M Y H:i') ?? '-' }}</td>
                            <td>
                                <div class="actions">
                                    <form method="GET" action="{{ route('akun_terhubung.status', $akun->id) }}">
                                        <button class="btn-mini" type="submit">Cek Status Manual</button>
                                    </form>
                                    <form method="POST" action="{{ route('akun_terhubung.hapus', $akun->id) }}" onsubmit="return confirm('Putuskan akun ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-mini danger" type="submit">Putuskan</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">Belum ada akun terhubung.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </article>

        @if(!empty($pendingFacebookPages))
            <div class="at-modal-backdrop show" id="fb-page-backdrop"></div>
            <div class="at-modal show" id="fb-page-modal">
                <h3 style="margin:0 0 6px;color:#e7efff;">Pilih Facebook Page</h3>
                <p style="margin:0;color:#9bb1da;font-size:12px;">Login berhasil. Pilih Page yang ingin dihubungkan ke akun Anda.</p>

                <div class="fb-page-list">
                    @foreach($pendingFacebookPages as $page)
                        @php
                            $igAccount = $page['instagram_business_account'] ?? ($page['connected_instagram_account'] ?? null);
                        @endphp
                        <div class="fb-page-item">
                            <p class="fb-page-name">{{ $page['name'] }}</p>
                            <p class="fb-page-meta">Page ID: {{ $page['id'] }}</p>

                            @if(!empty($igAccount['id']))
                                <div class="fb-ig-chip">
                                    <i class="fab fa-instagram"></i>
                                    <span>
                                        IG Business: {{ $igAccount['username'] ?: $igAccount['id'] }}
                                    </span>
                                </div>
                            @else
                                <div class="fb-ig-miss">Belum terhubung ke Instagram Business Account.</div>
                            @endif

                            <form method="POST" action="{{ route('meta.save-page') }}">
                                @csrf
                                <input type="hidden" name="page_id" value="{{ $page['id'] }}">
                                <button type="submit" class="btn-mini">Pilih Page Ini</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            (function () {
                var openBtn = document.getElementById('btn-open-manual');
                var closeBtn = document.getElementById('btn-close-manual');
                var modal = document.getElementById('manual-modal');
                var backdrop = document.getElementById('manual-backdrop');
                var fbPageModal = document.getElementById('fb-page-modal');

                function setModal(open) {
                    if (!modal || !backdrop) return;
                    modal.classList.toggle('show', open);
                    backdrop.classList.toggle('show', open);
                }

                if (fbPageModal) {
                    setModal(false);
                }

                if (openBtn) openBtn.addEventListener('click', function () { setModal(true); });
                if (closeBtn) closeBtn.addEventListener('click', function () { setModal(false); });
                if (backdrop) backdrop.addEventListener('click', function () { setModal(false); });

                // Status check now uses a normal GET form so it works even if JS is blocked.
            })();
        </script>
    @endpush
@endsection
