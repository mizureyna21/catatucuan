<?php
require_once 'koneksi.php';
checkAuth();

$stmtSaldo = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN jenis='masuk' THEN nominal ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN jenis='keluar' THEN nominal ELSE 0 END), 0) AS saldo
    FROM tb_transaksi WHERE id_user = ?
");
$stmtSaldo->execute([$_SESSION['id_user']]);
$totalSaldo = (int) $stmtSaldo->fetchColumn();

$bulanIni = date('Y-m');
$stmtBulan = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN jenis='masuk' THEN nominal ELSE 0 END), 0) AS pemasukan,
        COALESCE(SUM(CASE WHEN jenis='keluar' THEN nominal ELSE 0 END), 0) AS pengeluaran
    FROM tb_transaksi 
    WHERE id_user = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?
");
$stmtBulan->execute([$_SESSION['id_user'], $bulanIni]);
$bulanData = $stmtBulan->fetch();
$pemasukan = (int) $bulanData['pemasukan'];
$pengeluaran = (int) $bulanData['pengeluaran'];
$keuntungan = $pemasukan - $pengeluaran;

$stmtTerkini = $pdo->prepare("
    SELECT t.*, k.nama_kategori, k.ikon
    FROM tb_transaksi t
    JOIN tb_kategori k ON t.id_kategori = k.id_kategori
    WHERE t.id_user = ?
    ORDER BY t.tanggal DESC, t.waktu DESC
    LIMIT 5
");
$stmtTerkini->execute([$_SESSION['id_user']]);
$transaksiTerkini = $stmtTerkini->fetchAll();

$tahun = date('Y');
$bulan = date('m');
$hariDlmBulan = (int) date('t');
$interval = max(1, (int) ($hariDlmBulan / 6));

$chartLabels = [];
$chartMasuk = [];
$chartKeluar = [];

for ($i = 0; $i < 7; $i++) {
    $hari = min(1 + ($i * $interval), $hariDlmBulan);
    $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);
    $chartLabels[] = $hari . ' ' . date('M', mktime(0, 0, 0, $bulan, 1, $tahun));

    $stmtGraf = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN jenis='masuk' THEN nominal ELSE 0 END), 0) AS masuk,
            COALESCE(SUM(CASE WHEN jenis='keluar' THEN nominal ELSE 0 END), 0) AS keluar
        FROM tb_transaksi 
        WHERE id_user = ? AND tanggal <= ? AND DATE_FORMAT(tanggal,'%Y-%m') = ?
    ");
    $stmtGraf->execute([$_SESSION['id_user'], $tgl, $bulanIni]);
    $row = $stmtGraf->fetch();
    $chartMasuk[] = (int) $row['masuk'];
    $chartKeluar[] = (int) $row['keluar'];
}

$namaBulan = [
    '',
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
];
$periodeTeks = $namaBulan[(int) $bulan] . ' ' . $tahun;

function formatCompact(int $n): string
{
    if ($n >= 1000000)
        return 'Rp ' . number_format($n / 1000000, 1) . ' Jt';
    if ($n >= 1000)
        return 'Rp ' . number_format($n / 1000, 0) . ' Rb';
    return 'Rp ' . $n;
}

$alertMsg = $_GET['msg'] ?? '';
$alertStatus = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CatatCuan UMKM</title>
    <meta name="description"
        content="Dashboard keuangan UMKM CatatCuan – pantau saldo, pemasukan, dan pengeluaran usaha Anda secara real-time.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="logoc.png">
    <style>
        * {
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: #f0f2f8;
            margin: 0;
        }


        .navbar-custom {
            background: linear-gradient(135deg, #1a1f3c 0%, #2d3561 100%);
            padding: 0 32px;
            min-height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
        }

        .navbar-brand-text {
            color: #fff;
            font-weight: 700;
            font-size: 1.2rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand-text .cuan {
            color: #f5c518;
        }

        .navbar-nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .navbar-nav-links a {
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: all 0.2s;
        }

        .navbar-nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .navbar-nav-links a.active {
            background: rgba(245, 197, 24, 0.18);
            color: #f5c518;
        }

        /* ─── MOBILE HAMBURGER ─── */
        .nav-hamburger {
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
        .nav-hamburger:hover { background: rgba(255,255,255,0.1); }

        @media (max-width: 768px) {
            .navbar-custom  { padding: 0 16px; }
            .nav-hamburger  { display: flex; align-items: center; }
            .navbar-nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                padding: 8px 0 14px;
                gap: 0;
                border-top: 1px solid rgba(255,255,255,0.08);
            }
            .navbar-nav-links.mobile-open { display: flex; }
            .navbar-nav-links a { padding: 10px 12px; width: 100%; box-sizing: border-box; }
        }

        /* ─── USER PILL ─── */
        .nav-user-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(245,197,24,0.1);
            border: 1px solid rgba(245,197,24,0.25);
            border-radius: 20px;
            padding: 5px 12px 5px 6px;
            color: rgba(255,255,255,0.9);
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
            margin-right: 4px;
            cursor: default;
            user-select: none;
        }
        .nav-user-avatar {
            width: 26px; height: 26px;
            background: linear-gradient(135deg, #f5c518, #e6b800);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.72rem; color: #1a1f3c;
            flex-shrink: 0;
        }
        @media (max-width: 768px) {
            .nav-user-pill {
                width: 100%; border-radius: 10px;
                padding: 10px 12px; margin-right: 0;
                background: rgba(245,197,24,0.08);
                border-color: rgba(245,197,24,0.15);
                margin-bottom: 4px; white-space: normal;
            }
        }

        .page-wrapper {
            padding: 32px;
            max-width: 1280px;
            margin: 0 auto;
        }


        .page-header {
            margin-bottom: 28px;
        }

        .page-header h4 {
            font-weight: 700;
            color: #1a1f3c;
            margin: 0;
            font-size: 1.35rem;
        }

        .page-header p {
            color: #8a94a6;
            margin: 4px 0 0;
            font-size: 0.85rem;
        }


        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            opacity: 0.06;
        }

        .stat-card.saldo::after {
            background: #3b82f6;
        }

        .stat-card.masuk::after {
            background: #10b981;
        }

        .stat-card.keluar::after {
            background: #ef4444;
        }

        .stat-card.profit::after {
            background: #8b5cf6;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 16px;
        }

        .stat-icon.blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .stat-icon.green {
            background: #f0fdf4;
            color: #10b981;
        }

        .stat-icon.red {
            background: #fef2f2;
            color: #ef4444;
        }

        .stat-icon.purple {
            background: #f5f3ff;
            color: #8b5cf6;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #8a94a6;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1a1f3c;
            line-height: 1.1;
        }

        .badge-change {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
            margin-top: 8px;
        }

        .badge-change.up {
            background: #f0fdf4;
            color: #10b981;
        }

        .badge-change.down {
            background: #fef2f2;
            color: #ef4444;
        }


        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-action.success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-action.success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.4);
            color: #fff;
        }

        .btn-action.danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-action.danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(239, 68, 68, 0.4);
            color: #fff;
        }

        .btn-action.outline {
            background: #fff;
            color: #4b5563;
            border: 1.5px solid #e5e7eb;
        }

        .btn-action.outline:hover {
            background: #f9fafb;
            transform: translateY(-2px);
            color: #4b5563;
        }


        .content-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .content-card h6 {
            font-weight: 700;
            color: #1a1f3c;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .content-card p.sub {
            color: #8a94a6;
            font-size: 0.78rem;
            margin-bottom: 20px;
        }


        .table thead th {
            font-size: 0.75rem;
            color: #8a94a6;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f3f6;
            background: #fafbfc;
        }

        .table tbody td {
            font-size: 0.875rem;
            color: #374151;
            padding: 13px 12px;
            border-bottom: 1px solid #f8f9fb;
            vertical-align: middle;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background: #fafbfc;
        }

        .tx-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .tx-badge.in {
            background: #f0fdf4;
            color: #10b981;
        }

        .tx-badge.out {
            background: #fef2f2;
            color: #ef4444;
        }

        .nominal-masuk {
            color: #10b981;
            font-weight: 700;
        }

        .nominal-keluar {
            color: #ef4444;
            font-weight: 700;
        }

        .see-all {
            display: block;
            text-align: center;
            color: #3b4280;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
            margin-top: 16px;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .see-all:hover {
            background: #f0f2f8;
        }


        .alert-toast {
            position: fixed;
            top: 80px;
            right: 24px;
            z-index: 9999;
            background: #1a1f3c;
            color: #fff;
            border-radius: 12px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            min-width: 260px;
            animation: slideIn 0.3s ease;
        }

        .alert-toast.success i {
            color: #10b981;
        }

        .alert-toast.error i {
            color: #ef4444;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }


        .empty-row td {
            text-align: center;
            color: #9ca3af;
            padding: 32px !important;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-custom" id="mainNav">
        <a href="index.php" class="navbar-brand-text">
            <i class="fas fa-wallet"></i> <span>Catat<span class="cuan">Cuan</span></span>
        </a>
        <button class="nav-hamburger" id="navToggle" aria-label="Toggle navigation">
            <i class="fas fa-bars" id="navIcon"></i>
        </button>
        <div class="navbar-nav-links" id="navLinks">
            <div class="nav-user-pill">
                <div class="nav-user-avatar"><?= strtoupper(substr($_SESSION['nama_toko'] ?? 'U', 0, 1)) ?></div>
                <span>Hai, <strong><?= htmlspecialchars(explode(' ', trim($_SESSION['nama_toko'] ?? 'User'))[0]) ?></strong></span>
            </div>
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="kategori.php"><i class="fas fa-tags"></i> Kategori</a>
            <a href="produk.php"><i class="fas fa-box"></i> Produk</a>
            <a href="transaksi.php"><i class="fas fa-exchange-alt"></i> Transaksi</a>
            <a href="laporan.php"><i class="fas fa-file-alt"></i> Laporan</a>
            <a href="logout.php" style="color:rgba(255,255,255,0.5);" title="Logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    <script>
    (function(){
        var btn=document.getElementById('navToggle');
        var menu=document.getElementById('navLinks');
        var icon=document.getElementById('navIcon');
        if(btn&&menu){btn.addEventListener('click',function(){
            menu.classList.toggle('mobile-open');
            icon.className=menu.classList.contains('mobile-open')?'fas fa-times':'fas fa-bars';
        });}
    })();
    </script>

    <!-- ===== PAGE WRAPPER ===== -->
    <div class="page-wrapper">

        <!-- Page Header -->
        <div class="page-header">
            <h4>📊 Dashboard Keuangan</h4>
            <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama_toko'] ?? 'User') ?></strong>! Periode: <?= $periodeTeks ?>.</p>
        </div>

        <!-- ===== STAT CARDS ===== -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card saldo">
                    <div class="stat-icon blue"><i class="fas fa-wallet"></i></div>
                    <div class="stat-label">Total Saldo Kas</div>
                    <div class="stat-value"><?= formatCompact($totalSaldo) ?></div>
                    <span class="badge-change <?= $totalSaldo >= 0 ? 'up' : 'down' ?>">
                        <i class="fas fa-<?= $totalSaldo >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= $totalSaldo >= 0 ? 'Positif' : 'Defisit' ?>
                    </span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card masuk">
                    <div class="stat-icon green"><i class="fas fa-arrow-down"></i></div>
                    <div class="stat-label">Pemasukan Bulan Ini</div>
                    <div class="stat-value"><?= formatCompact($pemasukan) ?></div>
                    <span class="badge-change up"><i class="fas fa-calendar"></i> <?= $periodeTeks ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card keluar">
                    <div class="stat-icon red"><i class="fas fa-arrow-up"></i></div>
                    <div class="stat-label">Pengeluaran Bulan Ini</div>
                    <div class="stat-value"><?= formatCompact($pengeluaran) ?></div>
                    <span class="badge-change down"><i class="fas fa-calendar"></i> <?= $periodeTeks ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card profit">
                    <div class="stat-icon purple"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-label">Keuntungan Bersih</div>
                    <div class="stat-value"><?= formatCompact(abs($keuntungan)) ?></div>
                    <span class="badge-change <?= $keuntungan >= 0 ? 'up' : 'down' ?>">
                        <i class="fas fa-<?= $keuntungan >= 0 ? 'smile' : 'frown' ?>"></i>
                        <?= $keuntungan >= 0 ? 'Untung' : 'Rugi' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
            <a href="form_transaksi.php?jenis=masuk" class="btn-action success"><i class="fas fa-plus-circle"></i> Catat
                Pemasukan</a>
            <a href="form_transaksi.php?jenis=keluar" class="btn-action danger"><i class="fas fa-minus-circle"></i>
                Catat Pengeluaran</a>
            <a href="laporan.php" class="btn-action outline"><i class="fas fa-print"></i> Cetak Laporan</a>
        </div>

        <!-- Chart + Table -->
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="content-card">
                    <h6>Grafik Arus Kas</h6>
                    <p class="sub">Pergerakan pemasukan &amp; pengeluaran — <?= $periodeTeks ?></p>
                    <div style="position:relative;height:280px;">
                        <canvas id="cashFlowChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="content-card">
                    <h6>Transaksi Terkini</h6>
                    <p class="sub">5 transaksi terakhir yang dicatat</p>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kategori</th>
                                    <th>Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transaksiTerkini)): ?>
                                    <tr class="empty-row">
                                        <td colspan="3">
                                            <i class="fas fa-inbox"
                                                style="font-size:1.5rem;display:block;margin-bottom:8px;color:#d1d5db;"></i>
                                            Belum ada transaksi.<br>
                                            <a href="form_transaksi.php" style="color:#3b4280;font-weight:600;">Catat
                                                sekarang →</a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transaksiTerkini as $tx): ?>
                                        <tr>
                                            <td class="text-muted" style="font-size:0.8rem;white-space:nowrap;">
                                                <?= formatTanggal($tx['tanggal']) ?>
                                            </td>
                                            <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                                title="<?= htmlspecialchars($tx['nama_kategori']) ?>">
                                                <?= $tx['ikon'] ?>         <?= htmlspecialchars($tx['nama_kategori']) ?>
                                            </td>
                                            <td>
                                                <?php if ($tx['jenis'] === 'masuk'): ?>
                                                    <span class="tx-badge in"><i class="fas fa-arrow-down"></i>
                                                        <?= formatCompact($tx['nominal']) ?></span>
                                                <?php else: ?>
                                                    <span class="tx-badge out"><i class="fas fa-arrow-up"></i>
                                                        <?= formatCompact($tx['nominal']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="transaksi.php" class="see-all">Lihat Semua Transaksi <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

    </div>

    <!-- ===== TOAST ALERT ===== -->
    <?php if ($alertMsg): ?>
        <div class="alert-toast <?= $alertStatus === 'success' ? 'success' : 'error' ?>" id="toastAlert">
            <i class="fas fa-<?= $alertStatus === 'success' ? 'check-circle' : 'times-circle' ?>"></i>
            <?= htmlspecialchars($alertMsg) ?>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('cashFlowChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [
                        {
                            label: 'Pemasukan',
                            data: <?= json_encode($chartMasuk) ?>,
                            borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)',
                            borderWidth: 2.5, tension: 0.45, fill: true,
                            pointBackgroundColor: '#10b981', pointRadius: 4, pointHoverRadius: 6
                        },
                        {
                            label: 'Pengeluaran',
                            data: <?= json_encode($chartKeluar) ?>,
                            borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.08)',
                            borderWidth: 2.5, tension: 0.45, fill: true,
                            pointBackgroundColor: '#ef4444', pointRadius: 4, pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { usePointStyle: true, pointStyle: 'circle', font: { family: 'Inter', size: 12 }, color: '#6b7280' }
                        },
                        tooltip: {
                            backgroundColor: '#1a1f3c',
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': ' + new Intl.NumberFormat('id-ID', {
                                        style: 'currency', currency: 'IDR', minimumFractionDigits: 0
                                    }).format(ctx.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#9ca3af' } },
                        y: {
                            beginAtZero: true, grid: { color: '#f3f4f6' },
                            ticks: {
                                font: { family: 'Inter', size: 11 }, color: '#9ca3af',
                                callback: v => 'Rp ' + (v >= 1000000 ? (v / 1000000).toFixed(1) + ' Jt' : (v / 1000).toFixed(0) + ' Rb')
                            }
                        }
                    }
                }
            });
        });


        const toast = document.getElementById('toastAlert');
        if (toast) setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.4s';
            setTimeout(() => toast.remove(), 400);
        }, 3500);
    </script>
</body>

</html>