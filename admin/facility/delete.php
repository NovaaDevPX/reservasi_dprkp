<?php
session_start();
include '../../config/koneksi.php';
include '../../includes/notification-helper.php';

/* =====================
   AUTH ADMIN
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

if (!isset($_GET['id'])) {
  header("Location: index.php");
  exit;
}

$id = (int) $_GET['id'];

/* =====================
   AMBIL DATA FASILITAS
===================== */
$dataFasilitas = mysqli_fetch_assoc(
  mysqli_query($koneksi, "SELECT nama FROM fasilitas WHERE id=$id")
);

if (!$dataFasilitas) {
  header("Location: index.php");
  exit;
}

$nama_fasilitas = $dataFasilitas['nama'];

/* =====================
   CEK DIGUNAKAN / TIDAK
===================== */
$cek = mysqli_query(
  $koneksi,
  "SELECT COUNT(*) AS total FROM ruangan_fasilitas WHERE fasilitas_id=$id"
);
$data = mysqli_fetch_assoc($cek);

if ($data['total'] > 0) {
  // masih dipakai
  header("Location: index.php?error=used");
  exit;
}

/* =====================
   DELETE
===================== */
mysqli_query($koneksi, "DELETE FROM fasilitas WHERE id=$id");

/* =====================
   KIRIM NOTIFIKASI
===================== */
kirimNotifikasiByRole(
  $koneksi,
  ['admin', 'kepala_bagian'],
  'Fasilitas Dihapus',
  "Fasilitas \"$nama_fasilitas\" telah dihapus oleh admin."
);

header("Location: index.php?success=delete");
exit;
