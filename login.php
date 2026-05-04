<?php
require_once 'koneksi.php';
if (isset($_SESSION['id_user'])) {
    header('Location: dashboard.php');
    exit;
}
$error = $_GET['error'] ?? '';
$email = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CatatCuan</title>
    <meta name="description" content="Login ke CatatCuan dan akses laporan keuangan UMKM Anda.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #1a1f3c;
            --navy-light: #2d3561;
            --gold: #f5c518;
            --gold-dark: #e6b800;
        }
        * { font-family: 'Inter', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: linear-gradient(145deg, var(--navy) 0%, var(--navy-light) 55%, #1e2d5a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }
        .blob { position: absolute; border-radius: 50%; filter: blur(90px); opacity: 0.1; pointer-events: none; }
        .blob-1 { width: 500px; height: 500px; background: var(--gold); top: -200px; right: -100px; animation: blobFloat 10s ease-in-out infinite; }
        .blob-2 { width: 350px; height: 350px; background: #3b82f6; bottom: -150px; left: -80px; animation: blobFloat 12s ease-in-out infinite reverse; }
        @keyframes blobFloat {
            0%,100% { transform: translate(0,0) scale(1); }
            33% { transform: translate(20px,-30px) scale(1.05); }
            66% { transform: translate(-15px,20px) scale(0.95); }
        }
        body::before {
            content: '';
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .auth-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 440px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 24px 64px rgba(0,0,0,0.4);
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.5s ease both;
        }
        @keyframes fadeInUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }

        .auth-logo {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none; margin-bottom: 32px;
        }
        .auth-logo .icon-wrap {
            width: 44px; height: 44px; background: var(--gold);
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
        }
        .auth-logo .icon-wrap i { color: var(--navy); font-size: 1.15rem; }
        .auth-logo span { color: #fff; font-size: 1.4rem; font-weight: 800; }
        .auth-logo .cuan { color: var(--gold); }

        .auth-title {
            color: #fff; font-size: 1.6rem; font-weight: 800;
            margin-bottom: 6px; line-height: 1.2;
        }
        .auth-sub { color: rgba(255,255,255,0.5); font-size: 0.875rem; margin-bottom: 28px; }

        .error-alert {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.35);
            border-radius: 10px;
            padding: 12px 16px;
            color: #fca5a5;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }

        .form-group { margin-bottom: 18px; }
        .form-label-auth {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255,255,255,0.55);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .form-input-auth {
            width: 100%;
            background: rgba(255,255,255,0.07);
            border: 1.5px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 12px 16px;
            color: #fff;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-input-auth::placeholder { color: rgba(255,255,255,0.3); }
        .form-input-auth:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(245,197,24,0.15);
        }

        .btn-auth {
            width: 100%;
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            color: var(--navy);
            font-weight: 800;
            font-size: 1rem;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: all 0.25s;
            box-shadow: 0 8px 24px rgba(245,197,24,0.35);
            font-family: 'Inter', sans-serif;
            margin-top: 8px;
        }
        .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(245,197,24,0.45); }
        .btn-auth:active { transform: translateY(0); }

        .auth-footer {
            text-align: center;
            margin-top: 24px;
            color: rgba(255,255,255,0.4);
            font-size: 0.85rem;
        }
        .auth-footer a {
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
        }
        .auth-footer a:hover { text-decoration: underline; }

        .divider-auth {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 22px 0;
        }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="auth-card">
        <a href="index.php" class="auth-logo">
            <div class="icon-wrap"><i class="fas fa-wallet"></i></div>
            <span>Catat<span class="cuan">Cuan</span></span>
        </a>

        <h1 class="auth-title">Selamat Datang</h1>
        <p class="auth-sub">Login ke akun CatatCuan Anda untuk melanjutkan.</p>

        <?php if ($error): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="proses_login.php" id="formLogin">
            <div class="form-group">
                <label class="form-label-auth" for="email">Email</label>
                <input
                    type="email"
                    class="form-input-auth"
                    name="email"
                    id="email"
                    placeholder="contoh@email.com"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                    autocomplete="email"
                >
            </div>
            <div class="form-group">
                <label class="form-label-auth" for="password">Password</label>
                <input
                    type="password"
                    class="form-input-auth"
                    name="password"
                    id="password"
                    placeholder="Masukkan password Anda"
                    required
                    autocomplete="current-password"
                >
            </div>
            <button type="submit" class="btn-auth" id="btnLogin">
                <i class="fas fa-sign-in-alt"></i>
                Masuk ke CatatCuan
            </button>
        </form>

        <div class="divider-auth"></div>

        <div class="auth-footer">
            Belum punya akun?
            <a href="register.php">Daftar Gratis Sekarang</a>
        </div>
    </div>

    <script>
        document.getElementById('formLogin').addEventListener('submit', function() {
            const btn = document.getElementById('btnLogin');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memverifikasi...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
