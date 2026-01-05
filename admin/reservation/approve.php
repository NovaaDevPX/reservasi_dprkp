<?php
session_start();
include '../../config/koneksi.php';
include '../../includes/notification-helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

$id = (int) ($_GET['id'] ?? 0);

// ==========================
// AMBIL DATA RESERVASI
// ==========================
$res = mysqli_query($koneksi, "
  SELECT r.id, r.user_id, u.nama AS nama_pegawai
  FROM reservasi r
  JOIN users u ON u.id = r.user_id
  WHERE r.id = $id
  LIMIT 1
");

$data = mysqli_fetch_assoc($res);
if (!$data) {
  header("Location: index.php?error=data_not_found");
  exit;
}

// ==========================
// UPDATE STATUS
// ==========================
$update = mysqli_query($koneksi, "
  UPDATE reservasi 
  SET status = 'Menunggu Kepala Bagian'
  WHERE id = $id AND status = 'Menunggu Admin'
");

if ($update) {

  // ==========================
  // NOTIFIKASI KE KEPALA BAGIAN
  // ==========================
  kirimNotifikasiByRole(
    $koneksi,
    ['kepala_bagian'],
    'Reservasi Menunggu Persetujuan',
    "Reservasi dari {$data['nama_pegawai']} menunggu persetujuan Anda.",
    $id
  );

  // ==========================
  // NOTIFIKASI KE PEGAWAI PEMOHON
  // ==========================
  mysqli_query($koneksi, "
    INSERT INTO notifikasi (user_id, reservasi_id, judul, pesan)
    VALUES (
      {$data['user_id']},
      $id,
      'Reservasi Diproses Admin',
      'Reservasi Anda telah disetujui admin dan menunggu persetujuan Kepala Bagian.'
    )
  ");

  // ==========================
  // NOTIFIKASI KE ADMIN (LAIN)
  // ==========================
  kirimNotifikasiByRole(
    $koneksi,
    ['admin'],
    'Reservasi Diproses',
    "Reservasi ID #$id telah disetujui admin dan dikirim ke Kepala Bagian.",
    $id
  );
}

header("Location: detail.php?id=$id&success=approve");
exit;
