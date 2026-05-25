@extends('layout.main')

@section('title', 'Notifikasi')

@section('content')
	@php
		$typeMap = [
			'posting_success' => ['icon' => 'fa-check', 'class' => 'tp-success', 'label' => 'Berhasil'],
			'posting_failed' => ['icon' => 'fa-triangle-exclamation', 'class' => 'tp-failed', 'label' => 'Gagal'],
			'account_connected' => ['icon' => 'fa-link', 'class' => 'tp-system', 'label' => 'Akun'],
			'gmail_verified' => ['icon' => 'fa-circle-check', 'class' => 'tp-success', 'label' => 'Gmail'],
			'payment_confirmed' => ['icon' => 'fa-receipt', 'class' => 'tp-system', 'label' => 'Pembayaran'],
			'token_expired' => ['icon' => 'fa-key', 'class' => 'tp-warning', 'label' => 'Token'],
			'system' => ['icon' => 'fa-bell', 'class' => 'tp-system', 'label' => 'Sistem'],
		];
	@endphp

	<style>
		.notif-page {
			display: grid;
			grid-template-columns: minmax(0, 1fr) 280px;
			gap: 16px;
		}

		.notif-left,
		.notif-right {
			display: grid;
			align-content: start;
			gap: 14px;
		}

		.notif-head {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 14px;
			margin-bottom: 2px;
		}

		.notif-head h1 {
			margin: 0;
			font-size: clamp(34px, 4vw, 46px);
			line-height: 1.03;
			color: #eef4ff;
			font-weight: 700;
		}

		.notif-head p {
			margin: 6px 0 0;
			color: #96abd1;
			font-size: 14px;
			max-width: 680px;
		}

		.btn-mark-all {
			border: 1px solid rgba(142, 170, 230, 0.26);
			border-radius: 10px;
			background: rgba(31, 43, 69, 0.74);
			color: #e2ecff;
			padding: 10px 13px;
			font-size: 12px;
			font-weight: 700;
			letter-spacing: 0.02em;
			cursor: pointer;
		}

		.btn-mark-all[disabled] {
			opacity: 0.55;
			cursor: not-allowed;
		}

		.notif-card {
			background: linear-gradient(180deg, rgba(18, 27, 44, 0.96), rgba(12, 19, 33, 0.96));
			border: 1px solid rgba(157, 178, 228, 0.14);
			border-radius: 16px;
			box-shadow: 0 20px 50px rgba(3, 8, 20, 0.38);
		}

		.notif-list {
			margin: 0;
			padding: 0;
			list-style: none;
		}

		.notif-item {
			display: grid;
			grid-template-columns: 38px minmax(0, 1fr) auto;
			gap: 12px;
			align-items: start;
			padding: 14px 16px;
			border-bottom: 1px solid rgba(86, 112, 161, 0.2);
		}

		.notif-item:last-child {
			border-bottom: 0;
		}

		.notif-item.unread {
			background: linear-gradient(90deg, rgba(115, 155, 248, 0.12), rgba(14, 24, 39, 0));
		}

		.notif-icon {
			width: 38px;
			height: 38px;
			border-radius: 10px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			border: 1px solid rgba(150, 176, 230, 0.26);
			background: rgba(31, 43, 69, 0.85);
			color: #dce8ff;
			margin-top: 2px;
		}

		.tp-success { color: #93ffd7; border-color: rgba(98, 230, 180, 0.45); background: rgba(36, 111, 83, 0.24); }
		.tp-failed { color: #ffb8b0; border-color: rgba(255, 146, 138, 0.45); background: rgba(120, 56, 52, 0.26); }
		.tp-warning { color: #ffd49a; border-color: rgba(255, 195, 123, 0.45); background: rgba(122, 84, 39, 0.28); }
		.tp-system { color: #bfd2ff; border-color: rgba(153, 180, 245, 0.45); background: rgba(55, 78, 130, 0.28); }

		.notif-title {
			margin: 0;
			font-size: 16px;
			color: #f0f5ff;
			font-weight: 700;
			line-height: 1.2;
		}

		.notif-message {
			margin: 6px 0 8px;
			color: #b8c9ea;
			font-size: 13px;
			line-height: 1.5;
			white-space: pre-line;
		}

		.notif-meta {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			color: #7c95bf;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.1em;
			font-weight: 700;
			flex-wrap: wrap;
		}

		.dot {
			width: 3px;
			height: 3px;
			border-radius: 999px;
			background: #6d82ab;
		}

		.pill {
			display: inline-flex;
			align-items: center;
			border: 1px solid rgba(151, 176, 228, 0.35);
			border-radius: 999px;
			padding: 4px 8px;
			color: #c9daff;
			font-size: 10px;
			line-height: 1;
			letter-spacing: 0.12em;
			text-transform: uppercase;
			font-weight: 700;
			background: rgba(31, 43, 69, 0.68);
		}

		.pill-read {
			color: #a4b7dd;
			border-color: rgba(125, 149, 197, 0.35);
			background: rgba(37, 50, 75, 0.62);
		}

		.notif-action {
			margin-top: 2px;
		}

		.btn-read {
			border: 1px solid rgba(150, 179, 233, 0.34);
			border-radius: 9px;
			background: rgba(34, 48, 77, 0.82);
			color: #e0ebff;
			padding: 8px 10px;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.1em;
			cursor: pointer;
		}

		.flash {
			border-radius: 11px;
			border: 1px solid;
			padding: 11px 13px;
			font-size: 13px;
			margin-bottom: 10px;
		}

		.flash.success {
			color: #a5ffd9;
			border-color: rgba(96, 223, 177, 0.48);
			background: rgba(34, 104, 78, 0.26);
		}

		.flash.error {
			color: #ffb9b2;
			border-color: rgba(255, 143, 136, 0.45);
			background: rgba(118, 55, 52, 0.26);
		}

		.empty-state {
			padding: 30px 18px;
			text-align: center;
		}

		.empty-icon {
			width: 62px;
			height: 62px;
			margin: 0 auto 12px;
			border-radius: 16px;
			display: grid;
			place-items: center;
			background: rgba(46, 63, 98, 0.62);
			border: 1px solid rgba(140, 166, 220, 0.25);
			color: #c2d5ff;
			font-size: 21px;
		}

		.empty-state h3 {
			margin: 0;
			color: #e6efff;
			font-size: 21px;
		}

		.empty-state p {
			margin: 7px auto 0;
			color: #8ea5d0;
			font-size: 13px;
			max-width: 420px;
		}

		.notif-side-card {
			padding: 14px;
		}

		.notif-side-title {
			margin: 0;
			color: #7e96bf;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.16em;
			font-weight: 700;
		}

		.big-number {
			margin: 8px 0 2px;
			font-size: 44px;
			line-height: 1;
			color: #edf3ff;
			font-weight: 700;
		}

		.small-muted {
			margin: 0;
			color: #8ea4ce;
			font-size: 12px;
			line-height: 1.45;
		}

		.mini-list {
			margin: 10px 0 0;
			padding: 0;
			list-style: none;
			display: grid;
			gap: 8px;
		}

		.mini-list li {
			display: flex;
			justify-content: space-between;
			gap: 10px;
			color: #c4d5f7;
			font-size: 12px;
			border-bottom: 1px solid rgba(91, 117, 168, 0.16);
			padding-bottom: 8px;
		}

		.mini-list li:last-child {
			border-bottom: 0;
			padding-bottom: 0;
		}

		@media (max-width: 1060px) {
			.notif-page {
				grid-template-columns: 1fr;
			}

			.notif-right {
				grid-template-columns: 1fr 1fr;
			}
		}

		@media (max-width: 760px) {
			.notif-item {
				grid-template-columns: 34px minmax(0, 1fr);
			}

			.notif-action {
				grid-column: 1 / -1;
				margin-top: 6px;
			}

			.notif-right {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<div class="notif-page">
		<div class="notif-left">
			<div class="notif-head">
				<div>
					<h1>Notifikasi</h1>
					<p>Lihat update terbaru proses posting, error akun, dan status eksekusi konten Anda.</p>
				</div>

				<form method="POST" action="{{ route('notifikasi.baca-semua') }}">
					@csrf
					<button type="submit" class="btn-mark-all" {{ $unreadCount <= 0 ? 'disabled' : '' }}>
						Tandai Semua Dibaca
					</button>
				</form>
			</div>

			@if(session('success'))
				<div class="flash success">{{ session('success') }}</div>
			@endif
			@if(session('error'))
				<div class="flash error">{{ session('error') }}</div>
			@endif

			<section class="notif-card">
				@if($notifikasi->count() > 0)
					<ul class="notif-list">
						@foreach($notifikasi as $item)
							@php
								$typeMeta = $typeMap[$item->type] ?? ['icon' => 'fa-bell', 'class' => 'tp-system', 'label' => 'Umum'];
							@endphp
							<li class="notif-item {{ !$item->is_read ? 'unread' : '' }}">
								<span class="notif-icon {{ $typeMeta['class'] }}">
									<i class="fas {{ $typeMeta['icon'] }}"></i>
								</span>

								<div>
									<h3 class="notif-title">{{ $item->title }}</h3>
									<p class="notif-message">{{ $item->message }}</p>

									<div class="notif-meta">
										<span class="pill">{{ $typeMeta['label'] }}</span>
										<span class="dot"></span>
										<span>{{ optional($item->created_at)->diffForHumans() ?? '-' }}</span>
										@if($item->is_read)
											<span class="pill pill-read">Sudah Dibaca</span>
										@else
											<span class="pill">Belum Dibaca</span>
										@endif
									</div>
								</div>

								@if(!$item->is_read)
									<form method="POST" action="{{ route('notifikasi.baca', $item->id) }}" class="notif-action">
										@csrf
										<button type="submit" class="btn-read">Tandai Dibaca</button>
									</form>
								@endif
							</li>
						@endforeach
					</ul>

					<div style="padding: 14px 16px; border-top: 1px solid rgba(86, 112, 161, 0.2);">
						{{ $notifikasi->withQueryString()->links() }}
					</div>
				@else
					<div class="empty-state">
						<div class="empty-icon"><i class="fas fa-bell-slash"></i></div>
						<h3>Belum ada notifikasi</h3>
						<p>Notifikasi otomatis akan muncul ketika jadwal posting dieksekusi atau ketika ada perubahan status akun.</p>
					</div>
				@endif
			</section>
		</div>

		<aside class="notif-right">
			<section class="notif-card notif-side-card">
				<p class="notif-side-title">Belum Dibaca</p>
				<p class="big-number">{{ $unreadCount }}</p>
				<p class="small-muted">Jumlah notifikasi yang perlu Anda tindak lanjuti.</p>
			</section>

			<section class="notif-card notif-side-card">
				<p class="notif-side-title">Ringkasan</p>
				<ul class="mini-list">
					<li>
						<span>Total Notifikasi</span>
						<b>{{ $notifikasi->total() }}</b>
					</li>
					<li>
						<span>Halaman Aktif</span>
						<b>{{ $notifikasi->currentPage() }}</b>
					</li>
					<li>
						<span>Per Halaman</span>
						<b>{{ $notifikasi->perPage() }}</b>
					</li>
				</ul>
			</section>
		</aside>
	</div>
@endsection
