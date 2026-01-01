<?php
session_start();
include '../../config/koneksi.php';

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
header("Location: index.php?success=delete");
exit;
