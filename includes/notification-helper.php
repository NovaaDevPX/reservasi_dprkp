<?php

/**
 * Helper Notifikasi
 * Digunakan untuk kirim notifikasi ke banyak user / role
 */

if (!function_exists('kirimNotifikasiByRole')) {

  function kirimNotifikasiByRole(
    $koneksi,
    array $roles,
    string $judul,
    string $pesan,
    int $reservasi_id = null
  ) {
    if (empty($roles)) return;

    // sanitasi
    $judul = mysqli_real_escape_string($koneksi, $judul);
    $pesan = mysqli_real_escape_string($koneksi, $pesan);

    // ambil user sesuai role
    $roleList = "'" . implode("','", $roles) . "'";
    $query = mysqli_query($koneksi, "
      SELECT id FROM users
      WHERE role IN ($roleList)
    ");

    while ($user = mysqli_fetch_assoc($query)) {
      mysqli_query($koneksi, "
        INSERT INTO notifikasi (user_id, reservasi_id, judul, pesan)
        VALUES (
          {$user['id']},
          " . ($reservasi_id ? $reservasi_id : 'NULL') . ",
          '$judul',
          '$pesan'
        )
      ");
    }
  }
}
