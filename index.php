<?php require_once 'koneksi.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CatatCuan — Aplikasi Keuangan UMKM Digital</title>
    <meta name="description" content="CatatCuan membantu UMKM mencatat pemasukan & pengeluaran, mengelola produk, dan membuat laporan keuangan secara real-time. Gratis.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="logoc.png">
    <style>
        :root {
            --navy: #1a1f3c;
            --navy-light: #2d3561;
            --gold: #f5c518;
            --gold-dark: #e6b800;
            --text-muted: #8a94a6;
        }
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { background: #f0f2f8; color: var(--navy); overflow-x: hidden; }

        /* ─── NAVBAR ─── */
        .lp-nav {
            background: rgba(26,31,60,0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 0 40px;
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 200;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            transition: box-shadow 0.3s;
            flex-wrap: wrap;
        }
        .lp-nav.scrolled { box-shadow: 0 4px 30px rgba(0,0,0,0.25); }
        .lp-brand {
            color: #fff; font-size: 1.35rem; font-weight: 800;
            text-decoration: none; display: flex; align-items: center; gap: 10px;
        }
        .lp-brand .icon-wrap {
            width: 36px; height: 36px; background: var(--gold);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
        }
        .lp-brand .icon-wrap i { color: var(--navy); font-size: 1rem; }
        .lp-brand .cuan { color: var(--gold); }
        .lp-nav-links { display: flex; align-items: center; gap: 8px; }
        .lp-nav-links a {
            color: rgba(255,255,255,0.65); text-decoration: none;
            padding: 8px 14px; border-radius: 8px; font-size: 0.875rem; font-weight: 500;
            transition: all 0.2s;
        }
        .lp-nav-links a:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .btn-nav-cta {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--navy) !important;
            border-radius: 10px !important;
            font-weight: 700 !important;
            padding: 8px 20px !important;
            box-shadow: 0 4px 14px rgba(245,197,24,0.4);
        }
        .btn-nav-cta:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245,197,24,0.5) !important; }

        /* ─── LP NAV HAMBURGER ─── */
        .lp-hamburger {
            display: none;
            background: none;
            border: none;
            color: rgba(255,255,255,0.85);
            font-size: 1.35rem;
            cursor: pointer;
            padding: 8px 10px;
            border-radius: 8px;
            transition: background 0.2s;
            line-height: 1;
        }
        .lp-hamburger:hover { background: rgba(255,255,255,0.1); }
        @media (max-width: 768px) {
            .lp-nav    { padding: 0 20px; }
            .lp-hamburger { display: flex; align-items: center; }
            .lp-nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                padding: 10px 0 16px;
                gap: 2px;
                border-top: 1px solid rgba(255,255,255,0.08);
                background: inherit;
            }
            .lp-nav-links.lp-open { display: flex; }
            .lp-nav-links a { padding: 10px 8px; width: 100%; box-sizing: border-box; }
            .btn-nav-cta { text-align: center; }
        }

        /* ─── HERO ─── */
        .hero {
            background: linear-gradient(145deg, var(--navy) 0%, var(--navy-light) 55%, #1e2d5a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 100px 40px 80px;
            position: relative;
            overflow: hidden;
        }
        /* Blobs */
        .blob {
            position: absolute; border-radius: 50%;
            filter: blur(90px); opacity: 0.12; pointer-events: none;
        }
        .blob-1 { width: 600px; height: 600px; background: var(--gold); top: -200px; right: -100px; animation: blobFloat 10s ease-in-out infinite; }
        .blob-2 { width: 400px; height: 400px; background: #3b82f6; bottom: -150px; left: -80px; animation: blobFloat 12s ease-in-out infinite reverse; }
        .blob-3 { width: 300px; height: 300px; background: #8b5cf6; top: 50%; left: 40%; animation: blobFloat 8s ease-in-out infinite 2s; }
        @keyframes blobFloat {
            0%,100% { transform: translate(0,0) scale(1); }
            33% { transform: translate(20px,-30px) scale(1.05); }
            66% { transform: translate(-15px,20px) scale(0.95); }
        }
        /* Grid pattern */
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .hero-inner { max-width: 1140px; margin: 0 auto; display: flex; align-items: center; gap: 64px; position: relative; z-index: 1; width: 100%; }
        .hero-text { flex: 1; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(245,197,24,0.12); color: var(--gold);
            font-size: 0.78rem; font-weight: 700; padding: 7px 16px;
            border-radius: 30px; border: 1px solid rgba(245,197,24,0.25);
            margin-bottom: 28px; letter-spacing: 0.3px;
            animation: fadeInUp 0.6s ease both;
        }
        .badge-dot { width: 6px; height: 6px; background: var(--gold); border-radius: 50%; animation: pulse 1.5s ease infinite; }
        @keyframes pulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:0.5; transform:scale(1.4); } }

        .hero-title {
            font-size: clamp(2.4rem, 5vw, 3.8rem);
            font-weight: 900; color: #fff;
            line-height: 1.1; margin-bottom: 24px;
            animation: fadeInUp 0.6s ease 0.1s both;
        }
        .hero-title .highlight {
            color: var(--gold);
            position: relative; display: inline-block;
        }
        .hero-title .highlight::after {
            content: '';
            position: absolute; bottom: -4px; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--gold), transparent);
            border-radius: 2px;
        }

        .hero-sub {
            color: rgba(255,255,255,0.62); font-size: 1.1rem; line-height: 1.8;
            margin-bottom: 40px; max-width: 520px;
            animation: fadeInUp 0.6s ease 0.2s both;
        }

        .hero-btns {
            display: flex; gap: 14px; flex-wrap: wrap;
            animation: fadeInUp 0.6s ease 0.3s both;
        }
        .btn-primary-hero {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--navy); font-weight: 800; font-size: 1rem;
            padding: 15px 36px; border-radius: 14px; border: none;
            display: inline-flex; align-items: center; gap: 10px;
            text-decoration: none; cursor: pointer;
            box-shadow: 0 8px 28px rgba(245,197,24,0.45);
            transition: all 0.25s;
        }
        .btn-primary-hero:hover { transform: translateY(-3px); box-shadow: 0 14px 36px rgba(245,197,24,0.55); color: var(--navy); }
        .btn-primary-hero:active { transform: translateY(0); }

        .btn-ghost-hero {
            background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.85);
            font-weight: 600; font-size: 1rem;
            padding: 15px 32px; border-radius: 14px;
            border: 1.5px solid rgba(255,255,255,0.15);
            display: inline-flex; align-items: center; gap: 10px;
            text-decoration: none; cursor: pointer;
            backdrop-filter: blur(8px); transition: all 0.25s;
        }
        .btn-ghost-hero:hover { background: rgba(255,255,255,0.12); color: #fff; border-color: rgba(255,255,255,0.25); }

        /* Hero trust badges */
        .hero-trust {
            display: flex; align-items: center; gap: 20px; margin-top: 36px;
            animation: fadeInUp 0.6s ease 0.4s both;
        }
        .trust-item { display: flex; align-items: center; gap: 7px; color: rgba(255,255,255,0.45); font-size: 0.8rem; font-weight: 500; }
        .trust-item i { color: var(--gold); font-size: 0.85rem; }
        .trust-divider { width: 1px; height: 16px; background: rgba(255,255,255,0.15); }

        /* Hero Visual — Dashboard card mockup */
        .hero-visual { flex-shrink: 0; width: 420px; animation: fadeInRight 0.7s ease 0.3s both; }
        @keyframes fadeInRight { from { opacity:0; transform:translateX(30px); } to { opacity:1; transform:translateX(0); } }

        .mockup-card {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px; padding: 24px;
            backdrop-filter: blur(16px);
            box-shadow: 0 24px 64px rgba(0,0,0,0.3);
            position: relative;
        }
        .mockup-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .mockup-title { color: rgba(255,255,255,0.9); font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
        .mockup-dots { display: flex; gap: 5px; }
        .mockup-dots span { width: 8px; height: 8px; border-radius: 50%; }
        .mockup-dots span:nth-child(1) { background: #ef4444; }
        .mockup-dots span:nth-child(2) { background: #f59e0b; }
        .mockup-dots span:nth-child(3) { background: #10b981; }

        .mockup-stat { background: rgba(255,255,255,0.06); border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
        .mockup-stat-label { color: rgba(255,255,255,0.5); font-size: 0.72rem; font-weight: 500; }
        .mockup-stat-val { font-weight: 800; font-size: 1rem; color: #fff; }
        .mockup-stat-val.green { color: #34d399; }
        .mockup-stat-val.red { color: #f87171; }
        .mockup-stat-val.gold { color: var(--gold); }

        .mockup-bar-wrap { margin-top: 16px; }
        .mockup-bar-label { display: flex; justify-content: space-between; color: rgba(255,255,255,0.45); font-size: 0.68rem; margin-bottom: 6px; }
        .mockup-bar { height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; margin-bottom: 10px; }
        .mockup-bar .fill { height: 100%; border-radius: 3px; animation: barGrow 1.5s ease 0.8s both; }
        @keyframes barGrow { from { width: 0 !important; } }

        .mockup-chart { display: flex; align-items: flex-end; gap: 6px; height: 60px; margin-top: 16px; }
        .mockup-bar-item { flex: 1; border-radius: 4px 4px 0 0; opacity: 0.8; animation: barUp 0.8s ease both; }
        @keyframes barUp { from { transform: scaleY(0); transform-origin: bottom; } }



        @keyframes fadeInUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }

        /* ─── STATS STRIP ─── */
        .stats-strip { background: #fff; padding: 0; border-bottom: 1px solid #f1f3f6; }
        .stats-inner {
            max-width: 1140px; margin: 0 auto; padding: 36px 40px;
            display: flex; justify-content: space-around; gap: 24px; flex-wrap: wrap;
        }
        .stat-item { text-align: center; padding: 0 20px; }
        .stat-num { font-size: 2.2rem; font-weight: 900; color: var(--navy); line-height: 1; }
        .stat-num .accent { color: var(--gold); }
        .stat-desc { font-size: 0.82rem; color: var(--text-muted); margin-top: 6px; font-weight: 500; }
        .stat-divider { width: 1px; background: #f1f3f6; align-self: stretch; }

        /* ─── FEATURES ─── */
        .features { padding: 90px 40px; background: #f0f2f8; }
        .section-inner { max-width: 1140px; margin: 0 auto; }
        .section-tag {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(26,31,60,0.06); color: var(--navy);
            font-size: 0.75rem; font-weight: 700; padding: 6px 14px;
            border-radius: 20px; text-transform: uppercase; letter-spacing: 0.6px;
            margin-bottom: 16px;
        }
        .section-title { font-size: 2.2rem; font-weight: 800; color: var(--navy); margin-bottom: 14px; }
        .section-sub { color: var(--text-muted); font-size: 1rem; max-width: 520px; line-height: 1.7; margin-bottom: 52px; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .feat-card {
            background: #fff; border-radius: 20px; padding: 32px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1.5px solid transparent;
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
            position: relative; overflow: hidden;
        }
        .feat-card::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            opacity: 0; transition: opacity 0.3s;
        }
        .feat-card:hover { transform: translateY(-8px); box-shadow: 0 20px 48px rgba(0,0,0,0.1); border-color: var(--navy); }
        .feat-card:hover::before { opacity: 1; }
        .feat-card:hover .feat-icon,
        .feat-card:hover .feat-title,
        .feat-card:hover .feat-desc { position: relative; }
        .feat-card:hover .feat-icon { background: rgba(255,255,255,0.12) !important; color: #fff !important; }
        .feat-card:hover .feat-title { color: #fff; }
        .feat-card:hover .feat-desc { color: rgba(255,255,255,0.65); }

        .feat-icon {
            width: 54px; height: 54px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; margin-bottom: 20px;
            transition: all 0.3s; position: relative;
        }
        .feat-title { font-size: 1.05rem; font-weight: 700; color: var(--navy); margin-bottom: 10px; position: relative; transition: color 0.3s; }
        .feat-desc { font-size: 0.875rem; color: var(--text-muted); line-height: 1.7; margin: 0; position: relative; transition: color 0.3s; }

        /* ─── HOW IT WORKS ─── */
        .how { padding: 90px 40px; background: #fff; }
        .steps { display: flex; gap: 0; align-items: flex-start; margin-top: 52px; position: relative; }
        .steps::before {
            content: ''; position: absolute;
            top: 28px; left: calc(16.67%); right: calc(16.67%);
            height: 2px; background: linear-gradient(90deg, var(--gold), var(--navy));
            z-index: 0;
        }
        .step { flex: 1; text-align: center; padding: 0 16px; position: relative; z-index: 1; }
        .step-num {
            width: 56px; height: 56px; border-radius: 50%;
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: #fff; font-size: 1rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px; box-shadow: 0 4px 16px rgba(26,31,60,0.25);
            border: 3px solid var(--gold);
        }
        .step-title { font-size: 0.95rem; font-weight: 700; color: var(--navy); margin-bottom: 8px; }
        .step-desc { font-size: 0.82rem; color: var(--text-muted); line-height: 1.6; }

        /* ─── CTA SECTION ─── */
        .cta-section {
            padding: 90px 40px;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            position: relative; overflow: hidden; text-align: center;
        }
        .cta-section::before {
            content: ''; position: absolute; inset: 0;
            background-image: radial-gradient(circle at 20% 50%, rgba(245,197,24,0.08) 0%, transparent 50%),
                              radial-gradient(circle at 80% 50%, rgba(59,130,246,0.08) 0%, transparent 50%);
        }
        .cta-inner { max-width: 640px; margin: 0 auto; position: relative; z-index: 1; }
        .cta-inner .section-tag { background: rgba(245,197,24,0.12); color: var(--gold); }
        .cta-inner .section-title { color: #fff; }
        .cta-inner .section-sub { color: rgba(255,255,255,0.6); margin-left: auto; margin-right: auto; display: block; }

        /* ─── FOOTER ─── */
        .lp-footer {
            background: #0f1224; padding: 32px 40px;
            text-align: center; color: rgba(255,255,255,0.3); font-size: 0.82rem;
        }
        .lp-footer strong { color: rgba(255,255,255,0.5); }
        .lp-footer a { color: rgba(255,255,255,0.45); text-decoration: none; transition: color 0.2s; }
        .lp-footer a:hover { color: var(--gold); }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 992px) {
            .hero-inner { flex-direction: column; text-align: center; gap: 40px; }
            .hero-sub { margin-left: auto; margin-right: auto; }
            .hero-btns { justify-content: center; }
            .hero-trust { justify-content: center; flex-wrap: wrap; }
            .hero-visual { width: 100%; max-width: 380px; }
    
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .steps::before { display: none; }
            .steps { flex-direction: column; gap: 32px; }
            .stat-divider { display: none; }
        }
        @media (max-width: 640px) {
            .lp-nav { padding: 0 20px; }
            .hero { padding: 100px 20px 60px; }
            .features-grid { grid-template-columns: 1fr; }
            .hero-title { font-size: 2.2rem; }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="lp-nav" id="lpNav">
    <a href="index.php" class="lp-brand"><div class="icon-wrap"><i class="fas fa-wallet"></i></div><span>Catat<span class="cuan">Cuan</span></span></a>
    <button class="lp-hamburger" id="lpNavToggle" aria-label="Toggle menu">
        <i class="fas fa-bars" id="lpNavIcon"></i>
    </button>
    <div class="lp-nav-links" id="lpNavLinks">
        <a href="#fitur">Fitur</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <?php if (isset($_SESSION['id_user'])): ?>
            <a href="dashboard.php" class="btn-nav-cta">
                <i class="fas fa-tachometer-alt"></i> Masuk Dashboard
            </a>
        <?php else: ?>
            <a href="login.php" style="color:rgba(255,255,255,0.75);">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="register.php" class="btn-nav-cta">
                <i class="fas fa-user-plus"></i> Register
            </a>
        <?php endif; ?>
    </div>
</nav>
<script>
(function(){
    var btn=document.getElementById('lpNavToggle');
    var menu=document.getElementById('lpNavLinks');
    var icon=document.getElementById('lpNavIcon');
    if(btn&&menu){btn.addEventListener('click',function(){
        menu.classList.toggle('lp-open');
        icon.className=menu.classList.contains('lp-open')?'fas fa-times':'fas fa-bars';
    });}
    // Tutup menu saat link di-klik
    if(menu){menu.querySelectorAll('a').forEach(function(a){
        a.addEventListener('click',function(){
            menu.classList.remove('lp-open');
            icon.className='fas fa-bars';
        });
    });}
})();
</script>

<!-- ===== HERO ===== -->
<section class="hero" id="beranda">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="hero-inner">
        <!-- Text Side -->
        <div class="hero-text">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                Solusi Keuangan untuk UMKM Indonesia
            </div>
            <h1 class="hero-title">
                Catat Keuangan<br>
                UMKM Anda dengan<br>
                <span class="highlight">Lebih Cerdas</span>
            </h1>
            <p class="hero-sub">
                CatatCuan memudahkan pencatatan pemasukan &amp; pengeluaran,
                manajemen produk, perhitungan margin keuntungan, dan
                laporan keuangan — semua dalam satu platform yang sederhana.
            </p>
            <div class="hero-btns">
                <?php if (isset($_SESSION['id_user'])): ?>
                    <a href="dashboard.php" class="btn-primary-hero">
                        <i class="fas fa-tachometer-alt"></i>
                        Masuk Dashboard
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn-primary-hero">
                        <i class="fas fa-user-plus"></i>
                        Daftar Gratis Sekarang
                    </a>
                    <a href="login.php" class="btn-ghost-hero">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                <?php endif; ?>
            </div>
            <div class="hero-trust">
                <div class="trust-item"><i class="fas fa-check-circle"></i> 100% Gratis</div>
                <div class="trust-divider"></div>
                <div class="trust-item"><i class="fas fa-shield-alt"></i> Data Aman</div>
                <div class="trust-divider"></div>
                <div class="trust-item"><i class="fas fa-database"></i> MySQL + PHP</div>
            </div>
        </div>

        <!-- Visual Side -->
        <div class="hero-visual">
            <div class="mockup-card">
                <div class="mockup-header">
                    <div class="mockup-title">📊 Dashboard Keuangan</div>
                    <div class="mockup-dots"><span></span><span></span><span></span></div>
                </div>

                <div class="mockup-stat">
                    <div class="mockup-stat-label">💰 Saldo Kas</div>
                    <div class="mockup-stat-val gold">Rp 8,4 Juta</div>
                </div>
                <div class="mockup-stat">
                    <div class="mockup-stat-label">📥 Pemasukan Bulan Ini</div>
                    <div class="mockup-stat-val green">+Rp 12,5 Juta</div>
                </div>
                <div class="mockup-stat">
                    <div class="mockup-stat-label">📤 Pengeluaran Bulan Ini</div>
                    <div class="mockup-stat-val red">-Rp 4,1 Juta</div>
                </div>

                <div class="mockup-bar-wrap">
                    <div class="mockup-bar-label"><span>Pemasukan</span><span>88%</span></div>
                    <div class="mockup-bar"><div class="fill" style="width:88%;background:linear-gradient(90deg,#10b981,#34d399);"></div></div>
                    <div class="mockup-bar-label"><span>Pengeluaran</span><span>33%</span></div>
                    <div class="mockup-bar"><div class="fill" style="width:33%;background:linear-gradient(90deg,#ef4444,#f87171);"></div></div>
                </div>

                <div style="margin-top:16px;color:rgba(255,255,255,0.4);font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:8px;">Grafik 6 Bulan Terakhir</div>
                <div class="mockup-chart">
                    <?php
                    $bars = [
                        ['h'=>'45%','c'=>'#10b981'],['h'=>'60%','c'=>'#10b981'],
                        ['h'=>'35%','c'=>'#ef4444'],['h'=>'75%','c'=>'#10b981'],
                        ['h'=>'55%','c'=>'#10b981'],['h'=>'88%','c'=>'#f5c518'],
                    ];
                    foreach ($bars as $i => $b):
                    ?>
                    <div class="mockup-bar-item" style="height:<?= $b['h'] ?>;background:<?= $b['c'] ?>;animation-delay:<?= $i*0.1 ?>s;"></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== STATS STRIP ===== -->
<div class="stats-strip">
    <div class="stats-inner">
        <div class="stat-item">
            <div class="stat-num">5<span class="accent">+</span></div>
            <div class="stat-desc">Modul Fitur Terintegrasi</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-num">100<span class="accent">%</span></div>
            <div class="stat-desc">Gratis Tanpa Biaya</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-num">Real<span class="accent">-</span>time</div>
            <div class="stat-desc">Data Selalu Terkini</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <div class="stat-num">3<span class="accent">x</span></div>
            <div class="stat-desc">Format Ekspor Laporan</div>
        </div>
    </div>
</div>

<!-- ===== FEATURES ===== -->
<section class="features" id="fitur">
    <div class="section-inner">
        <div class="section-tag"><i class="fas fa-star"></i> Fitur Unggulan</div>
        <h2 class="section-title">Semua Yang Anda Butuhkan</h2>
        <p class="section-sub">Dirancang khusus untuk pelaku UMKM agar pencatatan keuangan menjadi mudah, akurat, dan terorganisir.</p>

        <div class="features-grid">
            <!-- Card 1 -->
            <div class="feat-card">
                <div class="feat-icon" style="background:#eff6ff;color:#3b82f6;">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="feat-title">Catat Transaksi</div>
                <p class="feat-desc">Rekam pemasukan dan pengeluaran secara cepat. Lengkap dengan kategori, produk, tanggal, dan waktu transaksi.</p>
            </div>
            <!-- Card 2 -->
            <div class="feat-card">
                <div class="feat-icon" style="background:#f0fdf4;color:#10b981;">
                    <i class="fas fa-box"></i>
                </div>
                <div class="feat-title">Manajemen Produk</div>
                <p class="feat-desc">Kelola data produk dengan harga beli dan jual. Sistem otomatis menghitung estimasi margin keuntungan per transaksi.</p>
            </div>
            <!-- Card 3 -->
            <div class="feat-card">
                <div class="feat-icon" style="background:#f5f3ff;color:#8b5cf6;">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="feat-title">Kategori Kustom</div>
                <p class="feat-desc">Buat kategori transaksi sendiri sesuai jenis usaha Anda. Pisahkan arus masuk dan keluar dengan label yang jelas.</p>
            </div>
            <!-- Card 4 -->
            <div class="feat-card">
                <div class="feat-icon" style="background:#fffbeb;color:#f59e0b;">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="feat-title">Dashboard Analitik</div>
                <p class="feat-desc">Visualisasi grafik arus kas 6 bulan terakhir, ringkasan saldo, total pemasukan dan pengeluaran dalam satu tampilan.</p>
            </div>
            <!-- Card 5 -->
            <div class="feat-card">
                <div class="feat-icon" style="background:#fef2f2;color:#ef4444;">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="feat-title">Laporan Keuangan</div>
                <p class="feat-desc">Generate laporan per periode bulanan. Ekspor ke <strong>CSV (Excel)</strong>, <strong>Word (.doc)</strong>, atau cetak langsung dari browser.</p>
            </div>
            <!-- Card 6 -->
            <div class="feat-card">
                <div class="feat-icon" style="background:#f0f2f8;color:#3b4280;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="feat-title">Aman &amp; Terpercaya</div>
                <p class="feat-desc">Dibangun dengan PHP PDO dan prepared statements untuk mencegah SQL Injection. Data tersimpan aman di database MySQL lokal Anda.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="how" id="cara-kerja">
    <div class="section-inner">
        <div class="section-tag"><i class="fas fa-map-signs"></i> Cara Kerja</div>
        <h2 class="section-title">Mulai dalam 3 Langkah</h2>
        <p class="section-sub">Daftar gratis, setup usaha Anda, dan langsung mulai mencatat keuangan — semua dalam hitungan menit.</p>

        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-title">Daftar Akun Gratis</div>
                <p class="step-desc">Buat akun dengan nama usaha, email, dan password. Proses registrasi cepat — tidak perlu kartu kredit.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-title">Setup Kategori &amp; Produk</div>
                <p class="step-desc">Buat kategori transaksi dan tambahkan data produk sesuai jenis usaha Anda agar pencatatan lebih terstruktur.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-title">Catat &amp; Pantau Laporan</div>
                <p class="step-desc">Mulai catat pemasukan &amp; pengeluaran harian, lalu ekspor laporan keuangan kapan saja dalam format CSV atau Word.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA SECTION ===== -->
<section class="cta-section">
    <div class="cta-inner">
        <div class="section-tag"><i class="fas fa-rocket"></i> Mulai Sekarang</div>
        <h2 class="section-title" style="font-size:2rem;margin-top:8px;">Siap Mencatat Keuangan<br>Lebih Rapi?</h2>
        <p class="section-sub" style="color:rgba(255,255,255,0.6);margin-bottom:36px;">
            CatatCuan sudah siap digunakan. Daftar gratis dan mulai catat keuangan usaha Anda sekarang juga.
        </p>
        <?php if (isset($_SESSION['id_user'])): ?>
            <a href="dashboard.php" class="btn-primary-hero" style="font-size:1.05rem;padding:16px 44px;margin:0 auto;">
                <i class="fas fa-tachometer-alt"></i>
                Buka Dashboard Anda
            </a>
        <?php else: ?>
            <a href="register.php" class="btn-primary-hero" style="font-size:1.05rem;padding:16px 44px;margin:0 auto;">
                <i class="fas fa-user-plus"></i>
                Daftar Gratis Sekarang
            </a>
        <?php endif; ?>
        <div style="margin-top:24px;display:flex;justify-content:center;gap:24px;flex-wrap:wrap;">
            <div class="trust-item"><i class="fas fa-check-circle"></i> Gratis Selamanya</div>
            <div class="trust-item"><i class="fas fa-shield-alt"></i> Data Terisolasi 100%</div>
            <div class="trust-item"><i class="fas fa-clock"></i> Langsung Pakai</div>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="lp-footer">
    <p>
        © <?= date('Y') ?> <strong>CatatCuan</strong> &mdash;
        Sistem Pencatatan Keuangan UMKM &nbsp;|&nbsp;
        Dibangun dengan ❤️ menggunakan <a href="#">PHP</a> &amp; <a href="#">MySQL</a>
    </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Navbar scroll effect
    const nav = document.getElementById('lpNav');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 20);
    });

    // Intersection Observer for scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.animation = 'fadeInUp 0.6s ease both';
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.feat-card, .step, .stat-item').forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });
</script>
</body>
</html>