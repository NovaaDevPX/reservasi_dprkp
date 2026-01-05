<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  exit;
}

$notif_id = (int)($_POST['id'] ?? 0);
$user_id  = $_SESSION['id_user'];

if ($notif_id > 0) {
  mysqli_query($koneksi, "
    UPDATE notifikasi
    SET is_read = 1
    WHERE id = $notif_id
      AND user_id = $user_id
  ");
}

echo json_encode(['success' => true]);
