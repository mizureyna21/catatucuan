<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$namaToko  = trim($_POST['nama_toko']           ?? '');
$email     = trim($_POST['email']               ?? '');
$password  = trim($_POST['password']            ?? '');
$konfirmasi = trim($_POST['konfirmasi_password'] ?? '');

// Validasi input
if (!$namaToko || !$email || !$password || !$konfirmasi) {
    header('Location: register.php?error=' . urlencode('Semua field wajib diisi!') . '&nama_toko=' . urlencode($namaToko) . '&email=' . urlencode($email));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=' . urlencode('Format email tidak valid!') . '&nama_toko=' . urlencode($namaToko) . '&email=' . urlencode($email));
    exit;
}

if (strlen($password) < 6) {
    header('Location: register.php?error=' . urlencode('Password minimal 6 karakter!') . '&nama_toko=' . urlencode($namaToko) . '&email=' . urlencode($email));
    exit;
}

if ($password !== $konfirmasi) {
    header('Location: register.php?error=' . urlencode('Password dan konfirmasi password tidak cocok!') . '&nama_toko=' . urlencode($namaToko) . '&email=' . urlencode($email));
    exit;
}

// Cek apakah email sudah terdaftar
$cekEmail = $pdo->prepare("SELECT id_user FROM tb_pengguna WHERE email = ? LIMIT 1");
$cekEmail->execute([$email]);
if ($cekEmail->fetch()) {
    header('Location: register.php?error=' . urlencode('Email tersebut sudah terdaftar. Silakan gunakan email lain atau login.') . '&nama_toko=' . urlencode($namaToko) . '&email=' . urlencode($email));
    exit;
}

// Hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// INSERT user baru
$stmtInsert = $pdo->prepare(
    "INSERT INTO tb_pengguna (nama_toko, email, password_hash, role) VALUES (?, ?, ?, 'user')"
);
$stmtInsert->execute([$namaToko, $email, $passwordHash]);

// Ambil ID user yang baru dibuat
$newIdUser = (int) $pdo->lastInsertId();

// AUTO-SEED: Buat kategori default untuk user baru ini
$kategoriDefault = [
    ['Penjualan Produk',    'masuk',  '💰'],
    ['Pendapatan Lain-lain','masuk',  '📥'],
    ['Biaya Operasional',   'keluar', '⚙️'],
    ['Pembelian Stok',      'keluar', '📦'],
    ['Biaya Pengiriman',    'keluar', '🚚'],
    ['Gaji Karyawan',       'keluar', '👥'],
];

$stmtKat = $pdo->prepare(
    "INSERT INTO tb_kategori (id_user, nama_kategori, jenis_arus, ikon) VALUES (?, ?, ?, ?)"
);
foreach ($kategoriDefault as $kat) {
    $stmtKat->execute([$newIdUser, $kat[0], $kat[1], $kat[2]]);
}

// Set session langsung — user langsung login setelah register
$_SESSION['id_user']   = $newIdUser;
$_SESSION['nama_toko'] = $namaToko;
$_SESSION['email']     = $email;

header('Location: dashboard.php?status=success&msg=' . urlencode('Selamat datang di CatatCuan, ' . $namaToko . '! 🎉 Akun Anda berhasil dibuat.'));
exit;
?>
