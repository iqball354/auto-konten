@php
    $navUnreadCount = $unreadCount ?? 0;
    $navUser = Auth::user();
    $navDisabled = Auth::check() && !$navUser->is_active; // jika user tidak aktif, non-aktifkan menu kecuali profile
    $navAvatarUrl = ($navUser && $navUser->avatar)
        ? asset('storage/' . ltrim($navUser->avatar, '/'))
        : null;
@endphp

<div class="topbar-wrap">
    <style>
        .topbar-wrap {
            margin-bottom: 14px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 2px 0;
        }

        .topbar-left .crumb {
            margin: 0;
            font-size: 12px;
            color: #7f90b2;
        }

        .topbar-left .crumb b {
            color: #d9e5ff;
            font-weight: 600;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-toggle {
            display: none;
        }

        .search-box {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 260px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(162, 183, 255, 0.12);
            background: rgba(20, 30, 48, 0.72);
            color: #91a2c5;
        }

        .search-box input {
            width: 100%;
            border: 0;
            outline: none;
            background: transparent;
            color: #dce8ff;
            font-family: inherit;
            font-size: 13px;
        }

        .search-box input::placeholder {
            color: #6f81a7;
        }

        .icon-btn {
            width: 34px;
            height: 34px;
            border: 1px solid rgba(162, 183, 255, 0.16);
            border-radius: 10px;
            background: rgba(20, 30, 48, 0.72);
            color: #9eb0d1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            position: relative;
            transition: all 0.2s ease;
        }

        .icon-btn svg,
        .search-box svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex: 0 0 auto;
        }

        .icon-btn:hover {
            color: #eef4ff;
            border-color: rgba(133, 168, 255, 0.42);
            background: rgba(31, 45, 70, 0.82);
        }

        .icon-btn.disabled, .menu-toggle.disabled {
            pointer-events: none;
            opacity: 0.5;
            cursor: not-allowed;
            border-color: rgba(162, 183, 255, 0.06);
            color: rgba(158,176,209,0.6);
            background: rgba(20,30,48,0.5);
        }

        .notif-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 18px;
            height: 18px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f77f77;
            color: #fff;
            border: 2px solid #091021;
            padding: 0 4px;
        }

        .profile-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px 4px 4px;
            border-radius: 999px;
            border: 1px solid rgba(162, 183, 255, 0.16);
            background: rgba(20, 30, 48, 0.72);
            color: #dbe6ff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .profile-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: linear-gradient(180deg, #9fc1ff, #6b9dff);
            color: #12244c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            overflow: hidden;
        }

        .profile-dot img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .topbar {
                flex-wrap: wrap;
            }

            .search-box {
                width: 100%;
                order: 3;
            }

            .topbar-actions {
                width: 100%;
                justify-content: flex-end;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 1279px) {
            .menu-toggle {
                display: inline-flex;
            }
        }
    </style>

    <header class="topbar">
        <div class="topbar-left">
            <p class="crumb">Pages / <b>@yield('title', 'Dashboard')</b></p>
        </div>

        <div class="topbar-actions">
            <button type="button" class="icon-btn menu-toggle{{ $navDisabled ? ' disabled' : '' }}" id="menu-toggle" title="Menu" {{ $navDisabled ? 'aria-disabled="true" tabindex="-1"' : '' }}>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <label class="search-box" for="global-search">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input id="global-search" type="text" placeholder="Cari konten...">
            </label>

            <a href="{{ $navDisabled ? 'javascript:void(0)' : route('notifikasi') }}" class="icon-btn{{ $navDisabled ? ' disabled' : '' }}" title="Notifikasi" {{ $navDisabled ? 'aria-disabled="true" tabindex="-1"' : '' }}>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M15 17H5a1 1 0 0 1-.8-1.6c.7-.95 1.8-2.8 1.8-5.4a6 6 0 0 1 12 0c0 2.6 1.1 4.45 1.8 5.4A1 1 0 0 1 19 17h-4"></path>
                    <path d="M9 17a3 3 0 0 0 6 0"></path>
                </svg>
                @if($navUnreadCount > 0)
                    <span class="notif-badge">{{ $navUnreadCount > 99 ? '99+' : $navUnreadCount }}</span>
                @endif
            </a>

            {{-- Profile always clickable so user can access payment/profile even if inactive --}}
            <a href="{{ route('profile') }}" class="profile-chip" title="Profile">
                <span class="profile-dot">
                    @if($navAvatarUrl)
                        <img src="{{ $navAvatarUrl }}" alt="avatar">
                    @else
                        {{ strtoupper(substr($navUser->name ?? 'U', 0, 1)) }}
                    @endif
                </span>
                <span>{{ Auth::check() && Auth::user()->role === 'admin' ? 'Admin' : 'User' }}</span>
            </a>
        </div>
    </header>

    <script>
        (function () {
            var trigger = document.getElementById('menu-toggle');
            var sidebar = document.querySelector('.app-sidebar');
            var overlay = document.getElementById('app-sidebar-overlay');

            if (!trigger || !sidebar) {
                return;
            }

            function setSidebarState(isOpen) {
                if (isOpen) {
                    sidebar.classList.add('open');
                    if (overlay) {
                        overlay.classList.add('show');
                    }
                } else {
                    sidebar.classList.remove('open');
                    if (overlay) {
                        overlay.classList.remove('show');
                    }
                }
            }

            trigger.addEventListener('click', function () {
                // block toggle when user is inactive
                if ({{ $navDisabled ? 'true' : 'false' }}) {
                    return;
                }
                setSidebarState(!sidebar.classList.contains('open'));
            });

            if (overlay) {
                overlay.addEventListener('click', function () {
                    setSidebarState(false);
                });
            }

            window.addEventListener('resize', function () {
                if (window.innerWidth > 1279) {
                    setSidebarState(false);
                }
            });
        })();
    </script>
</div>
