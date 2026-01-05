<?php
session_start();
include '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  exit('Akses ditolak');
}

require '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  exit('ID tidak valid');
}

/* =======================
   DATA RESERVASI
======================= */
$q = mysqli_query($koneksi, "
  SELECT
    r.*,
    u.nama AS nama_user,
    ru.nama_ruangan,
    kb.nama AS nama_kabag
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  LEFT JOIN users kb ON r.kabag_id = kb.id
  WHERE r.id = $id
  LIMIT 1
");

$data = mysqli_fetch_assoc($q);
if (!$data) {
  exit('Data tidak ditemukan');
}

/* =======================
   DATA FASILITAS
======================= */
$fasilitas = mysqli_query($koneksi, "
  SELECT f.nama, rf.qty
  FROM reservasi_fasilitas rf
  JOIN fasilitas f ON rf.fasilitas_id = f.id
  WHERE rf.reservasi_id = $id
");

/* =======================
   PATH TTD
======================= */
$ttd = '';
if (!empty($data['ttd_kabag'])) {
  $ttd = 'http://localhost/reservasi_dprkp/uploads/ttd/' . $data['ttd_kabag'];
}

/* =======================
   STATUS STYLE
======================= */
$status = $data['status'];

$statusColor = '#6b7280';
$statusBg    = '#f3f4f6';
$statusNote  = '';

switch ($status) {
  case 'Menunggu Admin':
    $statusColor = '#1d4ed8';
    $statusBg    = '#dbeafe';
    $statusNote  = 'Reservasi sedang menunggu persetujuan Admin.';
    break;

  case 'Menunggu Kepala Bagian':
    $statusColor = '#7c3aed';
    $statusBg    = '#ede9fe';
    $statusNote  = 'Reservasi telah disetujui Admin dan menunggu persetujuan Kepala Bagian.';
    break;

  case 'Disetujui':
    $statusColor = '#047857';
    $statusBg    = '#d1fae5';
    $statusNote  = 'Reservasi telah disetujui dan dapat digunakan.';
    break;

  case 'Ditolak':
    $statusColor = '#b91c1c';
    $statusBg    = '#fee2e2';
    $statusNote  = 'Reservasi ditolak. Alasan: ' . ($data['alasan_tolak'] ?: '-');
    break;

  case 'Dibatalkan':
    $statusColor = '#92400e';
    $statusBg    = '#fef3c7';
    $statusNote  = 'Reservasi dibatalkan oleh pemohon.';
    break;
}

/* =======================
   HTML PDF
======================= */
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    color: #111827;
  }

  .header {
    text-align: center;
    margin-bottom: 20px;
  }

  .header h1 {
    font-size: 20px;
    margin: 0;
  }

  .divider {
    height: 3px;
    background: #2563eb;
    margin: 16px 0 24px;
  }

  .card {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 18px;
  }

  .card-title {
    font-weight: bold;
    margin-bottom: 12px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  .detail td {
    padding: 6px;
  }

  .label {
    width: 30%;
    color: #6b7280;
    font-weight: bold;
  }

  .status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: bold;
  }

  .status-note {
    margin-top: 12px;
    padding: 10px;
    border-radius: 8px;
    font-size: 11px;
  }

  .table th {
    background: #f3f4f6;
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #d1d5db;
  }

  .table td {
    padding: 8px;
    border-bottom: 1px solid #e5e7eb;
  }

  .signature {
    margin-top: 40px;
    width: 100%;
  }

  .signature td {
    text-align: right;
    font-size: 12px;
  }

  .footer {
    margin-top: 24px;
    font-size: 10px;
    color: #6b7280;
  }
</style>
</head>
<body>

<div class="header">
  <h1>LAPORAN RESERVASI RUANGAN</h1>
  <p>DPRKP – Sistem Informasi Reservasi</p>
</div>

<div class="divider"></div>

<div class="card">
  <div class="card-title">Informasi Reservasi</div>
  <table class="detail">
    <tr><td class="label">Nama Pegawai</td><td>' . $data['nama_user'] . '</td></tr>
    <tr><td class="label">Ruangan</td><td>' . $data['nama_ruangan'] . '</td></tr>
    <tr><td class="label">Tanggal</td><td>' . date('d M Y', strtotime($data['tanggal'])) . '</td></tr>
    <tr><td class="label">Waktu</td><td>' . $data['jam_mulai'] . ' - ' . $data['jam_selesai'] . '</td></tr>
    <tr><td class="label">Jumlah Peserta</td><td>' . $data['jumlah_peserta'] . ' Orang</td></tr>
    <tr>
      <td class="label">Status</td>
      <td>
        <span class="status-badge" style="background:' . $statusBg . ';color:' . $statusColor . '">
          ' . $status . '
        </span>
      </td>
    </tr>
    <tr><td class="label">Keperluan</td><td>' . $data['keperluan'] . '</td></tr>
  </table>

  <div class="status-note"
    style="background:' . $statusBg . ';color:' . $statusColor . ';border-left:4px solid ' . $statusColor . '">
    ' . $statusNote . '
  </div>
</div>

<div class="card">
  <div class="card-title">Fasilitas Digunakan</div>
  <table class="table">
    <tr><th>Fasilitas</th><th width="20%">Jumlah</th></tr>';

while ($f = mysqli_fetch_assoc($fasilitas)) {
  $html .= '
    <tr>
      <td>' . $f['nama'] . '</td>
      <td>' . $f['qty'] . '</td>
    </tr>';
}

$html .= '
  </table>
</div>

<table class="signature">
  <tr>
    <td>
      <p>Disetujui oleh,</p>
      <p><b>Kepala Bagian</b></p>';

if ($status === 'Disetujui' && $ttd) {
  $html .= '<img src="' . $ttd . '" height="35">';
} else {
  $html .= '<br><br><br>';
}

$html .= '
      <p><b>' . ($data['nama_kabag'] ?: '-') . '</b></p>
    </td>
  </tr>
</table>

<div class="footer">
  Dicetak pada: ' . date('d M Y H:i') . '
</div>

</body>
</html>
';

/* =======================
   GENERATE PDF
======================= */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$pdf = new Dompdf($options);
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'portrait');
$pdf->render();
$pdf->stream("Reservasi-$id.pdf", ["Attachment" => false]);
