@extends('layout.main')

@section('title', 'Edit Postingan')

@section('content')
    @php
        $mentionEntries = ($akun_terhubung ?? collect())->map(function ($a) {
            $display = (string) ($a->username ?: $a->platform_user_id);

            return [
                'display'  => $display,
                'platform' => (string) $a->platform,
                'handle'   => preg_replace('/[^a-zA-Z0-9._]/', '', str_replace(' ', '', $display)),
            ];
        })->values();
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
            transition: 0.2s ease;
        }

        .btn-cm:hover {
            background: rgba(31, 43, 69, 0.95);
        }

        .btn-cm.primary {
            background: linear-gradient(180deg, #9fc1ff, #6f9eff);
            color: #0f2450;
            border-color: transparent;
        }

        .btn-cm.primary:hover {
            opacity: 0.9;
        }

        .btn-cm.secondary {
            background: rgba(49, 71, 111, 0.5);
            color: #d8e6ff;
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

        .platform-item:hover {
            border-color: rgba(149, 174, 233, 0.4);
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
        .f-textarea {
            width: 100%;
            border-radius: 10px;
            border: 1px solid rgba(128, 152, 205, 0.26);
            background: rgba(19, 28, 47, 0.82);
            color: #e8f0ff;
            padding: 10px 11px;
            font-size: 13px;
            font-family: inherit;
        }

        .f-input:focus,
        .f-textarea:focus {
            outline: none;
            border-color: rgba(149, 174, 233, 0.5);
            box-shadow: 0 0 0 3px rgba(149, 174, 233, 0.1);
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
            transition: 0.2s ease;
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
            display: flex;
            align-items: center;
            justify-content: center;
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

        .status-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-radius: 10px;
            background: rgba(24, 35, 56, 0.5);
            border: 1px solid rgba(149, 174, 233, 0.2);
            margin-bottom: 14px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.draft {
            background: rgba(255, 152, 0, 0.15);
            color: #ffb74d;
        }

        .status-badge.scheduled {
            background: rgba(76, 175, 80, 0.15);
            color: #81c784;
        }

        .status-badge.published {
            background: rgba(33, 150, 243, 0.15);
            color: #64b5f6;
        }

        .status-badge.failed {
            background: rgba(244, 67, 54, 0.15);
            color: #ef5350;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .media-info {
            padding: 12px;
            border-radius: 10px;
            background: rgba(24, 35, 56, 0.5);
            border: 1px solid rgba(149, 174, 233, 0.2);
            margin-bottom: 12px;
            font-size: 13px;
            color: #dbe7ff;
        }

        .media-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }

        .media-thumb {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(139, 165, 220, 0.2);
            background: rgba(19, 28, 47, 0.82);
            aspect-ratio: 1;
        }

        .media-thumb img,
        .media-thumb video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hide {
            display: none;
        }

        @media (max-width: 1180px) {
            .composer-page {
                grid-template-columns: 1fr;
            }

            .composer-right {
                grid-template-columns: 1fr;
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

            .platform-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section>
        <div class="composer-head">
            <div>
                <p class="composer-breadcrumb">Pages / Edit Postingan</p>
                <h2>Edit Postingan</h2>
                <p>Perbarui konten media sosial Anda dengan mudah.</p>
            </div>

            <div class="top-actions">
                @php
                    $postLog = $postLogs[$postingan->id] ?? null;
                    $platformUrl = null;
                    
                    if ($postingan->status === 'published' && $postLog && !empty($postLog->platform_post_id)) {
                        $platformPostId = $postLog->platform_post_id;
                        $platforms = is_array($postingan->platform_targets) ? $postingan->platform_targets : [];
                        $platform = !empty($platforms) ? reset($platforms) : 'facebook';
                        
                        if ($platform === 'instagram') {
                            $platformUrl = "https://www.instagram.com/p/{$platformPostId}/";
                        } else {
                            $platformUrl = "https://www.facebook.com/{$platformPostId}/";
                        }
                    }
                @endphp

                @if($platformUrl)
                    <a href="{{ $platformUrl }}" class="btn-cm secondary" target="_blank" rel="noopener noreferrer">Lihat di Platform</a>
                @else
                    <span class="btn-cm secondary" style="opacity:0.6;cursor:default;">Detail</span>
                @endif
                <button type="submit" form="updateForm" class="btn-cm primary">Simpan Perubahan</button>
            </div>
        </div>

        @if(session('success')) <div class="flash success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="flash error">{{ session('error') }}</div> @endif
        @error('caption') <div class="flash error">{{ $message }}</div> @enderror
        @error('platforms') <div class="flash error">{{ $message }}</div> @enderror
        @error('hashtags') <div class="flash error">{{ $message }}</div> @enderror

        <form id="updateForm" method="POST" action="{{ route('postingan.update', $postingan->id) }}">
            @csrf
            @method('PUT')

            <div class="composer-page">
                <div class="composer-left">
                    <!-- Status Info -->
                    <div class="status-info">
                        <span>Status Postingan:</span>
                        <span class="status-badge {{ strtolower($postingan->status) }}">
                            {{ ucfirst($postingan->status) }}
                        </span>
                        <span style="margin-left: auto; color: #8098c6; font-size: 12px;">
                            Dibuat {{ $postingan->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <!-- Platform Selection -->
                    <article class="composer-card composer-main">
                        <p class="section-label">Platform Tujuan</p>
                        <div class="platform-grid">
                            <label class="platform-item {{ in_array('instagram', $postingan->platform_targets) ? 'active' : '' }}" data-platform="instagram">
                                <input class="invisible-check" type="checkbox" name="platforms[]" value="instagram" {{ in_array('instagram', $postingan->platform_targets ?? []) ? 'checked' : '' }}>
                                <span class="platform-icon instagram"><i class="fab fa-instagram"></i></span>
                                <span>
                                    <p class="platform-title">Instagram</p>
                                    <p class="platform-sub">Feeds | Reels</p>
                                </span>
                            </label>

                            <label class="platform-item {{ in_array('facebook', $postingan->platform_targets) ? 'active' : '' }}" data-platform="facebook">
                                <input class="invisible-check" type="checkbox" name="platforms[]" value="facebook" {{ in_array('facebook', $postingan->platform_targets ?? []) ? 'checked' : '' }}>
                                <span class="platform-icon facebook"><i class="fab fa-facebook-f"></i></span>
                                <span>
                                    <p class="platform-title">Facebook</p>
                                    <p class="platform-sub">Wall Post</p>
                                </span>
                            </label>
                        </div>
                    </article>

                    <!-- Content Form -->
                    <article class="composer-card composer-main">
                        <p class="section-label">Detail Konten</p>
                        <div class="content-form">
                            <div class="f-field">
                                <label for="caption">Isi Konten</label>
                                <textarea id="caption" class="f-textarea" name="caption" required placeholder="Apa yang ingin Anda sampaikan?">{{ old('caption', $postingan->caption) }}</textarea>
                                <div class="meta-inline">
                                    <span class="quick-tools">
                                        <button type="button" class="quick-btn" id="btnEmoji"><i class="far fa-smile"></i> Emoji</button>
                                        <button type="button" class="quick-btn" id="btnHashtag"><i class="fas fa-hashtag"></i> Hashtag</button>
                                        <button type="button" class="quick-btn" id="btnMention"><i class="fas fa-at"></i> Tag</button>
                                        <button type="button" class="quick-btn" id="btnGenerateCaption"><i class="fas fa-magic"></i> Generate Caption</button>
                                    </span>
                                    <span><span id="charCount">{{ strlen(old('caption', $postingan->caption)) }}</span> / 2200 karakter</span>
                                </div>
                                <div id="emojiPicker" class="emoji-pop hide">
                                    <div id="emojiCats" class="emoji-cats"></div>
                                    <div id="emojiGrid" class="emoji-grid"></div>
                                </div>
                                <div id="mentionBox" class="mention-box hide"></div>

                                <div id="textTemplateWrap" class="template-wrap">
                                    <input type="hidden" id="textTemplateInput" name="text_template" value="{{ old('text_template', $postingan->text_template) }}">
                                    <div class="f-field" style="margin-bottom:8px;">
                                        <label for="templateTextInput">Teks Background Template</label>
                                        <input
                                            id="templateTextInput"
                                            class="f-input"
                                            type="text"
                                            name="template_text"
                                            maxlength="220"
                                            value="{{ old('template_text', $postingan->template_text ?? $postingan->caption) }}"
                                            placeholder="Contoh: Promo Spesial Hari Ini"
                                        >
                                        <small style="color:#8ea3cc;display:block;margin-top:6px;font-size:11px;">
                                            Teks ini khusus untuk gambar background template dan bisa berbeda dari Isi Konten.
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
                            </div>

                            <div class="f-field" style="margin-bottom:0;">
                                <label for="hashtags">Hashtags</label>
                                <input id="hashtags" class="f-input" type="text" name="hashtags" value="{{ old('hashtags', $postingan->hashtags) }}" placeholder="#MetaAutomation #FutureTech">
                            </div>
                        </div>
                    </article>

                    <!-- Media Info -->
                    @if($postingan->media && $postingan->media->count() > 0)
                        <article class="composer-card composer-main">
                            <p class="section-label">Media (Foto/Video)</p>
                            <div class="media-info">
                                <strong>{{ $postingan->media->count() }}</strong> file media terpasang pada postingan ini.
                                <br><span style="font-size: 11px; color: #8098c6;">Media tidak dapat diubah untuk postingan yang sudah dijadwalkan atau dipublikasi.</span>
                            </div>
                            <div class="media-gallery">
                                @foreach($postingan->media as $media)
                                    <div class="media-thumb">
                                        @if(strpos($media->mime_type, 'image') !== false)
                                            <img src="{{ $media->file_url }}" alt="Media" />
                                        @else
                                            <video>
                                                <source src="{{ $media->file_url }}" />
                                            </video>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endif

                </div>

                <!-- RIGHT COLUMN: Live Preview -->
                <div class="composer-right">
                    <article class="composer-card preview-card">
                        <p class="section-label">Live Preview</p>
                        <div class="preview-toggle">
                            <button class="active" type="button" data-preview-platform="instagram" onclick="switchPlatform(event, 'instagram')">Instagram</button>
                            <button type="button" data-preview-platform="facebook" onclick="switchPlatform(event, 'facebook')">Facebook</button>
                        </div>

                        <div class="phone-shell">
                            <div class="phone-screen">
                                <div class="pv-head">
                                    <span class="pv-user" id="previewUser">{{ $postingan->user->name ?? 'User' }}</span>
                                    <span><i class="fas fa-ellipsis-h"></i></span>
                                </div>
                                <div class="pv-media" id="previewMedia">
                                    @if($postingan->media && $postingan->media->count() > 0)
                                        @php $firstMedia = $postingan->media->first(); @endphp
                                        @if(strpos($firstMedia->mime_type, 'image') !== false)
                                            <img src="{{ $firstMedia->file_url }}" alt="Preview" />
                                        @else
                                            <video style="width: 100%; height: 100%;" controls>
                                                <source src="{{ $firstMedia->file_url }}" />
                                            </video>
                                        @endif
                                    @else
                                        <div style="color: #5a7a9e; font-size: 12px;">Tidak ada media dipilih</div>
                                    @endif
                                </div>
                                <div class="pv-meta">
                                    <div style="font-size:11px;color:#ffffff;"><i class="fas fa-heart"></i> &nbsp;<i class="fas fa-comment"></i> &nbsp;<i class="fas fa-paper-plane"></i></div>
                                    <p class="pv-caption" id="previewCaption">{{ $postingan->caption }}</p>
                                    <p class="pv-tags" id="previewTags">{{ $postingan->hashtags }}</p>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="composer-card tips">
                        <strong>💡 Tips Editing</strong><br>
                        Perubahan akan disimpan ke database. Jangan lupa untuk memverifikasi preview sebelum menyimpan perubahan.
                    </article>
                </div>
            </div>
        </form>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap;">
            <a href="{{ route('postingan') }}" class="btn-cm secondary">
                <i class="fas fa-arrow-left" style="margin-right: 6px;"></i>Kembali
            </a>
            @php
                $postLog = $postLogs[$postingan->id] ?? null;
                $platformUrl = null;
                
                if ($postingan->status === 'published' && $postLog && !empty($postLog->platform_post_id)) {
                    $platformPostId = $postLog->platform_post_id;
                    $platforms = is_array($postingan->platform_targets) ? $postingan->platform_targets : [];
                    $platform = !empty($platforms) ? reset($platforms) : 'facebook';
                    
                    if ($platform === 'instagram') {
                        $platformUrl = "https://www.instagram.com/p/{$platformPostId}/";
                    } else {
                        $platformUrl = "https://www.facebook.com/{$platformPostId}/";
                    }
                }
            @endphp

            @if($platformUrl)
                <a href="{{ $platformUrl }}" class="btn-cm secondary" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-external-link-alt" style="margin-right: 6px;"></i>Lihat di Platform
                </a>
            @else
                <span class="btn-cm secondary" style="opacity:0.6;cursor:default;">
                    <i class="fas fa-eye" style="margin-right: 6px;"></i>Detail
                </span>
            @endif
            @if(in_array($postingan->status, ['draft', 'scheduled']))
                <form method="POST" action="{{ route('postingan.hapus', $postingan->id) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-cm" style="background: rgba(244, 67, 54, 0.15); color: #ef5350; border-color: rgba(244, 67, 54, 0.3);" onclick="return confirm('Yakin hapus postingan ini?')">
                        <i class="fas fa-trash-alt" style="margin-right: 6px;"></i>Hapus
                    </button>
                </form>
            @endif
        </div>
    </section>

    <script>
        // Update char count
        const captionInput = document.getElementById('caption');
        const charCount = document.getElementById('charCount');
        const btnEmoji = document.getElementById('btnEmoji');
        const btnHashtag = document.getElementById('btnHashtag');
        const btnMention = document.getElementById('btnMention');
        const emojiPicker = document.getElementById('emojiPicker');
        const emojiCats = document.getElementById('emojiCats');
        const emojiGrid = document.getElementById('emojiGrid');
        const mentionBox = document.getElementById('mentionBox');
        let activeEmojiCat = 'smileys';
        let mentionState = null;
        const mentionEntries = @json($mentionEntries);

        const emojiCatalog = {
            smileys: ['😀', '😁', '😂', '🤣', '😊', '😍', '😘', '😎', '🤩', '🥳', '😇', '🙂', '😉', '🤗', '🤔', '😴'],
            gestures: ['👍', '👎', '👏', '🙌', '🙏', '💪', '👊', '🤝', '👌', '✌️', '🤘', '🫶', '🫡', '🖐️'],
            objects: ['🔥', '🚀', '✨', '💡', '🎯', '📈', '📌', '📣', '💻', '📱', '🎉', '🏆', '⚡', '🛠️'],
            symbols: ['✅', '❌', '⚠️', '⭐', '❤️', '💙', '💚', '💛', '💜', '#️⃣', '@', '&', '➕', '➖']
        };

        function insertAtCursor(text) {
            const start = captionInput.selectionStart || 0;
            const end = captionInput.selectionEnd || 0;
            const value = captionInput.value || '';
            captionInput.value = value.substring(0, start) + text + value.substring(end);
            captionInput.focus();
            const pos = start + text.length;
            captionInput.setSelectionRange(pos, pos);
            updatePreview();
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
            ].forEach(cat => {
                const btn = document.createElement('button');
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

            (emojiCatalog[activeEmojiCat] || []).forEach(emoji => {
                const btn = document.createElement('button');
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
            const value = captionInput.value || '';
            const before = value.slice(0, mentionState.atIndex + 1);
            const after = value.slice(mentionState.cursorPos);
            const mentionText = handle + ' ';

            captionInput.value = before + mentionText + after;
            const newPos = before.length + mentionText.length;
            captionInput.focus();
            captionInput.setSelectionRange(newPos, newPos);
            hideMentionSuggestions();
            updatePreview();
        }

        function updateMentionSuggestions() {
            if (!mentionBox) return;

            const cursorPos = captionInput.selectionStart || 0;
            const value = captionInput.value || '';
            const left = value.slice(0, cursorPos);
            const atIndex = left.lastIndexOf('@');

            if (atIndex < 0) {
                hideMentionSuggestions();
                return;
            }

            const boundary = atIndex > 0 ? left.charAt(atIndex - 1) : ' ';
            if (boundary !== ' ' && boundary !== '\n' && boundary !== '\t') {
                hideMentionSuggestions();
                return;
            }

            const query = left.slice(atIndex + 1);
            if (/\s/.test(query)) {
                hideMentionSuggestions();
                return;
            }

            const filtered = mentionEntries.filter(item => {
                const q = query.toLowerCase();
                return !q || item.handle.toLowerCase().includes(q) || item.display.toLowerCase().includes(q);
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
            filtered.forEach(item => {
                const btn = document.createElement('button');
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

        if (captionInput) {
            captionInput.addEventListener('input', function() {
                charCount.textContent = this.value.length;
                updatePreview();
            });
            captionInput.addEventListener('keyup', updateMentionSuggestions);
            captionInput.addEventListener('click', updateMentionSuggestions);
        }

        // Update hashtags preview
        const hashtagsInput = document.getElementById('hashtags');
        const textTemplateInput = document.getElementById('textTemplateInput');
        const templateTextInput = document.getElementById('templateTextInput');
        const textTemplateWrap = document.getElementById('textTemplateWrap');
        const templateGrid = document.getElementById('templateGrid');
        const textTemplatePreview = document.getElementById('textTemplatePreview');
        const previewMedia = document.getElementById('previewMedia');
        const hasMedia = {{ ($postingan->media && $postingan->media->count() > 0) ? 'true' : 'false' }};

        let currentTemplate = textTemplateInput ? (textTemplateInput.value || '') : '';

        const templateClassMap = {
            'classic_aurora': 'tpl-classic-aurora',
            'sunset_fade': 'tpl-sunset-fade',
            'royal_plum': 'tpl-royal-plum',
            'emerald_wave': 'tpl-emerald-wave',
            'midnight_blue': 'tpl-midnight-blue',
            'orange_pop': 'tpl-orange-pop',
            'mono_ink': 'tpl-mono-ink',
            'neon_blend': 'tpl-neon-blend'
        };

        function applyTemplateClass(target, template) {
            if (!target) return;
            Object.keys(templateClassMap).forEach(key => target.classList.remove(templateClassMap[key]));
            if (template && templateClassMap[template]) {
                target.classList.add(templateClassMap[template]);
            }
        }

        function refreshTemplateUi() {
            if (!templateGrid || !textTemplatePreview) return;

            templateGrid.querySelectorAll('.template-swatch').forEach(swatch => {
                swatch.classList.toggle('active', (swatch.getAttribute('data-template') || '') === currentTemplate);
            });

            applyTemplateClass(textTemplatePreview, currentTemplate);
            const templateText = (templateTextInput && templateTextInput.value.trim())
                ? templateTextInput.value.trim()
                : ((captionInput && captionInput.value.trim()) || '');
            textTemplatePreview.textContent = templateText || 'Pratinjau teks template';
            textTemplatePreview.style.background = currentTemplate ? '' : 'rgba(23, 35, 56, 0.76)';
        }
        if (hashtagsInput) {
            hashtagsInput.addEventListener('input', function() {
                updatePreview();
            });
        }

        // Platform selection
        document.querySelectorAll('.platform-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.closest('input[type="checkbox"]')) {
                    this.classList.toggle('active');
                    updatePreview();
                }
            });
        });

        // Update preview
        function updatePreview() {
            const caption = document.getElementById('caption').value;
            const hashtags = document.getElementById('hashtags').value;
            const templateText = (templateTextInput && templateTextInput.value.trim())
                ? templateTextInput.value.trim()
                : caption.trim();

            document.getElementById('previewCaption').textContent = caption;
            document.getElementById('previewTags').textContent = hashtags;

            if (!hasMedia && previewMedia) {
                if (currentTemplate && templateText) {
                    previewMedia.innerHTML = '';
                    const block = document.createElement('div');
                    block.className = 'template-preview';
                    block.style.minHeight = '100%';
                    block.style.borderRadius = '0';
                    block.style.border = '0';
                    block.style.fontSize = '18px';
                    block.style.padding = '16px';
                    applyTemplateClass(block, currentTemplate);
                    block.textContent = templateText;
                    previewMedia.appendChild(block);
                }
            }

            refreshTemplateUi();
        }

        // Switch platform in preview
        function switchPlatform(e, platform) {
            e.preventDefault();
            document.querySelectorAll('.preview-toggle button').forEach(btn => {
                btn.classList.remove('active');
            });
            e.target.classList.add('active');
        }

        if (textTemplateWrap && hasMedia) {
            textTemplateWrap.classList.add('disabled');
            if (templateTextInput) {
                templateTextInput.disabled = true;
            }
            currentTemplate = '';
            if (textTemplateInput) {
                textTemplateInput.value = '';
            }
        }

        if (templateTextInput) {
            templateTextInput.addEventListener('input', function () {
                refreshTemplateUi();
                updatePreview();
            });
        }

        if (templateGrid) {
            templateGrid.addEventListener('click', function (event) {
                const swatch = event.target.closest('.template-swatch');
                if (!swatch || hasMedia) return;

                currentTemplate = swatch.getAttribute('data-template') || '';
                if (textTemplateInput) {
                    textTemplateInput.value = currentTemplate;
                }
                refreshTemplateUi();
                updatePreview();
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
                const selected = captionInput.value.substring(captionInput.selectionStart || 0, captionInput.selectionEnd || 0).trim();
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

        renderEmojiCategories();
        renderEmojiGrid();
        refreshTemplateUi();
        updatePreview();

        /* Caption Generator Modal */
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
                    <label style="margin-top:10px; display:block;">Hasil</label>
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
        const captionInsertBtn = document.getElementById('captionInsertBtn');
        const captionCloseBtn = document.getElementById('captionCloseBtn');
        const closeCaptionModal = document.getElementById('closeCaptionModal');

        const captionGenerateUrl = "{{ route('captions.generate.ajax') }}";
        const csrfToken = "{{ csrf_token() }}";
        const currentPostId = {{ $postingan->id }};

        function openCaptionModal() {
            if (captionModalEl) captionModalEl.classList.remove('hide');
            if (captionPrompt) captionPrompt.focus();
        }

        function closeCaptionModalFn() {
            if (captionModalEl) captionModalEl.classList.add('hide');
            if (captionPrompt) captionPrompt.value = '';
            if (captionResult) captionResult.value = '';
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
                updatePreview();
                closeCaptionModalFn();
            });
        }

        // Close modal on ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && captionModalEl && !captionModalEl.classList.contains('hide')) {
                closeCaptionModalFn();
            }
        });

    </script>
@endsection
