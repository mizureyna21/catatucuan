<?php
require_once 'koneksi.php';
checkAuth();

$editMode = false;
$tx = null;
$idEdit = (int) ($_GET['id'] ?? 0);
$jenisDefault = $_GET['jenis'] ?? '';

if ($idEdit > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tb_transaksi WHERE id_transaksi = ? AND id_user = ?");
    $stmt->execute([$idEdit, $_SESSION['id_user']]);
    $tx = $stmt->fetch();
    if ($tx) {
        $editMode = true;
    } else {
        header('Location: transaksi.php');
        exit;
    }
}

$stmtKat = $pdo->prepare("SELECT * FROM tb_kategori WHERE id_user = ? ORDER BY jenis_arus, nama_kategori");
$stmtKat->execute([$_SESSION['id_user']]);
$kategoriAll = $stmtKat->fetchAll();

$kategoriMasuk  = array_filter($kategoriAll, fn($k) => $k['jenis_arus'] === 'masuk');
$kategoriKeluar = array_filter($kategoriAll, fn($k) => $k['jenis_arus'] === 'keluar');

$stmtPrd = $pdo->prepare("SELECT id_produk, nama_produk, harga_beli, harga_jual FROM tb_produk WHERE id_user = ? ORDER BY nama_produk");
$stmtPrd->execute([$_SESSION['id_user']]);
$produkAll = $stmtPrd->fetchAll();

$idProdukEdit = $editMode ? ($tx['id_produk'] ?? null) : null;

$errorMsg = $_GET['error'] ?? '';
$jenisInit = $editMode ? $tx['jenis'] : $jenisDefault;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editMode ? 'Edit' : 'Catat' ?> Transaksi - CatatCuan UMKM</title>
    <meta name="description" content="Catat pemasukan dan pengeluaran usaha Anda dengan mudah menggunakan CatatCuan.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            max-width: 900px;
            margin: 0 auto;
        }


        .breadcrumb-custom {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: #9ca3af;
            margin-bottom: 20px;
        }

        .breadcrumb-custom a {
            color: #3b4280;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb-custom a:hover {
            text-decoration: underline;
        }

        .breadcrumb-custom .sep {
            color: #d1d5db;
        }


        .jenis-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }

        .jenis-card {
            border: 2.5px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }

        .jenis-card:hover {
            border-color: #c4c9e0;
            transform: translateY(-2px);
        }

        .jenis-card.active-masuk {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.15);
        }

        .jenis-card.active-keluar {
            border-color: #ef4444;
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.15);
        }

        .jenis-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .jenis-icon.masuk {
            background: #dcfce7;
        }

        .jenis-icon.keluar {
            background: #fee2e2;
        }

        .jenis-title {
            font-weight: 700;
            font-size: 1rem;
            color: #1a1f3c;
        }

        .jenis-sub {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 2px;
        }

        .check-icon {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .check-icon.masuk {
            background: #10b981;
            color: #fff;
        }

        .check-icon.keluar {
            background: #ef4444;
            color: #fff;
        }

        .active-masuk .check-icon.masuk {
            opacity: 1;
        }

        .active-keluar .check-icon.keluar {
            opacity: 1;
        }


        .form-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
        }

        .form-card-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1a1f3c;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card-sub {
            color: #8a94a6;
            font-size: 0.85rem;
            margin-bottom: 28px;
        }

        .divider {
            height: 1px;
            background: #f1f3f6;
            margin: 24px 0;
        }


        .field-group {
            margin-bottom: 20px;
        }

        .field-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .field-label .req {
            color: #ef4444;
        }

        .field-input,
        .field-select,
        .field-textarea {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.875rem;
            color: #374151;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            background: #fff;
        }

        .field-input:focus,
        .field-select:focus,
        .field-textarea:focus {
            border-color: #3b4280;
            box-shadow: 0 0 0 3px rgba(59, 66, 128, 0.1);
        }

        .field-textarea {
            resize: vertical;
            min-height: 90px;
        }


        .nominal-wrap {
            position: relative;
        }

        .nominal-prefix {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            padding: 0 14px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-right: none;
            border-radius: 10px 0 0 10px;
            white-space: nowrap;
        }

        .nominal-wrap .field-input-nominal {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 0 10px 10px 0;
            padding: 11px 14px 11px 60px;
            font-size: 0.875rem;
            color: #374151;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            background: #fff;
            font-weight: 600;
        }

        .nominal-wrap .field-input-nominal:focus {
            border-color: #3b4280;
            box-shadow: 0 0 0 3px rgba(59, 66, 128, 0.1);
        }


        .preview-box {
            background: linear-gradient(135deg, #1a1f3c 0%, #2d3561 100%);
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .preview-box .label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.78rem;
            margin-bottom: 4px;
        }

        .preview-box .value {
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
        }

        .preview-nominal {
            font-size: 1.6rem;
            font-weight: 700;
            text-align: right;
        }

        .preview-nominal.masuk {
            color: #34d399;
        }

        .preview-nominal.keluar {
            color: #f87171;
        }


        .btn-row {
            display: flex;
            gap: 12px;
        }

        .btn-submit-main {
            flex: 1;
            background: linear-gradient(135deg, #3b4280, #2d3561);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 13px 24px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 6px 20px rgba(59, 66, 128, 0.3);
        }

        .btn-submit-main.masuk-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-submit-main.keluar-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }

        .btn-submit-main:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }

        .btn-reset {
            padding: 13px 24px;
            background: #f9fafb;
            color: #6b7280;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
        }

        .btn-reset:hover {
            background: #f3f4f6;
            color: #6b7280;
        }


        .error-box {
            background: #fef2f2;
            border: 1.5px solid #fca5a5;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 16px;
            color: #dc2626;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-custom" id="mainNav">
        <a href="dashboard.php" class="navbar-brand-text">
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

    <div class="page-wrapper">

        <!-- Breadcrumb -->
        <div class="breadcrumb-custom">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <span class="sep">/</span>
            <a href="transaksi.php">Transaksi</a>
            <span class="sep">/</span>
            <span><?= $editMode ? 'Edit Transaksi' : 'Catat Transaksi Baru' ?></span>
        </div>

        <!-- Page Title -->
        <div style="margin-bottom:24px;">
            <h4 style="font-weight:700;color:#1a1f3c;font-size:1.35rem;margin:0;">
                <?= $editMode ? '✏️ Edit Transaksi' : '✏️ Catat Transaksi Baru' ?>
            </h4>
            <p style="color:#8a94a6;margin:4px 0 0;font-size:0.85rem;">
                <?= $editMode ? 'Perbarui detail transaksi di bawah ini' : 'Pilih jenis transaksi lalu isi detail di bawah ini' ?>
            </p>
        </div>

        <!-- STEP 1: Pilih Jenis -->
        <div class="jenis-selector">
            <div class="jenis-card" id="cardMasuk" onclick="pilihJenis('masuk')">
                <div class="check-icon masuk"><i class="fas fa-check"></i></div>
                <div class="jenis-icon masuk">⬇️</div>
                <div>
                    <div class="jenis-title" style="color:#10b981;">Pemasukan</div>
                    <div class="jenis-sub">Penjualan, pendapatan, dsb.</div>
                </div>
            </div>
            <div class="jenis-card" id="cardKeluar" onclick="pilihJenis('keluar')">
                <div class="check-icon keluar"><i class="fas fa-check"></i></div>
                <div class="jenis-icon keluar">⬆️</div>
                <div>
                    <div class="jenis-title" style="color:#ef4444;">Pengeluaran</div>
                    <div class="jenis-sub">Biaya operasional, pembelian, dsb.</div>
                </div>
            </div>
        </div>

        <!-- Preview Box -->
        <div class="preview-box" id="previewBox">
            <div>
                <div class="label">JENIS TRANSAKSI</div>
                <div class="value" id="previewJenis">— Pilih salah satu di atas —</div>
                <div class="label" style="margin-top:10px;">KATEGORI</div>
                <div class="value" id="previewKat">—</div>
                <div class="label" style="margin-top:10px;">TANGGAL</div>
                <div class="value" id="previewTgl">—</div>
            </div>
            <div>
                <div class="label" style="text-align:right;">NOMINAL</div>
                <div class="preview-nominal" id="previewNominal" style="color:#ffffff;">Rp 0</div>
            </div>
        </div>

        <!-- FORM CARD -->
        <div class="form-card">
            <div class="form-card-title">
                <i class="fas fa-pencil-alt" style="color:#3b4280;"></i>
                Detail Transaksi
            </div>
            <div class="form-card-sub">Lengkapi informasi transaksi di bawah ini</div>

            <?php if ($errorMsg): ?>
                <div class="error-box">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="proses_transaksi.php" id="formTransaksi">
                <input type="hidden" name="aksi" value="<?= $editMode ? 'edit' : 'tambah' ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id_transaksi" value="<?= $tx['id_transaksi'] ?>">
                <?php endif; ?>
                <input type="hidden" name="jenis" id="jenisSelected" value="<?= htmlspecialchars($jenisInit) ?>">
                <input type="hidden" name="nominal_raw" id="nominalRaw" value="<?= $editMode ? $tx['nominal'] : '' ?>">
                <input type="hidden" name="id_produk" id="idProdukSelected"
                    value="<?= $editMode ? ($tx['id_produk'] ?? '') : '' ?>">

                <div class="row g-4">
                    <!-- Tanggal -->
                    <div class="col-md-6">
                        <div class="field-group">
                            <label class="field-label">
                                <i class="fas fa-calendar-alt" style="color:#3b82f6;"></i>
                                Tanggal <span class="req">*</span>
                            </label>
                            <input type="date" class="field-input" name="tanggal" id="tanggal"
                                value="<?= $editMode ? $tx['tanggal'] : date('Y-m-d') ?>" onchange="updatePreview()"
                                required>
                        </div>
                    </div>
                    <!-- Waktu -->
                    <div class="col-md-6">
                        <div class="field-group">
                            <label class="field-label">
                                <i class="fas fa-clock" style="color:#8b5cf6;"></i>
                                Waktu
                            </label>
                            <input type="time" class="field-input" name="waktu" id="waktu"
                                value="<?= $editMode ? substr($tx['waktu'], 0, 5) : date('H:i') ?>">
                        </div>
                    </div>
                    <!-- Kategori -->
                    <div class="col-md-6">
                        <div class="field-group">
                            <label class="field-label">
                                <i class="fas fa-tags" style="color:#f59e0b;"></i>
                                Kategori <span class="req">*</span>
                            </label>
                            <select class="field-select" name="id_kategori" id="kategori" onchange="updatePreview()"
                                required>
                                <option value="">-- Pilih Jenis Dulu --</option>
                            </select>
                        </div>
                    </div>
                    <!-- Nominal -->
                    <div class="col-md-6">
                        <div class="field-group">
                            <label class="field-label">
                                <i class="fas fa-money-bill-wave" style="color:#10b981;"></i>
                                Nominal <span class="req">*</span>
                            </label>
                            <div class="nominal-wrap">
                                <span class="nominal-prefix">Rp</span>
                                <input type="text" class="field-input-nominal" id="nominal" placeholder="0"
                                    value="<?= $editMode ? number_format($tx['nominal'], 0, ',', '.') : '' ?>"
                                    oninput="formatNominal(this); updatePreview()" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <!-- Produk Opsional -->
                    <div class="col-12">
                        <div class="field-group">
                            <label class="field-label">
                                <i class="fas fa-box" style="color:#8b5cf6;"></i>
                                Produk <span
                                    style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af;font-size:0.78rem;">(Opsional
                                    — untuk pelacakan margin)</span>
                            </label>
                            <select class="field-select" id="produkSelect" onchange="onProdukChange()">
                                <option value="">— Pilih Produk (Opsional) —</option>
                                <?php foreach ($produkAll as $prd): ?>
                                    <option value="<?= $prd['id_produk'] ?>" data-beli="<?= $prd['harga_beli'] ?>"
                                        data-jual="<?= $prd['harga_jual'] ?>" <?= $idProdukEdit == $prd['id_produk'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($prd['nama_produk']) ?>
                                        (Beli: <?= formatRupiah($prd['harga_beli']) ?> | Jual:
                                        <?= formatRupiah($prd['harga_jual']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="divider"></div>

                <div class="btn-row">
                    <a href="transaksi.php" class="btn-reset">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <button class="btn-submit-main" id="btnSimpan" type="submit">
                        <i class="fas fa-save"></i> <?= $editMode ? 'Perbarui Transaksi' : 'Simpan Transaksi' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        const kategoriData = {
            masuk: [
                <?php foreach ($kategoriMasuk as $k): ?>
                { val: '<?= $k['id_kategori'] ?>', label: '<?= $k['ikon'] ?>     <?= addslashes($k['nama_kategori']) ?>' },
                <?php endforeach; ?>
            ],
            keluar: [
                <?php foreach ($kategoriKeluar as $k): ?>
                { val: '<?= $k['id_kategori'] ?>', label: '<?= $k['ikon'] ?>     <?= addslashes($k['nama_kategori']) ?>' },
                <?php endforeach; ?>
            ]
        };

        function onProdukChange() {
            const sel = document.getElementById('produkSelect');
            const opt = sel.options[sel.selectedIndex];
            const jenis = document.getElementById('jenisSelected').value;
            document.getElementById('idProdukSelected').value = sel.value;
            if (!sel.value) return;
            const beli = parseInt(opt.dataset.beli || '0');
            const jual = parseInt(opt.dataset.jual || '0');
            const harga = jenis === 'keluar' ? beli : jual;
            if (harga > 0) {
                document.getElementById('nominal').value = harga.toLocaleString('id-ID');
                document.getElementById('nominalRaw').value = harga;
                updatePreview();
            }
        }


        const editKatId = '<?= $editMode ? $tx['id_kategori'] : '' ?>';
        const editJenis = '<?= $jenisInit ?>';

        function pilihJenis(jenis) {
            document.getElementById('jenisSelected').value = jenis;
            document.getElementById('cardMasuk').className = 'jenis-card' + (jenis === 'masuk' ? ' active-masuk' : '');
            document.getElementById('cardKeluar').className = 'jenis-card' + (jenis === 'keluar' ? ' active-keluar' : '');


            const sel = document.getElementById('kategori');
            sel.innerHTML = '<option value="">-- Pilih Kategori --</option>';
            (kategoriData[jenis] || []).forEach(k => {
                const opt = document.createElement('option');
                opt.value = k.val; opt.textContent = k.label;
                if (k.val === editKatId) opt.selected = true;
                sel.appendChild(opt);
            });


            const btn = document.getElementById('btnSimpan');
            btn.className = 'btn-submit-main ' + (jenis === 'masuk' ? 'masuk-btn' : 'keluar-btn');

            updatePreview();
        }

        function formatNominal(el) {
            let val = el.value.replace(/\D/g, '');
            el.value = val ? parseInt(val).toLocaleString('id-ID') : '';
            document.getElementById('nominalRaw').value = val;
        }

        function getRawNominal() {
            return parseInt(document.getElementById('nominal').value.replace(/\./g, '') || '0');
        }

        function updatePreview() {
            const jenis = document.getElementById('jenisSelected').value;
            const tgl = document.getElementById('tanggal').value;
            const katEl = document.getElementById('kategori');
            const katText = katEl.options[katEl.selectedIndex]?.text || '—';
            const nominal = getRawNominal();

            document.getElementById('previewJenis').textContent = jenis === 'masuk' ? '⬇ Pemasukan' : jenis === 'keluar' ? '⬆ Pengeluaran' : '— Pilih salah satu di atas —';
            document.getElementById('previewKat').textContent = katEl.value ? katText : '—';
            document.getElementById('previewTgl').textContent = tgl ? new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '—';

            const nomEl = document.getElementById('previewNominal');
            nomEl.textContent = 'Rp ' + nominal.toLocaleString('id-ID');
            nomEl.className = 'preview-nominal ' + (jenis === 'masuk' ? 'masuk' : jenis === 'keluar' ? 'keluar' : '');
        }


        document.getElementById('formTransaksi').addEventListener('submit', function (e) {
            const raw = document.getElementById('nominal').value.replace(/\./g, '');
            document.getElementById('nominalRaw').value = raw;
        });


        if (editJenis) pilihJenis(editJenis);
        updatePreview();
    </script>
</body>

</html>