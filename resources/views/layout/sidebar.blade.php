@php
    $menu = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'ni ni-tv-2'],
        ['label' => 'Akun Terhubung', 'route' => 'akun_terhubung', 'icon' => 'ni ni-circle-08'],
        ['label' => 'Buat Postingan', 'route' => 'postingan', 'icon' => 'ni ni-fat-add'],
        ['label' => 'Ekspor Data', 'route' => 'ekspor_data', 'icon' => 'ni ni-archive-2'],
        ['label' => 'Profile', 'route' => 'profile', 'icon' => 'ni ni-single-02'],
    ];

    $sidebarUser = Auth::user();
    $sidebarDisabled = Auth::check() && !$sidebarUser->is_active;
@endphp

<aside class="app-sidebar" aria-expanded="true">
    <style>
        .app-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            padding: 16px 14px;
            background: linear-gradient(180deg, rgba(19, 29, 47, 0.95), rgba(13, 21, 36, 0.95));
            border-right: 1px solid rgba(162, 183, 255, 0.14);
            display: flex;
            flex-direction: column;
            z-index: 40;
        }

        .sb-brand {
            display: flex;
            gap: 12px;
            align-items: center;
            color: #eaf0ff;
            text-decoration: none;
            padding: 10px 10px 14px;
            border-bottom: 1px solid rgba(130, 154, 211, 0.15);
        }

        .sb-brand-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #9ec0ff, #6f9fff);
            color: #11203f;
        }

        .sb-brand h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
        }

        .sb-brand p {
            margin: 2px 0 0;
            font-size: 9px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #8da0c5;
        }

        .sb-menu {
            margin-top: 14px;
            display: grid;
            gap: 2px;
        }

        .sb-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #aeb9d2;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 11px 12px;
            border-radius: 10px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .sb-item i {
            font-size: 14px;
        }

        .sb-item:hover {
            background: rgba(116, 155, 247, 0.12);
            color: #e4ecff;
        }

        .sb-item.disabled {
            pointer-events: none;
            opacity: 0.5;
            cursor: not-allowed;
            color: rgba(174,185,210,0.6);
            background: transparent;
        }

        .sb-item.active {
            background: linear-gradient(180deg, rgba(113, 151, 242, 0.23), rgba(76, 116, 213, 0.18));
            color: #eff4ff;
            border: 1px solid rgba(133, 168, 255, 0.3);
        }

        .sb-spacer {
            flex: 1;
        }

        .sb-foot {
            border-top: 1px solid rgba(130, 154, 211, 0.15);
            padding-top: 10px;
            display: grid;
            gap: 4px;
        }

        .sb-foot a,
        .sb-foot button {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #aeb9d2;
            text-decoration: none;
            border: 0;
            background: transparent;
            width: 100%;
            text-align: left;
            font-family: inherit;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .sb-foot a:hover,
        .sb-foot button:hover {
            background: rgba(116, 155, 247, 0.12);
            color: #eff4ff;
        }

        .mobile-trigger {
            display: none;
        }

        @media (max-width: 1279px) {
            .app-sidebar {
                position: fixed;
                left: -310px;
                width: 280px;
                transition: left 0.25s ease;
            }

            .app-sidebar.open {
                left: 0;
            }

            .mobile-trigger {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>

    <a class="sb-brand{{ $sidebarDisabled ? ' disabled' : '' }}" href="{{ $sidebarDisabled ? 'javascript:void(0)' : route('dashboard') }}" {{ $sidebarDisabled ? 'aria-disabled="true" tabindex="-1"' : '' }}>
        <span class="sb-brand-icon"><i class="ni ni-app"></i></span>
        <span>
            <h1>META</h1>
            <p>Automation Engine</p>
        </span>
    </a>

    <nav class="sb-menu">
        @foreach($menu as $item)
            @php
                $isActive = request()->routeIs($item['route']);
                $isProfile = $item['route'] === 'profile';
                $disabled = $sidebarDisabled && !$isProfile;
            @endphp
            <a href="{{ $disabled ? 'javascript:void(0)' : route($item['route']) }}" class="sb-item {{ $isActive ? 'active' : '' }}{{ $disabled ? ' disabled' : '' }}" {{ $disabled ? 'aria-disabled="true" tabindex="-1"' : '' }}>
                <i class="{{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        @if(auth()->check() && auth()->user()->role === 'admin')
            @php $disabled = $sidebarDisabled; @endphp
            <a href="{{ $disabled ? 'javascript:void(0)' : route('kelola_user') }}" class="sb-item {{ request()->routeIs('kelola_user') ? 'active' : '' }}{{ $disabled ? ' disabled' : '' }}" {{ $disabled ? 'aria-disabled="true" tabindex="-1"' : '' }}>
                <i class="ni ni-single-copy-04"></i>
                <span>Kelola User</span>
            </a>
        @endif
    </nav>

    <div class="sb-spacer"></div>

    <div class="sb-foot">
        <a href="#" class="{{ $sidebarDisabled ? 'disabled' : '' }}" {{ $sidebarDisabled ? 'aria-disabled="true" tabindex="-1"' : '' }}>
            <i class="ni ni-single-02"></i>
            <span>Bantuan</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">
                <i class="ni ni-button-power"></i>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>
