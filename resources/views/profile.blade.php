@extends('layout.main')

@section('title', 'Profile')

@section('content')
    <style>
        .pf-wrap {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 14px;
        }

        .pf-card {
            background: linear-gradient(180deg, rgba(16, 25, 42, 0.95), rgba(10, 18, 31, 0.95));
            border: 1px solid rgba(162, 183, 255, 0.12);
            border-radius: 14px;
            padding: 14px;
        }

        .pf-title {
            margin: 0 0 10px;
            color: #e7efff;
            font-size: 16px;
            font-weight: 700;
        }

        .pf-hero {
            text-align: center;
        }

        .pf-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #9ec0ff, #6f9fff);
            color: #11203f;
            font-size: 32px;
            font-weight: 700;
            overflow: hidden;
        }

        .pf-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pf-name {
            margin: 0;
            color: #eef4ff;
            font-size: 20px;
            font-weight: 700;
        }

        .pf-role {
            margin: 3px 0 0;
            color: #8ea3cc;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .pf-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 12px;
        }

        .pf-stat {
            border: 1px solid rgba(143, 167, 223, 0.2);
            border-radius: 10px;
            padding: 8px;
            text-align: center;
            background: rgba(20, 30, 48, 0.65);
        }

        .pf-stat b {
            display: block;
            color: #e7efff;
            font-size: 16px;
        }

        .pf-stat span {
            font-size: 11px;
            color: #93a7cf;
        }

        .pf-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .pf-list li {
            border: 1px solid rgba(143, 167, 223, 0.2);
            border-radius: 10px;
            padding: 9px;
            background: rgba(20, 30, 48, 0.65);
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
            color: #dce7ff;
            font-size: 13px;
        }

        .pf-badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            border: 1px solid;
            font-weight: 700;
        }

        .pf-badge.active { color: #a4ffd7; border-color: rgba(102, 223, 176, 0.45); background: rgba(39, 112, 85, 0.28); }
        .pf-badge.warn { color: #ffd9a8; border-color: rgba(255, 196, 117, 0.45); background: rgba(123, 88, 40, 0.28); }
        .pf-badge.off { color: #ffb7af; border-color: rgba(255, 144, 136, 0.45); background: rgba(120, 56, 52, 0.28); }

        .pf-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .pf-field {
            margin-bottom: 10px;
        }

        .pf-field.full {
            grid-column: 1 / -1;
        }

        .pf-field label {
            display: block;
            margin-bottom: 6px;
            color: #9bb1da;
            font-size: 12px;
        }

        .pf-field input {
            width: 100%;
            border-radius: 9px;
            border: 1px solid rgba(150, 175, 230, 0.2);
            background: rgba(21, 32, 52, 0.78);
            color: #e7efff;
            padding: 9px 10px;
            font-family: inherit;
        }

        .pf-dropzone {
            border: 1px dashed rgba(152, 179, 240, 0.35);
            border-radius: 10px;
            background: rgba(20, 32, 52, 0.62);
            padding: 14px;
            text-align: center;
            color: #d8e6ff;
            cursor: pointer;
            transition: border-color .18s ease, background .18s ease;
        }

        .pf-dropzone.active {
            border-color: rgba(121, 169, 255, 0.9);
            background: rgba(36, 56, 92, 0.55);
        }

        .pf-dropzone small {
            display: block;
            color: #8ea3cc;
            margin-top: 6px;
            font-size: 11px;
        }

        .pf-hidden-input {
            display: none;
        }

        .pf-qris-preview {
            margin-top: 10px;
            text-align: center;
        }

        .pf-qris-preview img {
            width: 220px;
            max-width: 100%;
            border-radius: 10px;
            border: 1px solid rgba(154, 180, 238, 0.25);
            background: rgba(15, 24, 41, 0.9);
        }

        .pf-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid rgba(133, 168, 255, 0.28);
            background: rgba(37, 54, 87, 0.86);
            color: #dce9ff;
            padding: 10px 14px;
            text-decoration: none;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .pf-subscription-actions {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .pf-subscription-actions .pf-btn {
            flex: 0 0 auto;
        }

        .pf-btn.primary {
            color: #11254e;
            background: linear-gradient(180deg, #9dc0ff, #709fff);
            border-color: transparent;
        }

        .pf-flash {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .pf-flash.success { border: 1px solid rgba(103, 221, 176, 0.35); background: rgba(40, 109, 84, 0.35); color: #a5ffd8; }
        .pf-flash.error { border: 1px solid rgba(255, 164, 157, 0.35); background: rgba(119, 53, 50, 0.35); color: #ffb8b1; }

        .pf-modal {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 14px;
            background: rgba(4, 9, 20, 0.72);
            backdrop-filter: blur(4px);
        }

        .pf-modal.show {
            display: flex;
        }

        .pf-modal-card {
            width: 100%;
            max-width: 520px;
            border-radius: 14px;
            border: 1px solid rgba(154, 180, 238, 0.28);
            background: linear-gradient(180deg, rgba(17, 27, 44, 0.98), rgba(10, 18, 31, 0.98));
            box-shadow: 0 22px 48px rgba(0, 0, 0, 0.42);
            padding: 16px;
        }

        .pf-modal-title {
            margin: 0 0 10px;
            color: #eef4ff;
            font-size: 18px;
            font-weight: 700;
        }

        .pf-modal-price {
            margin: 0 0 10px;
            color: #bcd2ff;
            font-size: 14px;
        }

        .pf-modal-price b {
            color: #eef4ff;
            font-size: 17px;
        }

        .pf-modal-list {
            margin: 0;
            padding-left: 18px;
            color: #a3b7dd;
            display: grid;
            gap: 6px;
            font-size: 13px;
        }

        .pf-modal-actions {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        @media (max-width: 1000px) {
            .pf-wrap {
                grid-template-columns: 1fr;
            }

            .pf-form-grid {
                grid-template-columns: 1fr;
            }

            .pf-subscription-actions {
                flex-wrap: wrap;
            }
        }
    </style>

    <section>
        @if(session('success')) <div class="pf-flash success">{{ session('success') }}</div> @endif
        @if($errors->any())
            <div class="pf-flash error">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <div class="pf-wrap">
            <aside>
                <article class="pf-card pf-hero">
                    @php
                        $avatarUrl = auth()->user()->avatar ? asset('storage/' . ltrim(auth()->user()->avatar, '/')) : null;
                    @endphp
                    <div class="pf-avatar">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="avatar">
                        @else
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        @endif
                    </div>
                    <h2 class="pf-name">{{ auth()->user()->name }}</h2>
                    <p class="pf-role">{{ auth()->user()->role ?? 'user' }}</p>
                    <div class="pf-stats">
                        <div class="pf-stat"><b>{{ $statPost['total'] ?? 0 }}</b><span>Total Post</span></div>
                        <div class="pf-stat"><b>{{ $statPost['berhasil'] ?? 0 }}</b><span>Berhasil</span></div>
                        <div class="pf-stat"><b>{{ $statPost['gagal'] ?? 0 }}</b><span>Gagal</span></div>
                    </div>
                </article>

                <article class="pf-card" style="margin-top:12px;">
                    <h3 class="pf-title">Akun Terhubung</h3>
                    <ul class="pf-list">
                        @forelse($sosial_accounts as $akun)
                            <li>
                                <div>
                                    <b style="display:block;color:#edf4ff">{{ $akun->username ?? '-' }}</b>
                                    <small style="color:#8ea3cc">{{ ucfirst($akun->platform) }}</small>
                                </div>
                                <span class="pf-badge {{ $akun->is_active ? 'active' : 'off' }}">{{ $akun->is_active ? 'aktif' : 'nonaktif' }}</span>
                            </li>
                        @empty
                            <li>Tidak ada akun terhubung.</li>
                        @endforelse
                    </ul>
                </article>

                @if((auth()->user()->role ?? null) !== 'admin')
                    <article class="pf-card" style="margin-top:12px;">
                        <h3 class="pf-title">Langganan</h3>
                        @if($subscription)
                            <ul class="pf-list">
                                <li><span>Paket</span><b>{{ strtoupper($subscription->plan ?? '-') }}</b></li>
                                <li><span>Status</span><span class="pf-badge {{ ($subscription->status ?? '-') === 'active' ? 'active' : 'warn' }}">{{ $subscription->status ?? '-' }}</span></li>
                                <li><span>Berakhir</span><b>{{ optional($subscription->expired_at)->format('d M Y') ?? '-' }}</b></li>
                            </ul>
                            @if(($subscription->status ?? null) === 'active')
                                <p style="color:#92f2c8;margin:6px 0 0;">Akun anda sudah aktif.</p>
                            @endif
                        @else
                            <p style="color:#92a8d2;margin:0;">akun anda masih belum jadi langganan aktif.</p>
                        @endif

                        <div class="pf-subscription-actions">
                            <a href="{{ route('payment.qris') }}" class="pf-btn primary">payment</a>
                        </div>
                    </article>
                @endif
            </aside>

            <div>
                <article class="pf-card" style="margin-bottom:12px;">
    <h3 class="pf-title">Perbarui Profil</h3>
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="pf-form-grid">
            <div class="pf-field full">
                <label>Nama</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required>
            </div>
            <div class="pf-field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
            </div>
            <div class="pf-field">
                <label>Avatar</label>
                <input type="file" name="avatar" accept="image/*">
            </div>
            @if((auth()->user()->role ?? null) === 'admin')
                <div class="pf-field full">
                    <label>Gambar QRIS</label>
                    <input id="qris_image" class="pf-hidden-input" type="file" name="qris_image" accept="image/*">
                    <div id="qrisDropzone" class="pf-dropzone">
                        <b>Drag & drop gambar QRIS di sini</b>
                        <small>atau klik area ini untuk pilih file (JPG, PNG, WEBP, max 4MB)</small>
                        <small id="qrisFileName">Belum ada file dipilih.</small>
                    </div>
                    <small style="color:#8ea3cc; font-size:11px; display:block; margin-top:6px;">
                        Gambar akan disimpan ke storage, lalu path-nya disimpan di database.
                    </small>
                </div>
            @endif
        </div>

        {{-- Baris tombol --}}
        <div style="display:flex; align-items:center; gap:10px; margin-top:8px;">
            <button class="pf-btn primary" type="submit">Simpan Profil</button>
            @if((auth()->user()->role ?? null) === 'admin' && !empty($qrisPreview))
                <button type="button" id="showQrisModalBtn" class="pf-btn primary">Tampilkan QRIS</button>
            @endif
        </div>
    </form>
</article>

                <article class="pf-card">
                    <h3 class="pf-title">Ubah Password</h3>
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        <div class="pf-form-grid">
                            <div class="pf-field full">
                                <label>Password Lama</label>
                                <input type="password" name="password_lama" required>
                            </div>
                            <div class="pf-field">
                                <label>Password Baru</label>
                                <input type="password" name="password_baru" required>
                            </div>
                            <div class="pf-field">
                                <label>Konfirmasi Password Baru</label>
                                <input type="password" name="password_baru_confirmation" required>
                            </div>
                        </div>
                        <button class="pf-btn" type="submit">Perbarui Password</button>
                    </form>
                </article>
            </div>
        </div>
    </section>

    <!-- Modal QRIS -->
    @if((auth()->user()->role ?? null) === 'admin' && !empty($qrisPreview))
        <div class="pf-modal" id="qrisModal">
            <div class="pf-modal-card" style="max-width: 420px; padding: 20px; text-align: center;">
                <h2 class="pf-modal-title">QRIS Payment</h2>
                <div style="margin: 20px 0;">
                    <img id="qrisPreview" src="{{ $qrisPreview }}" alt="Preview QRIS" style="max-width: 280px; border-radius: 12px; border: 1px solid rgba(154, 180, 238, 0.25); background: rgba(15, 24, 41, 0.9);">
                </div>
                <p style="color: #a3b7dd; font-size: 12px; margin: 0 0 16px;">Scan QR Code ini untuk melakukan pembayaran</p>
                <div class="pf-modal-actions">
                    <button type="button" id="closeQrisModalBtn" class="pf-btn">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    @if((auth()->user()->role ?? null) === 'admin')
        <script>
            (function () {
                // ============================================
                // File Upload & QRIS Preview Handling
                // ============================================
                var input = document.getElementById('qris_image');
                var dropzone = document.getElementById('qrisDropzone');
                var fileName = document.getElementById('qrisFileName');
                var preview = document.getElementById('qrisPreview');

                // File upload handlers
                if (input && dropzone && fileName && preview) {
                    var setFile = function (file) {
                        if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                            return;
                        }

                        var dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        input.files = dataTransfer.files;
                        fileName.textContent = file.name;

                        var reader = new FileReader();
                        reader.onload = function (e) {
                            preview.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    };

                    dropzone.addEventListener('click', function () {
                        input.click();
                    });

                    input.addEventListener('change', function () {
                        if (input.files && input.files[0]) {
                            setFile(input.files[0]);
                        }
                    });

                    ['dragenter', 'dragover'].forEach(function (eventName) {
                        dropzone.addEventListener(eventName, function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            dropzone.classList.add('active');
                        });
                    });

                    ['dragleave', 'drop'].forEach(function (eventName) {
                        dropzone.addEventListener(eventName, function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            dropzone.classList.remove('active');
                        });
                    });

                    dropzone.addEventListener('drop', function (e) {
                        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                            setFile(e.dataTransfer.files[0]);
                        }
                    });
                }

                // ============================================
                // QRIS Modal Handling
                // ============================================
                var qrisModal = document.getElementById('qrisModal');
                var showQrisBtn = document.getElementById('showQrisModalBtn');
                var closeQrisBtn = document.getElementById('closeQrisModalBtn');

                if (qrisModal && showQrisBtn && closeQrisBtn) {
                    // Show modal on button click
                    showQrisBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        qrisModal.classList.add('show');
                    });

                    // Close modal on close button click
                    closeQrisBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        qrisModal.classList.remove('show');
                    });

                    // Close modal on outside click
                    qrisModal.addEventListener('click', function (e) {
                        if (e.target === qrisModal) {
                            qrisModal.classList.remove('show');
                        }
                    });
                }
            })();
        </script>
    @endif

@endsection
