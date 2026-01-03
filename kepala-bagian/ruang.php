<?php
session_start();
include '../config/koneksi.php';

// Validasi session untuk kepala_bagian
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kepala_bagian') {
    header("Location: ../index.php");
    exit;
}

// Ambil data ruang
$ruang = mysqli_query($koneksi, "SELECT * FROM ruang");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Ruang - Kepala Bagian</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f4f6fb;
            font-family: Arial, sans-serif;
            margin: 0;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <h1>📋 Data Ruang</h1>
        <div class="table-container">
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
                            <td>$no</td>
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
    </div>
</body>
</html>
