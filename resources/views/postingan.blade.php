@extends('layout.main')

@section('title', 'Buat Postingan')

@section('content')
    @php
        // Ensure view has an active accounts collection even if controller didn't provide one
        $akunList = $akun_list ?? collect();
        if (empty($akunList) || ($akunList instanceof \Illuminate\Support\Collection && $akunList->isEmpty())) {
            $akunList = \App\Models\SosialAccount::where('user_id', auth()->id())
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->get();
        }
        $mentionEntries = $akunList->map(function ($a) {
            $display = (string) ($a->username ?: $a->platform_user_id);

            return [
                'display'  => $display,
                'platform' => (string) $a->platform,
                'handle'   => preg_replace('/[^a-zA-Z0-9._]/', '', str_replace(' ', '', $display)),
            ];
        })->values();

        $oldScheduledAt = old('scheduled_at');
        $oldScheduleDate = '';
        $oldScheduleTime = '';

        if (!empty($oldScheduledAt)) {
            try {
                $dt = \Illuminate\Support\Carbon::parse($oldScheduledAt);
                $oldScheduleDate = $dt->format('Y-m-d');
                $oldScheduleTime = $dt->format('H:i');
            } catch (\Throwable $e) {
                $oldScheduleDate = '';
                $oldScheduleTime = '';
            }
        }
    @endphp

    <style>
        .composer-page {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 16px;
        }

        .composer-left,
        .composer-right {
            display: grid;
            gap: 14px;
            align-content: start;
        }

        .composer-card {
            background: linear-gradient(180deg, rgba(17, 26, 44, 0.95), rgba(10, 17, 30, 0.95));
            border: 1px solid rgba(149, 174, 233, 0.16);
            border-radius: 15px;
            box-shadow: 0 12px 40px rgba(2, 7, 18, 0.45);
        }

        .composer-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .composer-head h2 {
            margin: 3px 0 0;
            font-size: clamp(30px, 4vw, 42px);
            line-height: 1.02;
            color: #edf3ff;
        }

        .composer-head p {
            margin: 6px 0 0;
            color: #8ea4ce;
            font-size: 13px;
            letter-spacing: 0.01em;
        }

        .composer-breadcrumb {
            margin: 0;
            color: #7f96bf;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }

        .top-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn-cm {
            border-radius: 9px;
            border: 1px solid rgba(142, 170, 230, 0.28);
            background: rgba(31, 43, 69, 0.75);
            color: #e2ecff;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-cm.primary {
            background: linear-gradient(180deg, #9fc1ff, #6f9eff);
            color: #0f2450;
            border-color: transparent;
        }

        .composer-main {
            padding: 16px;
        }

        .section-label {
            margin: 0 0 8px;
            color: #95acd8;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            font-weight: 700;
        }

        .platform-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .platform-item {
            border: 1px solid rgba(139, 165, 220, 0.2);
            background: rgba(24, 35, 56, 0.82);
            border-radius: 12px;
            padding: 10px 12px;
            display: flex;
            gap: 9px;
            align-items: center;
            color: #dbe7ff;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .platform-item.active {
            border-color: rgba(149, 184, 255, 0.75);
            box-shadow: inset 0 0 0 1px rgba(171, 198, 255, 0.2);
        }

        .platform-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            font-size: 14px;
        }

        .platform-icon.instagram {
            background: linear-gradient(145deg, #ff7854, #fd267d 58%, #6e41ff);
            color: #fff;
        }

        .platform-icon.facebook {
            background: linear-gradient(145deg, #4da2ff, #2d63ff);
            color: #fff;
        }

        .platform-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.1;
        }

        .platform-sub {
            margin: 1px 0 0;
            font-size: 11px;
            color: #8ca5d1;
        }

        .invisible-check {
            display: none;
        }

        .content-form {
            background: linear-gradient(180deg, rgba(27, 38, 59, 0.7), rgba(20, 29, 47, 0.7));
            border: 1px solid rgba(139, 163, 219, 0.13);
            border-radius: 13px;
            padding: 14px;
        }

        .f-field {
            margin-bottom: 10px;
        }

        .f-field label {
            display: block;
            margin-bottom: 6px;
            color: #90a6d0;
            font-size: 12px;
        }

        .f-input,
        .f-textarea,
        .f-select,
        .f-date,
        .f-time {
            width: 100%;
            border-radius: 10px;
            border: 1px solid rgba(128, 152, 205, 0.26);
            background: rgba(19, 28, 47, 0.82);
            color: #e8f0ff;
            padding: 10px 11px;
            font-size: 13px;
            font-family: inherit;
        }

        .f-textarea {
            min-height: 180px;
            resize: vertical;
        }

        .meta-inline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #8098c6;
            font-size: 11px;
            margin-top: 7px;
        }

        .quick-tools {
            display: inline-flex;
            gap: 7px;
            align-items: center;
            flex-wrap: wrap;
        }

        .quick-btn {
            border: 1px solid rgba(130, 157, 212, 0.32);
            background: rgba(27, 41, 67, 0.72);
            color: #a9c0eb;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 11px;
            cursor: pointer;
            font-family: inherit;
        }

        .quick-btn:hover {
            background: rgba(45, 64, 101, 0.72);
        }

        .emoji-pop {
            margin-top: 8px;
            background: rgba(15, 24, 40, 0.88);
            border: 1px solid rgba(128, 156, 215, 0.28);
            border-radius: 10px;
            padding: 8px;
        }

        .emoji-pop.hide {
            display: none;
        }

        .emoji-item {
            border: 0;
            background: rgba(39, 57, 90, 0.7);
            border-radius: 8px;
            color: #eff5ff;
            font-size: 16px;
            cursor: pointer;
            height: 30px;
        }

        .emoji-cats {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .emoji-cat {
            border: 1px solid rgba(130, 157, 212, 0.32);
            background: rgba(27, 41, 67, 0.72);
            color: #a9c0eb;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 11px;
            cursor: pointer;
            font-family: inherit;
        }

        .emoji-cat.active {
            background: rgba(67, 96, 168, 0.45);
            border-color: rgba(163, 190, 255, 0.62);
            color: #eef5ff;
        }

        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(10, minmax(0, 1fr));
            gap: 6px;
        }

        .mention-box {
            margin-top: 8px;
            border: 1px solid rgba(130, 157, 212, 0.32);
            background: rgba(15, 24, 40, 0.92);
            border-radius: 10px;
            max-height: 180px;
            overflow-y: auto;
        }

        .mention-box.hide {
            display: none;
        }

        .mention-item {
            width: 100%;
            border: 0;
            border-bottom: 1px solid rgba(128, 156, 215, 0.18);
            background: transparent;
            color: #dce8ff;
            text-align: left;
            padding: 8px 10px;
            cursor: pointer;
            font-size: 12px;
            font-family: inherit;
        }

        .mention-item:last-child {
            border-bottom: 0;
        }

        .mention-item strong {
            color: #f2f7ff;
        }

        .mention-meta {
            color: #8fa8d6;
            font-size: 11px;
            margin-left: 6px;
        }

        .template-wrap {
            margin-top: 10px;
            border: 1px solid rgba(130, 156, 214, 0.24);
            border-radius: 11px;
            padding: 10px;
            background: rgba(18, 27, 43, 0.55);
        }

        .template-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #9cb4e0;
            font-size: 11px;
        }

        .template-hint {
            color: #7f97c3;
            font-size: 11px;
        }

        .template-grid {
            display: grid;
            grid-template-columns: repeat(9, minmax(0, 1fr));
            gap: 7px;
        }

        .template-swatch {
            height: 34px;
            border-radius: 9px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: 0.18s ease;
            position: relative;
        }

        .template-swatch:hover {
            transform: translateY(-1px);
        }

        .template-swatch.active {
            border-color: #f4fbff;
            box-shadow: 0 0 0 2px rgba(133, 171, 255, 0.45);
        }

        .template-swatch.off {
            background: #1c2940;
        }

        .template-wrap.disabled {
            opacity: 0.55;
        }

        .template-wrap.disabled .template-swatch {
            cursor: not-allowed;
            transform: none;
        }

        .template-preview {
            margin-top: 9px;
            border-radius: 10px;
            min-height: 84px;
            padding: 11px;
            display: grid;
            place-items: center;
            color: #f5f9ff;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.25;
            border: 1px solid rgba(173, 201, 255, 0.25);
            white-space: pre-wrap;
            word-break: break-word;
        }

        .tpl-classic-aurora { background: linear-gradient(135deg, #3d7bff, #8d3dff 62%, #e8418d); }
        .tpl-sunset-fade { background: linear-gradient(135deg, #f97316, #ef4444 56%, #8b5cf6); }
        .tpl-royal-plum { background: linear-gradient(135deg, #7c3aed, #db2777); }
        .tpl-emerald-wave { background: linear-gradient(135deg, #059669, #14b8a6 58%, #0ea5e9); }
        .tpl-midnight-blue { background: linear-gradient(135deg, #0f172a, #1e3a8a); }
        .tpl-orange-pop { background: linear-gradient(135deg, #fb923c, #f43f5e); }
        .tpl-mono-ink { background: linear-gradient(135deg, #111827, #374151); }
        .tpl-neon-blend { background: linear-gradient(135deg, #06b6d4, #8b5cf6 50%, #f43f5e); }

        .media-grid {
            display: grid;
            grid-template-columns: 160px repeat(2, 1fr);
            gap: 10px;
        }

        .drop-zone {
            border-radius: 12px;
            border: 1px dashed rgba(133, 163, 223, 0.4);
            min-height: 152px;
            display: grid;
            place-items: center;
            color: #8ba4cf;
            background: rgba(20, 30, 50, 0.55);
            text-align: center;
            font-size: 12px;
            padding: 10px;
        }

        .thumb-slot {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            min-height: 152px;
            border: 1px solid rgba(125, 151, 206, 0.25);
            background: rgba(24, 35, 58, 0.62);
        }

        .thumb-remove {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 24px;
            height: 24px;
            border: 0;
            border-radius: 999px;
            background: rgba(10, 15, 26, 0.82);
            color: #ffb9b2;
            font-size: 12px;
            cursor: pointer;
            display: grid;
            place-items: center;
            z-index: 2;
        }

        .thumb-remove:hover {
            background: rgba(131, 46, 42, 0.82);
            color: #fff;
        }

        .thumb-slot img,
        .thumb-slot video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .thumb-empty {
            display: grid;
            place-items: center;
            height: 100%;
            font-size: 11px;
            color: #7f96be;
            padding: 8px;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .schedule-mode {
            border-radius: 11px;
            border: 1px solid rgba(137, 166, 224, 0.21);
            background: rgba(26, 37, 58, 0.72);
            color: #dce8ff;
            padding: 14px 12px;
            cursor: pointer;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        .schedule-mode.active {
            border-color: rgba(160, 192, 255, 0.82);
            box-shadow: inset 0 0 0 1px rgba(160, 192, 255, 0.3);
        }

        .schedule-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .preview-card {
            padding: 14px;
        }

        .preview-toggle {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            margin-bottom: 8px;
        }

        .preview-toggle button {
            border-radius: 999px;
            border: 1px solid rgba(140, 164, 214, 0.25);
            background: rgba(32, 45, 71, 0.78);
            color: #dce7ff;
            padding: 4px 10px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            cursor: pointer;
        }

        .preview-toggle button.active {
            border-color: rgba(157, 188, 255, 0.75);
            background: rgba(77, 112, 184, 0.35);
        }

        .phone-shell {
            margin: 0 auto;
            max-width: 270px;
            border: 1px solid rgba(131, 154, 206, 0.35);
            border-radius: 26px;
            padding: 10px;
            background: linear-gradient(180deg, #050b17, #090f1d);
        }

        .phone-screen {
            border-radius: 20px;
            border: 1px solid rgba(100, 122, 176, 0.45);
            background: #000;
            overflow: hidden;
        }

        .pv-head {
            padding: 8px 10px;
            color: #e9f2ff;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pv-user {
            font-weight: 700;
        }

        .pv-media {
            aspect-ratio: 1/1;
            background: linear-gradient(135deg, #0b1629, #162b4f);
            overflow: hidden;
        }

        .pv-media img,
        .pv-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .pv-meta {
            padding: 9px 10px 12px;
            color: #dce7ff;
        }

        .pv-caption {
            margin: 7px 0 0;
            font-size: 11px;
            line-height: 1.45;
            color: #ecf2ff;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .pv-tags {
            margin: 5px 0 0;
            font-size: 11px;
            color: #94b1ff;
        }

        .tips {
            padding: 13px;
            color: #9db1d8;
            font-size: 12px;
        }

        .tips strong {
            color: #e2edff;
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

        .history-card {
            padding: 14px;
        }

        .history-filter {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }

        .history-filter button {
            border-radius: 10px;
            border: 1px solid rgba(136, 163, 220, 0.3);
            background: rgba(32, 45, 69, 0.78);
            color: #deebff;
            font-weight: 700;
            cursor: pointer;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            border-bottom: 1px solid rgba(88, 111, 164, 0.2);
            padding: 11px;
            color: #d8e6ff;
            font-size: 13px;
            text-align: left;
            vertical-align: middle;
        }

        .history-table th {
            color: #7f97c1;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.13em;
        }

        .status-pill {
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            padding: 4px 9px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border: 1px solid;
        }

        .st-draft {
            color: #a5bde8;
            border-color: rgba(132, 163, 224, 0.45);
            background: rgba(52, 76, 121, 0.28);
        }

        .st-scheduled {
            color: #ffd8aa;
            border-color: rgba(255, 196, 117, 0.45);
            background: rgba(123, 88, 40, 0.28);
        }

        .st-published {
            color: #a4ffd7;
            border-color: rgba(102, 223, 176, 0.45);
            background: rgba(39, 112, 85, 0.28);
        }

        .st-failed {
            color: #ffb7af;
            border-color: rgba(255, 144, 136, 0.45);
            background: rgba(120, 56, 52, 0.28);
        }

        .row-media {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            object-fit: cover;
            border: 1px solid rgba(136, 164, 222, 0.26);
            background: rgba(118, 149, 217, 0.2);
            margin-right: 8px;
        }

        .row-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .row-actions .btn-cm {
            padding: 8px 10px;
            font-size: 12px;
        }

        .template-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 4px;
            border-radius: 999px;
            padding: 3px 9px;
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: 1px solid rgba(163, 190, 255, 0.38);
            background: rgba(67, 96, 168, 0.32);
            color: #dbe8ff;
        }

        .btn-danger {
            border-color: rgba(255, 133, 125, 0.38);
            color: #ffb4ac;
            background: rgba(113, 45, 42, 0.3);
        }

        .hide {
            display: none;
        }

        @media (max-width: 1180px) {
            .composer-page {
                grid-template-columns: 1fr;
            }

            .composer-right {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 860px) {
            .top-actions {
                width: 100%;
                justify-content: stretch;
            }

            .top-actions .btn-cm {
                flex: 1;
            }

            .platform-grid,
            .media-grid,
            .schedule-grid,
            .schedule-inputs,
            .history-filter,
            .composer-right {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section>
        <div class="composer-head">
            <div>
                <p class="composer-breadcrumb">Pages / Buat Postingan</p>
                <h2>Buat Postingan</h2>
                <p>Otomatisasi konten media sosial Anda dengan presisi algoritma.</p>
            </div>

            <div class="top-actions">
                <button type="button" class="btn-cm" id="btnSaveDraftTop">Simpan Draft</button>
            </div>
        </div>

        @if(session('success')) <div class="flash success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="flash error">{{ session('error') }}</div> @endif
        @error('caption') <div class="flash error">{{ $message }}</div> @enderror
        @error('platforms') <div class="flash error">{{ $message }}</div> @enderror
        @error('media.*') <div class="flash error">{{ $message }}</div> @enderror
        @error('template_text') <div class="flash error">{{ $message }}</div> @enderror
        @error('social_account_id') <div class="flash error">{{ $message }}</div> @enderror
        @error('scheduled_at') <div class="flash error">{{ $message }}</div> @enderror

        <form id="composerForm" method="POST" action="{{ route('posting.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="submit_mode" id="submitModeInput" value="publish">
            <input type="hidden" name="scheduled_at" id="scheduledAtInput" value="{{ old('scheduled_at') }}">

            <div class="composer-page">
                <div class="composer-left">
                    <article class="composer-card composer-main">
                        <p class="section-label">Platform Tujuan</p>
                        <div class="platform-grid">
                            <label class="platform-item {{ in_array('instagram', old('platforms', ['instagram'])) ? 'active' : '' }}" data-platform="instagram">
                                <input class="invisible-check" type="checkbox" name="platforms[]" value="instagram" {{ in_array('instagram', old('platforms', ['instagram'])) ? 'checked' : '' }}>
                                <span class="platform-icon instagram"><i class="fab fa-instagram"></i></span>
                                <span>
                                    <p class="platform-title">Instagram</p>
                                    <p class="platform-sub">Feeds | Reels</p>
                                </span>
                            </label>

                            <label class="platform-item {{ in_array('facebook', old('platforms', [])) ? 'active' : '' }}" data-platform="facebook">
                                <input class="invisible-check" type="checkbox" name="platforms[]" value="facebook" {{ in_array('facebook', old('platforms', [])) ? 'checked' : '' }}>
                                <span class="platform-icon facebook"><i class="fab fa-facebook-f"></i></span>
                                <span>
                                    <p class="platform-title">Facebook</p>
                                    <p class="platform-sub">Wall Post</p>
                                </span>
                            </label>
                        </div>

                        <div class="content-form">
                            <p class="section-label">Detail Konten</p>

                            <div class="f-field">
                                <label for="caption">Isi Konten</label>
                                <textarea id="caption" class="f-textarea" name="caption" required placeholder="Apa yang ingin Anda sampaikan?">{{ old('caption') }}</textarea>
                                <div class="meta-inline">
                                    <span class="quick-tools">
                                        <button type="button" class="quick-btn" id="btnEmoji"><i class="far fa-smile"></i> Emoji</button>
                                        <button type="button" class="quick-btn" id="btnHashtag"><i class="fas fa-hashtag"></i> Hashtag</button>
                                        <button type="button" class="quick-btn" id="btnMention"><i class="fas fa-at"></i> Tag</button>
                                        <button type="button" class="quick-btn" id="btnGenerateCaption"><i class="fas fa-pencil-alt"></i> Generate Caption</button>
                                    </span>
                                    <span><span id="charCount">0</span> / 2200 karakter</span>
                                </div>
                                <div id="emojiPicker" class="emoji-pop hide">
                                    <div id="emojiCats" class="emoji-cats"></div>
                                    <div id="emojiGrid" class="emoji-grid"></div>
                                </div>
                                <div id="mentionBox" class="mention-box hide"></div>

                                <div id="textTemplateWrap" class="template-wrap">
                                    <input type="hidden" id="textTemplateInput" name="text_template" value="{{ old('text_template') }}">
                                    <div class="f-field" style="margin-bottom:8px;">
                                        <label for="templateTextInput">Teks Background Template</label>
                                        <input
                                            id="templateTextInput"
                                            class="f-input"
                                            type="text"
                                            name="template_text"
                                            maxlength="220"
                                            value="{{ old('template_text', '') }}"
                                            placeholder="Contoh: Promo Spesial Hari Ini"
                                        >
                                        <small style="color:#8ea3cc;display:block;margin-top:6px;font-size:11px;">
                                            Teks ini khusus untuk gambar background template dan berbeda dari Isi Konten.
                                        </small>
                                    </div>
                                    <div class="template-head">
                                        <span>Template Background Teks</span>
                                        <span class="template-hint">Aktif hanya jika tanpa foto/video</span>
                                    </div>
                                    <div id="templateGrid" class="template-grid">
                                        <button type="button" class="template-swatch off" data-template="" title="Tanpa template"></button>
                                        <button type="button" class="template-swatch tpl-classic-aurora" data-template="classic_aurora" title="Classic Aurora"></button>
                                        <button type="button" class="template-swatch tpl-sunset-fade" data-template="sunset_fade" title="Sunset Fade"></button>
                                        <button type="button" class="template-swatch tpl-royal-plum" data-template="royal_plum" title="Royal Plum"></button>
                                        <button type="button" class="template-swatch tpl-emerald-wave" data-template="emerald_wave" title="Emerald Wave"></button>
                                        <button type="button" class="template-swatch tpl-midnight-blue" data-template="midnight_blue" title="Midnight Blue"></button>
                                        <button type="button" class="template-swatch tpl-orange-pop" data-template="orange_pop" title="Orange Pop"></button>
                                        <button type="button" class="template-swatch tpl-mono-ink" data-template="mono_ink" title="Mono Ink"></button>
                                        <button type="button" class="template-swatch tpl-neon-blend" data-template="neon_blend" title="Neon Blend"></button>
                                    </div>
                                    <div id="textTemplatePreview" class="template-preview">Pratinjau teks template</div>
                                </div>

                                <div class="f-field" style="margin-bottom:0; margin-top:10px;">
                                    <label for="hashtags">Hashtags</label>
                                    <input id="hashtags" class="f-input" type="text" name="hashtags" value="{{ old('hashtags') }}" placeholder="#MetaAutomation #FutureTech">
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="composer-card composer-main">
                        <p class="section-label">Media (Foto/Video)</p>
                        <div class="media-grid">
                            <label class="drop-zone" for="mediaInput">
                                <div>
                                    <i class="fas fa-cloud-upload-alt" style="font-size:21px;margin-bottom:7px;"></i><br>
                                    Drag & Drop Media<br>
                                    <span style="font-size:11px;">JPG, PNG, WEBP, MP4</span>
                                </div> 
                            </label>

                            <div class="thumb-slot" id="thumbSlot1"><div class="thumb-empty">Preview media 1</div></div>
                            <div class="thumb-slot" id="thumbSlot2"><div class="thumb-empty">Preview media 2</div></div>
                        </div>
                        <input id="mediaInput" class="hide" type="file" name="media[]" accept="image/*,video/*" multiple>
    
                    </article>

                    <article class="composer-card composer-main">
                        <p class="section-label">Jadwal Posting</p>

                        <div class="schedule-grid">
                            <button class="schedule-mode active" type="button" data-mode="publish">Sekarang</button>
                            <button class="schedule-mode" type="button" data-mode="schedule">Jadwalkan</button>
                            <button class="schedule-mode" type="button" data-mode="ai">Waktu AI</button>
                        </div>

                        <div id="scheduleFields" class="schedule-inputs hide">
                            <div class="f-field" style="margin-bottom:0;">
                                <label for="scheduleDate">Tanggal</label>
                                <input id="scheduleDate" class="f-date" type="date" value="{{ $oldScheduleDate }}">
                            </div>
                            <div class="f-field" style="margin-bottom:0;">
                                <label for="scheduleTime">Waktu</label>
                                <input id="scheduleTime" class="f-time" type="time" value="{{ $oldScheduleTime }}">
                            </div>
                        </div>
                        <div id="accountFields" class="f-field hide" style="margin-top:10px; margin-bottom:0;">
                            <label for="socialAccountSelect">Akun Terhubung</label>
                            <select id="socialAccountSelect" class="f-select" name="social_account_id">
                                <option value="">Pilih akun untuk jadwal posting</option>
                                @foreach($akunList as $akun)
                                    <option value="{{ $akun->id }}" {{ (string) old('social_account_id') === (string) $akun->id ? 'selected' : '' }}>
                                        {{ ucfirst($akun->platform) }} - {{ $akun->username ?: $akun->platform_user_id }}
                                    </option>
                                @endforeach
                            </select>
                            @if($akunList->isEmpty())
                                <div style="margin-top:6px;color:#ffb8b1;font-size:12px;">Belum ada akun terhubung aktif. Hubungkan akun dulu agar bisa menjadwalkan.</div>
                            @endif
                        </div>
                        <div id="aiRecommendationInfo" class="hide" style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(162,183,255,0.12); background:rgba(16,25,42,0.65); color:#a9bfeb; font-size:12px;"></div>

                        <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:8px;">
                            <button type="button" id="btnScheduleSubmit" class="btn-cm">Simpan Sebagai Jadwal</button>
                        </div>
                    </article>
                </div>

                <div class="composer-right">
                    <article class="composer-card preview-card">
                        <p class="section-label">Live Preview</p>
                        <div class="preview-toggle">
                            <button class="active" type="button" data-preview-platform="instagram">Instagram</button>
                            <button type="button" data-preview-platform="facebook">Facebook</button>
                        </div>

                        <div class="phone-shell">
                            <div class="phone-screen">
                                <div class="pv-head">
                                    <span class="pv-user" id="previewUser">luminous_authority</span>
                                    <span><i class="fas fa-ellipsis-h"></i></span>
                                </div>
                                <div class="pv-media" id="previewMedia">
                                    <div class="thumb-empty">Tidak ada media dipilih</div>
                                </div>
                                <div class="pv-meta">
                                    <div style="font-size:11px;color:#ffffff;"><i class="fas fa-heart"></i> &nbsp;<i class="fas fa-comment"></i> &nbsp;<i class="fas fa-paper-plane"></i></div>
                                    <p class="pv-caption" id="previewCaption">Caption akan muncul di sini...</p>
                                    <p class="pv-tags" id="previewTags"></p>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="composer-card tips">
                        <strong>Tips Optimasi AI</strong><br>
                        Berdasarkan analitik 7 hari terakhir, konten visual bertema teknologi memiliki engagement 40% lebih tinggi pada jam 19:00-20:00 WIB.
                    </article>
                </div>
            </div>
        </form>

        <article class="composer-card history-card" style="margin-top:14px;">
            <p class="section-label">Daftar Postingan</p>
            <form method="GET" action="{{ route('postingan') }}" class="history-filter">
                <select class="f-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>

                <select class="f-select" name="platform">
                    <option value="">Semua Platform</option>
                    <option value="instagram" {{ request('platform') === 'instagram' ? 'selected' : '' }}>Instagram</option>
                    <option value="facebook" {{ request('platform') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                </select>

                <input class="f-date" type="date" name="tanggal" value="{{ request('tanggal') }}">
                <button type="submit">Terapkan</button>
            </form>

            <div style="overflow:auto;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Konten</th>
                            <th>Status</th>
                            <th>Platform</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($postingan as $item)
                            @php
                                $firstMedia = $item->media->first();
                                $thumb = $firstMedia?->file_url ?: ($firstMedia?->file_path ? asset('storage/' . ltrim($firstMedia->file_path, '/')) : null);
                                $isVideoThumb = $firstMedia && (
                                    ($firstMedia->media_type ?? null) === 'video'
                                    || \Illuminate\Support\Str::startsWith((string) ($firstMedia->mime_type ?? ''), 'video/')
                                );
                                $platformText = is_array($item->platform_targets) ? implode(', ', $item->platform_targets) : '-';
                                $statusClass = 'st-' . strtolower($item->status);
                                $publishError = ($publishErrors[$item->id] ?? null);
                            @endphp
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;">
                                        @if($thumb)
                                            @if($isVideoThumb)
                                                <video class="row-media" muted playsinline preload="metadata">
                                                    <source src="{{ $thumb }}" type="{{ $firstMedia->mime_type ?? 'video/mp4' }}">
                                                </video>
                                            @else
                                                <img src="{{ $thumb }}" class="row-media" alt="thumb">
                                            @endif
                                        @else
                                            <span class="row-media"></span>
                                        @endif
                                        <span>
                                            <div>{{ \Illuminate\Support\Str::limit($item->caption, 44) }}</div>
                                            @if(!empty($item->text_template))
                                                <span class="template-badge">
                                                    <i class="fas fa-palette"></i>
                                                    Teks Template
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-pill {{ $statusClass }}">{{ $item->status }}</span>
                                    @if(!empty($publishError))
                                        <div style="margin-top:6px;color:#ffb7af;font-size:11px;max-width:260px;word-break:break-word;">
                                            {{ \Illuminate\Support\Str::limit($publishError, 120) }}
                                        </div>
                                    @endif
                                </td>
                                <td style="color:#93a9d2;">{{ $platformText }}</td>
                                <td>
                                    <div class="row-actions">
                                        @php
                                            $postLog = $postLogs[$item->id] ?? null;
                                            $platformUrl = null;
                                            
                                            if ($item->status === 'published' && $postLog && !empty($postLog->platform_post_id)) {
                                                $platformPostId = $postLog->platform_post_id;
                                                // Determine platform from post targets or from first platform
                                                $platforms = is_array($item->platform_targets) ? $item->platform_targets : [];
                                                $platform = !empty($platforms) ? reset($platforms) : 'facebook';
                                                
                                                if ($platform === 'instagram') {
                                                    $platformUrl = "https://www.instagram.com/p/{$platformPostId}/";
                                                } else { // facebook or default
                                                    $platformUrl = "https://www.facebook.com/{$platformPostId}/";
                                                }
                                            }
                                        @endphp

                                        @if($platformUrl)
                                        <a href="{{ $platformUrl }}" style="color:#8ea6d8;text-decoration:none;" target="_blank" rel="noopener noreferrer">Lihat di Platform</a>
                                        @else
                                            <span style="color:#8ea6d8;opacity:0.6;">Detail</span>
                                        @endif

                                        @if(in_array($item->status, ['draft', 'scheduled']))
                                            <a class="btn-cm" href="{{ route('postingan.edit', $item->id) }}">Edit Postingan</a>
                                        @endif

                                        <form method="POST" action="{{ route('postingan.hapus', $item->id) }}" onsubmit="return confirm('Hapus postingan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-cm btn-danger" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center;color:#8ca0c6;">Belum ada postingan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:10px;">{{ $postingan->withQueryString()->links() }}</div>
        </article>
    </section>

    <script>
        (function () {
            console.log('composer script loaded', {
                mediaInput: !!document.getElementById('mediaInput'),
                btnGenerateCaption: !!document.getElementById('btnGenerateCaption')
            });
            var form = document.getElementById('composerForm');
            var btnSaveDraftTop = document.getElementById('btnSaveDraftTop');
            var btnPublishTop = document.getElementById('btnPublishTop');
            var btnScheduleSubmit = document.getElementById('btnScheduleSubmit');
            var submitModeInput = document.getElementById('submitModeInput');
            var scheduledAtInput = document.getElementById('scheduledAtInput');
            var mediaInput = document.getElementById('mediaInput');
            var captionInput = document.getElementById('caption');
            var hashtagsInput = document.getElementById('hashtags');
            var btnEmoji = document.getElementById('btnEmoji');
            var btnHashtag = document.getElementById('btnHashtag');
            var btnMention = document.getElementById('btnMention');
            var emojiPicker = document.getElementById('emojiPicker');
            var emojiCats = document.getElementById('emojiCats');
            var emojiGrid = document.getElementById('emojiGrid');
            var mentionBox = document.getElementById('mentionBox');
            var charCount = document.getElementById('charCount');
            var previewCaption = document.getElementById('previewCaption');
            var previewTags = document.getElementById('previewTags');
            var previewMedia = document.getElementById('previewMedia');
            var thumbSlot1 = document.getElementById('thumbSlot1');
            var thumbSlot2 = document.getElementById('thumbSlot2');
            var scheduleDate = document.getElementById('scheduleDate');
            var scheduleTime = document.getElementById('scheduleTime');
            var socialAccountSelect = document.getElementById('socialAccountSelect');
            var aiRecommendationInfo = document.getElementById('aiRecommendationInfo');
            var scheduleFields = document.getElementById('scheduleFields');
            var accountFields = document.getElementById('accountFields');
            var platformItems = document.querySelectorAll('.platform-item');
            var scheduleButtons = document.querySelectorAll('.schedule-mode');
            var previewButtons = document.querySelectorAll('[data-preview-platform]');
            var templateWrap = document.getElementById('textTemplateWrap');
            var textTemplateInput = document.getElementById('textTemplateInput');
            var templateTextInput = document.getElementById('templateTextInput');
            var templateGrid = document.getElementById('templateGrid');
            var textTemplatePreview = document.getElementById('textTemplatePreview');
            var currentMode = 'publish';
            var selectedFiles = [];
            var currentTemplate = (textTemplateInput && textTemplateInput.value) ? textTemplateInput.value : '';
            var activeEmojiCat = 'smileys';
            var mentionEntries = @json($mentionEntries);
            var mentionState = null;
            var isApplyingAiRecommendation = false;
            var aiRecommendationUrl = "{{ route('ai.recommendation.data') }}";

            // Drop-zone click & drag/drop support (fallback if label behavior is prevented)
            var dropZone = document.querySelector('.drop-zone');
            if (dropZone && mediaInput) {
                dropZone.style.cursor = 'pointer';
                dropZone.addEventListener('click', function (e) {
                    e.preventDefault();
                    try { mediaInput.click(); } catch (err) { console.warn('mediaInput click failed', err); }
                });

                dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.classList.add('dragover'); });
                dropZone.addEventListener('dragleave', function (e) { e.preventDefault(); dropZone.classList.remove('dragover'); });
                dropZone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    dropZone.classList.remove('dragover');
                    var dt = e.dataTransfer;
                    if (dt && dt.files && dt.files.length) {
                        selectedFiles = Array.from(dt.files);
                        syncMediaInputFromState();
                        updateMediaPreview();
                    }
                });
            }

            var emojiCatalog = {
                smileys: ['😀', '😁', '😂', '🤣', '😊', '😍', '😘', '😎', '🤩', '🥳', '😇', '🙂', '😉', '🤗', '🤔', '😴'],
                gestures: ['👍', '👎', '👏', '🙌', '🙏', '💪', '👊', '🤝', '👌', '✌️', '🤘', '🫶', '🫡', '🖐️'],
                objects: ['🔥', '🚀', '✨', '💡', '🎯', '📈', '📌', '📣', '💻', '📱', '🎉', '🏆', '⚡', '🛠️'],
                symbols: ['✅', '❌', '⚠️', '⭐', '❤️', '💙', '💚', '💛', '💜', '#️⃣', '@', '&', '➕', '➖']
            };

            function insertAtCursor(text) {
                var start = captionInput.selectionStart || 0;
                var end = captionInput.selectionEnd || 0;
                var value = captionInput.value || '';
                captionInput.value = value.substring(0, start) + text + value.substring(end);
                captionInput.focus();
                var pos = start + text.length;
                captionInput.setSelectionRange(pos, pos);
                updatePreviewText();
                updateMentionSuggestions();
            }

            function renderEmojiCategories() {
                if (!emojiCats) return;
                emojiCats.innerHTML = '';

                [
                    { key: 'smileys', label: 'Wajah' },
                    { key: 'gestures', label: 'Gestur' },
                    { key: 'objects', label: 'Objek' },
                    { key: 'symbols', label: 'Simbol' }
                ].forEach(function (cat) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'emoji-cat' + (activeEmojiCat === cat.key ? ' active' : '');
                    btn.textContent = cat.label;
                    btn.addEventListener('click', function () {
                        activeEmojiCat = cat.key;
                        renderEmojiCategories();
                        renderEmojiGrid();
                    });
                    emojiCats.appendChild(btn);
                });
            }

            function renderEmojiGrid() {
                if (!emojiGrid) return;
                emojiGrid.innerHTML = '';

                (emojiCatalog[activeEmojiCat] || []).forEach(function (emoji) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'emoji-item';
                    btn.setAttribute('data-emoji', emoji);
                    btn.textContent = emoji;
                    btn.addEventListener('click', function () {
                        insertAtCursor(emoji + ' ');
                    });
                    emojiGrid.appendChild(btn);
                });
            }

            function hideMentionSuggestions() {
                mentionState = null;
                if (mentionBox) {
                    mentionBox.classList.add('hide');
                    mentionBox.innerHTML = '';
                }
            }

            function applyMention(handle) {
                if (!mentionState || !handle) return;
                var value = captionInput.value || '';
                var before = value.slice(0, mentionState.atIndex + 1);
                var after = value.slice(mentionState.cursorPos);
                var mentionText = handle + ' ';

                captionInput.value = before + mentionText + after;
                var newPos = before.length + mentionText.length;
                captionInput.focus();
                captionInput.setSelectionRange(newPos, newPos);
                hideMentionSuggestions();
                updatePreviewText();
            }

            function updateMentionSuggestions() {
                if (!mentionBox) return;

                var cursorPos = captionInput.selectionStart || 0;
                var value = captionInput.value || '';
                var left = value.slice(0, cursorPos);
                var atIndex = left.lastIndexOf('@');

                if (atIndex < 0) {
                    hideMentionSuggestions();
                    return;
                }

                var boundary = atIndex > 0 ? left.charAt(atIndex - 1) : ' ';
                if (boundary !== ' ' && boundary !== '\n' && boundary !== '\t') {
                    hideMentionSuggestions();
                    return;
                }

                var query = left.slice(atIndex + 1);
                if (/\s/.test(query)) {
                    hideMentionSuggestions();
                    return;
                }

                var filtered = mentionEntries.filter(function (item) {
                    var q = query.toLowerCase();
                    return !q || item.handle.toLowerCase().indexOf(q) !== -1 || item.display.toLowerCase().indexOf(q) !== -1;
                }).slice(0, 6);

                if (!filtered.length) {
                    hideMentionSuggestions();
                    return;
                }

                mentionState = {
                    atIndex: atIndex,
                    cursorPos: cursorPos,
                };

                mentionBox.innerHTML = '';
                filtered.forEach(function (item) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'mention-item';
                    btn.innerHTML = '<strong>@' + item.handle + '</strong><span class="mention-meta">' + item.display + ' • ' + item.platform + '</span>';
                    btn.addEventListener('click', function () {
                        applyMention(item.handle);
                    });
                    mentionBox.appendChild(btn);
                });

                mentionBox.classList.remove('hide');
            }

            var templateClassMap = {
                'classic_aurora': 'tpl-classic-aurora',
                'sunset_fade': 'tpl-sunset-fade',
                'royal_plum': 'tpl-royal-plum',
                'emerald_wave': 'tpl-emerald-wave',
                'midnight_blue': 'tpl-midnight-blue',
                'orange_pop': 'tpl-orange-pop',
                'mono_ink': 'tpl-mono-ink',
                'neon_blend': 'tpl-neon-blend'
            };

            function updateCharCount() {
                if (!charCount || !captionInput) return;
                var count = captionInput.value.length;
                charCount.textContent = count;
            }

            function updatePreviewText() {
                var caption = captionInput ? captionInput.value.trim() : '';
                var tags = hashtagsInput ? hashtagsInput.value.trim() : '';
                if (previewCaption) {
                    previewCaption.textContent = caption || 'Caption akan muncul di sini...';
                }
                if (previewTags) {
                    previewTags.textContent = tags;
                }
                updateCharCount();
                updateTemplatePreviewBox();
            }

            function applyTemplateClass(target, template) {
                Object.keys(templateClassMap).forEach(function (key) {
                    target.classList.remove(templateClassMap[key]);
                });

                if (template && templateClassMap[template]) {
                    target.classList.add(templateClassMap[template]);
                }
            }

            function hasMediaSelected() {
                return selectedFiles.length > 0;
            }

            function updateTemplateSwatches() {
                if (!templateGrid) return;

                var swatches = templateGrid.querySelectorAll('.template-swatch');
                swatches.forEach(function (swatch) {
                    var val = swatch.getAttribute('data-template') || '';
                    swatch.classList.toggle('active', val === currentTemplate);
                });
            }

            function updateTemplateAvailability() {
                if (!templateWrap || !textTemplateInput) return;

                var disabled = hasMediaSelected();
                templateWrap.classList.toggle('disabled', disabled);
                if (templateTextInput) {
                    templateTextInput.disabled = disabled;
                }

                if (disabled) {
                    textTemplateInput.value = '';
                    currentTemplate = '';
                } else {
                    textTemplateInput.value = currentTemplate;
                }

                updateTemplateSwatches();
                updateTemplatePreviewBox();
                updateMediaPreview();
            }

            function updateTemplatePreviewBox() {
                if (!textTemplatePreview) return;

                applyTemplateClass(textTemplatePreview, currentTemplate);
                var text = templateTextInput && templateTextInput.value.trim()
                    ? templateTextInput.value.trim()
                    : '';
                textTemplatePreview.textContent = text || 'Pratinjau teks template';

                if (!currentTemplate) {
                    textTemplatePreview.style.background = 'rgba(23, 35, 56, 0.76)';
                } else {
                    textTemplatePreview.style.background = '';
                }
            }

            function createMediaNode(file) {
                var url = URL.createObjectURL(file);
                var isVideo = file.type.indexOf('video') === 0;
                var node = document.createElement(isVideo ? 'video' : 'img');
                node.src = url;
                if (isVideo) {
                    node.controls = true;
                }
                return node;
            }

            function syncMediaInputFromState() {
                if (!mediaInput) return;
                var dataTransfer = new DataTransfer();
                selectedFiles.forEach(function (file) {
                    dataTransfer.items.add(file);
                });
                mediaInput.files = dataTransfer.files;
            }

            function removeMediaAt(index) {
                if (index < 0 || index >= selectedFiles.length) {
                    return;
                }

                selectedFiles.splice(index, 1);
                syncMediaInputFromState();
                updateMediaPreview();
            }

            function setThumb(target, file, index) {
                target.innerHTML = '';
                if (!file) {
                    target.innerHTML = '<div class="thumb-empty">Tidak ada media</div>';
                    return;
                }
                target.appendChild(createMediaNode(file));

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'thumb-remove';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.title = 'Hapus media';
                removeBtn.addEventListener('click', function () {
                    removeMediaAt(index);
                });
                target.appendChild(removeBtn);
            }

            function updateMediaPreview() {
                setThumb(thumbSlot1, selectedFiles[0], 0);
                setThumb(thumbSlot2, selectedFiles[1], 1);

                previewMedia.innerHTML = '';
                if (!selectedFiles[0]) {
                    var templateText = templateTextInput && templateTextInput.value.trim()
                        ? templateTextInput.value.trim()
                        : '';

                    if (currentTemplate && templateText) {
                        var txt = document.createElement('div');
                        txt.className = 'template-preview';
                        txt.style.minHeight = '100%';
                        txt.style.borderRadius = '0';
                        txt.style.border = '0';
                        txt.style.fontSize = '18px';
                        txt.style.padding = '16px';
                        applyTemplateClass(txt, currentTemplate);
                        txt.textContent = templateText;
                        previewMedia.appendChild(txt);
                    } else {
                        previewMedia.innerHTML = '<div class="thumb-empty">Tidak ada media dipilih</div>';
                    }
                    return;
                }
                previewMedia.appendChild(createMediaNode(selectedFiles[0]));
            }

            function setMode(mode) {
                currentMode = mode;
                submitModeInput.value = mode;

                scheduleButtons.forEach(function (btn) {
                    btn.classList.toggle('active', btn.getAttribute('data-mode') === mode);
                });

                var showScheduleFields = mode === 'schedule' || mode === 'ai';
                var showAccountFields = mode === 'publish' || mode === 'schedule' || mode === 'ai';
                scheduleFields.classList.toggle('hide', !showScheduleFields);
                accountFields.classList.toggle('hide', !showAccountFields);
                if (aiRecommendationInfo) {
                    aiRecommendationInfo.classList.toggle('hide', mode !== 'ai');
                }

                if (btnScheduleSubmit) {
                    if (mode === 'publish') {
                        btnScheduleSubmit.textContent = 'Publish Sekarang';
                    } else {
                        btnScheduleSubmit.textContent = 'Simpan Sebagai Jadwal';
                    }
                }

                if (mode === 'ai') {
                    var now = new Date();
                    now.setDate(now.getDate() + 1);
                    now.setHours(19, 0, 0, 0);
                    scheduleDate.value = now.toISOString().slice(0, 10);
                    scheduleTime.value = '19:00';
                    applyAiRecommendation();
                }
            }

            function pad2(value) {
                return String(value).padStart(2, '0');
            }

            function setAiInfo(text, isError) {
                if (!aiRecommendationInfo) return;
                aiRecommendationInfo.style.color = isError ? '#ffb8b1' : '#a9bfeb';
                aiRecommendationInfo.textContent = text;
            }

            function getNextDateByDayAndHour(dayOfWeek, hour) {
                var now = new Date();
                var target = new Date(now.getTime());
                var currentDay = now.getDay();
                var diff = (dayOfWeek - currentDay + 7) % 7;
                target.setDate(target.getDate() + diff);
                target.setHours(hour, 0, 0, 0);

                if (target <= now) {
                    target.setDate(target.getDate() + 7);
                }

                return target;
            }

            async function applyAiRecommendation() {
                if (currentMode !== 'ai' || isApplyingAiRecommendation) {
                    return;
                }

                if (!socialAccountSelect || !socialAccountSelect.value) {
                    setAiInfo('Pilih akun terhubung untuk mengambil rekomendasi waktu AI.', true);
                    return;
                }

                isApplyingAiRecommendation = true;
                setAiInfo('Mengambil rekomendasi waktu AI...');

                try {
                    var accountId = encodeURIComponent(socialAccountSelect.value);
                    var res = await fetch(aiRecommendationUrl + '?account_id=' + accountId, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    var payload = await res.json();
                    if (!res.ok || !payload.success) {
                        setAiInfo(payload.message || 'Gagal mengambil rekomendasi AI.', true);
                        return;
                    }

                    var bestHour = payload.hours && payload.hours.length ? parseInt(payload.hours[0].hour, 10) : 19;
                    if (isNaN(bestHour) || bestHour < 0 || bestHour > 23) {
                        bestHour = 19;
                    }

                    var now = new Date();
                    var targetDate = new Date(now.getTime());
                    targetDate.setHours(bestHour, 0, 0, 0);

                    // Jika jam sudah lewat hari ini, jadwalkan untuk besok
                    if (targetDate <= now) {
                        targetDate.setDate(targetDate.getDate() + 1);
                    }

                    scheduleDate.value = targetDate.toISOString().slice(0, 10);
                    scheduleTime.value = pad2(targetDate.getHours()) + ':00';

                    var infoText = payload.is_default
                        ? 'AI memakai data default. Jadwal disarankan pada ' + scheduleDate.value + ' ' + scheduleTime.value + '.'
                        : 'AI merekomendasikan jadwal pada ' + scheduleDate.value + ' ' + scheduleTime.value + ' berdasarkan insight akun.';
                    setAiInfo(infoText, false);
                } catch (err) {
                    console.error(err);
                    setAiInfo('Terjadi error saat mengambil rekomendasi AI.', true);
                } finally {
                    isApplyingAiRecommendation = false;
                }
            }

            function submitStore(mode) {
                setMode(mode);
                form.action = "{{ route('posting.store') }}";
                scheduledAtInput.value = '';
                form.submit();
            }

            function submitSchedule(mode) {
                if (mode === 'publish') {
                    submitStore('publish');
                    return;
                }

                setMode(mode);

                if (!scheduleDate.value || !scheduleTime.value) {
                    alert('Tanggal dan waktu jadwal wajib diisi.');
                    return;
                }

                if (!socialAccountSelect || !socialAccountSelect.value) {
                    alert('Pilih akun terhubung untuk menjadwalkan posting.');
                    return;
                }

                scheduledAtInput.value = scheduleDate.value + ' ' + scheduleTime.value + ':00';
                form.action = "{{ route('jadwal.store') }}";
                form.submit();
            }

            platformItems.forEach(function (item) {
                var checkbox = item.querySelector('input[type="checkbox"]');
                if (!checkbox) return;
                checkbox.addEventListener('change', function () {
                    item.classList.toggle('active', checkbox.checked);
                });
            });

            scheduleButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    setMode(btn.getAttribute('data-mode'));
                });
            });

            if (socialAccountSelect) {
                socialAccountSelect.addEventListener('change', function () {
                    if (currentMode === 'ai') {
                        applyAiRecommendation();
                    }
                });
            }

            previewButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    previewButtons.forEach(function (x) { x.classList.remove('active'); });
                    btn.classList.add('active');
                    var platform = btn.getAttribute('data-preview-platform');
                    document.getElementById('previewUser').textContent = platform === 'facebook' ? 'Meta Official Page' : 'luminous_authority';
                });
            });

            if (captionInput) {
                captionInput.addEventListener('input', updatePreviewText);
                captionInput.addEventListener('keyup', updateMentionSuggestions);
                captionInput.addEventListener('click', updateMentionSuggestions);
            }
            if (hashtagsInput) {
                hashtagsInput.addEventListener('input', updatePreviewText);
            }
            if (templateTextInput) {
                templateTextInput.addEventListener('input', function () {
                    updateTemplatePreviewBox();
                    updateMediaPreview();
                });
            }
            if (mediaInput) {
                mediaInput.addEventListener('change', function () {
                    selectedFiles = Array.from(mediaInput.files || []);
                    updateMediaPreview();
                    updateTemplateAvailability();
                });
            }

            if (btnEmoji) {
                btnEmoji.addEventListener('click', function () {
                    if (!emojiPicker) return;
                    emojiPicker.classList.toggle('hide');
                    if (!emojiPicker.classList.contains('hide')) {
                        renderEmojiCategories();
                        renderEmojiGrid();
                    }
                });
            }

            if (btnHashtag) {
                btnHashtag.addEventListener('click', function () {
                    var selected = captionInput.value.substring(captionInput.selectionStart || 0, captionInput.selectionEnd || 0).trim();
                    if (selected) {
                        if (selected.charAt(0) !== '#') {
                            insertAtCursor('#' + selected + ' ');
                        }
                    } else {
                        insertAtCursor('#');
                    }
                });
            }

            if (btnMention) {
                btnMention.addEventListener('click', function () {
                    insertAtCursor('@');
                    updateMentionSuggestions();
                });
            }

            if (templateGrid) {
                templateGrid.addEventListener('click', function (event) {
                    var swatch = event.target.closest('.template-swatch');
                    if (!swatch) return;
                    if (hasMediaSelected()) return;

                    currentTemplate = swatch.getAttribute('data-template') || '';
                    textTemplateInput.value = currentTemplate;
                    updateTemplateSwatches();
                    updateTemplatePreviewBox();
                    updateMediaPreview();
                });
            }

            if (btnSaveDraftTop) {
                btnSaveDraftTop.addEventListener('click', function () {
                    submitStore('draft');
                });
            }

            if (btnPublishTop) {
                btnPublishTop.addEventListener('click', function () {
                    submitStore('publish');
                });
            }

            if (btnScheduleSubmit) {
                btnScheduleSubmit.addEventListener('click', function () {
                    if (currentMode === 'publish') {
                        submitStore('publish');
                        return;
                    }

                    submitSchedule(currentMode === 'ai' ? 'ai' : 'schedule');
                });
            }

            try {
                setMode("{{ old('submit_mode', 'publish') }}");
            } catch (e) {
                console.error('setMode init failed', e);
            }
            selectedFiles = mediaInput ? Array.from(mediaInput.files || []) : [];
            renderEmojiCategories();
            renderEmojiGrid();
            updateTemplateSwatches();
            updatePreviewText();
            updateMediaPreview();
            updateTemplateAvailability();

            // Caption Generator Modal (create page)
            const btnGenerateCaption = document.getElementById('btnGenerateCaption');
            const captionModal = document.createElement('div');
            captionModal.innerHTML = `
            <style>
                .modal-overlay { position: fixed; inset: 0; background: rgba(2,6,23,0.6); display: grid; place-items: center; z-index: 90; }
                .modal-overlay.hide { display: none; }
                .modal-content { width: 720px; max-width: 96%; background: #0f1724; border: 1px solid rgba(149,174,233,0.12); border-radius: 10px; padding: 14px; box-shadow: 0 12px 40px rgba(2,7,18,0.6); color: #e8f0ff; }
                .modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
                .modal-close { background: transparent; border: 0; color: #cfe6ff; font-size:20px; cursor:pointer; }
                .modal-body .f-textarea { min-height: 100px; }
            </style>
            <div id="captionModal" class="modal-overlay hide">
                <div class="modal-content">
                    <div class="modal-head">
                        <strong>Generate Caption</strong>
                        <button id="closeCaptionModal" class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <label for="captionPrompt">Prompt</label>
                        <textarea id="captionPrompt" class="f-textarea" placeholder="Masukkan prompt untuk membuat caption..."></textarea>
                        <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                            <button id="captionGenerateBtn" class="btn-cm">Generate</button>
                            <span id="captionGenerating" style="color:#9db1d8; display:none;">Generating...</span>
                        </div>
                        <label id="captionResultLabel" style="margin-top:10px; display:block;">Hasil</label>
                        <textarea id="captionResult" class="f-textarea" style="min-height:120px;"></textarea>
                    </div>
                    <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:10px;">
                        <button id="captionInsertBtn" class="btn-cm primary" disabled>Selesai</button>
                        <button id="captionCloseBtn" class="btn-cm secondary">Tutup</button>
                    </div>
                </div>
            </div>`;

            document.body.appendChild(captionModal);

            const captionModalEl = document.getElementById('captionModal');
            const captionPrompt = document.getElementById('captionPrompt');
            const captionGenerateBtn = document.getElementById('captionGenerateBtn');
            const captionGenerating = document.getElementById('captionGenerating');
            const captionResult = document.getElementById('captionResult');
            const captionResultLabel = document.getElementById('captionResultLabel');
            const captionInsertBtn = document.getElementById('captionInsertBtn');
            const captionCloseBtn = document.getElementById('captionCloseBtn');
            const closeCaptionModal = document.getElementById('closeCaptionModal');

            const captionGenerateUrl = "{{ route('captions.generate.ajax') }}";
            const csrfToken = "{{ csrf_token() }}";
            const currentPostId = 0;

            function openCaptionModal() {
                if (captionModalEl) captionModalEl.classList.remove('hide');
                if (captionPrompt) captionPrompt.focus();
            }

            function closeCaptionModalFn() {
                if (captionModalEl) captionModalEl.classList.add('hide');
                if (captionPrompt) captionPrompt.value = '';
                if (captionResult) captionResult.value = '';
                if (captionResultLabel) captionResultLabel.textContent = 'Hasil';
                if (captionInsertBtn) captionInsertBtn.disabled = true;
            }

            if (btnGenerateCaption) {
                btnGenerateCaption.addEventListener('click', function () {
                    openCaptionModal();
                });
            }

            if (captionCloseBtn) captionCloseBtn.addEventListener('click', closeCaptionModalFn);
            if (closeCaptionModal) closeCaptionModal.addEventListener('click', closeCaptionModalFn);

            if (captionGenerateBtn) {
                captionGenerateBtn.addEventListener('click', async function () {
                    const prompt = captionPrompt.value.trim();
                    if (!prompt) {
                        alert('Masukkan prompt terlebih dahulu.');
                        return;
                    }

                    captionGenerateBtn.disabled = true;
                    captionGenerating.style.display = 'inline';
                    captionResult.value = '';
                    if (captionInsertBtn) captionInsertBtn.disabled = true;

                    try {
                        const res = await fetch(captionGenerateUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ prompt: prompt })
                        });

                        const data = await res.json();

                        if (res.ok && data.success && data.caption) {
                            captionResult.value = data.caption;
                            captionResult.style.minHeight = '300px';
                            if (captionResultLabel) captionResultLabel.textContent = 'Hasil Caption AI';
                            if (captionInsertBtn) captionInsertBtn.disabled = false;
                        } else {
                            alert(data.message || 'Gagal membuat caption.');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Terjadi kesalahan saat generate caption.');
                    } finally {
                        captionGenerateBtn.disabled = false;
                        captionGenerating.style.display = 'none';
                    }
                });
            }

            if (captionInsertBtn) {
                captionInsertBtn.addEventListener('click', function () {
                    if (!captionResult.value) return;
                    captionInput.value = captionResult.value;
                    charCount.textContent = captionInput.value.length;
                    updatePreviewText();
                    closeCaptionModalFn();
                });
            }

            // Close modal on ESC
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && captionModalEl && !captionModalEl.classList.contains('hide')) {
                    closeCaptionModalFn();
                }
            });

        })();
    </script>
@endsection
