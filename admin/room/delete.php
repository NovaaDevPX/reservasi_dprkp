<?php
session_start();
include '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

$id = (int) $_GET['id'];

/* CEK DIPAKAI RESERVASI */
$cek = mysqli_query($koneksi, "
  SELECT id FROM reservasi WHERE ruangan_id=$id LIMIT 1
");

if (mysqli_num_rows($cek) > 0) {
  header("Location: index.php?error=used");
  exit;
}

mysqli_begin_transaction($koneksi);

try {
  mysqli_query($koneksi, "DELETE FROM ruangan_fasilitas WHERE ruangan_id=$id");
  mysqli_query($koneksi, "DELETE FROM ruangan WHERE id=$id");

  mysqli_commit($koneksi);
  header("Location: index.php?success=delete");
  exit;
} catch (Exception $e) {
  mysqli_rollback($koneksi);
  die("Gagal hapus data");
}
