
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Meta Automation</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #050a16;
            --bg-soft: #0b1326;
            --panel: rgba(21, 30, 51, 0.62);
            --panel-line: rgba(161, 186, 255, 0.16);
            --text: #edf2ff;
            --muted: #8e9ab8;
            --input: rgba(255, 255, 255, 0.04);
            --input-line: rgba(179, 201, 255, 0.12);
            --btn-start: #93b5ff;
            --btn-end: #5f95ff;
            --alert: #ffc1c1;
            --alert-bg: rgba(148, 34, 34, 0.35);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: 'Space Grotesk', sans-serif;
            background:
                radial-gradient(circle at 0% 0%, rgba(79, 121, 255, 0.22), transparent 45%),
                radial-gradient(circle at 100% 100%, rgba(73, 100, 201, 0.17), transparent 40%),
                linear-gradient(135deg, #020612 0%, #050a16 48%, #030815 100%);
            position: relative;
            overflow-x: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        body::before {
            background-image: linear-gradient(rgba(148, 175, 255, 0.035) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(148, 175, 255, 0.035) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(circle at center, black, transparent 75%);
        }

        body::after {
            background: radial-gradient(circle at 50% 10%, rgba(140, 169, 255, 0.12), transparent 45%);
            filter: blur(20px);
        }

        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto;
            padding: 28px 32px 20px;
        }

        .topbar,
        .bottombar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9aaccf;
        }

        .brand {
            font-weight: 700;
            font-size: 26px;
            letter-spacing: 0.01em;
            color: #f2f6ff;
            text-decoration: none;
        }

        .top-link,
        .bottombar a {
            color: #9aaccf;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .top-link:hover,
        .bottombar a:hover {
            color: #d4e2ff;
        }

        .center {
            display: grid;
            place-items: center;
            padding: 34px 16px;
        }

        .panel {
            width: 100%;
            max-width: 480px;
            border-radius: 14px;
            border: 1px solid var(--panel-line);
            background: linear-gradient(165deg, rgba(30, 40, 63, 0.75), rgba(18, 26, 45, 0.68));
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(10px);
            padding: 44px 42px 34px;
            animation: riseIn 0.55s ease;
        }

        .heading {
            margin: 0;
            text-align: center;
            font-size: clamp(34px, 5vw, 46px);
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .subheading {
            margin: 8px 0 34px;
            text-align: center;
            font-size: 11px;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .error-box {
            border: 1px solid rgba(255, 168, 168, 0.35);
            background: var(--alert-bg);
            color: var(--alert);
            border-radius: 10px;
            font-size: 13px;
            padding: 11px 13px;
            margin-bottom: 14px;
        }

        .field {
            margin-bottom: 18px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            letter-spacing: 0.17em;
            text-transform: uppercase;
            color: #9eb0d2;
            font-weight: 600;
        }

        .input {
            width: 100%;
            border-radius: 9px;
            border: 1px solid var(--input-line);
            background: var(--input);
            color: var(--text);
            font-size: 15px;
            outline: none;
            padding: 13px 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .input::placeholder {
            color: #6b7896;
        }

        .input:focus {
            border-color: rgba(150, 185, 255, 0.7);
            box-shadow: 0 0 0 4px rgba(114, 157, 255, 0.18);
            background: rgba(255, 255, 255, 0.06);
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin: 2px 0 22px;
            font-size: 12px;
            color: #9eafcf;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .remember input {
            width: 14px;
            height: 14px;
            accent-color: #86a8ff;
        }

        .row a {
            color: #9fb8ff;
            text-decoration: none;
        }

        .row a:hover {
            color: #c8d8ff;
        }

        .submit {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 13px 18px;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: #0e1f45;
            background: linear-gradient(180deg, var(--btn-start), var(--btn-end));
            cursor: pointer;
            transition: transform 0.15s ease, filter 0.2s ease;
        }

        .submit:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
        }

        .submit:active {
            transform: translateY(0);
        }

        .signup {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: #8fa0c1;
        }

        .signup a {
            color: #c7d8ff;
            text-decoration: none;
            font-weight: 600;
        }

        .meta {
            margin-top: 18px;
            text-align: center;
            font-size: 10px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #6d7ea0;
        }

        @keyframes riseIn {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 640px) {
            .page {
                padding: 18px 16px 14px;
            }

            .brand {
                font-size: 20px;
            }

            .panel {
                padding: 30px 18px 24px;
            }

            .heading {
                font-size: 32px;
            }

            .submit {
                font-size: 18px;
            }

            .bottombar {
                gap: 10px;
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <header class="topbar">
            <a href="{{ url('/') }}" class="brand">Meta Automation</a>
            <a href="#" class="top-link">Support</a>
        </header>

        <main class="center">
            <section class="panel">
                <h1 class="heading">Welcome Back</h1>
                <p class="subheading">Authorize Your Session</p>

                <form method="post" action="{{ route('login') }}">
                    @csrf

                    @if(session('success'))
                        <div class="error-box" style="color:#c8ffd3;background:rgba(24,84,41,.35);border-color:rgba(140,240,171,.35);">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="error-box">{{ session('error') }}</div>
                    @endif

                    @error('email')
                        <div class="error-box">{{ $message }}</div>
                    @enderror

                    @error('password')
                        <div class="error-box">{{ $message }}</div>
                    @enderror

                    <div class="field">
                        <label for="email">Email Address</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            class="input"
                            placeholder="executive@meta-auto.com"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="input"
                            placeholder="........"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <div class="row">
                        <label class="remember" for="rememberMe">
                            <input id="rememberMe" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>Remember this device</span>
                        </label>
                        <a href="{{ route('password.request') }}">Forgot password?</a>
                    </div>

                    <button type="submit" class="submit">Sign In</button>
                </form>

                <p class="signup">
                    Don't have an account?
                    <a href="{{ route('register') }}">Create Authority</a>
                </p>

                <div class="meta">End-to-end encrypted • Quantum Core v2.4</div>
            </section>
        </main>

        <footer class="bottombar">
            <span>© {{ date('Y') }} Meta Automation. Luminous Authority Systems.</span>
            <div>
                <a href="#">Privacy Policy</a>
                <span> · </span>
                <a href="#">Terms of Service</a>
                <span> · </span>
                <a href="#">Security Architecture</a>
            </div>
        </footer>
    </div>
</body>
</html>
