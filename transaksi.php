<?php
require_once 'koneksi.php';
checkAuth();

$search = trim($_GET['search'] ?? '');
$jenis  = $_GET['jenis'] ?? 'all';
$kat    = (int) ($_GET['kat'] ?? 0);
$page   = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where  = "WHERE t.id_user = :uid";
$params = [':uid' => $_SESSION['id_user']];

if ($search !== '') {
    $where .= " AND (t.keterangan LIKE :search OR t.catatan LIKE :search2)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
}
if (in_array($jenis, ['masuk', 'keluar'])) {
    $where .= " AND t.jenis = :jenis";
    $params[':jenis'] = $jenis;
}
if ($kat > 0) {
    $where .= " AND t.id_kategori = :kat";
    $params[':kat'] = $kat;
}

$countSql = "SELECT COUNT(*) FROM tb_transaksi t $where";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRows = (int) $stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$sql = "SELECT t.*, k.nama_kategori, k.ikon, p.nama_produk AS nama_produk_linked FROM tb_transaksi t
        JOIN tb_kategori k ON t.id_kategori = k.id_kategori
        LEFT JOIN tb_produk p ON t.id_produk = p.id_produk
        $where
        ORDER BY t.tanggal DESC, t.waktu DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val)
    $stmt->bindValue($key, $val);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transaksiList = $stmt->fetchAll();

$stmtKat = $pdo->prepare("SELECT id_kategori, nama_kategori, jenis_arus FROM tb_kategori WHERE id_user = ? ORDER BY jenis_arus, nama_kategori");
$stmtKat->execute([$_SESSION['id_user']]);
$kategoriFilter = $stmtKat->fetchAll();

$sqlSum = "SELECT 
    COALESCE(SUM(CASE WHEN t.jenis='masuk' THEN t.nominal ELSE 0 END), 0) AS total_masuk,
    COALESCE(SUM(CASE WHEN t.jenis='keluar' THEN t.nominal ELSE 0 END), 0) AS total_keluar
    FROM tb_transaksi t $where";
$stmtSum = $pdo->prepare($sqlSum);
$stmtSum->execute($params);
$summary = $stmtSum->fetch();

$alertMsg = $_GET['msg'] ?? '';
$alertStatus = $_GET['status'] ?? '';

function buildQuery(array $overrides = []): string
{
    global $search, $jenis, $kat, $page;
    $base = ['search' => $search, 'jenis' => $jenis, 'kat' => $kat, 'page' => $page];
    $merged = array_merge($base, $overrides);
    $filtered = array_filter($merged, fn($v) => $v !== '' && $v !== 'all' && $v !== 0 && $v !== '0');
    return http_build_query($filtered);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - CatatCuan UMKM</title>
    <meta name="description" content="Kelola semua riwayat pemasukan dan pengeluaran usaha Anda di CatatCuan.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="logoc.png">
    <style>
        * {
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        html, body {
            overflow-x: hidden;
            max-width: 100%;
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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
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


        .filter-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .filter-card label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }

        .form-control,
        .form-select {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.875rem;
            padding: 9px 14px;
            color: #374151;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3b4280;
            box-shadow: 0 0 0 3px rgba(59, 66, 128, 0.1);
            outline: none;
        }

        .input-group-text {
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #9ca3af;
            font-size: 0.85rem;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .btn-filter {
            background: linear-gradient(135deg, #3b4280, #2d3561);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 20px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            width: 100%;
            justify-content: center;
        }

        .btn-filter:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-reset-filter {
            background: #f9fafb;
            color: #6b7280;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            width: 100%;
            justify-content: center;
            text-decoration: none;
        }

        .btn-reset-filter:hover {
            background: #f3f4f6;
            color: #6b7280;
        }


        .summary-row {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .summ-pill {
            background: #fff;
            border-radius: 10px;
            padding: 12px 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.875rem;
        }

        .summ-pill .lbl {
            color: #8a94a6;
            font-size: 0.75rem;
        }

        .summ-pill .val {
            font-weight: 700;
        }

        .summ-pill .val.masuk {
            color: #10b981;
        }

        .summ-pill .val.keluar {
            color: #ef4444;
        }

        .summ-pill .val.saldo {
            color: #3b4280;
        }


        .table-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table-card-header {
            padding: 20px 24px 18px;
            border-bottom: 1px solid #f1f3f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-card-header h6 {
            font-weight: 700;
            color: #1a1f3c;
            margin: 0;
            font-size: 1rem;
        }

        .table-card-header p {
            color: #8a94a6;
            margin: 3px 0 0;
            font-size: 0.78rem;
        }

        .count-badge {
            background: #f0f2f8;
            color: #3b4280;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: #fafbfc;
            font-size: 0.75rem;
            color: #8a94a6;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 13px 24px;
            border-bottom: 1px solid #f1f3f6;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 14px 24px;
            font-size: 0.875rem;
            color: #374151;
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
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 500;
        }

        .nominal-masuk {
            color: #10b981;
            font-weight: 700;
        }

        .nominal-keluar {
            color: #ef4444;
            font-weight: 700;
        }

        .btn-tbl {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-tbl.edit {
            background: #eff6ff;
            color: #3b82f6;
        }

        .btn-tbl.edit:hover {
            background: #dbeafe;
        }

        .btn-tbl.hapus {
            background: #fef2f2;
            color: #ef4444;
        }

        .btn-tbl.hapus:hover {
            background: #fee2e2;
        }


        .pagination-wrapper {
            padding: 16px 24px;
            border-top: 1px solid #f1f3f6;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .page-btn {
            border: 1.5px solid #e5e7eb;
            color: #3b4280;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .page-btn:hover {
            background: #f0f2f8;
            color: #3b4280;
        }

        .page-btn.active {
            background: linear-gradient(135deg, #3b4280, #2d3561);
            border-color: transparent;
            color: #fff;
        }

        .page-btn.disabled {
            color: #d1d5db;
            border-color: #f1f3f6;
            pointer-events: none;
        }


        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
        }

        .toast-custom {
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
            animation: slideUp 0.3s ease;
            min-width: 260px;
        }

        .toast-custom.success i {
            color: #10b981;
        }

        .toast-custom.error i {
            color: #ef4444;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =========================================
           RESPONSIVE — MOBILE (≤768px)
        ========================================= */
        @media (max-width: 768px) {
            .page-wrapper { padding: 16px; }

            /* Page header stack vertikal */
            .page-header { flex-direction: column; gap: 12px; align-items: flex-start; }
            .page-header > div:last-child { display: flex; flex-direction: column; gap: 8px; width: 100%; }
            .btn-action { width: 100%; justify-content: center; padding: 12px 16px; font-size: 0.9rem; }

            /* Filter card */
            .filter-card { padding: 14px; }

            /* Summary pills — 2 kolom */
            .summary-row { gap: 8px; }
            .summ-pill { flex: 1 1 calc(50% - 4px); min-width: 0; padding: 10px 12px; }
            .summ-pill .val { font-size: 0.82rem; }

            /* Table header */
            .table-card-header { flex-direction: column; align-items: flex-start; gap: 6px; padding: 14px 16px; }

            /* Sembunyikan tabel biasa */
            .table-responsive { display: none; }

            /* Mobile card list */
            .mobile-tx-list { display: block; }
            .mobile-tx-card {
                border-bottom: 1px solid #f1f3f6;
                padding: 14px 16px;
            }
            .mobile-tx-card:last-child { border-bottom: none; }
            .mobile-tx-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 6px;
            }
            .mobile-tx-keterangan { font-size: 0.9rem; font-weight: 600; color: #1a1f3c; flex: 1; word-break: break-word; }
            .mobile-tx-nominal { font-size: 0.95rem; font-weight: 700; white-space: nowrap; }
            .mobile-tx-meta {
                display: flex; gap: 8px; flex-wrap: wrap;
                font-size: 0.75rem; color: #8a94a6;
                align-items: center; margin-bottom: 8px;
            }
            .mobile-tx-actions { display: flex; gap: 6px; }
            .mobile-tx-actions .btn-tbl { flex: 1; justify-content: center; padding: 7px 10px; font-size: 0.8rem; }

            /* Pagination */
            .pagination-wrapper { padding: 12px 16px; gap: 4px; }
            .page-btn { padding: 6px 10px; font-size: 0.8rem; }

            /* Toast */
            .toast-container { bottom: 16px; right: 16px; left: 16px; }
            .toast-custom { min-width: 0; width: 100%; }
        }

        /* Desktop — sembunyikan mobile card */
        @media (min-width: 769px) { .mobile-tx-list { display: none; } }

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
            <a href="transaksi.php" class="active"><i class="fas fa-exchange-alt"></i> Transaksi</a>
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
            <div>
                <h4>💳 Riwayat Transaksi</h4>
                <p>Kelola semua pemasukan dan pengeluaran usaha Anda</p>
            </div>
            <div style="display:flex;gap:10px;">
                <a href="form_transaksi.php?jenis=masuk" class="btn-action success">
                    <i class="fas fa-plus-circle"></i> Tambah Pemasukan
                </a>
                <a href="form_transaksi.php?jenis=keluar" class="btn-action danger">
                    <i class="fas fa-minus-circle"></i> Tambah Pengeluaran
                </a>
            </div>
        </div>

        <!-- ===== FILTER CARD ===== -->
        <div class="filter-card">
            <form method="GET" action="transaksi.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label>Cari Transaksi</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Cari keterangan...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label>Jenis</label>
                    <select class="form-select" name="jenis">
                        <option value="all" <?= $jenis === 'all' ? 'selected' : '' ?>>Semua</option>
                        <option value="masuk" <?= $jenis === 'masuk' ? 'selected' : '' ?>>Pemasukan</option>
                        <option value="keluar" <?= $jenis === 'keluar' ? 'selected' : '' ?>>Pengeluaran</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Kategori</label>
                    <select class="form-select" name="kat">
                        <option value="0">Semua Kategori</option>
                        <?php foreach ($kategoriFilter as $k): ?>
                            <option value="<?= $k['id_kategori'] ?>" <?= $kat === $k['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kategori']) ?> (<?= $k['jenis_arus'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="transaksi.php" class="btn-reset-filter">
                        <i class="fas fa-times"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- SUMMARY ROW -->
        <div class="summary-row">
            <div class="summ-pill">
                <i class="fas fa-receipt" style="color:#3b4280;"></i>
                <div>
                    <div class="lbl">Total Transaksi</div>
                    <div class="val saldo"><?= $totalRows ?></div>
                </div>
            </div>
            <div class="summ-pill">
                <i class="fas fa-arrow-down" style="color:#10b981;"></i>
                <div>
                    <div class="lbl">Total Pemasukan</div>
                    <div class="val masuk"><?= formatRupiah((int) $summary['total_masuk']) ?></div>
                </div>
            </div>
            <div class="summ-pill">
                <i class="fas fa-arrow-up" style="color:#ef4444;"></i>
                <div>
                    <div class="lbl">Total Pengeluaran</div>
                    <div class="val keluar"><?= formatRupiah((int) $summary['total_keluar']) ?></div>
                </div>
            </div>
            <div class="summ-pill">
                <i class="fas fa-wallet" style="color:#3b4280;"></i>
                <div>
                    <div class="lbl">Selisih</div>
                    <div class="val saldo">
                        <?= formatRupiah((int) $summary['total_masuk'] - (int) $summary['total_keluar']) ?></div>
                </div>
            </div>
        </div>

        <!-- ===== TABEL TRANSAKSI ===== -->
        <div class="table-card">
            <div class="table-card-header">
                <div>
                    <h6>Daftar Transaksi</h6>
                    <p>Menampilkan <?= $totalRows ?>
                        transaksi<?= $search ? ' dengan kata kunci "' . htmlspecialchars($search) . '"' : '' ?></p>
                </div>
                <span class="count-badge"><?= $totalRows ?> Transaksi</span>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Kategori</th>
                            <th>Jenis</th>
                            <th>Nominal</th>
                            <th style="width:140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksiList)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center;padding:60px;color:#9ca3af;">
                                    <i class="fas fa-inbox"
                                        style="font-size:2.5rem;display:block;margin-bottom:12px;color:#d1d5db;"></i>
                                    Tidak ada transaksi ditemukan.<br>
                                    <a href="form_transaksi.php" style="color:#3b4280;font-weight:600;">Catat transaksi
                                        pertama →</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transaksiList as $i => $tx): ?>
                                <tr>
                                    <td style="color:#b0b8c8;font-weight:600;"><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <span style="font-weight:500;"><?= formatTanggal($tx['tanggal']) ?></span><br>
                                        <span style="font-size:0.75rem;color:#9ca3af;"><?= substr($tx['waktu'], 0, 5) ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($tx['keterangan']) ?>
                                        <?php if (!empty($tx['nama_produk_linked'])): ?>
                                            <br><span
                                                style="display:inline-flex;align-items:center;gap:4px;background:#f5f3ff;color:#7c3aed;font-size:0.72rem;font-weight:600;padding:2px 9px;border-radius:20px;margin-top:3px;">
                                                <i class="fas fa-box" style="font-size:0.65rem;"></i>
                                                <?= htmlspecialchars($tx['nama_produk_linked']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($tx['catatan']): ?>
                                            <br><span style="font-size:0.75rem;color:#9ca3af;"
                                                title="<?= htmlspecialchars($tx['catatan']) ?>">
                                                <i class="fas fa-sticky-note"></i>
                                                <?= mb_strimwidth(htmlspecialchars($tx['catatan']), 0, 40, '...') ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="kat-badge"><?= $tx['ikon'] ?>
                                            <?= htmlspecialchars($tx['nama_kategori']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($tx['jenis'] === 'masuk'): ?>
                                            <span class="tx-badge masuk"><i class="fas fa-arrow-down"></i> Masuk</span>
                                        <?php else: ?>
                                            <span class="tx-badge keluar"><i class="fas fa-arrow-up"></i> Keluar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="nominal-<?= $tx['jenis'] ?>">
                                        <?= $tx['jenis'] === 'masuk' ? '+' : '-' ?>         <?= formatRupiah($tx['nominal']) ?>
                                    </td>
                                    <td>
                                        <a href="form_transaksi.php?id=<?= $tx['id_transaksi'] ?>" class="btn-tbl edit me-1">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="hapus_transaksi.php?id=<?= $tx['id_transaksi'] ?>" class="btn-tbl hapus"
                                            onclick="return confirm('Hapus transaksi ini? Tindakan tidak dapat dibatalkan.')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- MOBILE CARD LIST -->
            <div class="mobile-tx-list">
                <?php if (empty($transaksiList)): ?>
                    <div style="text-align:center;padding:50px 20px;color:#9ca3af;">
                        <i class="fas fa-inbox" style="font-size:2.5rem;display:block;margin-bottom:12px;color:#d1d5db;"></i>
                        Tidak ada transaksi ditemukan.<br>
                        <a href="form_transaksi.php" style="color:#3b4280;font-weight:600;">Catat transaksi pertama →</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($transaksiList as $i => $tx): ?>
                        <div class="mobile-tx-card">
                            <div class="mobile-tx-top">
                                <div class="mobile-tx-keterangan">
                                    <?= htmlspecialchars($tx['keterangan']) ?>
                                    <?php if (!empty($tx['nama_produk_linked'])): ?>
                                        <br><span style="display:inline-flex;align-items:center;gap:4px;background:#f5f3ff;color:#7c3aed;font-size:0.72rem;font-weight:600;padding:2px 9px;border-radius:20px;margin-top:3px;">
                                            <i class="fas fa-box" style="font-size:0.65rem;"></i>
                                            <?= htmlspecialchars($tx['nama_produk_linked']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="mobile-tx-nominal <?= $tx['jenis'] === 'masuk' ? 'nominal-masuk' : 'nominal-keluar' ?>">
                                    <?= $tx['jenis'] === 'masuk' ? '+' : '-' ?><?= formatRupiah($tx['nominal']) ?>
                                </div>
                            </div>
                            <div class="mobile-tx-meta">
                                <span><?= formatTanggal($tx['tanggal']) ?> <?= substr($tx['waktu'], 0, 5) ?></span>
                                <span class="kat-badge"><?= $tx['ikon'] ?> <?= htmlspecialchars($tx['nama_kategori']) ?></span>
                                <?php if ($tx['jenis'] === 'masuk'): ?>
                                    <span class="tx-badge masuk"><i class="fas fa-arrow-down"></i> Masuk</span>
                                <?php else: ?>
                                    <span class="tx-badge keluar"><i class="fas fa-arrow-up"></i> Keluar</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($tx['catatan']): ?>
                                <div style="font-size:0.75rem;color:#9ca3af;margin-bottom:8px;">
                                    <i class="fas fa-sticky-note"></i>
                                    <?= mb_strimwidth(htmlspecialchars($tx['catatan']), 0, 60, '...') ?>
                                </div>
                            <?php endif; ?>
                            <div class="mobile-tx-actions">
                                <a href="form_transaksi.php?id=<?= $tx['id_transaksi'] ?>" class="btn-tbl edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="hapus_transaksi.php?id=<?= $tx['id_transaksi'] ?>" class="btn-tbl hapus"
                                    onclick="return confirm('Hapus transaksi ini? Tindakan tidak dapat dibatalkan.')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <?php if ($page > 1): ?>
                        <a class="page-btn" href="?<?= buildQuery(['page' => $page - 1]) ?>">« Sebelumnya</a>
                    <?php else: ?>
                        <span class="page-btn disabled">« Sebelumnya</span>
                    <?php endif; ?>

                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <a class="page-btn <?= $p === $page ? 'active' : '' ?>"
                            href="?<?= buildQuery(['page' => $p]) ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a class="page-btn" href="?<?= buildQuery(['page' => $page + 1]) ?>">Selanjutnya »</a>
                    <?php else: ?>
                        <span class="page-btn disabled">Selanjutnya »</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- TOAST CONTAINER -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($alertMsg): ?>
            showToast('<?= $alertStatus === 'success' ? 'success' : 'error' ?>', '<?= htmlspecialchars($alertMsg, ENT_QUOTES) ?>');
        <?php endif; ?>

        function showToast(type, msg) {
            const container = document.getElementById('toastContainer');
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
            const toast = document.createElement('div');
            toast.className = `toast-custom ${type}`;
            toast.innerHTML = `<i class="fas ${icon}"></i> ${msg}`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>

</html>