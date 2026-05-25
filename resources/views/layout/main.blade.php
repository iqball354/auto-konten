<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Dashboard')</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/argon-dashboard-tailwind.css') }}" rel="stylesheet" />

    <style>
        :root {
            --dash-bg: #040a16;
            --dash-bg-soft: #0a1121;
            --dash-panel: #121b2f;
            --dash-panel-2: #101a2a;
            --dash-border: rgba(162, 183, 255, 0.13);
            --dash-text: #f0f4ff;
            --dash-muted: #95a4c6;
            --dash-accent: #79a6ff;
            --dash-accent-2: #6496ff;
            --dash-danger: #ff9f99;
            --dash-success: #4ddcb3;
        }

        * {
            box-sizing: border-box;
        }

        body.app-body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 0% 0%, rgba(79, 121, 255, 0.2), transparent 38%),
                radial-gradient(circle at 100% 100%, rgba(73, 100, 201, 0.14), transparent 32%),
                linear-gradient(135deg, #020611 0%, #040a16 48%, #030815 100%);
            color: var(--dash-text);
            font-family: 'Space Grotesk', sans-serif;
        }

        .app-shell {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        .app-main {
            min-width: 0;
            padding: 18px 22px 20px;
        }

        .app-sidebar-overlay {
            display: none;
        }

        .main-inner {
            border-left: 1px solid rgba(95, 125, 198, 0.15);
            padding-left: 20px;
        }

        .content-wrap {
            width: 100%;
            margin-top: 12px;
        }

        .card-dark {
            background: linear-gradient(180deg, rgba(18, 28, 45, 0.92), rgba(13, 23, 39, 0.92));
            border: 1px solid var(--dash-border);
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
        }

        @media (max-width: 1279px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .app-sidebar-overlay {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(2, 7, 18, 0.58);
                backdrop-filter: blur(2px);
                z-index: 30;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.22s ease;
            }

            .app-sidebar-overlay.show {
                opacity: 1;
                pointer-events: auto;
            }

            .app-main {
                padding: 14px 14px 18px;
            }

            .main-inner {
                border-left: 0;
                padding-left: 0;
            }
        }
    </style>

    @stack('styles')
</head>

<body class="app-body">
    <div class="app-shell">
        @include('layout.sidebar')
        <div id="app-sidebar-overlay" class="app-sidebar-overlay"></div>

        <main class="app-main">
            <div class="main-inner">
                @include('layout.navbar')

                <div class="content-wrap">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>

    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/argon-dashboard-tailwind.js') }}"></script>
    @stack('scripts')
</body>

</html>
