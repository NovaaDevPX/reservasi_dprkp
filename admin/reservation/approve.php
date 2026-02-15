<?php
session_start();
include '../../config/koneksi.php';
include '../../includes/notification-helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

$id = (int) ($_GET['id'] ?? 0);

mysqli_begin_transaction($koneksi);

try {

  // ==========================
  // AMBIL DATA RESERVASI YANG DIAPPROVE
  // ==========================
  $res = mysqli_query($koneksi, "
    SELECT r.*, u.nama AS nama_pegawai
    FROM reservasi r
    JOIN users u ON u.id = r.user_id
    WHERE r.id = $id
    LIMIT 1
  ");

  $data = mysqli_fetch_assoc($res);

  if (!$data || $data['status'] !== 'Menunggu Admin') {
    throw new Exception("Data tidak valid.");
  }

  // ==========================
  // APPROVE RESERVASI INI
  // ==========================
  mysqli_query($koneksi, "
    UPDATE reservasi
    SET status = 'Menunggu Kepala Bagian'
    WHERE id = $id
  ");

  // ==========================
  // CARI RESERVASI LAIN YANG BENTROK
  // ==========================
  $bentrok = mysqli_query($koneksi, "
    SELECT id, user_id
    FROM reservasi
    WHERE id != $id
      AND ruangan_id = {$data['ruangan_id']}
      AND tanggal = '{$data['tanggal']}'
      AND status = 'Menunggu Admin'
      AND ('{$data['jam_mulai']}' < jam_selesai 
           AND '{$data['jam_selesai']}' > jam_mulai)
  ");

  while ($r = mysqli_fetch_assoc($bentrok)) {

    // ==========================
    // UPDATE JADI DITOLAK
    // ==========================
    mysqli_query($koneksi, "
      UPDATE reservasi
      SET status = 'Ditolak',
          alasan_tolak = 'Ditolak otomatis karena jadwal bertabrakan dengan reservasi lain yang telah diproses lebih dahulu.'
      WHERE id = {$r['id']}
    ");

    // ==========================
    // NOTIFIKASI KE PEGAWAI YANG DITOLAK
    // ==========================
    mysqli_query($koneksi, "
      INSERT INTO notifikasi (user_id, reservasi_id, judul, pesan)
      VALUES (
        {$r['user_id']},
        {$r['id']},
        'Reservasi Ditolak',
        'Reservasi Anda ditolak otomatis karena jadwal bertabrakan dengan reservasi lain yang telah disetujui lebih dahulu.'
      )
    ");
  }

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
  // NOTIFIKASI KE PEGAWAI YANG DIAPPROVE
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

  mysqli_commit($koneksi);

  header("Location: detail.php?id=$id&success=approve");
  exit;
} catch (Exception $e) {

  mysqli_rollback($koneksi);
  header("Location: detail.php?id=$id&error=approve_failed");
  exit;
}
