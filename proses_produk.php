<?php
require_once 'koneksi.php';
checkAuth();

$aksi = $_POST['aksi'] ?? '';

if ($aksi === 'tambah') {
    $nama      = trim($_POST['nama_produk'] ?? '');
    $hargaBeli = (int)($_POST['harga_beli'] ?? 0);
    $hargaJual = (int)($_POST['harga_jual'] ?? 0);

    if (!$nama) {
        redirectWith('produk.php', 'error', 'Nama produk tidak boleh kosong!');
    }
    if ($hargaBeli < 0 || $hargaJual < 0) {
        redirectWith('produk.php', 'error', 'Harga tidak boleh negatif!');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO tb_produk (id_user, nama_produk, harga_beli, harga_jual) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$_SESSION['id_user'], $nama, $hargaBeli, $hargaJual]);
    redirectWith('produk.php', 'success', 'Produk "' . $nama . '" berhasil ditambahkan!');

} elseif ($aksi === 'edit') {
    $id        = (int)($_POST['id_produk']   ?? 0);
    $nama      = trim($_POST['nama_produk']  ?? '');
    $hargaBeli = (int)($_POST['harga_beli']  ?? 0);
    $hargaJual = (int)($_POST['harga_jual']  ?? 0);

    if (!$id || !$nama) {
        redirectWith('produk.php', 'error', 'Data tidak valid!');
    }

    // Pastikan produk milik user ini
    $cek = $pdo->prepare("SELECT id_produk FROM tb_produk WHERE id_produk = ? AND id_user = ?");
    $cek->execute([$id, $_SESSION['id_user']]);
    if (!$cek->fetch()) {
        redirectWith('produk.php', 'error', 'Produk tidak ditemukan!');
    }

    $stmt = $pdo->prepare(
        "UPDATE tb_produk SET nama_produk=?, harga_beli=?, harga_jual=? WHERE id_produk=? AND id_user=?"
    );
    $stmt->execute([$nama, $hargaBeli, $hargaJual, $id, $_SESSION['id_user']]);
    redirectWith('produk.php', 'success', 'Produk "' . $nama . '" berhasil diperbarui!');

} elseif ($aksi === 'hapus') {
    $id = (int)($_POST['id_produk'] ?? 0);

    // Cek apakah sudah dipakai di transaksi
    $cek = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE id_produk = ?");
    $cek->execute([$id]);
    if ((int)$cek->fetchColumn() > 0) {
        redirectWith('produk.php', 'error', 'Produk tidak bisa dihapus karena sudah dipakai di transaksi!');
    }

    $stmt = $pdo->prepare("DELETE FROM tb_produk WHERE id_produk = ? AND id_user = ?");
    $stmt->execute([$id, $_SESSION['id_user']]);
    redirectWith('produk.php', 'success', 'Produk berhasil dihapus!');

} else {
    header('Location: produk.php');
    exit;
}
?>
