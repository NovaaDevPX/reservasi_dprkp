<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) exit;

$id = (int)($_POST['id'] ?? 0);
$user_id = $_SESSION['id_user'];

mysqli_query($koneksi, "
  UPDATE notifikasi
  SET is_read = 1
  WHERE id = $id AND user_id = $user_id
");
