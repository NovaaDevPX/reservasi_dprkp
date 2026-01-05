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

/* =====================
   VALIDASI ID
===================== */
$id = intval($_GET['id'] ?? 0);
if (!$id) {
  header("Location: index.php?error=invalid_id");
  exit;
}

/* =====================
   AMBIL DATA RESERVASI
===================== */
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

/* =====================
   HANDLE SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $alasan = trim($_POST['alasan'] ?? '');

  if ($alasan !== '') {
    $alasan_db = mysqli_real_escape_string($koneksi, $alasan);

    // =====================
    // UPDATE STATUS
    // =====================
    mysqli_query($koneksi, "
      UPDATE reservasi 
      SET status = 'Ditolak',
          alasan_tolak = '$alasan_db'
      WHERE id = $id
    ");

    // =====================
    // NOTIFIKASI KE PEGAWAI
    // =====================
    mysqli_query($koneksi, "
      INSERT INTO notifikasi (user_id, reservasi_id, judul, pesan)
      VALUES (
        {$data['user_id']},
        $id,
        'Reservasi Ditolak',
        'Reservasi Anda ditolak oleh admin. Alasan: $alasan_db'
      )
    ");

    // =====================
    // NOTIFIKASI KE KEPALA BAGIAN
    // =====================
    kirimNotifikasiByRole(
      $koneksi,
      ['kepala_bagian'],
      'Reservasi Ditolak Admin',
      "Reservasi dari {$data['nama_pegawai']} telah ditolak oleh admin.",
      $id
    );

    // =====================
    // NOTIFIKASI KE ADMIN LAIN
    // =====================
    kirimNotifikasiByRole(
      $koneksi,
      ['admin'],
      'Reservasi Ditolak',
      "Reservasi ID #$id telah ditolak oleh admin.",
      $id
    );

    header("Location: detail.php?id=$id&success=reject");
    exit;
  }
}
