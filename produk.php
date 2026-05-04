<?php
require_once 'koneksi.php';
checkAuth();

$stmtPrd = $pdo->prepare(
    "SELECT p.*, COUNT(t.id_transaksi) AS jml_transaksi
     FROM tb_produk p
     LEFT JOIN tb_transaksi t ON p.id_produk = t.id_produk AND t.id_user = ?
     WHERE p.id_user = ?
     GROUP BY p.id_produk
     ORDER BY p.nama_produk"
);
$stmtPrd->execute([$_SESSION['id_user'], $_SESSION['id_user']]);
$produkList = $stmtPrd->fetchAll();

$totalProduk = count($produkList);
$sumMargin = 0;
$marginMax = 0;
foreach ($produkList as $p) {
    $m = $p['harga_jual'] - $p['harga_beli'];
    $sumMargin += $m;
    if ($m > $marginMax)
        $marginMax = $m;
}
$rataMargin = $totalProduk > 0 ? (int) ($sumMargin / $totalProduk) : 0;

$alertMsg = $_GET['msg'] ?? '';
$alertStatus = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - CatatCuan UMKM</title>
    <meta name="description" content="Kelola data produk dan pantau margin keuntungan usaha Anda dengan CatatCuan.">
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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
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

        .stat-mini {
            background: #fff;
            border-radius: 14px;
            padding: 18px 22px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s;
        }

        .stat-mini:hover {
            transform: translateY(-3px);
        }

        .stat-mini-icon {
            width: 44px;
            height: 44px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .stat-mini-icon.blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .stat-mini-icon.green {
            background: #f0fdf4;
            color: #10b981;
        }

        .stat-mini-icon.yellow {
            background: #fffbeb;
            color: #f59e0b;
        }

        .stat-mini-label {
            font-size: 0.78rem;
            color: #8a94a6;
            font-weight: 500;
        }

        .stat-mini-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1f3c;
            line-height: 1.2;
        }

        .stat-mini-value.small {
            font-size: 1rem;
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

        .btn-action.primary {
            background: linear-gradient(135deg, #3b4280, #2d3561);
            color: #fff;
            box-shadow: 0 4px 12px rgba(59, 66, 128, 0.3);
        }

        .btn-action.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(59, 66, 128, 0.4);
            color: #fff;
        }

        .search-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-wrap {
            position: relative;
            flex: 1;
        }

        .search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .search-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 14px 9px 38px;
            font-size: 0.875rem;
            color: #374151;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .search-input:focus {
            border-color: #3b4280;
            box-shadow: 0 0 0 3px rgba(59, 66, 128, 0.1);
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

        .margin-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
        }

        .margin-badge.positif {
            background: #f0fdf4;
            color: #10b981;
        }

        .margin-badge.negatif {
            background: #fef2f2;
            color: #ef4444;
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

        .modal-header-custom {
            background: linear-gradient(135deg, #1a1f3c 0%, #2d3561 100%);
            border-radius: 16px 16px 0 0;
            padding: 20px 24px;
        }

        .modal-header-custom .modal-title {
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
        }

        .modal-header-custom .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.7;
        }

        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #f1f3f6;
        }

        .form-label-custom {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 7px;
            display: block;
        }

        .form-control-custom {
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

        .form-control-custom:focus {
            border-color: #3b4280;
            box-shadow: 0 0 0 3px rgba(59, 66, 128, 0.1);
        }

        .input-rp-wrap {
            position: relative;
        }

        .input-rp-prefix {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            padding: 0 12px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #6b7280;
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-rp-wrap .form-control-custom {
            border-radius: 0 10px 10px 0;
            padding-left: 44px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #3b4280, #2d3561);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 24px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-cancel {
            background: #f9fafb;
            color: #6b7280;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 24px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #f3f4f6;
        }

        .empty-state {
            text-align: center;
            padding: 60px 24px;
        }

        .empty-state i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 16px;
            display: block;
        }

        .empty-state h6 {
            color: #9ca3af;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .empty-state p {
            color: #d1d5db;
            font-size: 0.85rem;
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
            <a href="produk.php" class="active"><i class="fas fa-box"></i> Produk</a>
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
            <div>
                <h4>📦 Manajemen Produk</h4>
                <p>Kelola data produk dan pantau margin keuntungan usaha Anda</p>
            </div>
            <button class="btn-action primary" onclick="openModal()" id="btnTambah">
                <i class="fas fa-plus"></i> Tambah Produk
            </button>
        </div>

        <!-- Stat Mini Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-mini">
                    <div class="stat-mini-icon blue"><i class="fas fa-box"></i></div>
                    <div>
                        <div class="stat-mini-label">Total Produk</div>
                        <div class="stat-mini-value"><?= $totalProduk ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini">
                    <div class="stat-mini-icon green"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <div class="stat-mini-label">Rata-rata Margin</div>
                        <div class="stat-mini-value small"><?= formatRupiah($rataMargin) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini">
                    <div class="stat-mini-icon yellow"><i class="fas fa-trophy"></i></div>
                    <div>
                        <div class="stat-mini-label">Margin Tertinggi</div>
                        <div class="stat-mini-value small"><?= formatRupiah($marginMax) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="search-card">
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Cari nama produk..."
                    oninput="filterTable()">
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-card-header">
                <div>
                    <h6>Daftar Produk</h6>
                    <p id="tableSubtitle">Menampilkan semua produk</p>
                </div>
                <span class="count-badge" id="countBadge"><?= $totalProduk ?> Produk</span>
            </div>
            <div class="table-responsive">
                <table class="table" id="produkTable">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Nama Produk</th>
                            <th>Harga Beli</th>
                            <th>Harga Jual</th>
                            <th>Margin (Keuntungan)</th>
                            <th>Jml. Transaksi</th>
                            <th style="width:140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="produkBody">
                        <?php if (empty($produkList)): ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding:60px;color:#9ca3af;">
                                    <i class="fas fa-box"
                                        style="font-size:2.5rem;display:block;margin-bottom:12px;color:#d1d5db;"></i>
                                    Belum ada produk. Klik "+ Tambah Produk" untuk mulai.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($produkList as $i => $p): ?>
                                <?php $margin = $p['harga_jual'] - $p['harga_beli']; ?>
                                <tr data-nama="<?= strtolower(htmlspecialchars($p['nama_produk'])) ?>">
                                    <td style="color:#b0b8c8;font-weight:600;"><?= $i + 1 ?></td>
                                    <td><span
                                            style="font-weight:600;color:#1a1f3c;"><?= htmlspecialchars($p['nama_produk']) ?></span>
                                    </td>
                                    <td><?= formatRupiah($p['harga_beli']) ?></td>
                                    <td><?= formatRupiah($p['harga_jual']) ?></td>
                                    <td>
                                        <span class="margin-badge <?= $margin >= 0 ? 'positif' : 'negatif' ?>">
                                            <i
                                                class="fas <?= $margin >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?>"></i>
                                            <?= ($margin >= 0 ? '+' : '') . formatRupiah(abs($margin)) ?>
                                        </span>
                                    </td>
                                    <td><span style="font-weight:600;color:#3b4280;"><?= $p['jml_transaksi'] ?> tx</span></td>
                                    <td>
                                        <button class="btn-tbl edit me-1"
                                            onclick="editProduk(<?= $p['id_produk'] ?>, '<?= htmlspecialchars($p['nama_produk'], ENT_QUOTES) ?>', <?= $p['harga_beli'] ?>, <?= $p['harga_jual'] ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($p['jml_transaksi'] == 0): ?>
                                            <button class="btn-tbl hapus"
                                                onclick="hapusProduk(<?= $p['id_produk'] ?>, '<?= htmlspecialchars($p['nama_produk'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-tbl hapus" style="opacity:0.4;cursor:not-allowed;" disabled
                                                title="Tidak bisa dihapus, sudah dipakai di <?= $p['jml_transaksi'] ?> transaksi">
                                                <i class="fas fa-lock"></i> Terkunci
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="emptyState" class="empty-state" style="display:none;">
                <i class="fas fa-box"></i>
                <h6>Tidak ada produk ditemukan</h6>
                <p>Coba ubah kata kunci pencarian Anda</p>
            </div>
        </div>

    </div>

    <!-- ===== MODAL TAMBAH/EDIT PRODUK ===== -->
    <div class="modal fade" id="modalProduk" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="modal-title" id="modalLabel"><i class="fas fa-box me-2"></i>Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="proses_produk.php">
                    <div class="modal-body">
                        <input type="hidden" name="aksi" id="formAksi" value="tambah">
                        <input type="hidden" name="id_produk" id="formIdProduk" value="">
                        <div class="mb-4">
                            <label class="form-label-custom">Nama Produk *</label>
                            <input type="text" class="form-control-custom" name="nama_produk" id="namaProduk"
                                placeholder="Contoh: Tas Kulit Premium" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-custom">Harga Beli (Modal) *</label>
                            <div class="input-rp-wrap">
                                <span class="input-rp-prefix">Rp</span>
                                <input type="text" class="form-control-custom" id="hargaBeliDisplay" placeholder="0"
                                    oninput="formatHarga(this, 'hargaBeli')" autocomplete="off">
                            </div>
                            <input type="hidden" name="harga_beli" id="hargaBeli" value="0">
                        </div>
                        <div class="mb-2">
                            <label class="form-label-custom">Harga Jual *</label>
                            <div class="input-rp-wrap">
                                <span class="input-rp-prefix">Rp</span>
                                <input type="text" class="form-control-custom" id="hargaJualDisplay" placeholder="0"
                                    oninput="formatHarga(this, 'hargaJual')" autocomplete="off">
                            </div>
                            <input type="hidden" name="harga_jual" id="hargaJual" value="0">
                        </div>
                        <!-- Margin Preview -->
                        <div id="marginPreview"
                            style="background:#f0f2f8;border-radius:10px;padding:12px 16px;margin-top:16px;display:none;">
                            <div
                                style="font-size:0.72rem;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
                                Estimasi Margin
                            </div>
                            <div id="marginPreviewVal" style="font-size:1.1rem;font-weight:700;margin-top:4px;"></div>
                        </div>
                    </div>
                    <div class="modal-footer gap-2">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== MODAL KONFIRMASI HAPUS ===== -->
    <div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body p-4 text-center">
                    <div
                        style="width:60px;height:60px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="fas fa-trash" style="color:#ef4444;font-size:1.4rem;"></i>
                    </div>
                    <h6 style="font-weight:700;color:#1a1f3c;margin-bottom:8px;">Hapus Produk?</h6>
                    <p style="color:#8a94a6;font-size:0.875rem;margin-bottom:24px;">
                        Tindakan ini tidak dapat dibatalkan.<br>Produk "<strong id="namaHapus"></strong>" akan dihapus.
                    </p>
                    <form method="POST" action="proses_produk.php">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id_produk" id="hapusId">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn-cancel w-100" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn-submit w-100"
                                style="background:linear-gradient(135deg,#ef4444,#dc2626);">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModal() {
            document.getElementById('formAksi').value = 'tambah';
            document.getElementById('formIdProduk').value = '';
            document.getElementById('modalLabel').innerHTML = '<i class="fas fa-plus me-2"></i>Tambah Produk';
            document.getElementById('namaProduk').value = '';
            document.getElementById('hargaBeliDisplay').value = '';
            document.getElementById('hargaBeli').value = '0';
            document.getElementById('hargaJualDisplay').value = '';
            document.getElementById('hargaJual').value = '0';
            document.getElementById('marginPreview').style.display = 'none';
            new bootstrap.Modal(document.getElementById('modalProduk')).show();
        }

        function editProduk(id, nama, beli, jual) {
            document.getElementById('formAksi').value = 'edit';
            document.getElementById('formIdProduk').value = id;
            document.getElementById('modalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Produk';
            document.getElementById('namaProduk').value = nama;
            document.getElementById('hargaBeliDisplay').value = parseInt(beli).toLocaleString('id-ID');
            document.getElementById('hargaBeli').value = beli;
            document.getElementById('hargaJualDisplay').value = parseInt(jual).toLocaleString('id-ID');
            document.getElementById('hargaJual').value = jual;
            updateMarginPreview();
            new bootstrap.Modal(document.getElementById('modalProduk')).show();
        }

        function hapusProduk(id, nama) {
            document.getElementById('hapusId').value = id;
            document.getElementById('namaHapus').textContent = nama;
            new bootstrap.Modal(document.getElementById('modalHapus')).show();
        }

        function formatHarga(el, targetId) {
            let raw = el.value.replace(/\D/g, '');
            el.value = raw ? parseInt(raw).toLocaleString('id-ID') : '';
            document.getElementById(targetId).value = raw || '0';
            updateMarginPreview();
        }

        function updateMarginPreview() {
            const beli = parseInt(document.getElementById('hargaBeli').value || '0');
            const jual = parseInt(document.getElementById('hargaJual').value || '0');
            const wrap = document.getElementById('marginPreview');
            const valEl = document.getElementById('marginPreviewVal');
            if (beli > 0 || jual > 0) {
                const margin = jual - beli;
                wrap.style.display = 'block';
                valEl.style.color = margin >= 0 ? '#10b981' : '#ef4444';
                const pct = beli > 0 ? ' (' + (margin / beli * 100).toFixed(1) + '%)' : '';
                valEl.textContent = (margin >= 0 ? '+ ' : '- ') + 'Rp ' +
                    Math.abs(margin).toLocaleString('id-ID') + pct;
            } else {
                wrap.style.display = 'none';
            }
        }

        function filterTable() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#produkBody tr[data-nama]');
            let visible = 0;
            rows.forEach(row => {
                const match = (row.dataset.nama || '').includes(q);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            document.getElementById('countBadge').textContent = visible + ' Produk';
            document.getElementById('emptyState').style.display = visible === 0 ? 'block' : 'none';
            document.querySelector('.table-responsive').style.display = visible === 0 ? 'none' : 'block';
        }

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
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>

</html>