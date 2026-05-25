@extends('layout.main')

@section('title', 'Dashboard')

@section('content')
    @php
        $statusMap = [
            'draft' => ['label' => 'Draft', 'class' => 'st-processing'],
            'scheduled' => ['label' => 'Terjadwal', 'class' => 'st-pending'],
            'published' => ['label' => 'Berhasil', 'class' => 'st-done'],
            'failed' => ['label' => 'Gagal', 'class' => 'st-failed'],
        ];
    @endphp

    <style>
        .dash-title {
            margin: 0;
            font-size: clamp(34px, 4vw, 46px);
            font-weight: 700;
            color: #eef4ff;
            letter-spacing: 0.01em;
        }

        .dash-subtitle {
            margin: 6px 0 0;
            color: #91a2c5;
            font-size: 15px;
        }

        .dash-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .add-post-btn {
            margin-top: 12px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 0;
            border-radius: 10px;
            padding: 11px 16px;
            text-decoration: none;
            font-weight: 600;
            color: #13264f;
            background: linear-gradient(180deg, #9dc0ff, #709fff);
            box-shadow: 0 12px 24px rgba(50, 88, 170, 0.3);
        }

        .add-post-btn i {
            font-size: 13px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .stat-card {
            background: linear-gradient(180deg, rgba(18, 28, 45, 0.92), rgba(12, 21, 36, 0.92));
            border: 1px solid rgba(162, 183, 255, 0.12);
            border-radius: 14px;
            padding: 14px 14px 13px;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .stat-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        .ic-blue { background: rgba(112, 155, 255, 0.18); color: #9fc0ff; }
        .ic-green { background: rgba(72, 196, 158, 0.16); color: #4ddcb3; }
        .ic-red { background: rgba(248, 127, 127, 0.17); color: #ffb3ad; }
        .ic-orange { background: rgba(255, 181, 112, 0.17); color: #ffc58a; }

        .stat-kicker {
            margin: 0;
            font-size: 10px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #82a4e6;
        }

        .stat-label {
            margin: 0;
            font-size: 11px;
            text-transform: uppercase;
            color: #8ea1c8;
            letter-spacing: 0.12em;
        }

        .stat-value {
            margin: 6px 0 4px;
            font-size: 38px;
            line-height: 1;
            color: #f1f6ff;
            font-weight: 700;
        }

        .stat-foot {
            font-size: 11px;
            color: #8ea1c8;
        }

        .stat-foot.green { color: #4ddcb3; }
        .stat-foot.red { color: #ffaaa3; }
        .stat-foot.orange { color: #ffc58a; }

        .queue-card {
            background: linear-gradient(180deg, rgba(16, 26, 44, 0.95), rgba(10, 18, 31, 0.95));
            border: 1px solid rgba(162, 183, 255, 0.12);
            border-radius: 16px;
            overflow: hidden;
        }

        .queue-head {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(130, 154, 211, 0.12);
        }

        .queue-head h2 {
            margin: 0;
            font-size: 28px;
            color: #edf3ff;
            font-weight: 700;
        }

        .queue-tools {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .queue-tools form {
            display: inline-flex;
            gap: 8px;
        }

        .queue-tools select,
        .queue-tools button {
            height: 36px;
            border-radius: 10px;
            border: 1px solid rgba(145, 169, 223, 0.18);
            background: rgba(23, 34, 55, 0.88);
            color: #c8d6f4;
            font-family: inherit;
            font-size: 12px;
            padding: 0 12px;
        }

        .queue-tools button {
            background: rgba(37, 53, 85, 0.95);
            cursor: pointer;
            font-weight: 600;
        }

        .queue-table-wrap {
            overflow-x: auto;
        }

        .queue-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .queue-table th {
            text-align: left;
            padding: 12px 20px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #7186ad;
            border-bottom: 1px solid rgba(130, 154, 211, 0.12);
        }

        .queue-table td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(91, 116, 170, 0.14);
            color: #dce6fb;
            font-size: 13px;
            vertical-align: middle;
        }

        .content-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .thumb {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: cover;
            background: rgba(118, 149, 217, 0.2);
            border: 1px solid rgba(136, 164, 222, 0.26);
            flex-shrink: 0;
        }

        .caption-title {
            margin: 0;
            color: #f1f6ff;
            font-weight: 500;
        }

        .caption-sub {
            margin: 3px 0 0;
            color: #7f95bf;
            font-size: 11px;
        }

        .platform-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #9fb4de;
            font-size: 12px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 700;
            border: 1px solid;
        }

        .st-pending { color: #ffcf9a; border-color: rgba(255, 191, 117, 0.5); background: rgba(125, 83, 35, 0.25); }
        .st-processing { color: #a6c6ff; border-color: rgba(133, 168, 255, 0.5); background: rgba(52, 77, 126, 0.25); }
        .st-done { color: #9dffd8; border-color: rgba(88, 216, 170, 0.54); background: rgba(32, 99, 78, 0.24); }
        .st-failed { color: #ffb6ae; border-color: rgba(255, 141, 133, 0.5); background: rgba(122, 53, 49, 0.25); }

        .queue-foot {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #768ab0;
            font-size: 12px;
        }

        .page-links {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }

        .page-links a,
        .page-links span {
            color: #8ea4ce;
            text-decoration: none;
        }

        .dash-footer {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid rgba(95, 125, 198, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #6f82a8;
            font-size: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .dash-footer h4 {
            margin: 0;
            font-size: 26px;
            color: #d9e6ff;
        }

        .dash-footer nav {
            display: inline-flex;
            gap: 16px;
        }

        .dash-footer nav a {
            color: #8397be;
            text-decoration: none;
        }

        @media (max-width: 1280px) {
            .cards-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dash-header {
                flex-direction: column;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }

            .queue-head {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>

    <section>
        <header class="dash-header">
            <div>
                <h1 class="dash-title">Dashboard Utama</h1>
                <p class="dash-subtitle">Pantau performa distribusi konten otomatis Anda secara real-time.</p>
            </div>
            <a href="{{ route('postingan') }}" class="add-post-btn">
                <i class="fas fa-plus-circle"></i>
                <span>Tambah Postingan</span>
            </a>
        </header>

        <div class="cards-grid">
            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-icon ic-blue"><i class="ni ni-chart-bar-32"></i></span>
                    <p class="stat-kicker">Bulan Ini</p>
                </div>
                <p class="stat-label">Total Postingan</p>
                <p class="stat-value">{{ number_format($totalBulanIni) }}</p>
                <p class="stat-foot">{{ $totalChangeLabel }}</p>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-icon ic-green"><i class="ni ni-check-bold"></i></span>
                    <p class="stat-kicker">Success Rate</p>
                </div>
                <p class="stat-label">Postingan Berhasil</p>
                <p class="stat-value">{{ number_format($totalBerhasil) }}</p>
                <p class="stat-foot green">{{ $successRateLabel }}</p>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-icon ic-red"><i class="ni ni-fat-remove"></i></span>
                    <p class="stat-kicker">Attention</p>
                </div>
                <p class="stat-label">Postingan Gagal</p>
                <p class="stat-value">{{ number_format($totalGagal) }}</p>
                <p class="stat-foot red">Perlu review manual</p>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-icon ic-orange"><i class="ni ni-time-alarm"></i></span>
                    <p class="stat-kicker">Queued</p>
                </div>
                <p class="stat-label">Postingan Tunggu</p>
                <p class="stat-value">{{ number_format($totalTunggu) }}</p>
                <p class="stat-foot orange">Next 24 jam</p>
            </article>
        </div>

        <section class="queue-card">
            <div class="queue-head">
                <h2>Riwayat Postingan</h2>
                <div class="queue-tools">
                    <form method="GET" action="{{ route('dashboard') }}">
                        <select name="filter_status">
                            <option value="">Filter</option>
                            <option value="draft" {{ request('filter_status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="scheduled" {{ request('filter_status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="published" {{ request('filter_status') === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="failed" {{ request('filter_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>

                        <select name="urutan">
                            <option value="desc" {{ $urutan === 'desc' ? 'selected' : '' }}>Urutkan: Terbaru</option>
                            <option value="asc" {{ $urutan === 'asc' ? 'selected' : '' }}>Urutkan: Terlama</option>
                        </select>

                        <button type="submit">Terapkan</button>
                    </form>
                </div>
            </div>

            <div class="queue-table-wrap">
                <table class="queue-table">
                    <thead>
                        <tr>
                            <th>Judul Konten</th>
                            <th>Platform</th>
                            <th>Waktu Terjadwal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($antrean as $item)
                            @php
                                $firstMedia = $item->media->first();
                                $thumb = $firstMedia?->file_url ?: ($firstMedia?->file_path ? asset('storage/' . ltrim($firstMedia->file_path, '/')) : null);
                                $isVideoThumb = $firstMedia && (
                                    ($firstMedia->media_type ?? null) === 'video'
                                    || \Illuminate\Support\Str::startsWith((string) ($firstMedia->mime_type ?? ''), 'video/')
                                );
                                $caption = \Illuminate\Support\Str::limit($item->caption ?? 'Tanpa judul', 38);
                                $targets = collect($item->platform_targets ?? [])->filter()->map(fn($p) => strtolower((string) $p))->values();
                                $platform = $targets->first();
                                if (!$platform) {
                                    $platform = strtolower(optional($item->jadwal->first()?->akunSosial)->platform ?? '-');
                                }
                                $scheduledAt = optional($item->jadwal->first())->scheduled_at;
                                $meta = $statusMap[$item->status] ?? ['label' => strtoupper($item->status), 'class' => 'st-processing'];
                                $isCleanedMedia = $item->status === 'published' && !$firstMedia;
                            @endphp

                            <tr>
                                <td>
                                    <div class="content-cell">
                                        @if($thumb)
                                            @if($isVideoThumb)
                                                <video class="thumb" muted playsinline preload="metadata">
                                                    <source src="{{ $thumb }}" type="{{ $firstMedia->mime_type ?? 'video/mp4' }}">
                                                </video>
                                            @else
                                                <img class="thumb" src="{{ $thumb }}" alt="media">
                                            @endif
                                        @else
                                            <span class="thumb"></span>
                                        @endif
                                        <div>
                                            <p class="caption-title">{{ $caption }}</p>
                                            @if($isCleanedMedia)
                                                <p class="caption-sub">Media sudah dibersihkan setelah publish sukses.</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="platform-tag">
                                        @if($platform === 'instagram')
                                            <i class="fab fa-instagram" style="color:#ff6fb5"></i>
                                        @elseif($platform === 'facebook')
                                            <i class="fab fa-facebook" style="color:#6fa1ff"></i>
                                        @else
                                            <i class="fas fa-globe"></i>
                                        @endif
                                        {{ ucfirst($platform ?: '-') }}
                                    </span>
                                </td>
                                <td>
                                    @if($scheduledAt)
                                        <strong>{{ $scheduledAt->translatedFormat('d M, H:i') }}</strong><br>
                                        <small style="color:#7e92b9;">WIB</small>
                                    @else
                                        <strong>{{ $item->created_at->translatedFormat('d M, H:i') }}</strong><br>
                                        <small style="color:#7e92b9;">Dibuat</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-pill {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                </td>
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
                                        <a href="{{ $platformUrl }}" style="color:#8ea6d8;text-decoration:none;" target="_blank" rel="noopener noreferrer">Lihat di Platform</a>
                                    @else
                                        <span style="color:#8ea6d8;opacity:0.6;">Detail</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center;color:#8ca0c6;">Belum ada riwayat postingan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="queue-foot">
                <span>Menampilkan {{ $antrean->count() }} dari {{ $antrean->total() }} riwayat postingan</span>
                <div class="page-links">
                    {{ $antrean->withQueryString()->links() }}
                </div>
            </div>
        </section>

        <footer class="dash-footer">
            <div>
                <h4>Meta Automation Dashboard</h4>
                <p style="margin:4px 0 0;">(c) {{ date('Y') }} Meta Automation Dashboard - Quantum Executive Interface</p>
            </div>
            <nav>
                <a href="#">Privasi</a>
                <a href="#">Ketentuan</a>
                <a href="#">SLA Digital</a>
            </nav>
        </footer>
    </section>
@endsection
