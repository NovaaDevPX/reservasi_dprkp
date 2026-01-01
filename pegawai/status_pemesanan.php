<?php
session_start();
include '../config/koneksi.php';
if ($_SESSION['role'] != 'pegawai') {
    header('Location: ../index.php');
}
$id_user = $_SESSION['id_user'];
$pemesanan = mysqli_query($koneksi, "SELECT p.*, r.nama_ruang FROM pemesanan p 
                                    JOIN ruang r ON p.id_ruang=r.id_ruang 
                                    WHERE p.id_user='$id_user' ORDER BY p.id_pemesanan DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Status Pemesanan</title>
</head>
<body>
  <h2>Status Pemesanan Anda</h2>
  <table border="1" cellpadding="8">
    <tr><th>Ruang</th><th>Tanggal</th><th>Waktu</th><th>Keperluan</th><th>Status</th></tr>
    <?php while($p = mysqli_fetch_array($pemesanan)) { ?>
      <tr>
        <td><?= $p['nama_ruang']; ?></td>
        <td><?= $p['tanggal']; ?></td>
        <td><?= $p['waktu_mulai']; ?> - <?= $p['waktu_selesai']; ?></td>
        <td><?= $p['keperluan']; ?></td>
        <td><?= ucfirst($p['status']); ?></td>
      </tr>
    <?php } ?>
  </table>
  <br><a href="dashboard.php">⬅ Kembali</a>
</body>
</html>
