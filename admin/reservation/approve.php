<?php
session_start();
include '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

$id = (int) ($_GET['id'] ?? 0);

/*
  Alur:
  Admin → Menunggu Kepala Bagian
*/
mysqli_query($koneksi, "
  UPDATE reservasi 
  SET status = 'Menunggu Kepala Bagian'
  WHERE id = $id AND status = 'Menunggu Admin'
");

header("Location: index.php?success=approve");
exit;
