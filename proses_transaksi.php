<?php
require_once 'koneksi.php';
checkAuth();

$aksi = $_POST['aksi'] ?? '';

if ($aksi === 'tambah') {
    $jenis       = $_POST['jenis']        ?? '';
    $tanggal     = $_POST['tanggal']      ?? '';
    $waktu       = $_POST['waktu']        ?: date('H:i:s');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $nominal     = (int)str_replace('.', '', $_POST['nominal_raw'] ?? '0');
    $keterangan  = '';
    $catatan     = '';
    $id_produk_raw = trim($_POST['id_produk'] ?? '');
    $id_produk   = ($id_produk_raw !== '' && (int)$id_produk_raw > 0) ? (int)$id_produk_raw : null;

    if (!in_array($jenis, ['masuk', 'keluar']))
        header('Location: form_transaksi.php?error=' . urlencode('Jenis transaksi tidak valid!')) and exit;
    if (!$tanggal)
        header('Location: form_transaksi.php?error=' . urlencode('Tanggal tidak boleh kosong!')) and exit;
    if (!$id_kategori)
        header('Location: form_transaksi.php?error=' . urlencode('Pilih kategori transaksi!')) and exit;
    if ($nominal <= 0)
        header('Location: form_transaksi.php?error=' . urlencode('Nominal harus lebih dari Rp 0!')) and exit;

    $stmt = $pdo->prepare("
        INSERT INTO tb_transaksi (id_user, id_kategori, id_produk, jenis, tanggal, waktu, nominal, keterangan, catatan)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$_SESSION['id_user'], $id_kategori, $id_produk, $jenis, $tanggal, $waktu, $nominal, $keterangan, $catatan]);

    header('Location: transaksi.php?status=success&msg=' . urlencode('Transaksi berhasil dicatat! 🎉'));
    exit;

} elseif ($aksi === 'edit') {
    $id_transaksi  = (int)($_POST['id_transaksi'] ?? 0);
    $jenis         = $_POST['jenis']         ?? '';
    $tanggal       = $_POST['tanggal']       ?? '';
    $waktu         = $_POST['waktu']         ?: date('H:i:s');
    $id_kategori   = (int)($_POST['id_kategori'] ?? 0);
    $nominal       = (int)str_replace('.', '', $_POST['nominal_raw'] ?? '0');
    $keterangan    = '';
    $catatan       = '';
    $id_produk_raw = trim($_POST['id_produk']  ?? '');
    $id_produk     = ($id_produk_raw !== '' && (int)$id_produk_raw > 0) ? (int)$id_produk_raw : null;

    if (!$id_transaksi || !in_array($jenis, ['masuk', 'keluar']) || !$tanggal || !$id_kategori || $nominal <= 0) {
        header('Location: form_transaksi.php?id=' . $id_transaksi . '&error=' . urlencode('Semua field wajib diisi dengan benar!'));
        exit;
    }

    $cek = $pdo->prepare("SELECT id_transaksi FROM tb_transaksi WHERE id_transaksi = ? AND id_user = ?");
    $cek->execute([$id_transaksi, $_SESSION['id_user']]);
    if (!$cek->fetch()) {
        header('Location: transaksi.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE tb_transaksi SET
            id_kategori = ?, id_produk = ?, jenis = ?, tanggal = ?, waktu = ?,
            nominal = ?, keterangan = ?, catatan = ?
        WHERE id_transaksi = ? AND id_user = ?
    ");
    $stmt->execute([$id_kategori, $id_produk, $jenis, $tanggal, $waktu, $nominal, $keterangan, $catatan, $id_transaksi, $_SESSION['id_user']]);

    header('Location: transaksi.php?status=success&msg=' . urlencode('Transaksi berhasil diperbarui!'));
    exit;

} else {
    header('Location: transaksi.php');
    exit;
}
?>
