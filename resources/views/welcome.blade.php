<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PostAuto — Otomatisasi Konten Sosial Media</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:       #080C10;
      --bg2:      #0D1117;
      --bg3:      #0f1623;
      --border:   rgba(255,255,255,.07);
      --green:    #3B82F6;
      --green2:   #2563EB;
      --teal:     #60A5FA;
      --white:    #F0F6FF;
      --muted:    rgba(240,246,255,.45);
      --card-bg:  rgba(255,255,255,.035);
    }

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--white);
      font-family: 'DM Sans', sans-serif;
      font-size: 16px;
      line-height: 1.6;
      overflow-x: hidden;
    }

    h1,h2,h3,h4 { font-family: 'Syne', sans-serif; line-height: 1.1; }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: var(--green); border-radius: 99px; }

    /* ── NOISE OVERLAY ── */
    body::before {
      content: '';
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
      background-size: 180px;
      opacity: .5;
    }

    section, nav, footer { position: relative; z-index: 1; }

    /* ── NAV ── */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      padding: 18px 0;
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      background: rgba(8,12,16,.8);
    }
    .nav-inner {
      max-width: 1160px; margin: 0 auto; padding: 0 24px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .logo {
      font-family: 'Syne', sans-serif; font-weight: 800;
      font-size: 22px; color: var(--white); text-decoration: none;
      display: flex; align-items: center; gap: 10px;
    }
    .logo-dot {
      width: 10px; height: 10px; border-radius: 50%;
      background: var(--green);
      box-shadow: 0 0 12px var(--green);
      animation: pulse 2s ease infinite;
    }
    @keyframes pulse {
      0%,100% { box-shadow: 0 0 8px var(--green); }
      50% { box-shadow: 0 0 20px var(--green), 0 0 40px rgba(59,130,246,.4); }
    }
    .nav-links { display: flex; gap: 32px; list-style: none; }
    .nav-links a {
      color: var(--muted); text-decoration: none; font-size: 14px; font-weight: 500;
      transition: color .2s;
    }
    .nav-links a:hover { color: var(--white); }
    .nav-cta {
      background: var(--green); color: #fff; border: none;
      font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 14px;
      padding: 10px 22px; border-radius: 8px; cursor: pointer;
      transition: all .2s; text-decoration: none;
    }
    .nav-cta:hover { background: var(--green2); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246,.4); }

    /* ── HERO ── */
    .hero {
      min-height: 100vh;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      text-align: center; padding: 120px 24px 80px;
      position: relative; overflow: hidden;
    }
    .hero-glow {
      position: absolute; top: -200px; left: 50%; transform: translateX(-50%);
      width: 700px; height: 700px; border-radius: 50%;
      background: radial-gradient(circle, rgba(59,130,246,.14) 0%, transparent 65%);
      pointer-events: none;
    }
    .hero-grid {
      position: absolute; inset: 0;
      background-image:
        linear-gradient(var(--border) 1px, transparent 1px),
        linear-gradient(90deg, var(--border) 1px, transparent 1px);
      background-size: 60px 60px;
      mask-image: radial-gradient(ellipse 70% 60% at 50% 0%, black, transparent);
      pointer-events: none;
    }
    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(59,130,246,.08); border: 1px solid rgba(59,130,246,.2);
      color: var(--green); border-radius: 99px; padding: 6px 16px;
      font-size: 13px; font-weight: 600; margin-bottom: 28px;
      animation: fadeUp .6s ease both;
    }
    .hero h1 {
      font-size: clamp(42px, 7vw, 86px);
      font-weight: 800; letter-spacing: -2px;
      animation: fadeUp .6s .1s ease both;
    }
    .hero h1 em {
      font-style: normal;
      background: linear-gradient(135deg, var(--green) 0%, var(--teal) 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .hero-sub {
      margin-top: 20px; max-width: 560px;
      color: var(--muted); font-size: 18px; font-weight: 400;
      animation: fadeUp .6s .2s ease both;
    }
    .hero-actions {
      margin-top: 36px; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;
      animation: fadeUp .6s .3s ease both;
    }
    .btn-primary-hero {
      background: var(--green); color: #fff; border: none;
      font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 15px;
      padding: 14px 30px; border-radius: 10px; cursor: pointer;
      transition: all .25s; text-decoration: none;
      display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-primary-hero:hover { background: var(--green2); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(59,130,246,.35); }
    .btn-ghost {
      background: transparent; color: var(--white);
      border: 1px solid var(--border);
      font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 15px;
      padding: 14px 30px; border-radius: 10px; cursor: pointer;
      transition: all .25s; text-decoration: none;
      display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-ghost:hover { border-color: rgba(255,255,255,.25); background: var(--card-bg); }
    .hero-trust {
      margin-top: 48px; color: var(--muted); font-size: 13px;
      animation: fadeUp .6s .4s ease both;
    }
    .trust-logos {
      margin-top: 14px; display: flex; gap: 28px; justify-content: center; align-items: center; flex-wrap: wrap;
    }
    .trust-logo {
      font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
      color: rgba(255,255,255,.2); letter-spacing: 1px;
    }

    /* ── MARQUEE STATS ── */
    .marquee-wrap {
      border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
      background: var(--bg2); padding: 18px 0; overflow: hidden;
    }
    .marquee-track {
      display: flex; gap: 60px; white-space: nowrap;
      animation: marquee 22s linear infinite;
    }
    .marquee-item {
      display: flex; align-items: center; gap: 10px; flex-shrink: 0;
      font-size: 14px; font-weight: 500; color: var(--muted);
    }
    .marquee-item strong { color: var(--green); font-family: 'Syne', sans-serif; font-size: 18px; }
    @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }

    /* ── SECTION COMMONS ── */
    section { padding: 100px 24px; }
    .container { max-width: 1160px; margin: 0 auto; }
    .section-label {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(59,130,246,.07); border: 1px solid rgba(59,130,246,.15);
      color: var(--green); border-radius: 99px; padding: 4px 14px;
      font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
      margin-bottom: 16px;
    }
    .section-title {
      font-size: clamp(32px, 4vw, 52px); font-weight: 800; letter-spacing: -1.5px; margin-bottom: 16px;
    }
    .section-sub { color: var(--muted); font-size: 17px; max-width: 560px; }

    /* ── HOW IT WORKS ── */
    .how-bg { background: var(--bg2); }
    .steps-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 2px; margin-top: 56px;
      border: 1px solid var(--border); border-radius: 16px; overflow: hidden;
    }
    .step-card {
      background: var(--bg2); padding: 36px 28px;
      border-right: 1px solid var(--border);
      transition: background .25s;
    }
    .step-card:last-child { border-right: none; }
    .step-card:hover { background: var(--bg3); }
    .step-num {
      font-family: 'Syne', sans-serif; font-size: 48px; font-weight: 800;
      color: rgba(59,130,246,.18); line-height: 1; margin-bottom: 16px;
    }
    .step-icon {
      width: 44px; height: 44px; border-radius: 12px;
      background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; margin-bottom: 16px;
    }
    .step-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 8px; }
    .step-desc { color: var(--muted); font-size: 14px; line-height: 1.65; }

    /* ── FEATURES ── */
    .features-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 16px; margin-top: 56px;
    }
    .feature-card {
      background: var(--card-bg); border: 1px solid var(--border);
      border-radius: 16px; padding: 28px;
      transition: border-color .25s, transform .25s;
    }
    .feature-card:hover { border-color: rgba(59,130,246,.3); transform: translateY(-3px); }
    .feature-card.featured {
      background: rgba(59,130,246,.06); border-color: rgba(59,130,246,.2);
      grid-column: span 2;
    }
    .feature-icon {
      width: 46px; height: 46px; border-radius: 12px;
      background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.15);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; margin-bottom: 18px;
    }
    .feature-title { font-family: 'Syne', sans-serif; font-size: 19px; font-weight: 700; margin-bottom: 8px; }
    .feature-desc { color: var(--muted); font-size: 14px; line-height: 1.65; }
    .feature-tags { margin-top: 16px; display: flex; gap: 6px; flex-wrap: wrap; }
    .feature-tag {
      font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px;
      background: rgba(59,130,246,.08); color: var(--green); border: 1px solid rgba(59,130,246,.15);
    }

    /* ── PLATFORM ── */
    .platform-bg { background: var(--bg2); }
    .platform-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 56px;
    }
    .platform-card {
      background: var(--card-bg); border: 1px solid var(--border);
      border-radius: 20px; padding: 36px; overflow: hidden; position: relative;
    }
    .platform-card::before {
      content: '';
      position: absolute; top: -60px; right: -60px;
      width: 180px; height: 180px; border-radius: 50%;
      pointer-events: none;
    }
    .platform-card.ig::before { background: radial-gradient(circle, rgba(225,48,108,.15), transparent); }
    .platform-card.fb::before { background: radial-gradient(circle, rgba(24,119,242,.15), transparent); }
    .platform-logo { font-size: 44px; margin-bottom: 16px; }
    .platform-name { font-family: 'Syne', sans-serif; font-size: 24px; font-weight: 800; margin-bottom: 8px; }
    .platform-desc { color: var(--muted); font-size: 14px; margin-bottom: 24px; }
    .platform-features { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .platform-features li {
      display: flex; align-items: center; gap: 10px;
      font-size: 14px; color: var(--muted);
    }
    .platform-features li::before {
      content: '✓'; color: var(--green); font-weight: 700; flex-shrink: 0;
    }

    /* ── PRICING ── */
    .pricing-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 16px; margin-top: 56px; align-items: start;
    }
    .pricing-card {
      background: var(--card-bg); border: 1px solid var(--border);
      border-radius: 20px; padding: 32px;
      transition: transform .25s;
    }
    .pricing-card:hover { transform: translateY(-4px); }
    .pricing-card.popular {
      border-color: rgba(59,130,246,.35);
      background: rgba(59,130,246,.06);
    }
    .pricing-popular-badge {
      display: inline-block; background: var(--green); color: #fff;
      font-size: 11px; font-weight: 800; letter-spacing: .5px;
      padding: 3px 12px; border-radius: 99px; margin-bottom: 16px;
    }
    .pricing-plan { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
    .pricing-price {
      font-family: 'Syne', sans-serif; font-size: 44px; font-weight: 800;
      letter-spacing: -2px; margin-bottom: 4px;
    }
    .pricing-price span { font-size: 18px; font-weight: 500; color: var(--muted); }
    .pricing-period { color: var(--muted); font-size: 13px; margin-bottom: 24px; }
    .pricing-divider { border: none; border-top: 1px solid var(--border); margin: 24px 0; }
    .pricing-features { list-style: none; display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }
    .pricing-features li {
      display: flex; align-items: flex-start; gap: 10px;
      font-size: 14px; color: var(--muted);
    }
    .pricing-features li .check { color: var(--green); font-weight: 700; flex-shrink: 0; margin-top: 1px; }
    .pricing-features li .cross { color: rgba(255,255,255,.2); flex-shrink: 0; margin-top: 1px; }
    .btn-pricing-green {
      display: block; width: 100%; background: var(--green); color: #fff;
      border: none; font-family: 'DM Sans', sans-serif; font-weight: 700;
      font-size: 15px; padding: 13px; border-radius: 10px; cursor: pointer;
      transition: all .2s; text-align: center; text-decoration: none;
    }
    .btn-pricing-green:hover { background: var(--green2); box-shadow: 0 8px 24px rgba(59,130,246,.35); }
    .btn-pricing-ghost {
      display: block; width: 100%; background: transparent; color: var(--white);
      border: 1px solid var(--border); font-family: 'DM Sans', sans-serif; font-weight: 600;
      font-size: 15px; padding: 13px; border-radius: 10px; cursor: pointer;
      transition: all .2s; text-align: center; text-decoration: none;
    }
    .btn-pricing-ghost:hover { border-color: rgba(255,255,255,.3); background: var(--card-bg); }

    /* ── TESTIMONIALS ── */
    .testi-bg { background: var(--bg2); }
    .testi-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 16px; margin-top: 56px;
    }
    .testi-card {
      background: var(--card-bg); border: 1px solid var(--border);
      border-radius: 16px; padding: 28px;
    }
    .testi-stars { color: #FFB800; font-size: 14px; margin-bottom: 14px; letter-spacing: 2px; }
    .testi-text { color: var(--muted); font-size: 15px; line-height: 1.7; margin-bottom: 20px; }
    .testi-text strong { color: var(--white); }
    .testi-author { display: flex; align-items: center; gap: 12px; }
    .testi-avatar {
      width: 40px; height: 40px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: 15px;
      flex-shrink: 0;
    }
    .testi-name { font-weight: 700; font-size: 14px; }
    .testi-role { color: var(--muted); font-size: 12px; }

    /* ── FAQ ── */
    .faq-list { max-width: 720px; margin: 56px auto 0; display: flex; flex-direction: column; gap: 8px; }
    .faq-item {
      background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; overflow: hidden;
    }
    .faq-q {
      width: 100%; background: none; border: none; color: var(--white);
      font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 600;
      padding: 20px 24px; text-align: left; cursor: pointer;
      display: flex; justify-content: space-between; align-items: center; gap: 16px;
      transition: background .2s;
    }
    .faq-q:hover { background: var(--card-bg); }
    .faq-icon { color: var(--green); font-size: 20px; flex-shrink: 0; transition: transform .25s; }
    .faq-a {
      max-height: 0; overflow: hidden;
      color: var(--muted); font-size: 14px; line-height: 1.7;
      padding: 0 24px; transition: max-height .35s ease, padding .35s ease;
    }
    .faq-item.open .faq-a { max-height: 200px; padding: 0 24px 20px; }
    .faq-item.open .faq-icon { transform: rotate(45deg); }

    /* ── CTA SECTION ── */
    .cta-section {
      text-align: center; padding: 100px 24px;
      background: radial-gradient(ellipse 70% 60% at 50% 100%, rgba(59,130,246,.1), transparent);
    }
    .cta-section h2 { font-size: clamp(34px, 5vw, 62px); letter-spacing: -2px; margin-bottom: 16px; }
    .cta-section p { color: var(--muted); font-size: 17px; max-width: 480px; margin: 0 auto 36px; }

    /* ── FOOTER ── */
    footer {
      background: var(--bg2); border-top: 1px solid var(--border);
      padding: 60px 24px 32px;
    }
    .footer-grid {
      max-width: 1160px; margin: 0 auto;
      display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 48px;
    }
    .footer-brand { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 12px; }
    .footer-desc { color: var(--muted); font-size: 14px; line-height: 1.7; max-width: 280px; }
    .footer-socials { display: flex; gap: 10px; margin-top: 20px; }
    .footer-social {
      width: 36px; height: 36px; border-radius: 9px; border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      color: var(--muted); font-size: 16px; text-decoration: none; transition: all .2s;
    }
    .footer-social:hover { border-color: var(--green); color: var(--green); }
    .footer-col h5 { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--white); margin-bottom: 16px; }
    .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .footer-col a { color: var(--muted); text-decoration: none; font-size: 14px; transition: color .2s; }
    .footer-col a:hover { color: var(--white); }
    .footer-bottom {
      max-width: 1160px; margin: 40px auto 0;
      border-top: 1px solid var(--border); padding-top: 24px;
      display: flex; justify-content: space-between; align-items: center; flex-wrap: gap;
    }
    .footer-bottom p { color: var(--muted); font-size: 13px; }

    /* ── ANIMATIONS ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .reveal {
      opacity: 0; transform: translateY(28px);
      transition: opacity .6s ease, transform .6s ease;
    }
    .reveal.visible { opacity: 1; transform: translateY(0); }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
      .nav-links { display: none; }
      .platform-grid { grid-template-columns: 1fr; }
      .feature-card.featured { grid-column: span 1; }
      .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
      .footer-grid { grid-template-columns: 1fr; }
      .pricing-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ══ NAV ══ -->
<nav>
  <div class="nav-inner">
    <a href="#" class="logo">
      <div class="logo-dot"></div>
      PostAuto
    </a>
    <ul class="nav-links">
      <li><a href="#cara-kerja">Cara Kerja</a></li>
      <li><a href="#fitur">Fitur</a></li>
      <li><a href="#platform">Platform</a></li>
      <li><a href="#harga">Harga</a></li>
      <li><a href="#faq">FAQ</a></li>
    </ul>
    <a href="{{ route('login') }}" class="nav-cta">Mulai Gratis →</a>
  </div>
</nav>

<!-- ══ HERO ══ -->
<section class="hero">
  <div class="hero-glow"></div>
  <div class="hero-grid"></div>

  <div class="hero-badge">
    <span>🚀</span> Lebih dari 5.000+ bisnis percaya pada kami
  </div>

  <h1>
    Otomatisasi Konten<br/>
    <em>Instagram & Facebook</em><br/>
    Tanpa Ribet
  </h1>

  <p class="hero-sub">
    Jadwalkan, publish, dan kelola seluruh konten sosial media bisnis Anda secara otomatis. Hemat waktu hingga <strong style="color:var(--white)">10 jam per minggu.</strong>
  </p>

  <div class="hero-actions">
    <a href="#harga" class="btn-primary-hero">
      ⚡ Coba Gratis 14 Hari
    </a>
    <a href="#cara-kerja" class="btn-ghost">
      ▶ Lihat Demo
    </a>
  </div>

  <div class="hero-trust">
    Dipercaya oleh brand-brand terkemuka Indonesia
    <div class="trust-logos">
      <span class="trust-logo">TOKOBAJU</span>
      <span class="trust-logo">KOPI NUSANTARA</span>
      <span class="trust-logo">SKINLAB</span>
      <span class="trust-logo">EDUTECH+</span>
      <span class="trust-logo">RASA ID</span>
    </div>
  </div>
</section>

<!-- ══ MARQUEE ══ -->
<div class="marquee-wrap">
  <div class="marquee-track">
    <div class="marquee-item"><strong>5.000+</strong> Bisnis Aktif</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>2 Juta+</strong> Postingan Terjadwal</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>98.7%</strong> Uptime</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>10 Jam</strong> Dihemat Per Minggu</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>4.9★</strong> Rating Pengguna</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>Instagram</strong> & <strong>Facebook</strong> Resmi</div>
    <div class="marquee-item">•</div>
    <!-- duplicate for infinite -->
    <div class="marquee-item"><strong>5.000+</strong> Bisnis Aktif</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>2 Juta+</strong> Postingan Terjadwal</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>98.7%</strong> Uptime</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>10 Jam</strong> Dihemat Per Minggu</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>4.9★</strong> Rating Pengguna</div>
    <div class="marquee-item">•</div>
    <div class="marquee-item"><strong>Instagram</strong> & <strong>Facebook</strong> Resmi</div>
    <div class="marquee-item">•</div>
  </div>
</div>

<!-- ══ HOW IT WORKS ══ -->
<section class="how-bg" id="cara-kerja">
  <div class="container">
    <div class="reveal">
      <div class="section-label">✦ Cara Kerja</div>
      <h2 class="section-title">Mulai dalam<br/>3 Langkah Mudah</h2>
      <p class="section-sub">Tidak perlu keahlian teknis. Setup selesai dalam 5 menit dan konten Anda langsung berjalan otomatis.</p>
    </div>
    <div class="steps-grid reveal">
      <div class="step-card">
        <div class="step-num">01</div>
        <div class="step-icon">🔗</div>
        <div class="step-title">Hubungkan Akun</div>
        <div class="step-desc">Sambungkan akun Instagram Business dan Facebook Page Anda dalam satu klik. Aman & terenkripsi.</div>
      </div>
      <div class="step-card">
        <div class="step-num">02</div>
        <div class="step-icon">✍️</div>
        <div class="step-title">Buat Konten</div>
        <div class="step-desc">Upload media, tulis caption, tambah hashtag, atau biarkan AI kami yang membuatkan konten untuk Anda.</div>
      </div>
      <div class="step-card">
        <div class="step-num">03</div>
        <div class="step-icon">📅</div>
        <div class="step-title">Atur Jadwal</div>
        <div class="step-desc">Pilih waktu terbaik secara otomatis atau atur jadwal manual. Konten diposting tepat waktu, setiap saat.</div>
      </div>
      <div class="step-card">
        <div class="step-num">04</div>
        <div class="step-icon">📊</div>
        <div class="step-title">Pantau Performa</div>
        <div class="step-desc">Lihat engagement, jangkauan, dan pertumbuhan akun dalam dashboard analitik real-time yang mudah dipahami.</div>
      </div>
    </div>
  </div>
</section>

<!-- ══ FEATURES ══ -->
<section id="fitur">
  <div class="container">
    <div class="reveal">
      <div class="section-label">✦ Fitur Unggulan</div>
      <h2 class="section-title">Semua yang Anda<br/>Butuhkan, Dalam Satu Dasbor</h2>
      <p class="section-sub">Dari penjadwalan hingga analitik, semua terintegrasi tanpa perlu berpindah aplikasi.</p>
    </div>
    <div class="features-grid reveal">
      <div class="feature-card featured">
        <div class="feature-icon">🤖</div>
        <div class="feature-title">AI Caption Generator</div>
        <div class="feature-desc">Buat caption menarik, hashtag relevan, dan variasi konten secara otomatis menggunakan kecerdasan buatan yang terlatih khusus untuk kebutuhan pasar Indonesia. Tinggal klik, konten siap publish.</div>
        <div class="feature-tags">
          <span class="feature-tag">GPT-Powered</span>
          <span class="feature-tag">Bahasa Indonesia</span>
          <span class="feature-tag">Auto Hashtag</span>
          <span class="feature-tag">Multi-variasi</span>
        </div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🗓️</div>
        <div class="feature-title">Smart Scheduling</div>
        <div class="feature-desc">Sistem otomatis mendeteksi waktu terbaik posting berdasarkan perilaku audiens Anda untuk maksimalkan engagement.</div>
        <div class="feature-tags">
          <span class="feature-tag">Best Time AI</span>
          <span class="feature-tag">Bulk Schedule</span>
        </div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🖼️</div>
        <div class="feature-title">Media Library</div>
        <div class="feature-desc">Kelola semua foto, video, dan template konten dalam satu perpustakaan media terpusat yang mudah diorganisir.</div>
        <div class="feature-tags">
          <span class="feature-tag">Cloud Storage</span>
          <span class="feature-tag">Template</span>
        </div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📈</div>
        <div class="feature-title">Analitik Real-time</div>
        <div class="feature-desc">Pantau reach, impressions, likes, komentar, dan pertumbuhan follower dalam laporan visual yang komprehensif.</div>
        <div class="feature-tags">
          <span class="feature-tag">Live Data</span>
          <span class="feature-tag">Export PDF</span>
        </div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">👥</div>
        <div class="feature-title">Manajemen Tim</div>
        <div class="feature-desc">Undang anggota tim, atur hak akses per platform, dan kolaborasi konten dalam satu workspace yang terorganisir.</div>
        <div class="feature-tags">
          <span class="feature-tag">Multi-user</span>
          <span class="feature-tag">Approval Flow</span>
        </div>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔔</div>
        <div class="feature-title">Notifikasi Otomatis</div>
        <div class="feature-desc">Dapatkan pemberitahuan saat konten berhasil dipublish, gagal, atau ada engagement penting di postingan Anda.</div>
        <div class="feature-tags">
          <span class="feature-tag">Email & Push</span>
          <span class="feature-tag">WhatsApp</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ PLATFORM ══ -->
<section class="platform-bg" id="platform">
  <div class="container">
    <div class="reveal">
      <div class="section-label">✦ Platform</div>
      <h2 class="section-title">Integrasi Resmi<br/>Instagram & Facebook</h2>
      <p class="section-sub">Terhubung langsung via API resmi Meta. Aman, stabil, dan tidak melanggar kebijakan platform.</p>
    </div>
    <div class="platform-grid reveal">
      <div class="platform-card ig">
        <div class="platform-logo">📸</div>
        <div class="platform-name">Instagram</div>
        <div class="platform-desc">Kelola semua jenis konten Instagram dari satu dasbor</div>
        <ul class="platform-features">
          <li>Feed Foto & Carousel</li>
          <li>Instagram Reels</li>
          <li>Stories (24 jam)</li>
          <li>Auto Hashtag & Mention</li>
          <li>Statistik Engagement</li>
          <li>Multi-akun Instagram</li>
        </ul>
      </div>
      <div class="platform-card fb">
        <div class="platform-logo">👍</div>
        <div class="platform-name">Facebook</div>
        <div class="platform-desc">Otomatisasi penuh untuk Facebook Page bisnis Anda</div>
        <ul class="platform-features">
          <li>Postingan & Album Foto</li>
          <li>Facebook Reels</li>
          <li>Facebook Stories</li>
          <li>Tombol CTA & Link</li>
          <li>Insights & Analitik</li>
          <li>Multi-halaman Facebook</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ══ PRICING ══ -->
<section id="harga">
  <div class="container">
    <div class="reveal" style="text-align:center">
      <div class="section-label" style="display:inline-flex">✦ Harga</div>
      <h2 class="section-title">Harga Transparan,<br/>Tanpa Biaya Tersembunyi</h2>
      <p class="section-sub" style="margin:0 auto">Mulai gratis 14 hari. Tidak perlu kartu kredit.</p>
    </div>
    <div class="pricing-grid reveal">

      <!-- Starter -->
      <div class="pricing-card">
        <div class="pricing-plan">Starter</div>
        <div class="pricing-price">Rp 0 <span>/bln</span></div>
        <div class="pricing-period">Gratis selamanya, fitur terbatas</div>
        <hr class="pricing-divider"/>
        <ul class="pricing-features">
          <li><span class="check">✓</span> 1 akun Instagram</li>
          <li><span class="check">✓</span> 1 halaman Facebook</li>
          <li><span class="check">✓</span> 15 postingan/bulan</li>
          <li><span class="check">✓</span> Penjadwalan dasar</li>
          <li><span class="cross">✗</span> <span style="opacity:.35">AI Caption Generator</span></li>
          <li><span class="cross">✗</span> <span style="opacity:.35">Analitik lanjutan</span></li>
          <li><span class="cross">✗</span> <span style="opacity:.35">Manajemen tim</span></li>
        </ul>
        <a href="#" class="btn-pricing-ghost">Mulai Gratis</a>
      </div>

      <!-- Pro -->
      <div class="pricing-card popular">
        <div class="pricing-popular-badge">🔥 PALING POPULER</div>
        <div class="pricing-plan">Pro</div>
        <div class="pricing-price">Rp 199K <span>/bln</span></div>
        <div class="pricing-period">Tagih bulanan · Hemat 20% bayar tahunan</div>
        <hr class="pricing-divider"/>
        <ul class="pricing-features">
          <li><span class="check">✓</span> 5 akun Instagram</li>
          <li><span class="check">✓</span> 5 halaman Facebook</li>
          <li><span class="check">✓</span> Postingan tak terbatas</li>
          <li><span class="check">✓</span> AI Caption Generator</li>
          <li><span class="check">✓</span> Smart Scheduling</li>
          <li><span class="check">✓</span> Analitik lengkap</li>
          <li><span class="check">✓</span> 3 anggota tim</li>
        </ul>
        <a href="#" class="btn-pricing-green">Coba 14 Hari Gratis →</a>
      </div>

      <!-- Business -->
      <div class="pricing-card">
        <div class="pricing-plan">Business</div>
        <div class="pricing-price">Rp 499K <span>/bln</span></div>
        <div class="pricing-period">Untuk tim dan agensi besar</div>
        <hr class="pricing-divider"/>
        <ul class="pricing-features">
          <li><span class="check">✓</span> Akun tidak terbatas</li>
          <li><span class="check">✓</span> Halaman tidak terbatas</li>
          <li><span class="check">✓</span> Postingan tak terbatas</li>
          <li><span class="check">✓</span> AI Premium + Prioritas</li>
          <li><span class="check">✓</span> White-label laporan</li>
          <li><span class="check">✓</span> Anggota tim tak terbatas</li>
          <li><span class="check">✓</span> Dukungan prioritas 24/7</li>
        </ul>
        <a href="#" class="btn-pricing-ghost">Hubungi Sales →</a>
      </div>

    </div>
  </div>
</section>

<!-- ══ TESTIMONIALS ══ -->
<section class="testi-bg">
  <div class="container">
    <div class="reveal" style="text-align:center">
      <div class="section-label" style="display:inline-flex">✦ Testimonial</div>
      <h2 class="section-title">Kata Mereka yang<br/>Sudah Merasakan Hasilnya</h2>
    </div>
    <div class="testi-grid reveal">

      <div class="testi-card">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Sejak pakai PostAuto, <strong>follower toko kami naik 3x lipat</strong> dalam 2 bulan. Nggak perlu lagi repot posting manual setiap hari."</p>
        <div class="testi-author">
          <div class="testi-avatar" style="background:rgba(225,48,108,.15);color:#E1306C">D</div>
          <div>
            <div class="testi-name">Dina Rahayu</div>
            <div class="testi-role">Owner @tokobajudina · Jakarta</div>
          </div>
        </div>
      </div>

      <div class="testi-card">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Fitur AI Caption-nya <strong>luar biasa!</strong> Saya tinggal upload foto produk, caption dan hashtag langsung jadi. Hemat waktu banget buat tim kecil kayak kami."</p>
        <div class="testi-author">
          <div class="testi-avatar" style="background:rgba(24,119,242,.15);color:#1877F2">B</div>
          <div>
            <div class="testi-name">Budi Santoso</div>
            <div class="testi-role">Marketing Manager · Kopi Nusantara</div>
          </div>
        </div>
      </div>

      <div class="testi-card">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Sebagai agensi, kami kelola <strong>20+ klien sekaligus</strong> tanpa stres. Dashboard-nya bersih, fitur tim-nya solid. Worth every rupiah!"</p>
        <div class="testi-author">
          <div class="testi-avatar" style="background:rgba(59,130,246,.1);color:var(--green)">A</div>
          <div>
            <div class="testi-name">Andi Pratama</div>
            <div class="testi-role">Founder · Kreasi Digital Agency</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ══ FAQ ══ -->
<section id="faq">
  <div class="container">
    <div class="reveal" style="text-align:center">
      <div class="section-label" style="display:inline-flex">✦ FAQ</div>
      <h2 class="section-title">Pertanyaan yang<br/>Sering Ditanyakan</h2>
    </div>
    <div class="faq-list reveal">

      <div class="faq-item open">
        <button class="faq-q" onclick="toggleFaq(this)">
          Apakah PostAuto aman dan tidak melanggar kebijakan Instagram & Facebook?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">Ya, 100% aman. PostAuto menggunakan API resmi dari Meta (perusahaan induk Instagram & Facebook). Kami tidak menggunakan metode scraping atau otomasi yang dilarang, sehingga akun Anda terlindungi sepenuhnya.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          Bisakah saya mencoba sebelum membayar?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">Tentu! Kami menyediakan uji coba gratis 14 hari untuk paket Pro tanpa memerlukan kartu kredit. Setelah 14 hari, Anda bisa memilih paket berbayar atau kembali ke paket Starter gratis.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          Berapa banyak akun yang bisa dihubungkan?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">Paket Starter: 1 akun Instagram + 1 Facebook. Paket Pro: 5 akun masing-masing. Paket Business: tidak terbatas. Anda bisa upgrade kapan saja sesuai kebutuhan.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          Apakah bisa digunakan untuk Instagram Personal (bukan bisnis)?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">Untuk fungsionalitas penuh (terutama penjadwalan otomatis), akun Instagram perlu diubah menjadi akun Bisnis atau Creator. Proses konversi gratis dan mudah dilakukan langsung dari aplikasi Instagram.</div>
      </div>

      <div class="faq-item">
        <button class="faq-q" onclick="toggleFaq(this)">
          Bagaimana dengan keamanan data saya?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">Keamanan data adalah prioritas kami. Semua data dienkripsi dengan standar AES-256, server berada di Indonesia, dan kami tidak pernah menjual atau membagikan data Anda kepada pihak ketiga.</div>
      </div>

    </div>
  </div>
</section>

<!-- ══ CTA ══ -->
<section class="cta-section">
  <div class="container">
    <div class="reveal">
      <h2 class="section-title">Siap Menghemat<br/><em style="background:linear-gradient(135deg,var(--green),var(--teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent">10 Jam Setiap Minggu?</em></h2>
      <p>Bergabunglah dengan 5.000+ bisnis Indonesia yang sudah mengotomatisasi konten sosial media mereka.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="#" class="btn-primary-hero">⚡ Mulai Gratis 14 Hari</a>
        <a href="#" class="btn-ghost">Jadwalkan Demo</a>
      </div>
      <p style="margin-top:20px;color:var(--muted);font-size:13px">Tidak perlu kartu kredit · Batalkan kapan saja · Setup 5 menit</p>
    </div>
  </div>
</section>

<!-- ══ FOOTER ══ -->
<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-brand">
        <span style="color:var(--green)">Post</span>Auto
      </div>
      <p class="footer-desc">Platform otomatisasi konten sosial media #1 Indonesia. Jadwalkan, publish, dan analisis konten Instagram & Facebook dengan mudah.</p>
      <div class="footer-socials">
        <a href="#" class="footer-social">ig</a>
        <a href="#" class="footer-social">fb</a>
        <a href="#" class="footer-social">tw</a>
        <a href="#" class="footer-social">yt</a>
      </div>
    </div>
    <div class="footer-col">
      <h5>Produk</h5>
      <ul>
        <li><a href="#">Fitur</a></li>
        <li><a href="#">Harga</a></li>
        <li><a href="#">Changelog</a></li>
        <li><a href="#">Status</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h5>Perusahaan</h5>
      <ul>
        <li><a href="#">Tentang Kami</a></li>
        <li><a href="#">Blog</a></li>
        <li><a href="#">Karir</a></li>
        <li><a href="#">Kontak</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h5>Bantuan</h5>
      <ul>
        <li><a href="#">Dokumentasi</a></li>
        <li><a href="#">Tutorial</a></li>
        <li><a href="#">Komunitas</a></li>
        <li><a href="#">WhatsApp Support</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© 2025 PostAuto. Hak cipta dilindungi.</p>
    <p><a href="#" style="color:var(--muted);text-decoration:none">Kebijakan Privasi</a> &nbsp;·&nbsp; <a href="#" style="color:var(--muted);text-decoration:none">Syarat & Ketentuan</a></p>
  </div>
</footer>

<script>
  // Reveal on scroll
  const reveals = document.querySelectorAll('.reveal');
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e, i) => {
      if (e.isIntersecting) {
        e.target.style.transitionDelay = (i * 0.05) + 's';
        e.target.classList.add('visible');
      }
    });
  }, { threshold: 0.1 });
  reveals.forEach(el => io.observe(el));

  // FAQ toggle
  function toggleFaq(btn) {
    const item = btn.closest('.faq-item');
    const isOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
    if (!isOpen) item.classList.add('open');
  }
</script>
</body>
</html>