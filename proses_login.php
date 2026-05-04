<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    header('Location: login.php?error=' . urlencode('Email dan password wajib diisi!'));
    exit;
}

$stmt = $pdo->prepare("SELECT id_user, nama_toko, email, password_hash FROM tb_pengguna WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    header('Location: login.php?error=' . urlencode('Email atau password salah. Silakan coba lagi.') . '&email=' . urlencode($email));
    exit;
}

// Set session
$_SESSION['id_user']   = (int) $user['id_user'];
$_SESSION['nama_toko'] = $user['nama_toko'];
$_SESSION['email']     = $user['email'];

header('Location: dashboard.php?status=success&msg=' . urlencode('Selamat datang kembali, ' . $user['nama_toko'] . '! 👋'));
exit;
?>
