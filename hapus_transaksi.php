<?php
require_once 'koneksi.php';
checkAuth();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $cek = $pdo->prepare("SELECT id_transaksi FROM tb_transaksi WHERE id_transaksi = ? AND id_user = ?");
    $cek->execute([$id, $_SESSION['id_user']]);
    if ($cek->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM tb_transaksi WHERE id_transaksi = ? AND id_user = ?");
        $stmt->execute([$id, $_SESSION['id_user']]);
        header('Location: transaksi.php?status=success&msg=' . urlencode('Transaksi berhasil dihapus!'));
        exit;
    }
}

header('Location: transaksi.php?status=error&msg=' . urlencode('Transaksi tidak ditemukan!'));
exit;
?>
