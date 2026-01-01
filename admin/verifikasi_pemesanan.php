<?php
session_start();
include '../config/koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

$data = mysqli_query($koneksi, "SELECT r.*, u.nama AS nama_pegawai FROM reservasi r JOIN user u ON r.id_user = u.id_user ORDER BY tanggal DESC");

if (isset($_GET['verif'])) {
    $id = $_GET['verif'];
    mysqli_query($koneksi, "UPDATE reservasi SET status='disetujui' WHERE id_reservasi='$id'");
    header("Location: verifikasi.php");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Reservasi - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Verifikasi Reservasi</h1>
        <table border="0" width="100%" cellspacing="0" cellpadding="10" style="background:white; border-radius:10px;">
            <tr style="background:#1e90ff; color:white;">
                <th>No</th>
                <th>Nama Pegawai</th>
                <th>Ruang</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($data)) {
                echo "<tr>
                        <td>$no</td>
                        <td>{$row['nama_pegawai']}</td>
                        <td>{$row['nama_ruang']}</td>
                        <td>{$row['tanggal']}</td>
                        <td>{$row['status']}</td>
                        <td>";
                if ($row['status'] == 'menunggu') {
                    echo "<a href='verifikasi.php?verif={$row['id_reservasi']}' style='background:green; color:white; padding:6px 12px; border-radius:5px; text-decoration:none;'>Setujui</a>";
                } else {
                    echo "-";
                }
                echo "</td></tr>";
                $no++;
            }
            ?>
        </table>
    </div>
</body>
</html>
