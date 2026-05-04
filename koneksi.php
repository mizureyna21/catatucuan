<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'db_catatcuan');
define('DB_USER', 'root');
define('DB_PASS', '0000');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:Inter,sans-serif;background:#fef2f2;border:2px solid #ef4444;color:#dc2626;padding:20px 28px;border-radius:12px;margin:40px auto;max-width:600px;">
        <strong>❌ Gagal konek ke database!</strong><br><br>
        Pastikan:<br>
        • Laragon sudah berjalan (MySQL aktif)<br>
        • Database <code>db_catatcuan</code> sudah dibuat di phpMyAdmin<br>
        • Error: ' . htmlspecialchars($e->getMessage()) . '
    </div>');
}

function checkAuth(): void
{
    if (!isset($_SESSION['id_user'])) {
        header('Location: login.php');
        exit;
    }
}

function formatRupiah(int $nominal): string
{
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

function formatTanggal(string $date): string
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $d = explode('-', $date);
    return $d[2] . ' ' . $bulan[(int) $d[1]] . ' ' . $d[0];
}

function redirectWith(string $url, string $status, string $msg): void
{
    header("Location: $url?status=$status&msg=" . urlencode($msg));
    exit;
}
?>