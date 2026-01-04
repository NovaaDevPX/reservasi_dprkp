<?php
session_start();
include '../../config/koneksi.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* =====================
   AUTH ADMIN
===================== */

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  exit('Unauthorized');
}

/* =====================
   FILTER
===================== */
$tgl_awal  = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';
$status    = $_GET['status'] ?? '';

$where = [];
if ($tgl_awal && $tgl_akhir) {
  $where[] = "r.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'";
}
if ($status) {
  $where[] = "r.status = '$status'";
}
$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/* =====================
   DATA
===================== */
$q = mysqli_query($koneksi, "
  SELECT r.*, u.nama AS nama_user, ru.nama_ruangan
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  $whereSQL
  ORDER BY r.tanggal DESC
");

/* =====================
   HTML
===================== */
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #333;
  }

  .header {
    text-align: center;
    margin-bottom: 15px;
  }

  .header h1 {
    margin: 0;
    font-size: 18px;
    letter-spacing: 1px;
  }

  .header p {
    margin: 3px 0 0;
    font-size: 12px;
    color: #666;
  }

  .info-box {
    border: 1px solid #ddd;
    padding: 8px;
    margin-bottom: 12px;
    border-radius: 4px;
  }

  .info-box table {
    width: 100%;
    font-size: 11px;
  }

  .info-box td {
    padding: 3px 0;
  }

  table.data {
    width: 100%;
    border-collapse: collapse;
  }

  table.data th {
    background: #f2f2f2;
    border: 1px solid #ccc;
    padding: 6px;
    text-align: center;
    font-size: 11px;
  }

  table.data td {
    border: 1px solid #ccc;
    padding: 6px;
    font-size: 11px;
  }

  table.data tr:nth-child(even) {
    background: #fafafa;
  }

  .text-center {
    text-align: center;
  }

  .badge {
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 10px;
    color: #fff;
    display: inline-block;
  }

  .menunggu { background: #f0ad4e; }
  .disetujui { background: #5cb85c; }
  .ditolak { background: #d9534f; }
  .dibatalkan { background: #777; }

  .footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    font-size: 10px;
    color: #666;
    text-align: right;
  }
</style>
</head>
<body>

<div class="header">
  <h1>LAPORAN RESERVASI RUANGAN</h1>
  <p>DPRKP – Sistem Informasi Reservasi</p>
</div>

<div class="info-box">
  <table>
    <tr>
      <td width="20%"><strong>Periode</strong></td>
      <td width="30%">: ' . ($tgl_awal ? "$tgl_awal s/d $tgl_akhir" : '-') . '</td>
      <td width="20%"><strong>Status</strong></td>
      <td width="30%">: ' . ($status ?: 'Semua') . '</td>
    </tr>
    <tr>
      <td><strong>Tanggal Cetak</strong></td>
      <td colspan="3">: ' . date('d-m-Y H:i') . '</td>
    </tr>
  </table>
</div>

<table class="data">
  <thead>
    <tr>
      <th width="4%">No</th>
      <th width="18%">Pegawai</th>
      <th width="16%">Ruangan</th>
      <th width="10%">Tanggal</th>
      <th width="12%">Waktu</th>
      <th width="8%">Peserta</th>
      <th width="12%">Status</th>
    </tr>
  </thead>
  <tbody>';

$no = 1;
while ($r = mysqli_fetch_assoc($q)) {

  $statusClass = 'menunggu';
  if ($r['status'] == 'Disetujui') $statusClass = 'disetujui';
  if ($r['status'] == 'Ditolak') $statusClass = 'ditolak';
  if ($r['status'] == 'Dibatalkan') $statusClass = 'dibatalkan';

  $html .= '
    <tr>
      <td class="text-center">' . $no++ . '</td>
      <td>' . $r['nama_user'] . '</td>
      <td>' . $r['nama_ruangan'] . '</td>
      <td class="text-center">' . date('d-m-Y', strtotime($r['tanggal'])) . '</td>
      <td class="text-center">' . $r['jam_mulai'] . ' - ' . $r['jam_selesai'] . '</td>
      <td class="text-center">' . $r['jumlah_peserta'] . '</td>
      <td class="text-center">
        <span class="badge ' . $statusClass . '">' . $r['status'] . '</span>
      </td>
    </tr>';
}

$html .= '
  </tbody>
</table>

<div class="footer">
  Dicetak oleh Sistem Reservasi DPRKP
</div>

</body>
</html>
';

/* =====================
   DOMPDF
===================== */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("laporan_reservasi.pdf", ["Attachment" => false]);
