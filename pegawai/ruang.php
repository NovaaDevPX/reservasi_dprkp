<?php
session_start();
include '../config/koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../index.php");
    exit();
}

// Batasi hanya untuk role pegawai
if ($_SESSION['role'] != 'pegawai') {
    header("Location: ../index.php");
    exit();
}

// Ambil data ruang
$ruang = mysqli_query($koneksi, "SELECT * FROM ruang");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Ruang | Pegawai</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f6fa;
            margin: 0;
            padding: 0;
        }
        .main-content {
            padding: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f1f7ff;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <h1>Data Ruang Rapat & Aula</h1>
        <table>
            <tr>
                <th>No</th>
                <th>Nama Ruang</th>
                <th>Kapasitas</th>
                <th>Fasilitas</th>
                <th>Status</th>
            </tr>
            <?php
            $no = 1;
            while ($r = mysqli_fetch_assoc($ruang)) {
                echo "<tr>
                        <td>{$no}</td>
                        <td>{$r['nama_ruang']}</td>
                        <td>{$r['kapasitas']}</td>
                        <td>{$r['fasilitas']}</td>
                        <td>{$r['status']}</td>
                      </tr>";
                $no++;
            }
            ?>
        </table>
    </div>
</body>
</html>
