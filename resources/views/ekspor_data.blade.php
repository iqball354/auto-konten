@extends('layout.main')

@section('title', 'Ekspor Data')

@section('content')
    @php
        $selectedFormat = request('format', 'excel');
    @endphp

    <style>
        .exp-page {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 16px;
        }

        .exp-left,
        .exp-right {
            display: grid;
            gap: 14px;
            align-content: start;
        }

        .exp-head {
            margin-bottom: 4px;
        }

        .exp-head h1 {
            margin: 0;
            font-size: clamp(36px, 4vw, 46px);
            line-height: 1.02;
            color: #eef4ff;
            font-weight: 700;
        }

        .exp-head p {
            margin: 6px 0 0;
            color: #96abd1;
            font-size: 14px;
            max-width: 650px;
        }

        .exp-card {
            background: linear-gradient(180deg, rgba(18, 27, 44, 0.96), rgba(12, 19, 33, 0.96));
            border: 1px solid rgba(157, 178, 228, 0.14);
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(3, 8, 20, 0.4);
        }

        .exp-config {
            padding: 16px;
        }

        .exp-title {
            margin: 0 0 12px;
            color: #e8f0ff;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.1;
        }

        .exp-subtitle {
            margin: 0 0 12px;
            color: #a4b6d8;
            font-size: 15px;
        }

        .exp-kicker {
            margin: 0 0 10px;
            color: #7e96bf;
            letter-spacing: 0.16em;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .type-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .type-item {
            border-radius: 11px;
            border: 1px solid rgba(140, 166, 219, 0.2);
            background: rgba(31, 43, 68, 0.72);
            color: #d9e6ff;
            padding: 12px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .type-item.active {
            border-color: rgba(166, 195, 255, 0.8);
            box-shadow: inset 0 0 0 1px rgba(166, 195, 255, 0.25);
        }

        .type-item input {
            display: none;
        }

        .exp-grid-two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 10px;
        }

        .field-wrap {
            display: grid;
            gap: 6px;
        }

        .field-wrap label {
            color: #7e96bf;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 700;
        }

        .field-wrap select,
        .field-wrap input {
            border: 0;
            border-bottom: 1px solid rgba(131, 155, 205, 0.5);
            background: rgba(36, 49, 74, 0.55);
            color: #ecf4ff;
            border-radius: 9px 9px 0 0;
            padding: 12px 10px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
        }

        .history-wrap {
            padding: 14px;
        }

        .history-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .history-head h3 {
            margin: 0;
            color: #ebf2ff;
            font-size: 24px;
        }

        .history-head a {
            text-decoration: none;
            color: #9bb6e7;
            font-size: 11px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 11px 8px;
            border-bottom: 1px solid rgba(83, 108, 158, 0.2);
            text-align: left;
            color: #d7e4ff;
            font-size: 13px;
            vertical-align: middle;
        }

        .history-table th {
            color: #768fb9;
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .st {
            display: inline-flex;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 10px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border: 1px solid;
            font-weight: 700;
        }

        .st-success {
            color: #a4ffd7;
            border-color: rgba(102, 223, 176, 0.45);
            background: rgba(39, 112, 85, 0.28);
        }

        .st-failed {
            color: #ffb7af;
            border-color: rgba(255, 144, 136, 0.45);
            background: rgba(120, 56, 52, 0.28);
        }

        .format-card {
            padding: 14px;
        }

        .format-title {
            margin: 0 0 10px;
            color: #7f96be;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 700;
        }

        .format-option {
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 10px;
            border: 1px solid rgba(133, 161, 216, 0.2);
            background: rgba(35, 47, 73, 0.72);
            color: #e5efff;
            padding: 11px;
            cursor: pointer;
            margin-bottom: 8px;
        }

        .format-option input {
            display: none;
        }

        .format-option.active {
            border-color: rgba(161, 192, 255, 0.85);
            box-shadow: inset 0 0 0 1px rgba(161, 192, 255, 0.24);
        }

        .format-dot {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            display: grid;
            place-items: center;
            font-size: 11px;
        }

        .dot-excel {
            background: rgba(54, 225, 139, 0.2);
            color: #52ffae;
        }

        .dot-pdf {
            background: rgba(255, 109, 109, 0.2);
            color: #ff9595;
        }

        .dot-csv {
            background: rgba(181, 191, 214, 0.22);
            color: #d2def6;
        }

        .format-name {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.1;
        }

        .format-note {
            margin: 2px 0 0;
            color: #8ca3cc;
            font-size: 11px;
        }

        .exp-btn-main {
            width: 100%;
            margin-top: 8px;
            border-radius: 10px;
            border: 0;
            background: linear-gradient(180deg, #a2c3ff, #6697ff);
            color: #0f2450;
            font-family: inherit;
            font-size: 22px;
            font-weight: 700;
            padding: 13px 10px;
            cursor: pointer;
        }

        .exp-est {
            margin: 7px 0 0;
            color: #8ea5cf;
            font-size: 11px;
            text-align: center;
        }

        .mini-card {
            padding: 16px;
            min-height: 140px;
            display: grid;
            align-content: end;
            background:
                radial-gradient(circle at 80% 20%, rgba(85, 122, 216, 0.14), transparent 34%),
                linear-gradient(180deg, rgba(16, 30, 52, 0.9), rgba(10, 21, 38, 0.9));
        }

        .mini-card h4 {
            margin: 0 0 6px;
            color: #e8f2ff;
            font-size: 20px;
        }

        .mini-card p {
            margin: 0;
            color: #9eb2d7;
            font-size: 13px;
            line-height: 1.45;
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

        .flash.warn {
            border: 1px solid rgba(255, 214, 128, 0.35);
            background: rgba(120, 90, 35, 0.35);
            color: #ffdba3;
        }

        @media (max-width: 1180px) {
            .exp-page {
                grid-template-columns: 1fr;
            }

            .exp-right {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 820px) {
            .type-grid,
            .exp-grid-two,
            .exp-right {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section>
        <div class="exp-head">
            <h1>Ekspor Data</h1>
            <p>Unduh laporan performa dan data akun Anda dalam berbagai format untuk analisis mendalam.</p>
        </div>

        @if(session('success')) <div class="flash success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="flash error">{{ session('error') }}</div> @endif
        @error('status') <div class="flash error">{{ $message }}</div> @enderror
        @error('akun_id') <div class="flash error">{{ $message }}</div> @enderror
        @if(now()->day >= 29)
            <div class="flash warn">Menjelang akhir bulan, ekspor data sekarang agar tidak hilang saat riwayat postingan dibersihkan otomatis.</div>
        @endif

        <div class="exp-page">
            <div class="exp-left">
                <article class="exp-card exp-config">
                    <p class="exp-kicker"><i class="fas fa-sliders-h"></i> Konfigurasi Ekspor</p>

                    <form method="GET" action="{{ route('ekspor_data') }}" id="filterForm">
                        <p class="exp-kicker" style="margin-top:0;">Tipe Data</p>
                        <div class="type-grid">
                            <label class="type-item active">
                                <input type="radio" name="tipe_data" value="postingan" checked>
                                <i class="far fa-copy"></i> Postingan
                            </label>
                        </div>

                        <div class="exp-grid-two">
                            <div class="field-wrap">
                                <label>Rentang Waktu</label>
                                <select name="periode" id="periodeSelect">
                                    <option value="7d" {{ $periode === '7d' ? 'selected' : '' }}>7 Hari Terakhir</option>
                                    <option value="30d" {{ $periode === '30d' ? 'selected' : '' }}>30 Hari Terakhir</option>
                                </select>
                            </div>

                            <div class="field-wrap">
                                <label>Target Akun</label>
                                <select name="akun_id">
                                    <option value="">Semua Akun Hubung</option>
                                    @foreach($akunList as $akun)
                                        <option value="{{ $akun->id }}" {{ (string) $akunId === (string) $akun->id ? 'selected' : '' }}>
                                            {{ ucfirst($akun->platform) }} - {{ $akun->username ?: $akun->platform_user_id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="tipe_data" value="postingan">
                    </form>
                </article>

                <article class="exp-card history-wrap">
                    <div class="history-head">
                        <h3>List Postingan</h3>
                        <a href="{{ route('ekspor_data') }}">Lihat Semua</a>
                    </div>

                    <div style="overflow:auto;">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Judul Konten</th>
                                    <th>Platform</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($riwayat as $item)
                                    @php
                                        $platformText = is_array($item->platform_targets)
                                            ? implode(', ', array_map('ucfirst', $item->platform_targets))
                                            : '-';
                                    @endphp
                                    <tr>
                                        <td>{{ \Illuminate\Support\Str::limit($item->caption ?? 'Tanpa Caption', 48) }}</td>
                                        <td>{{ $platformText }}</td>
                                        <td>
                                            <span class="st {{ $item->status === 'published' ? 'st-success' : ($item->status === 'failed' ? 'st-failed' : '') }}">
                                                {{ strtoupper($item->status ?? '-') }}
                                            </span>
                                        </td>
                                        <td>{{ optional($item->created_at)->format('M d, Y') ?? '-' }}</td>
                                        <td>
                                            @php
                                                $postLog = $postLogs[$item->id] ?? null;
                                                $platformUrl = null;
                                                
                                                if ($item->status === 'published' && $postLog && !empty($postLog->platform_post_id)) {
                                                    $platformPostId = $postLog->platform_post_id;
                                                    $platforms = is_array($item->platform_targets) ? $item->platform_targets : [];
                                                    $platform = !empty($platforms) ? reset($platforms) : 'facebook';
                                                    
                                                    if ($platform === 'instagram') {
                                                        $platformUrl = "https://www.instagram.com/p/{$platformPostId}/";
                                                    } else {
                                                        $platformUrl = "https://www.facebook.com/{$platformPostId}/";
                                                    }
                                                }
                                            @endphp

                                            @if($platformUrl)
                                                <a href="{{ $platformUrl }}" style="color:#a7bee8;text-decoration:none;font-size:12px;" target="_blank" rel="noopener noreferrer">Lihat di Platform</a>
                                            @else
                                                <span style="color:#a7bee8;opacity:0.6;font-size:12px;">Detail</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" style="text-align:center;color:#8ca0c6;">Belum ada data postingan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:10px;">{{ $riwayat->withQueryString()->links() }}</div>
                </article>
            </div>

            <div class="exp-right">
                <article class="exp-card format-card">
                    <p class="format-title">Pilih Format</p>

                    <form id="exportForm" method="POST" action="{{ route('ekspor_data.excel') }}">
                        @csrf
                        <input type="hidden" name="status" id="expStatus" value="{{ $status }}">
                        <input type="hidden" name="akun_id" id="expAkunId" value="{{ $akunId }}">
                        <input type="hidden" name="tipe_data" id="expTipeData" value="postingan">
                        <input type="hidden" name="format" id="expFormat" value="{{ $selectedFormat }}">

                        <label class="format-option {{ $selectedFormat === 'excel' ? 'active' : '' }}">
                            <input type="radio" name="format_picker" value="excel" {{ $selectedFormat === 'excel' ? 'checked' : '' }}>
                            <span class="format-dot dot-excel"><i class="fas fa-table"></i></span>
                            <span>
                                <p class="format-name">Excel (.xlsx)</p>
                                <p class="format-note">Optimalkan untuk MS Excel</p>
                            </span>
                        </label>

                        <label class="format-option {{ $selectedFormat === 'pdf' ? 'active' : '' }}">
                            <input type="radio" name="format_picker" value="pdf" {{ $selectedFormat === 'pdf' ? 'checked' : '' }}>
                            <span class="format-dot dot-pdf"><i class="far fa-file-pdf"></i></span>
                            <span>
                                <p class="format-name">PDF Report</p>
                                <p class="format-note">Visual dan siap cetak</p>
                            </span>
                        </label>

                        <button type="button" class="exp-btn-main" id="btnRunExport">Mulai Ekspor</button>
                        <p class="exp-est">Estimasi waktu: ~45 detik</p>
                    </form>
                </article>

                <article class="exp-card mini-card">
                    <h4>Butuh kustomisasi?</h4>
                    <p>Hubungi tim data kami untuk format ekspor khusus atau integrasi langsung API.</p>
                </article>
            </div>
        </div>
    </section>

    <script>
        (function () {
            var filterForm = document.getElementById('filterForm');
            var exportForm = document.getElementById('exportForm');
            var periodeSelect = document.getElementById('periodeSelect');
            var btnRunExport = document.getElementById('btnRunExport');
            var expAkunId = document.getElementById('expAkunId');
            var expTipeData = document.getElementById('expTipeData');
            var expFormat = document.getElementById('expFormat');

            function updateFormatVisual() {
                document.querySelectorAll('.format-option').forEach(function (item) {
                    var input = item.querySelector('input[type="radio"]');
                    item.classList.toggle('active', input.checked);
                });
            }

            function syncExportHidden() {
                var akunSelect = filterForm.querySelector('select[name="akun_id"]');
                var formatChecked = exportForm.querySelector('input[name="format_picker"]:checked');

                expAkunId.value = akunSelect ? akunSelect.value : '';
                expTipeData.value = 'postingan';
                expFormat.value = formatChecked ? formatChecked.value : 'excel';
            }

            function handlePeriodChange() {
                filterForm.submit();
            }

            filterForm.querySelector('select[name="akun_id"]').addEventListener('change', function () {
                filterForm.submit();
            });

            periodeSelect.addEventListener('change', handlePeriodChange);

            exportForm.querySelectorAll('input[name="format_picker"]').forEach(function (input) {
                input.addEventListener('change', function () {
                    updateFormatVisual();
                    syncExportHidden();
                });
            });

            btnRunExport.addEventListener('click', function () {
                syncExportHidden();

                var format = expFormat.value;
                if (format === 'pdf') {
                    exportForm.action = "{{ route('ekspor_data.pdf') }}";
                    exportForm.target = '_blank';
                } else {
                    exportForm.action = "{{ route('ekspor_data.excel') }}";
                    exportForm.target = '_self';
                }

                exportForm.submit();
            });

            updateFormatVisual();
            syncExportHidden();
        })();
    </script>
@endsection
