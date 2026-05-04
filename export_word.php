<?php
require_once 'koneksi.php';
checkAuth();

// ── Sanitize Parameters ───────────────────────────────────────────────────────
$bulanDefault = date('m');
$tahunDefault = date('Y');

$bulan = (int)($_GET['bulan'] ?? $bulanDefault);
$tahun = (int)($_GET['tahun'] ?? $tahunDefault);
$jenis = $_GET['jenis'] ?? 'all';

if ($bulan < 1 || $bulan > 12) $bulan = $bulanDefault;
if ($tahun < 2020 || $tahun > 2099) $tahun = $tahunDefault;
if (!in_array($jenis, ['all', 'masuk', 'keluar'])) $jenis = 'all';

// ── Nama Bulan ────────────────────────────────────────────────────────────────
$namaBulanArr = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$periodeStr   = sprintf('%04d-%02d', $tahun, $bulan);
$periodeLabel = $namaBulanArr[$bulan] . ' ' . $tahun;

// ── Ambil Nama Toko/Pengguna (safe fallback) ──────────────────────────────────
$namaToko = $_SESSION['nama_toko'] ?? 'CatatCuan UMKM';

// ── Query Transaksi (PDO, aman dari SQL Injection) ────────────────────────────
$where  = "WHERE t.id_user = :uid AND DATE_FORMAT(t.tanggal, '%Y-%m') = :periode";
$params = [':uid' => $_SESSION['id_user'], ':periode' => $periodeStr];

if (in_array($jenis, ['masuk', 'keluar'])) {
    $where .= ' AND t.jenis = :jenis';
    $params[':jenis'] = $jenis;
}

$sql = "SELECT t.tanggal, t.waktu, t.keterangan, t.nominal, t.jenis,
               k.nama_kategori, k.ikon,
               p.nama_produk AS nama_produk_linked,
               p.harga_beli  AS produk_harga_beli
        FROM   tb_transaksi t
        JOIN   tb_kategori  k ON t.id_kategori = k.id_kategori
        LEFT JOIN tb_produk p ON t.id_produk   = p.id_produk
        $where
        ORDER BY t.tanggal ASC, t.waktu ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksiList = $stmt->fetchAll();

// ── Hitung Ringkasan ──────────────────────────────────────────────────────────
$totalMasuk  = 0;
$totalKeluar = 0;
$estimasi    = 0;

foreach ($transaksiList as $tx) {
    if ($tx['jenis'] === 'masuk') {
        $totalMasuk += $tx['nominal'];
        if (!empty($tx['nama_produk_linked']) && isset($tx['produk_harga_beli'])) {
            $estimasi += ($tx['nominal'] - (int)$tx['produk_harga_beli']);
        }
    } else {
        $totalKeluar += $tx['nominal'];
    }
}
$saldo    = $totalMasuk - $totalKeluar;
$jumlahTx = count($transaksiList);

// ── Helper: Format Rupiah ─────────────────────────────────────────────────────
function fRp(int $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// ── Helper: Format Tanggal ────────────────────────────────────────────────────
function fTgl(string $tgl): string {
    $bulanIdx = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei',
                 '06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt',
                 '11'=>'Nov','12'=>'Des'];
    $parts = explode('-', $tgl);
    return ($parts[2] ?? '') . ' ' . ($bulanIdx[$parts[1] ?? ''] ?? '') . ' ' . ($parts[0] ?? '');
}

// ── Label Filter Jenis ────────────────────────────────────────────────────────
$jenisLabel = match($jenis) {
    'masuk'  => 'Pemasukan Saja',
    'keluar' => 'Pengeluaran Saja',
    default  => 'Semua Transaksi',
};

// ── Saldo warna ───────────────────────────────────────────────────────────────
$saldoColor   = $saldo >= 0 ? '#7c3aed' : '#dc2626';
$estimasiColor = $estimasi >= 0 ? '#b45309' : '#dc2626';

// ── Nama File ─────────────────────────────────────────────────────────────────
$filename = 'Laporan_CatatCuan_' . $namaBulanArr[$bulan] . '_' . $tahun . '.doc';

// ── Output Headers ────────────────────────────────────────────────────────────
header('Content-Type: application/vnd.ms-word; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ── Baris tabel transaksi ─────────────────────────────────────────────────────
$rowsHtml = '';
if (empty($transaksiList)) {
    $rowsHtml = '<tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:20px;">
                    Tidak ada transaksi di periode ini.
                 </td></tr>';
} else {
    foreach ($transaksiList as $i => $tx) {
        $ismasuk   = $tx['jenis'] === 'masuk';
        $nomColor  = $ismasuk ? '#059669' : '#dc2626';
        $nomSign   = $ismasuk ? '+' : '-';
        $jenisText = $ismasuk ? 'Pemasukan' : 'Pengeluaran';
        $bgRow     = $i % 2 === 0 ? '#ffffff' : '#f9fafb';
        $produkBadge = !empty($tx['nama_produk_linked'])
            ? '<br><span style="font-size:9pt;color:#7c3aed;font-weight:bold;">&#128230; ' . htmlspecialchars($tx['nama_produk_linked']) . '</span>'
            : '';
        $rowsHtml .= '
        <tr style="background:' . $bgRow . ';">
            <td style="padding:8pt 10pt;border-bottom:1px solid #e5e7eb;text-align:center;color:#9ca3af;font-size:9pt;">' . ($i + 1) . '</td>
            <td style="padding:8pt 10pt;border-bottom:1px solid #e5e7eb;white-space:nowrap;font-size:9pt;">' . fTgl($tx['tanggal']) . '<br><span style="color:#9ca3af;font-size:8pt;">' . substr($tx['waktu'], 0, 5) . '</span></td>
            <td style="padding:8pt 10pt;border-bottom:1px solid #e5e7eb;font-size:9pt;">' . htmlspecialchars($tx['keterangan']) . $produkBadge . '</td>
            <td style="padding:8pt 10pt;border-bottom:1px solid #e5e7eb;font-size:9pt;">' . htmlspecialchars($tx['ikon'] . ' ' . $tx['nama_kategori']) . '</td>
            <td style="padding:8pt 10pt;border-bottom:1px solid #e5e7eb;text-align:center;font-size:9pt;color:' . $nomColor . ';font-weight:bold;">' . $jenisText . '</td>
            <td style="padding:8pt 10pt;border-bottom:1px solid #e5e7eb;text-align:right;font-size:9pt;font-weight:bold;color:' . $nomColor . ';">' . $nomSign . ' ' . fRp($tx['nominal']) . '</td>
        </tr>';
    }
}

// ── Estimasi Keuntungan row (tampilkan hanya jika ada) ────────────────────────
$estimasiRow = ($estimasi !== 0 || true) ? '
    <tr style="background:#fffbeb;">
        <td colspan="4" style="padding:10pt 14pt;border-top:2px solid #f59e0b;font-size:9pt;color:#92400e;font-weight:bold;">
            &#128200; Estimasi Keuntungan Produk
            <span style="font-weight:normal;font-size:8pt;"> (Nominal Masuk &minus; Harga Beli)</span>
        </td>
        <td colspan="2" style="padding:10pt 14pt;border-top:2px solid #f59e0b;text-align:right;font-size:10pt;font-weight:bold;color:' . $estimasiColor . ';">
            ' . (($estimasi < 0 ? '- ' : '') . fRp(abs($estimasi))) . '
        </td>
    </tr>' : '';

// ── HTML Output untuk Word ────────────────────────────────────────────────────
echo '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml>
 <w:WordDocument>
  <w:View>Print</w:View>
  <w:Zoom>90</w:Zoom>
  <w:DoNotOptimizeForBrowser/>
 </w:WordDocument>
</xml>
<![endif]-->
<style>
    body {
        font-family: Calibri, "Segoe UI", Arial, sans-serif;
        font-size: 10pt;
        color: #1f2937;
        margin: 0;
        padding: 0;
    }
    .page-wrap {
        max-width: 700pt;
        margin: 0 auto;
        padding: 40pt 48pt;
    }
    /* Header */
    .doc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 3pt solid #1a1f3c;
        padding-bottom: 16pt;
        margin-bottom: 20pt;
    }
    .doc-logo {
        font-size: 18pt;
        font-weight: 700;
        color: #1a1f3c;
        line-height: 1.2;
    }
    .doc-logo .cuan { color: #f5c518; }
    .doc-logo small { font-size: 8pt; font-weight: 400; color: #6b7280; display: block; }
    .doc-title { text-align: right; }
    .doc-title h1 { font-size: 13pt; font-weight: 700; color: #1a1f3c; margin: 0 0 4pt; }
    .doc-title p  { font-size: 8.5pt; color: #6b7280; margin: 0; }
    /* Info Box */
    .info-box {
        background: #f8f9fb;
        border: 1px solid #e5e7eb;
        border-radius: 6pt;
        padding: 12pt 16pt;
        margin-bottom: 20pt;
    }
    .info-box table { width: 100%; border-collapse: collapse; }
    .info-box td { width: 33%; font-size: 8.5pt; padding: 3pt 6pt; border: none; }
    .info-box .key { font-size: 7.5pt; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5pt; display: block; margin-bottom: 2pt; }
    .info-box .val { font-size: 9pt; font-weight: 600; color: #1a1f3c; }
    /* Ringkasan Cards */
    .summary-section { margin-bottom: 20pt; }
    .summary-title { font-size: 10pt; font-weight: 700; color: #1a1f3c; margin-bottom: 10pt; padding-bottom: 6pt; border-bottom: 1px solid #e5e7eb; }
    .summary-table { width: 100%; border-collapse: collapse; }
    .summary-table td { padding: 10pt 14pt; border: none; }
    .s-card { border-radius: 6pt; padding: 10pt 14pt; }
    .s-card .s-label { font-size: 7.5pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4pt; display: block; margin-bottom: 4pt; font-weight: 600; }
    .s-card .s-value { font-size: 12pt; font-weight: 700; }
    /* Transaksi Table */
    .tx-section-title { font-size: 10pt; font-weight: 700; color: #1a1f3c; margin-bottom: 10pt; padding-bottom: 6pt; border-bottom: 1px solid #e5e7eb; }
    .tx-table { width: 100%; border-collapse: collapse; }
    .tx-table thead tr { background: #1a1f3c; }
    .tx-table th {
        background: #1a1f3c;
        color: #ffffff;
        font-size: 8pt;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5pt;
        padding: 9pt 10pt;
        text-align: left;
        border: 1px solid #1a1f3c;
    }
    .tx-table td { border: 1px solid #e5e7eb; }
    /* Footer */
    .doc-footer {
        margin-top: 24pt;
        padding-top: 14pt;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }
    .doc-footer .timestamp { font-size: 7.5pt; color: #9ca3af; }
    .signature { text-align: center; }
    .signature .sig-title { font-size: 8pt; color: #6b7280; margin-bottom: 42pt; }
    .signature .sig-line  { border-top: 1px solid #1a1f3c; padding-top: 4pt; font-size: 8pt; font-weight: 600; }
</style>
</head>
<body>
<div class="page-wrap">

    <!-- ===== HEADER ===== -->
    <table width="100%" style="border-collapse:collapse;margin-bottom:20pt;border-bottom:3pt solid #1a1f3c;padding-bottom:16pt;">
        <tr>
            <td style="border:none;vertical-align:top;">
                <div style="font-size:18pt;font-weight:700;color:#1a1f3c;line-height:1.2;">
                    Catat<span style="color:#f5c518;">Cuan</span>
                </div>
                <div style="font-size:8pt;color:#6b7280;margin-top:2pt;">' . htmlspecialchars($namaToko) . '</div>
                <div style="font-size:8pt;color:#9ca3af;">Sistem Keuangan UMKM</div>
            </td>
            <td style="border:none;text-align:right;vertical-align:top;">
                <div style="font-size:14pt;font-weight:700;color:#1a1f3c;">LAPORAN ARUS KAS</div>
                <div style="font-size:9pt;color:#6b7280;margin-top:4pt;">Periode: ' . $periodeLabel . '</div>
                <div style="font-size:9pt;color:#6b7280;">Filter: ' . htmlspecialchars($jenisLabel) . '</div>
            </td>
        </tr>
    </table>

    <!-- ===== INFO BOX ===== -->
    <table width="100%" style="border-collapse:collapse;background:#f8f9fb;border:1pt solid #e5e7eb;border-radius:6pt;margin-bottom:20pt;">
        <tr>
            <td style="border:none;padding:10pt 14pt;width:33%;">
                <span style="display:block;font-size:7.5pt;color:#9ca3af;text-transform:uppercase;">Periode</span>
                <strong style="font-size:9pt;color:#1a1f3c;">' . $periodeLabel . '</strong>
            </td>
            <td style="border:none;padding:10pt 14pt;width:33%;">
                <span style="display:block;font-size:7.5pt;color:#9ca3af;text-transform:uppercase;">Total Transaksi</span>
                <strong style="font-size:9pt;color:#1a1f3c;">' . $jumlahTx . ' Transaksi</strong>
            </td>
            <td style="border:none;padding:10pt 14pt;width:33%;">
                <span style="display:block;font-size:7.5pt;color:#9ca3af;text-transform:uppercase;">Dibuat Pada</span>
                <strong style="font-size:9pt;color:#1a1f3c;">' . date('d F Y, H:i') . '</strong>
            </td>
        </tr>
    </table>

    <!-- ===== RINGKASAN STATISTIK ===== -->
    <div style="font-size:10pt;font-weight:700;color:#1a1f3c;margin-bottom:10pt;border-bottom:1pt solid #e5e7eb;padding-bottom:6pt;">
        Ringkasan Statistik
    </div>
    <table width="100%" style="border-collapse:collapse;margin-bottom:20pt;">
        <tr>
            <td style="width:25%;border:none;padding:6pt 8pt 6pt 0;">
                <table width="100%" style="border-collapse:collapse;background:#eff6ff;border-radius:6pt;">
                    <tr>
                        <td style="border:none;padding:10pt 14pt;">
                            <span style="display:block;font-size:7.5pt;color:#6b7280;text-transform:uppercase;font-weight:600;">Total Transaksi</span>
                            <strong style="font-size:14pt;color:#1e3a8a;">' . $jumlahTx . '</strong>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width:25%;border:none;padding:6pt 8pt;">
                <table width="100%" style="border-collapse:collapse;background:#f0fdf4;border-radius:6pt;">
                    <tr>
                        <td style="border:none;padding:10pt 14pt;">
                            <span style="display:block;font-size:7.5pt;color:#6b7280;text-transform:uppercase;font-weight:600;">Total Pemasukan</span>
                            <strong style="font-size:10pt;color:#059669;">' . fRp($totalMasuk) . '</strong>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width:25%;border:none;padding:6pt 8pt;">
                <table width="100%" style="border-collapse:collapse;background:#fef2f2;border-radius:6pt;">
                    <tr>
                        <td style="border:none;padding:10pt 14pt;">
                            <span style="display:block;font-size:7.5pt;color:#6b7280;text-transform:uppercase;font-weight:600;">Total Pengeluaran</span>
                            <strong style="font-size:10pt;color:#dc2626;">' . fRp($totalKeluar) . '</strong>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width:25%;border:none;padding:6pt 0 6pt 8pt;">
                <table width="100%" style="border-collapse:collapse;background:#1a1f3c;border-radius:6pt;">
                    <tr>
                        <td style="border:none;padding:10pt 14pt;">
                            <span style="display:block;font-size:7.5pt;color:rgba(255,255,255,0.6);text-transform:uppercase;font-weight:600;">Saldo Akhir' . ($saldo < 0 ? ' (Defisit)' : '') . '</span>
                            <strong style="font-size:10pt;color:' . ($saldo >= 0 ? '#f5c518' : '#f87171') . ';">' . ($saldo < 0 ? '- ' : '') . fRp(abs($saldo)) . '</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="border:none;padding:6pt 0 0 0;">
                <table width="100%" style="border-collapse:collapse;background:#fffbeb;border:1pt solid #fcd34d;border-radius:6pt;">
                    <tr>
                        <td style="border:none;padding:10pt 14pt;">
                            <span style="display:block;font-size:7.5pt;color:#92400e;text-transform:uppercase;font-weight:600;">&#128200; Estimasi Keuntungan Produk <span style="font-weight:400;text-transform:none;">(tx masuk ber-produk: Nominal &minus; Harga Beli)</span></span>
                            <strong style="font-size:11pt;color:' . $estimasiColor . ';">' . ($estimasi < 0 ? '- ' : '') . fRp(abs($estimasi)) . '</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ===== TABEL TRANSAKSI ===== -->
    <div style="font-size:10pt;font-weight:700;color:#1a1f3c;margin-bottom:10pt;border-bottom:1pt solid #e5e7eb;padding-bottom:6pt;">
        Data Transaksi &mdash; ' . $periodeLabel . '
    </div>
    <table class="tx-table" width="100%">
        <thead>
            <tr>
                <th style="width:4%;text-align:center;">No</th>
                <th style="width:13%;">Tanggal</th>
                <th style="width:30%;">Keterangan</th>
                <th style="width:18%;">Kategori</th>
                <th style="width:12%;text-align:center;">Jenis</th>
                <th style="width:23%;text-align:right;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            ' . $rowsHtml . '
            <!-- Baris Total -->
            <tr style="background:#f8f9fb;">
                <td colspan="4" style="padding:10pt 14pt;border:1pt solid #e5e7eb;font-weight:700;font-size:9pt;color:#374151;text-align:right;border-top:2pt solid #374151;">
                    Total Pemasukan:
                </td>
                <td colspan="2" style="padding:10pt 14pt;border:1pt solid #e5e7eb;text-align:right;font-weight:700;font-size:10pt;color:#059669;border-top:2pt solid #374151;">+ ' . fRp($totalMasuk) . '</td>
            </tr>
            <tr style="background:#f8f9fb;">
                <td colspan="4" style="padding:8pt 14pt;border:1pt solid #e5e7eb;font-weight:700;font-size:9pt;color:#374151;text-align:right;">
                    Total Pengeluaran:
                </td>
                <td colspan="2" style="padding:8pt 14pt;border:1pt solid #e5e7eb;text-align:right;font-weight:700;font-size:10pt;color:#dc2626;">- ' . fRp($totalKeluar) . '</td>
            </tr>
            <tr style="background:#1a1f3c;">
                <td colspan="4" style="padding:10pt 14pt;border:1pt solid #1a1f3c;font-weight:700;font-size:10pt;color:rgba(255,255,255,0.8);text-align:right;">
                    SALDO AKHIR' . ($saldo < 0 ? ' (DEFISIT)' : '') . ':
                </td>
                <td colspan="2" style="padding:10pt 14pt;border:1pt solid #1a1f3c;text-align:right;font-weight:700;font-size:12pt;color:' . ($saldo >= 0 ? '#f5c518' : '#f87171') . ';">' . ($saldo < 0 ? '- ' : '') . fRp(abs($saldo)) . '</td>
            </tr>
            ' . $estimasiRow . '
        </tbody>
    </table>

    <!-- ===== FOOTER ===== -->
    <table width="100%" style="border-collapse:collapse;margin-top:24pt;border-top:1pt solid #e5e7eb;padding-top:14pt;">
        <tr>
            <td style="border:none;vertical-align:bottom;">
                <div style="font-size:7.5pt;color:#9ca3af;">
                    Dicetak pada: ' . date('d F Y, H:i') . ' WIB<br>
                    CatatCuan UMKM &mdash; Sistem Keuangan Digital<br>
                    Dokumen ini dibuat secara otomatis oleh sistem.
                </div>
            </td>
            <td style="border:none;text-align:center;vertical-align:bottom;width:200pt;">
                <div style="font-size:8pt;color:#6b7280;margin-bottom:42pt;">Pemilik Usaha / Penanggungjawab</div>
                <div style="border-top:1pt solid #1a1f3c;padding-top:4pt;font-size:8pt;font-weight:600;color:#1a1f3c;">( _________________________ )</div>
            </td>
        </tr>
    </table>

</div>
</body>
</html>';
exit;
?>
