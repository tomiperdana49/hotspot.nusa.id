<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nusa Hotspot — Kelola hotspot MikroTik dari satu panel</title>
<meta name="description" content="Nusa Hotspot: hubungkan router MikroTik, buat paket, dan jual voucher hotspot dari satu panel. Didukung oleh Nusanet.">
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
@verbatim
<style>
  /* Layout: dark emerald hero (same world as the panel's login screen) with a
     voucher + live-device mock, then a themed page of features built around
     the one thing only this product shows: how 5M is split between devices. */
  :root {
    --bg: #f6f8f7;
    --surface: #ffffff;
    --fg: #0f1a16;
    --muted: #55655e;
    --line: #dfe6e2;
    --accent: #059669;
    --accent-strong: #047857;
    --accent-soft: #e7f6ef;
    --amber: #b45309;
    --amber-soft: #fdf3e3;
    --hero-bg: #022c22;
    --hero-fg: #ecfdf5;
    --hero-muted: #a7d7c5;
    --hero-line: rgba(236, 253, 245, 0.14);
    --font-sans: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
    --font-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    --radius: 14px;
  }
  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
      --bg: #0b1310; --surface: #111c18; --fg: #e8f1ed; --muted: #94a8a0; --line: #22322c;
      --accent: #34d399; --accent-strong: #6ee7b7; --accent-soft: #0f2a21;
      --amber: #fbbf24; --amber-soft: #2a2110;
      --hero-bg: #021a14; --hero-fg: #ecfdf5; --hero-muted: #9cc9b8; --hero-line: rgba(236, 253, 245, 0.12);
      color-scheme: dark;
    }
  }
  :root[data-theme="dark"] {
    --bg: #0b1310; --surface: #111c18; --fg: #e8f1ed; --muted: #94a8a0; --line: #22322c;
    --accent: #34d399; --accent-strong: #6ee7b7; --accent-soft: #0f2a21;
    --amber: #fbbf24; --amber-soft: #2a2110;
    --hero-bg: #021a14; --hero-fg: #ecfdf5; --hero-muted: #9cc9b8; --hero-line: rgba(236, 253, 245, 0.12);
    color-scheme: dark;
  }

  * { box-sizing: border-box; }
  body {
    background: var(--bg); color: var(--fg);
    font-family: var(--font-sans); font-size: 16px; line-height: 1.6;
    font-feature-settings: "cv02", "cv03", "cv04", "cv11";
    -webkit-font-smoothing: antialiased;
  }
  h1, h2, h3 { text-wrap: balance; margin: 0; letter-spacing: -0.02em; line-height: 1.15; }
  p { margin: 0; }
  a { color: inherit; }
  a:focus-visible, button:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; border-radius: 8px; }
  .wrap { max-width: 1120px; margin-inline: auto; padding-inline: 20px; }
  .mono { font-family: var(--font-mono); font-variant-numeric: tabular-nums; }
  .eyebrow { font-size: 12px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--accent); }

  /* ---------- Hero ---------- */
  .hero {
    background: var(--hero-bg); color: var(--hero-fg);
    background-image: radial-gradient(circle at 12% 18%, rgba(4, 120, 87, 0.55) 0%, transparent 42%),
                      radial-gradient(circle at 88% 85%, rgba(5, 150, 105, 0.35) 0%, transparent 38%);
  }
  .nav { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-block: 20px; }
  .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; font-weight: 600; }
  .brand-mark { width: 36px; height: 36px; border-radius: 9px; background: #fff; color: #047857; display: grid; place-items: center; font-size: 14px; font-weight: 800; }
  .nav-links { display: flex; align-items: center; gap: 24px; font-size: 14px; }
  .nav-links a.plain { color: var(--hero-muted); text-decoration: none; }
  .nav-links a.plain:hover { color: var(--hero-fg); }
  .btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; padding: 11px 18px; font-size: 14px; font-weight: 600; text-decoration: none; transition: background .15s, border-color .15s; }
  .btn-light { background: #fff; color: #065f46; }
  .btn-light:hover { background: #d1fae5; }
  .btn-ghost { border: 1px solid var(--hero-line); color: var(--hero-fg); }
  .btn-ghost:hover { background: rgba(255, 255, 255, 0.06); }

  .hero-grid { display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 56px; align-items: center; padding-block: 48px 72px; }
  .hero-copy { display: flex; flex-direction: column; gap: 22px; min-width: 0; }
  .supported { align-self: flex-start; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--hero-line); border-radius: 999px; padding: 6px 12px 6px 8px; font-size: 13px; color: var(--hero-muted); }
  .supported b { color: var(--hero-fg); font-weight: 600; }
  .supported .pip { width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.18); }
  .hero h1 { font-size: clamp(34px, 5vw, 54px); font-weight: 800; }
  .hero h1 em { font-style: normal; color: #6ee7b7; }
  .hero-lead { font-size: 18px; color: var(--hero-muted); max-width: 34em; }
  .hero-cta { display: flex; flex-wrap: wrap; gap: 12px; }

  /* Voucher + live devices mock */
  .mock { position: relative; display: flex; flex-direction: column; gap: 14px; min-width: 0; }
  .voucher {
    background: #fff; color: #0f1a16; border-radius: 16px; padding: 20px 22px;
    box-shadow: 0 24px 60px -20px rgba(0, 0, 0, 0.55);
    display: grid; grid-template-columns: 1fr auto; gap: 14px 18px; position: relative;
  }
  .voucher::before, .voucher::after { content: ""; position: absolute; top: 50%; width: 18px; height: 18px; border-radius: 50%; background: var(--hero-bg); transform: translateY(-50%); }
  .voucher::before { left: -9px; } .voucher::after { right: -9px; }
  .v-label { font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #6b7c75; }
  .v-code { font-size: 26px; font-weight: 600; letter-spacing: 0.04em; color: #064e3b; }
  .v-pass { text-align: right; }
  .v-pass .mono { font-size: 15px; color: #0f1a16; }
  .v-meta { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 8px; border-top: 1px dashed #cfdad5; padding-top: 14px; }
  .chip { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 500; background: #ecfdf5; color: #065f46; white-space: nowrap; }
  .chip.amber { background: #fef3c7; color: #92400e; }

  .live { background: rgba(255, 255, 255, 0.05); border: 1px solid var(--hero-line); border-radius: 16px; padding: 16px 18px; display: flex; flex-direction: column; gap: 12px; }
  .live-head { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--hero-muted); }
  .live-head b { color: var(--hero-fg); font-weight: 600; }
  .dev { display: grid; grid-template-columns: auto 1fr auto; gap: 12px; align-items: center; font-size: 13px; }
  .dev .dot { width: 8px; height: 8px; border-radius: 50%; background: #34d399; }
  .dev .name { font-weight: 500; color: var(--hero-fg); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .dev .name small { display: block; font-family: var(--font-mono); font-size: 11px; color: var(--hero-muted); font-weight: 500; }
  .dev .use { font-family: var(--font-mono); font-size: 12px; color: var(--hero-muted); text-align: right; }

  /* ---------- Sections ---------- */
  section { padding-block: 88px; }
  .section-head { display: flex; flex-direction: column; gap: 12px; max-width: 640px; margin-bottom: 44px; }
  .section-head h2 { font-size: clamp(28px, 3.4vw, 38px); font-weight: 800; }
  .section-head p { color: var(--muted); font-size: 17px; }

  /* How it works: a real 3-step sequence, so numbers carry meaning */
  .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; border-top: 1px solid var(--line); }
  .step { padding: 28px 28px 0 0; display: flex; flex-direction: column; gap: 10px; min-width: 0; }
  .step + .step { padding-left: 28px; border-left: 1px solid var(--line); }
  .step-no { font-family: var(--font-mono); font-size: 13px; font-weight: 600; color: var(--accent); }
  .step h3 { font-size: 20px; font-weight: 700; }
  .step p { color: var(--muted); }

  /* Bandwidth mode feature */
  .bw { background: var(--surface); border: 1px solid var(--line); border-radius: 20px; padding: 36px; display: grid; grid-template-columns: 0.85fr 1.15fr; gap: 40px; align-items: center; }
  .bw-copy { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
  .bw-copy h3 { font-size: 26px; font-weight: 800; }
  .bw-copy p { color: var(--muted); }
  .bw-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; min-width: 0; }
  .bw-panel { border: 1px solid var(--line); border-radius: var(--radius); padding: 18px; display: flex; flex-direction: column; gap: 14px; min-width: 0; }
  .bw-panel header { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
  .bw-panel h4 { margin: 0; font-size: 15px; font-weight: 700; }
  .bw-total { font-family: var(--font-mono); font-size: 12px; color: var(--muted); white-space: nowrap; }
  .bars { display: flex; flex-direction: column; gap: 10px; }
  .bar-row { display: grid; grid-template-columns: 74px 1fr 56px; gap: 10px; align-items: center; font-size: 12px; }
  .bar-row span:first-child { color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .track { height: 10px; border-radius: 999px; background: var(--line); overflow: hidden; }
  .fill { height: 100%; border-radius: 999px; background: var(--accent); }
  .fill.amber { background: var(--amber); }
  .bar-row .mono { text-align: right; font-size: 12px; }
  .bw-note { font-size: 12px; color: var(--muted); border-top: 1px dashed var(--line); padding-top: 12px; }

  /* Feature grid */
  .features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: var(--line); border: 1px solid var(--line); border-radius: 20px; overflow: hidden; margin-top: 28px; }
  .feature { background: var(--surface); padding: 28px; display: flex; flex-direction: column; gap: 10px; min-width: 0; }
  .feature svg { width: 22px; height: 22px; color: var(--accent); }
  .feature h3 { font-size: 17px; font-weight: 700; }
  .feature p { color: var(--muted); font-size: 15px; }
  .feature .mono { font-size: 13px; color: var(--fg); background: var(--accent-soft); border-radius: 6px; padding: 1px 6px; }

  /* Nusanet support band */
  .support { background: var(--accent-soft); border-block: 1px solid var(--line); }
  .support-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 48px; align-items: center; }
  .support h2 { font-size: clamp(26px, 3vw, 34px); font-weight: 800; }
  .support p { color: var(--muted); font-size: 17px; margin-top: 14px; max-width: 36em; }
  .support-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 14px; }
  .support-list li { display: grid; grid-template-columns: 22px 1fr; gap: 12px; align-items: start; }
  .support-list svg { width: 20px; height: 20px; color: var(--accent); margin-top: 3px; }
  .support-list b { display: block; font-weight: 600; }
  .support-list span { color: var(--muted); font-size: 15px; }

  /* Closing CTA + footer */
  .closing { text-align: left; }
  .closing-box { background: var(--hero-bg); color: var(--hero-fg); border-radius: 22px; padding: 44px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 24px;
    background-image: radial-gradient(circle at 90% 10%, rgba(5, 150, 105, 0.4) 0%, transparent 45%); }
  .closing-box h2 { font-size: clamp(24px, 3vw, 32px); font-weight: 800; }
  .closing-box p { color: var(--hero-muted); margin-top: 8px; }
  footer { border-top: 1px solid var(--line); padding-block: 28px; font-size: 14px; color: var(--muted); }
  .foot { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; }
  .foot a { color: var(--muted); text-decoration: none; }
  .foot a:hover { color: var(--fg); }

  @media (max-width: 900px) {
    .hero-grid, .bw, .support-grid { grid-template-columns: 1fr; }
    .hero-grid { gap: 40px; padding-block: 24px 56px; }
    .features { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 640px) {
    .nav-links a.plain { display: none; }
    .steps { grid-template-columns: 1fr; }
    .step, .step + .step { padding: 22px 0; border-left: 0; }
    .step + .step { border-top: 1px solid var(--line); }
    .bw { padding: 22px; }
    .bw-panels, .features { grid-template-columns: 1fr; }
    section { padding-block: 64px; }
    .closing-box { padding: 28px; }
    .v-code { font-size: 22px; }
  }
  @media (prefers-reduced-motion: no-preference) {
    .live .dot { animation: pulse 2.4s ease-in-out infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
  }
</style>
@endverbatim
</head>
<body>
<header class="hero">
  <div class="wrap">
    <nav class="nav" aria-label="Utama">
      <a class="brand" href="#top" id="top">
        <span class="brand-mark">NH</span>
        <span>Nusa Hotspot</span>
      </a>
      <div class="nav-links">
        <a class="plain" href="#cara-kerja">Cara kerja</a>
        <a class="plain" href="#fitur">Fitur</a>
        <a class="btn btn-light" href="{{ route('login') }}">Masuk</a>
      </div>
    </nav>

    <div class="hero-grid">
      <div class="hero-copy">
        <span class="supported"><span class="pip"></span>Didukung oleh <b>Nusanet</b></span>
        <h1>Kelola hotspot MikroTik Anda dari <em>satu panel</em>.</h1>
        <p class="hero-lead">Hubungkan router, buat paket, lalu jual voucher. Nusa Hotspot mengatur RADIUS, kecepatan, dan perangkat pelanggan secara otomatis, jadi Anda cukup fokus melayani pelanggan.</p>
        <div class="hero-cta">
          <a class="btn btn-light" href="{{ route('login') }}">Masuk ke panel</a>
          <a class="btn btn-ghost" href="#fitur">Lihat fitur</a>
        </div>
      </div>

      <div class="mock" aria-label="Contoh tampilan voucher dan perangkat online">
        <div class="voucher">
          <div>
            <div class="v-label">Username</div>
            <div class="v-code mono">WIFI-8KD2Q7</div>
          </div>
          <div class="v-pass">
            <div class="v-label">Password</div>
            <div class="mono">8KD2Q7</div>
          </div>
          <div class="v-meta">
            <span class="chip">↑ 5M ↓ 5M</span>
            <span class="chip">1 Bulan · dari login pertama</span>
            <span class="chip amber">5 perangkat · dibagi</span>
          </div>
        </div>
        <div class="live">
          <div class="live-head"><span><b>Perangkat online</b> · Router Customer</span><span class="mono">3 aktif</span></div>
          <div class="dev"><span class="dot"></span><span class="name">Android-Rina<small>192.168.100.247</small></span><span class="use">1j 12m · 640 MB</span></div>
          <div class="dev"><span class="dot"></span><span class="name">iPhone-Budi<small>192.168.100.254</small></span><span class="use">48m · 212 MB</span></div>
          <div class="dev"><span class="dot"></span><span class="name">LAPTOP-KASIR<small>192.168.100.245</small></span><span class="use">15m · 96 MB</span></div>
        </div>
      </div>
    </div>
  </div>
</header>

<main>
  <section id="cara-kerja">
    <div class="wrap">
      <div class="section-head">
        <span class="eyebrow">Cara kerja</span>
        <h2>Siap dipakai pelanggan dalam tiga langkah.</h2>
        <p>Tidak perlu mengatur RADIUS atau mengetik script di WinBox satu per satu.</p>
      </div>
      <div class="steps">
        <div class="step">
          <span class="step-no">Langkah 1</span>
          <h3>Hubungkan router</h3>
          <p>Masukkan IP dan akun API MikroTik. Sistem terhubung langsung dan mengatur RADIUS secara otomatis.</p>
        </div>
        <div class="step">
          <span class="step-no">Langkah 2</span>
          <h3>Buat paket</h3>
          <p>Tentukan kecepatan, masa aktif, dan berapa perangkat yang boleh memakai satu voucher.</p>
        </div>
        <div class="step">
          <span class="step-no">Langkah 3</span>
          <h3>Buat voucher</h3>
          <p>Buat satu voucher untuk satu pelanggan, atau ratusan sekaligus untuk dicetak dan dijual.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="fitur" style="padding-top: 0;">
    <div class="wrap">
      <div class="section-head">
        <span class="eyebrow">Fitur</span>
        <h2>Semua yang dibutuhkan untuk menjalankan hotspot.</h2>
        <p>Dari pengaturan router sampai riwayat koneksi pelanggan, semuanya di satu tempat.</p>
      </div>

      <div class="bw">
        <div class="bw-copy">
          <span class="eyebrow">Mode bandwidth</span>
          <h3>Pilih cara kecepatan dibagi antar perangkat.</h3>
          <p>Setiap paket bisa memberi kecepatan penuh ke setiap perangkat, atau membagi satu kecepatan untuk semua perangkat dalam satu voucher. Cocok untuk paket keluarga atau paket kantor kecil.</p>
        </div>
        <div class="bw-panels">
          <div class="bw-panel">
            <header><h4>Per perangkat</h4><span class="bw-total">5M × 3</span></header>
            <div class="bars">
              <div class="bar-row"><span>HP Rina</span><div class="track"><div class="fill" style="width:100%"></div></div><span class="mono">5M</span></div>
              <div class="bar-row"><span>HP Budi</span><div class="track"><div class="fill" style="width:100%"></div></div><span class="mono">5M</span></div>
              <div class="bar-row"><span>Laptop</span><div class="track"><div class="fill" style="width:100%"></div></div><span class="mono">5M</span></div>
            </div>
            <p class="bw-note">Setiap perangkat mendapat kecepatan penuh.</p>
          </div>
          <div class="bw-panel">
            <header><h4>Dibagi</h4><span class="bw-total">5M total</span></header>
            <div class="bars">
              <div class="bar-row"><span>HP Rina</span><div class="track"><div class="fill amber" style="width:33.3%"></div></div><span class="mono">1,7M</span></div>
              <div class="bar-row"><span>HP Budi</span><div class="track"><div class="fill amber" style="width:33.3%"></div></div><span class="mono">1,7M</span></div>
              <div class="bar-row"><span>Laptop</span><div class="track"><div class="fill amber" style="width:33.3%"></div></div><span class="mono">1,7M</span></div>
            </div>
            <p class="bw-note">Dibagi rata saat semua dipakai. Perangkat yang aktif sendirian tetap bisa memakai hingga 5M.</p>
          </div>
        </div>
      </div>

      <div class="features">
        <article class="feature">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 9.5a14 14 0 0119 0" stroke-linecap="round"/><path d="M5.8 13a9.5 9.5 0 0112.4 0" stroke-linecap="round"/><path d="M9 16.3a5 5 0 016 0" stroke-linecap="round"/><circle cx="12" cy="19.5" r="1.2" fill="currentColor" stroke="none"/></svg>
          <h3>Router terkonfigurasi otomatis</h3>
          <p>Sistem terhubung lewat MikroTik API dan menyiapkan RADIUS, profile hotspot, dan queue tanpa perlu mengetik script.</p>
        </article>
        <article class="feature">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M8 6v12" stroke-dasharray="2 2"/><path d="M12 10h5M12 14h3" stroke-linecap="round"/></svg>
          <h3>Voucher satuan atau massal</h3>
          <p>Buat hingga <span class="mono">1000</span> voucher sekaligus dengan awalan, panjang kode, dan jenis karakter sesuai keinginan.</p>
        </article>
        <article class="feature">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <h3>Masa aktif yang fleksibel</h3>
          <p>Hitung masa aktif sejak pelanggan pertama kali login, atau sejak voucher dibuat. Dalam jam, hari, atau bulan.</p>
        </article>
        <article class="feature">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3z" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <h3>Tidak perlu login ulang</h3>
          <p>Perangkat yang sudah login tetap terhubung sampai vouchernya habis, tanpa diminta memasukkan password lagi.</p>
        </article>
        <article class="feature">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18h2" stroke-linecap="round"/></svg>
          <h3>Pantau perangkat online</h3>
          <p>Lihat nama perangkat, IP, MAC, lama terhubung, dan pemakaian data. Putuskan perangkat langsung dari panel.</p>
        </article>
        <article class="feature">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 12h4l3-7 4 14 3-7h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <h3>Status router secara langsung</h3>
          <p>Ketahui router mana yang online atau offline begitu halaman dibuka, lengkap dengan waktu terakhir terlihat.</p>
        </article>
        <article class="feature">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16M4 10h16M4 15h10M4 20h7" stroke-linecap="round"/></svg>
          <h3>Riwayat koneksi 90 hari</h3>
          <p>Telusuri siapa terhubung, kapan, dari perangkat apa, dan berapa datanya. Ekspor ke CSV untuk laporan.</p>
        </article>
        <article class="feature">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <h3>Voucher habis dibersihkan otomatis</h3>
          <p>Voucher yang kedaluwarsa atau dinonaktifkan langsung diputus dari router. Tidak ada pelanggan yang tetap online tanpa hak.</p>
        </article>
        <article class="feature">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/></svg>
          <h3>Bahasa Indonesia &amp; English</h3>
          <p>Panel tersedia dalam dua bahasa dan bisa diganti kapan saja oleh setiap pengguna.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="support">
    <div class="wrap support-grid">
      <div>
        <span class="eyebrow">Didukung oleh Nusanet</span>
        <h2 style="margin-top:12px;">Infrastruktur dan dukungan dari Nusanet.</h2>
        <p>Nusa Hotspot dikelola dan didukung oleh Nusanet. Server dan RADIUS dijalankan oleh tim Nusanet, jadi Anda tidak perlu menyiapkan server sendiri.</p>
      </div>
      <ul class="support-list">
        <li>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="7" rx="1.5"/><rect x="3" y="13" width="18" height="7" rx="1.5"/><path d="M7 7.5h.01M7 16.5h.01" stroke-linecap="round" stroke-width="2.4"/></svg>
          <div><b>Server dikelola Nusanet</b><span>Aplikasi, database, dan RADIUS berjalan di server Nusanet.</span></div>
        </li>
      </ul>
    </div>
  </section>

  <section class="closing">
    <div class="wrap">
      <div class="closing-box">
        <div>
          <h2>Sudah punya akun Nusa Hotspot?</h2>
          <p>Masuk untuk mengelola router, paket, dan voucher Anda.</p>
        </div>
        <a class="btn btn-light" href="{{ route('login') }}">Masuk ke panel</a>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap foot">
    <span>© {{ date('Y') }} Nusa Hotspot · Didukung oleh Nusanet</span>
    <a href="{{ route('login') }}">Masuk</a>
  </div>
</footer>
</body>
</html>
