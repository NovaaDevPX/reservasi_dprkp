<?php
session_start();
include '../config/koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kepala_bagian') {
    header("Location: ../index.php");
    exit;
}

$data = mysqli_query($koneksi, "SELECT nama_ruang, COUNT(*) AS total FROM reservasi GROUP BY nama_ruang");
$ruang = [];
$total = [];
while ($row = mysqli_fetch_assoc($data)) {
    $ruang[] = $row['nama_ruang'];
    $total[] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Statistik</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <h1>Laporan Statistik Penggunaan Ruang</h1>
        <canvas id="chart" width="400" height="150"></canvas>

        <br>
        <table border="0" width="100%" cellspacing="0" cellpadding="10" style="background:white; border-radius:10px;">
            <tr style="background:#1e90ff; color:white;">
                <th>No</th>
                <th>Nama Ruang</th>
                <th>Total Reservasi</th>
            </tr>
            <?php
            $no = 1;
            mysqli_data_seek($data, 0);
            while ($r = mysqli_fetch_assoc($data)) {
                echo "<tr>
                        <td>$no</td>
                        <td>{$r['nama_ruang']}</td>
                        <td>{$r['total']}</td>
                    </tr>";
                $no++;
            }
            ?>
        </table>
    </div>

    <script>
        const ctx = document.getElementById('chart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($ruang) ?>,
                datasets: [{
                    label: 'Total Reservasi',
                    data: <?= json_encode($total) ?>,
                    backgroundColor: 'rgba(30, 144, 255, 0.7)',
                    borderRadius: 8
                }]
            }
        });
    </script>
</body>
</html>
