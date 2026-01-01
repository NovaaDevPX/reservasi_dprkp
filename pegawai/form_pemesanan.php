<?php
session_start();
include '../config/koneksi.php';
if ($_SESSION['role'] != 'pegawai') {
    header('Location: ../index.php');
}

if (isset($_POST['simpan'])) {
    $id_user = $_SESSION['id_user'];
    $id_ruang = $_POST['id_ruang'];
    $tanggal = $_POST['tanggal'];
    $mulai = $_POST['waktu_mulai'];
    $selesai = $_POST['waktu_selesai'];
    $keperluan = $_POST['keperluan'];

    $query = "INSERT INTO pemesanan (id_user, id_ruang, tanggal, waktu_mulai, waktu_selesai, keperluan)
              VALUES ('$id_user','$id_ruang','$tanggal','$mulai','$selesai','$keperluan')";
    mysqli_query($koneksi, $query);

    echo "<script>alert('Pemesanan berhasil dikirim, menunggu verifikasi admin.'); window.location='status_pemesanan.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Form Pemesanan Ruang</title>
</head>
<body>
  <h2>Form Pemesanan Ruang</h2>
  <form method="POST">
    <label>Ruang:</label>
    <select name="id_ruang" required>
      <option value="">-- Pilih Ruang --</option>
      <?php
      $ruang = mysqli_query($koneksi, "SELECT * FROM ruang WHERE status='tersedia'");
      while($r = mysqli_fetch_array($ruang)) {
          echo "<option value='{$r['id_ruang']}'>{$r['nama_ruang']}</option>";
      }
      ?>
    </select><br><br>

    <label>Tanggal:</label>
    <input type="date" name="tanggal" required><br><br>

    <label>Waktu Mulai:</label>
    <input type="time" name="waktu_mulai" required><br><br>

    <label>Waktu Selesai:</label>
    <input type="time" name="waktu_selesai" required><br><br>

    <label>Keperluan:</label><br>
    <textarea name="keperluan" rows="4" cols="40" required></textarea><br><br>

    <button type="submit" name="simpan">Kirim Pemesanan</button>
  </form>
  <br><a href="dashboard.php">⬅ Kembali</a>
</body>
</html>
