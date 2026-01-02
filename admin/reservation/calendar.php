<?php
session_start();
include '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  exit;
}

$q = mysqli_query($koneksi, "
  SELECT r.*, ru.nama_ruangan
  FROM reservasi r
  JOIN ruangan ru ON r.ruangan_id = ru.id
  WHERE r.status != 'Ditolak'
");

$events = [];
while ($r = mysqli_fetch_assoc($q)) {
  $color = match ($r['status']) {
    'Disetujui' => '#16a34a',
    'Menunggu Admin' => '#f59e0b',
    'Menunggu Kepala Bagian' => '#0ea5e9',
    'Dibatalkan' => '#64748b',
    default => '#94a3b8'
  };

  $events[] = [
    'title' => $r['nama_ruangan'],
    'start' => $r['tanggal'] . 'T' . $r['jam_mulai'],
    'end'   => $r['tanggal'] . 'T' . $r['jam_selesai'],
    'color' => $color
  ];
}

header('Content-Type: application/json');
echo json_encode($events);
