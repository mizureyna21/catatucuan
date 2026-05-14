<?php
require_once 'koneksi.php';
checkAuth();

$bulanDefault = date('m');
$tahunDefault = date('Y');

$bulan = (int) ($_GET['bulan'] ?? $bulanDefault);
$tahun = (int) ($_GET['tahun'] ?? $tahunDefault);
$jenis = $_GET['jenis'] ?? 'all';

if ($bulan < 1 || $bulan > 12)
    $bulan = $bulanDefault;
if ($tahun < 2020 || $tahun > 2099)
    $tahun = $tahunDefault;

$periodeStr = sprintf('%04d-%02d', $tahun, $bulan);

$where  = "WHERE t.id_user = :uid AND DATE_FORMAT(t.tanggal, '%Y-%m') = :periode";
$params = [':uid' => $_SESSION['id_user'], ':periode' => $periodeStr];
if (in_array($jenis, ['masuk', 'keluar'])) {
    $where .= " AND t.jenis = :jenis";
    $params[':jenis'] = $jenis;
}

$sql = "SELECT t.*, k.nama_kategori, k.ikon, p.nama_produk, p.harga_beli AS produk_harga_beli FROM tb_transaksi t
        JOIN tb_kategori k ON t.id_kategori = k.id_kategori
        LEFT JOIN tb_produk p ON t.id_produk = p.id_produk
        $where ORDER BY t.tanggal ASC, t.waktu ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksiList = $stmt->fetchAll();

$totalMasuk = array_sum(array_column(array_filter($transaksiList, fn($t) => $t['jenis'] === 'masuk'), 'nominal'));
$totalKeluar = array_sum(array_column(array_filter($transaksiList, fn($t) => $t['jenis'] === 'keluar'), 'nominal'));
$saldo = $totalMasuk - $totalKeluar;
$jumlahTx = count($transaksiList);

// Estimasi Keuntungan: transaksi masuk yg punya id_produk => nominal - harga_beli produk
$estimasiKeuntungan = 0;
foreach ($transaksiList as $t) {
    if ($t['jenis'] === 'masuk' && !empty($t['id_produk']) && isset($t['produk_harga_beli'])) {
        $estimasiKeuntungan += ($t['nominal'] - (int) $t['produk_harga_beli']);
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $namaBln = [
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
    $filename = 'laporan_catatcuan_' . $namaBln[$bulan] . '_' . $tahun . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    fputcsv($out, ['LAPORAN KEUANGAN CATATCUAN'], ';');
    fputcsv($out, ['Periode', $namaBln[$bulan] . ' ' . $tahun], ';');
    fputcsv($out, ['Total Transaksi', $jumlahTx], ';');
    fputcsv($out, ['Total Pemasukan', $totalMasuk], ';');
    fputcsv($out, ['Total Pengeluaran', $totalKeluar], ';');
    fputcsv($out, ['Saldo Akhir', $saldo], ';');
    fputcsv($out, [], ';');
    fputcsv($out, ['No', 'Tanggal', 'Waktu', 'Produk', 'Kategori', 'Jenis', 'Nominal'], ';');

    foreach ($transaksiList as $i => $tx) {
        fputcsv($out, [
            $i + 1,
            $tx['tanggal'],
            substr($tx['waktu'], 0, 5),
            $tx['nama_produk'] ?? '-',
            $tx['ikon'] . ' ' . $tx['nama_kategori'],
            $tx['jenis'] === 'masuk' ? 'Pemasukan' : 'Pengeluaran',
            $tx['nominal'],
        ], ';');
    }
    fclose($out);
    exit;
}

$namaBulanArr = [
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

$tahunList = range(date('Y') + 1, max(2024, date('Y') - 4), -1);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - CatatCuan UMKM</title>
    <meta name="description" content="Ekspor dan cetak laporan arus kas keuangan usaha Anda dengan CatatCuan.">
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
            max-width: 1100px;
            margin: 0 auto;
        }


        .card-custom {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .card-title-custom {
            font-weight: 700;
            font-size: 1rem;
            color: #1a1f3c;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-sub {
            color: #8a94a6;
            font-size: 0.82rem;
            margin-bottom: 22px;
        }


        .field-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 7px;
            display: block;
        }

        .field-input,
        .field-select {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.875rem;
            color: #374151;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            background: #fff;
        }

        .field-input:focus,
        .field-select:focus {
            border-color: #3b4280;
            box-shadow: 0 0 0 3px rgba(59, 66, 128, 0.1);
        }


        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .summ-card {
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .summ-card.blue {
            background: #eff6ff;
        }

        .summ-card.green {
            background: #f0fdf4;
        }

        .summ-card.red {
            background: #fef2f2;
        }

        .summ-card.purple {
            background: #f5f3ff;
        }

        .summ-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .summ-card.blue .summ-icon {
            background: #dbeafe;
            color: #3b82f6;
        }

        .summ-card.green .summ-icon {
            background: #bbf7d0;
            color: #10b981;
        }

        .summ-card.red .summ-icon {
            background: #fecaca;
            color: #ef4444;
        }

        .summ-card.purple .summ-icon {
            background: #ede9fe;
            color: #8b5cf6;
        }

        .summ-label {
            font-size: 0.72rem;
            color: #6b7280;
            font-weight: 500;
        }

        .summ-value {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1f3c;
        }


        .preview-table-wrap .table thead th {
            background: #fafbfc;
            font-size: 0.72rem;
            color: #8a94a6;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 11px 16px;
            border-bottom: 1px solid #f1f3f6;
        }

        .preview-table-wrap .table tbody td {
            padding: 12px 16px;
            font-size: 0.84rem;
            color: #374151;
            border-bottom: 1px solid #f8f9fb;
            vertical-align: middle;
        }

        .preview-table-wrap .table tbody tr:last-child td {
            border-bottom: none;
        }

        .preview-table-wrap .table tbody tr:hover {
            background: #fafbfc;
        }

        .tx-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 20px;
        }

        .tx-badge.masuk {
            background: #f0fdf4;
            color: #10b981;
        }

        .tx-badge.keluar {
            background: #fef2f2;
            color: #ef4444;
        }

        .kat-badge {
            background: #f0f2f8;
            color: #3b4280;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 500;
        }


        .btn-generate {
            background: linear-gradient(135deg, #3b4280, #2d3561);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 13px 28px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 6px 20px rgba(59, 66, 128, 0.3);
            text-decoration: none;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            color: #fff;
        }

        .btn-generate.csv-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-generate.print-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-generate.word-btn {
            background: linear-gradient(135deg, #1d4ed8, #1e3a8a);
            box-shadow: 0 6px 20px rgba(29, 78, 216, 0.35);
        }

        .btn-filter-main {
            background: linear-gradient(135deg, #3b4280, #2d3561);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            width: 100%;
            justify-content: center;
        }

        .btn-filter-main:hover {
            opacity: 0.9;
        }


        .print-preview-wrap {
            background: #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            margin-top: 24px;
            display: none;
        }

        .print-preview-wrap.visible {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .a4-page {
            background: #fff;
            max-width: 794px;
            margin: 0 auto;
            padding: 40px 48px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border-radius: 4px;
        }

        .a4-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1a1f3c;
        }

        .a4-logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: #1a1f3c;
        }

        .a4-logo span {
            color: #f5c518;
        }

        .a4-report-title {
            text-align: right;
        }

        .a4-report-title h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1f3c;
            margin: 0;
        }

        .a4-report-title p {
            color: #6b7280;
            font-size: 0.78rem;
            margin: 2px 0 0;
        }

        .a4-info-box {
            background: #f8f9fb;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }

        .a4-info-item .key {
            font-size: 0.68rem;
            color: #9ca3af;
            font-weight: 500;
            text-transform: uppercase;
        }

        .a4-info-item .val {
            font-size: 0.82rem;
            font-weight: 600;
            color: #1a1f3c;
            margin-top: 2px;
        }

        .a4-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .a4-table th {
            background: #1a1f3c;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            padding: 10px 12px;
            text-align: left;
        }

        .a4-table td {
            font-size: 0.75rem;
            color: #374151;
            padding: 9px 12px;
            border-bottom: 1px solid #f1f3f6;
        }

        .a4-table tbody tr:nth-child(even) td {
            background: #fafbfc;
        }

        .a4-table .masuk-text {
            color: #10b981;
            font-weight: 700;
        }

        .a4-table .keluar-text {
            color: #ef4444;
            font-weight: 700;
        }

        .a4-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .a4-summ-box {
            border-radius: 8px;
            padding: 12px 16px;
        }

        .a4-summ-box.masuk {
            background: #f0fdf4;
        }

        .a4-summ-box.keluar {
            background: #fef2f2;
        }

        .a4-summ-box.total {
            background: #f0f2f8;
        }

        .a4-summ-box.saldo {
            background: linear-gradient(135deg, #1a1f3c, #2d3561);
        }

        .a4-summ-box .label {
            font-size: 0.65rem;
            color: #9ca3af;
            text-transform: uppercase;
            font-weight: 600;
        }

        .a4-summ-box .amount {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1a1f3c;
            margin-top: 4px;
        }

        .a4-summ-box.saldo .label {
            color: rgba(255, 255, 255, 0.6);
        }

        .a4-summ-box.saldo .amount {
            color: #f5c518;
        }

        .a4-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            margin-top: 20px;
        }

        .a4-footer .timestamp {
            font-size: 0.68rem;
            color: #9ca3af;
        }

        .a4-signature {
            text-align: center;
        }

        .a4-signature .title {
            font-size: 0.72rem;
            color: #6b7280;
            margin-bottom: 50px;
        }

        .a4-signature .line {
            border-top: 1px solid #1a1f3c;
            padding-top: 6px;
            font-size: 0.72rem;
            font-weight: 600;
            color: #1a1f3c;
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
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="kategori.php"><i class="fas fa-tags"></i> Kategori</a>
            <a href="produk.php"><i class="fas fa-box"></i> Produk</a>
            <a href="transaksi.php"><i class="fas fa-exchange-alt"></i> Transaksi</a>
            <a href="laporan.php" class="active"><i class="fas fa-file-alt"></i> Laporan</a>
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

    <div class="page-wrapper">

        <!-- Page Header -->
        <div style="margin-bottom:28px;">
            <h4 style="font-weight:700;color:#1a1f3c;font-size:1.35rem;margin:0;">📋 Laporan Keuangan</h4>
            <p style="color:#8a94a6;margin:4px 0 0;font-size:0.85rem;">
                Generate dan ekspor laporan arus kas — <?= $namaBulanArr[$bulan] . ' ' . $tahun ?>
            </p>
        </div>

        <div class="row g-4">
            <!-- Left Column: Filter -->
            <div class="col-lg-4">
                <div class="card-custom">
                    <div class="card-title-custom">
                        <i class="fas fa-calendar-alt" style="color:#3b82f6;"></i>
                        Filter Laporan
                    </div>
                    <div class="card-sub">Pilih periode dan jenis transaksi</div>

                    <form method="GET" action="laporan.php">
                        <div style="margin-bottom:16px;">
                            <label class="field-label">Bulan</label>
                            <select class="field-select" name="bulan">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>>
                                        <?= $namaBulanArr[$m] ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label class="field-label">Tahun</label>
                            <select class="field-select" name="tahun">
                                <?php foreach ($tahunList as $y): ?>
                                    <option value="<?= $y ?>" <?= $y === $tahun ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom:20px;">
                            <label class="field-label">Filter Jenis</label>
                            <select class="field-select" name="jenis">
                                <option value="all" <?= $jenis === 'all' ? 'selected' : '' ?>>Semua Transaksi</option>
                                <option value="masuk" <?= $jenis === 'masuk' ? 'selected' : '' ?>>Pemasukan Saja</option>
                                <option value="keluar" <?= $jenis === 'keluar' ? 'selected' : '' ?>>Pengeluaran Saja
                                </option>
                            </select>
                        </div>
                        <button type="submit" class="btn-filter-main mb-3">
                            <i class="fas fa-filter"></i> Tampilkan Laporan
                        </button>
                    </form>

                    <div style="display:flex;flex-direction:column;gap:10px;margin-top:4px;">
                        <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&jenis=<?= $jenis ?>&export=csv"
                            class="btn-generate csv-btn" style="justify-content:center;">
                            <i class="fas fa-file-csv"></i> Export CSV (Excel)
                        </a>
                        <a href="export_word.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&jenis=<?= urlencode($jenis) ?>"
                            class="btn-generate word-btn" style="justify-content:center;">
                            <i class="fas fa-file-word"></i> Export ke Word
                        </a>
                        <button class="btn-generate print-btn" onclick="cetakLaporan()" style="justify-content:center;">
                            <i class="fas fa-print"></i> Cetak Laporan
                        </button>
                        <button class="btn-generate" onclick="togglePreview()" id="btnPreview"
                            style="justify-content:center;background:linear-gradient(135deg,#6b7280,#4b5563);">
                            <i class="fas fa-eye"></i> <span id="previewBtnText">Lihat Preview</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Summary + Table -->
            <div class="col-lg-8">
                <!-- Summary Cards -->
                <div class="card-custom" style="padding:20px 24px;">
                    <div class="card-title-custom" style="margin-bottom:16px;">
                        <i class="fas fa-chart-bar" style="color:#f59e0b;"></i>
                        Ringkasan — <?= $namaBulanArr[$bulan] . ' ' . $tahun ?>
                    </div>
                    <div class="summary-grid" style="grid-template-columns:repeat(2,1fr);">
                        <div class="summ-card blue">
                            <div class="summ-icon"><i class="fas fa-receipt"></i></div>
                            <div>
                                <div class="summ-label">Total Transaksi</div>
                                <div class="summ-value"><?= $jumlahTx ?></div>
                            </div>
                        </div>
                        <div class="summ-card green">
                            <div class="summ-icon"><i class="fas fa-arrow-down"></i></div>
                            <div>
                                <div class="summ-label">Total Masuk</div>
                                <div class="summ-value" style="color:#10b981;"><?= formatRupiah($totalMasuk) ?></div>
                            </div>
                        </div>
                        <div class="summ-card red">
                            <div class="summ-icon"><i class="fas fa-arrow-up"></i></div>
                            <div>
                                <div class="summ-label">Total Keluar</div>
                                <div class="summ-value" style="color:#ef4444;"><?= formatRupiah($totalKeluar) ?></div>
                            </div>
                        </div>
                        <div class="summ-card <?= $saldo >= 0 ? 'purple' : 'red' ?>">
                            <div class="summ-icon"><i class="fas fa-wallet"></i></div>
                            <div>
                                <div class="summ-label">Saldo Akhir <?= $saldo < 0 ? '⚠️ Defisit' : '' ?></div>
                                <div class="summ-value" style="color:<?= $saldo >= 0 ? '#8b5cf6' : '#ef4444' ?>;">
                                    <?= ($saldo < 0 ? '- ' : '') . formatRupiah(abs($saldo)) ?>
                                </div>
                            </div>
                        </div>
                        <!-- Estimasi Keuntungan Produk (full width) -->
                        <div class="summ-card" style="background:#fffbeb;grid-column:1/-1;">
                            <div class="summ-icon" style="background:#fef3c7;color:#f59e0b;"><i
                                    class="fas fa-chart-line"></i></div>
                            <div>
                                <div class="summ-label">
                                    Estimasi Keuntungan Produk
                                    <span
                                        style="font-weight:400;text-transform:none;letter-spacing:0;font-size:0.72rem;">(dari
                                        tx masuk ber-produk: Nominal &minus; Harga Beli)</span>
                                </div>
                                <div class="summ-value"
                                    style="color:<?= $estimasiKeuntungan >= 0 ? '#f59e0b' : '#ef4444' ?>;">
                                    <?= ($estimasiKeuntungan < 0 ? '- ' : '') . formatRupiah(abs($estimasiKeuntungan)) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="card-custom" style="padding:0;overflow:hidden;">
                    <div
                        style="padding:20px 24px 16px;border-bottom:1px solid #f1f3f6;display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-weight:700;color:#1a1f3c;font-size:1rem;">Data Transaksi</div>
                            <div style="color:#8a94a6;font-size:0.78rem;margin-top:2px;">
                                Periode <?= $namaBulanArr[$bulan] . ' ' . $tahun ?>
                            </div>
                        </div>
                        <span
                            style="background:#f0f2f8;color:#3b4280;font-size:0.78rem;font-weight:600;padding:4px 12px;border-radius:20px;">
                            <?= $jumlahTx ?> Transaksi
                        </span>
                    </div>
                    <div class="preview-table-wrap">
                        <?php if (empty($transaksiList)): ?>
                            <div style="text-align:center;padding:60px;color:#9ca3af;">
                                <i class="fas fa-inbox"
                                    style="font-size:2.5rem;display:block;margin-bottom:12px;color:#d1d5db;"></i>
                                Tidak ada transaksi di periode ini.
                            </div>
                        <?php else: ?>
                            <table class="table" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th>Jenis</th>
                                        <th>Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transaksiList as $i => $tx): ?>
                                        <tr>
                                            <td style="color:#b0b8c8;font-weight:600;"><?= $i + 1 ?></td>
                                            <td style="font-weight:500;white-space:nowrap;"><?= formatTanggal($tx['tanggal']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($tx['nama_produk'] ?? '-') ?></td>
                                            <td><span class="kat-badge"><?= $tx['ikon'] ?>
                                                    <?= htmlspecialchars($tx['nama_kategori']) ?></span></td>
                                            <td>
                                                <?php if ($tx['jenis'] === 'masuk'): ?>
                                                    <span class="tx-badge masuk"><i class="fas fa-arrow-down"></i> Masuk</span>
                                                <?php else: ?>
                                                    <span class="tx-badge keluar"><i class="fas fa-arrow-up"></i> Keluar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td
                                                style="font-weight:700;color:<?= $tx['jenis'] === 'masuk' ? '#10b981' : '#ef4444' ?>;">
                                                <?= $tx['jenis'] === 'masuk' ? '+' : '-' ?>         <?= formatRupiah($tx['nominal']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRINT PREVIEW -->
        <div class="print-preview-wrap" id="printPreview">
            <div style="text-align:center;margin-bottom:16px;">
                <span
                    style="background:#1a1f3c;color:#fff;padding:6px 16px;border-radius:20px;font-size:0.78rem;font-weight:600;">
                    👁️ PREVIEW CETAK — TAMPILAN HALAMAN A4
                </span>
            </div>
            <div class="a4-page" id="a4Content">
                <div class="a4-header">
                    <div class="a4-logo">Catat<span>Cuan</span><br>
                        <span style="font-size:0.72rem;font-weight:400;color:#6b7280;">Sistem Keuangan UMKM</span>
                    </div>
                    <div class="a4-report-title">
                        <h3>LAPORAN ARUS KAS</h3>
                        <p>Periode: <?= $namaBulanArr[$bulan] . ' ' . $tahun ?></p>
                    </div>
                </div>
                <div class="a4-info-box">
                    <div class="a4-info-item">
                        <div class="key">Periode</div>
                        <div class="val"><?= $namaBulanArr[$bulan] . ' ' . $tahun ?></div>
                    </div>
                    <div class="a4-info-item">
                        <div class="key">Total Transaksi</div>
                        <div class="val"><?= $jumlahTx ?> Transaksi</div>
                    </div>
                    <div class="a4-info-item">
                        <div class="key">Dibuat Pada</div>
                        <div class="val"><?= date('d F Y, H:i') ?></div>
                    </div>
                </div>
                <table class="a4-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Jenis</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transaksiList as $i => $tx): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= formatTanggal($tx['tanggal']) ?></td>
                                <td><?= htmlspecialchars($tx['nama_produk'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($tx['nama_kategori']) ?></td>
                                <td><?= $tx['jenis'] === 'masuk' ? 'Pemasukan' : 'Pengeluaran' ?></td>
                                <td class="<?= $tx['jenis'] === 'masuk' ? 'masuk-text' : 'keluar-text' ?>">
                                    <?= $tx['jenis'] === 'masuk' ? '+' : '-' ?>     <?= formatRupiah($tx['nominal']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="a4-summary">
                    <div class="a4-summ-box masuk">
                        <div class="label">Total Pemasukan</div>
                        <div class="amount" style="color:#10b981;"><?= formatRupiah($totalMasuk) ?></div>
                    </div>
                    <div class="a4-summ-box keluar">
                        <div class="label">Total Pengeluaran</div>
                        <div class="amount" style="color:#ef4444;"><?= formatRupiah($totalKeluar) ?></div>
                    </div>
                    <div class="a4-summ-box total">
                        <div class="label">Jumlah Transaksi</div>
                        <div class="amount"><?= $jumlahTx ?> transaksi</div>
                    </div>
                    <div class="a4-summ-box saldo">
                        <div class="label">SALDO AKHIR<?= $saldo < 0 ? ' (DEFISIT)' : '' ?></div>
                        <div class="amount" style="color:<?= $saldo >= 0 ? '#f5c518' : '#f87171' ?>;">
                            <?= ($saldo < 0 ? '- ' : '') . formatRupiah(abs($saldo)) ?>
                        </div>
                    </div>
                </div>
                <div class="a4-footer">
                    <div class="timestamp">Dicetak pada: <?= date('d F Y, H:i') ?><br>CatatCuan UMKM System</div>
                    <div class="a4-signature">
                        <div class="title">Pemilik Usaha / Penanggungjawab</div>
                        <div class="line">(_________________________)</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let previewVisible = false;

        function togglePreview() {
            previewVisible = !previewVisible;
            const wrap = document.getElementById('printPreview');
            const btn = document.getElementById('previewBtnText');
            wrap.classList.toggle('visible', previewVisible);
            btn.textContent = previewVisible ? 'Sembunyikan Preview' : 'Lihat Preview';
            if (previewVisible) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function cetakLaporan() {
            const a4Html = document.getElementById('a4Content').innerHTML;
            const win = window.open('', '_blank');
            win.document.write(`<!DOCTYPE html><html><head><title>Laporan - CatatCuan</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            * { font-family: 'Inter', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
            body { padding: 40px 48px; max-width: 794px; margin: 0 auto; }
            .a4-header { display: flex; justify-content: space-between; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 3px solid #1a1f3c; }
            .a4-logo { font-size: 1.4rem; font-weight: 800; color: #1a1f3c; }
            .a4-logo span { color: #f5c518; }
            .a4-report-title { text-align: right; }
            .a4-report-title h3 { font-size: 1rem; font-weight: 700; color: #1a1f3c; }
            .a4-report-title p  { color: #6b7280; font-size: 0.78rem; }
            .a4-info-box { background: #f8f9fb; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
            .a4-info-item .key { font-size: 0.68rem; color: #9ca3af; text-transform: uppercase; }
            .a4-info-item .val { font-size: 0.82rem; font-weight: 600; color: #1a1f3c; }
            .a4-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
            .a4-table th { background: #1a1f3c; color: #fff; font-size: 0.68rem; text-transform: uppercase; padding: 10px 12px; text-align: left; }
            .a4-table td { font-size: 0.75rem; color: #374151; padding: 9px 12px; border-bottom: 1px solid #f1f3f6; }
            .a4-table tbody tr:nth-child(even) td { background: #fafbfc; }
            .masuk-text  { color: #10b981; font-weight: 700; }
            .keluar-text { color: #ef4444; font-weight: 700; }
            .a4-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
            .a4-summ-box { border-radius: 8px; padding: 12px 16px; }
            .a4-summ-box.masuk  { background: #f0fdf4; }
            .a4-summ-box.keluar { background: #fef2f2; }
            .a4-summ-box.total  { background: #f0f2f8; }
            .a4-summ-box.saldo  { background: #1a1f3c; }
            .a4-summ-box .label  { font-size: 0.65rem; color: #9ca3af; text-transform: uppercase; font-weight: 600; }
            .a4-summ-box .amount { font-size: 0.95rem; font-weight: 700; color: #1a1f3c; margin-top: 4px; }
            .a4-summ-box.saldo .label  { color: rgba(255,255,255,0.6); }
            .a4-summ-box.saldo .amount { color: #f5c518; }
            .a4-footer { display: flex; justify-content: space-between; padding-top: 20px; border-top: 1px solid #e5e7eb; }
            .a4-footer .timestamp { font-size: 0.68rem; color: #9ca3af; }
            .a4-signature { text-align: center; }
            .a4-signature .title { font-size: 0.72rem; color: #6b7280; margin-bottom: 50px; }
            .a4-signature .line  { border-top: 1px solid #1a1f3c; padding-top: 6px; font-size: 0.72rem; font-weight: 600; }
            @media print { body { padding: 20px 30px; } }
        </style></head><body>${a4Html}</body></html>`);
            win.document.close();
            setTimeout(() => win.print(), 500);
        }
    </script>
</body>

</html>