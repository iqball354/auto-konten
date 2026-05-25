<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Ekspor Data</title>
    <style>
        :root {
            --text: #1f2937;
            --muted: #6b7280;
            --line: #d1d5db;
            --bg-soft: #f9fafb;
            --ok: #065f46;
            --bad: #991b1b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 24px;
            color: var(--text);
            font-family: "Segoe UI", Tahoma, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }

        .header {
            margin-bottom: 16px;
            border-bottom: 1px solid var(--line);
            padding-bottom: 12px;
        }

        .title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }

        .subtitle {
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px;
            background: var(--bg-soft);
        }

        .card-label {
            display: block;
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 3px;
        }

        .card-value {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .section-title {
            margin: 20px 0 8px;
            font-size: 14px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid var(--line);
            padding: 6px 8px;
            vertical-align: top;
            text-align: left;
            font-size: 11px;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }

        .ok { color: var(--ok); font-weight: 700; }
        .bad { color: var(--bad); font-weight: 700; }

        .footer {
            margin-top: 16px;
            color: var(--muted);
            font-size: 10px;
            text-align: right;
        }

        @media print {
            body { margin: 12mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Laporan Ekspor Data</h1>
        <div class="subtitle">
            Periode: {{ $dari->format('d M Y') }} - {{ $sampai->format('d M Y') }}
            | Dibuat: {{ now()->format('d M Y H:i') }}
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <span class="card-label">Total Postingan</span>
            <p class="card-value">{{ number_format($totalPost) }}</p>
        </div>
        <div class="card">
            <span class="card-label">Berhasil</span>
            <p class="card-value ok">{{ number_format($berhasil) }}</p>
        </div>
        <div class="card">
            <span class="card-label">Gagal</span>
            <p class="card-value bad">{{ number_format($gagal) }}</p>
        </div>
        <div class="card">
            <span class="card-label">Success Rate</span>
            <p class="card-value">{{ $successRate }}%</p>
        </div>
    </div>

    <h2 class="section-title">Ringkasan Per Platform</h2>
    <table>
        <thead>
            <tr>
                <th>Platform</th>
                <th>Total</th>
                <th>Berhasil</th>
                <th>Gagal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($perPlatform as $platform => $summary)
                <tr>
                    <td>{{ ucfirst($platform) }}</td>
                    <td>{{ $summary['total'] ?? 0 }}</td>
                    <td class="ok">{{ $summary['berhasil'] ?? 0 }}</td>
                    <td class="bad">{{ $summary['gagal'] ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Tidak ada data pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section-title">Riwayat Eksekusi</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Waktu</th>
                <th>Platform</th>
                <th>Status</th>
                <th>Konten</th>
                <th>Akun</th>
                <th>Error</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($riwayat as $index => $log)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ optional($log->executed_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->schedule->akunSosial->platform ?? '-' }}</td>
                    <td class="{{ $log->status === 'success' ? 'ok' : 'bad' }}">{{ strtoupper($log->status ?? '-') }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($log->schedule->post->caption ?? '-', 80) }}</td>
                    <td>{{ $log->schedule->akunSosial->username ?? '-' }}</td>
                    <td>{{ $log->error_message ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Tidak ada riwayat eksekusi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dibuat otomatis oleh sistem.
    </div>
</body>
</html>
