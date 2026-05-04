<?php
require_once 'koneksi.php';
checkAuth();

$aksi = $_POST['aksi'] ?? '';

if ($aksi === 'tambah') {
    $nama  = trim($_POST['nama_kategori'] ?? '');
    $jenis = $_POST['jenis_arus'] ?? '';
    $ikon  = trim($_POST['ikon'] ?? '') ?: ($jenis === 'masuk' ? '💰' : '💸');

    if (!$nama || !in_array($jenis, ['masuk', 'keluar'])) {
        redirectWith('kategori.php', 'error', 'Data kategori tidak lengkap!');
    }
    $stmt = $pdo->prepare("INSERT INTO tb_kategori (id_user, nama_kategori, jenis_arus, ikon) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['id_user'], $nama, $jenis, $ikon]);
    redirectWith('kategori.php', 'success', 'Kategori "' . $nama . '" berhasil ditambahkan!');

} elseif ($aksi === 'edit') {
    $id    = (int)($_POST['id_kategori'] ?? 0);
    $nama  = trim($_POST['nama_kategori'] ?? '');
    $jenis = $_POST['jenis_arus'] ?? '';
    $ikon  = trim($_POST['ikon'] ?? '') ?: '💰';

    if (!$id || !$nama || !in_array($jenis, ['masuk', 'keluar'])) {
        redirectWith('kategori.php', 'error', 'Data tidak valid!');
    }
    $stmt = $pdo->prepare("UPDATE tb_kategori SET nama_kategori=?, jenis_arus=?, ikon=? WHERE id_kategori=? AND id_user=?");
    $stmt->execute([$nama, $jenis, $ikon, $id, $_SESSION['id_user']]);
    redirectWith('kategori.php', 'success', 'Kategori "' . $nama . '" berhasil diperbarui!');

} elseif ($aksi === 'hapus') {
    $id = (int)($_POST['id_kategori'] ?? 0);
    
    $cek = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE id_kategori = ?");
    $cek->execute([$id]);
    if ((int)$cek->fetchColumn() > 0) {
        redirectWith('kategori.php', 'error', 'Kategori tidak bisa dihapus karena sudah dipakai di transaksi!');
    }
    $stmt = $pdo->prepare("DELETE FROM tb_kategori WHERE id_kategori = ? AND id_user = ?");
    $stmt->execute([$id, $_SESSION['id_user']]);
    redirectWith('kategori.php', 'success', 'Kategori berhasil dihapus!');

} else {
    header('Location: kategori.php');
    exit;
}
?>
