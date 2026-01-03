<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kepala_bagian') {
    header('Location: ../index.php');
    exit;
}

// Ambil data statistik jumlah penggunaan ruang
$query = "
    SELECT r.nama_ruang, COUNT(p.id_pemesanan) AS total_dipakai
    FROM pemesanan p
    JOIN ruang r ON p.id_ruang = r.id_ruang
    WHERE p.status = 'disetujui'
    GROUP BY r.nama_ruang
";
$result = mysqli_query($koneksi, $query);

$ruang = [];
$total = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ruang[] = $row['nama_ruang'];
    $total[] = $row['total_dipakai'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Grafik Statistik Penggunaan Ruang</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>📊 Statistik Penggunaan Ruang Rapat & Aula</h2>
    <a href="laporan.php">⬅️ Kembali ke Laporan</a> | 
    <a href="../logout.php">Logout</a>
    <br><br>

    <canvas id="grafikRuang" width="600" height="300"></canvas>

    <script>
        const ctx = document.getElementById('grafikRuang').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($ruang); ?>,
                datasets: [{
                    label: 'Jumlah Pemakaian Ruang',
                    data: <?= json_encode($total); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>
</html>
