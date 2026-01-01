<?php
session_start();
include '../config/koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pegawai') {
    header("Location: ../index.php");
    exit;
}

$total = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pemesanan WHERE id_user='{$_SESSION['id_user']}'"));
$today = date('Y-m-d');
$hari_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pemesanan WHERE id_user='{$_SESSION['id_user']}' AND tanggal='$today'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pegawai</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <h1>Dashboard Pegawai</h1>
        <div class="cards">
            <div class="card">
                <h3>Total Reservasi Saya</h3>
                <p><?= $total['total'] ?></p>
            </div>
            <div class="card">
                <h3>Reservasi Hari Ini</h3>
                <p><?= $hari_ini['total'] ?></p>
            </div>
        </div>

        <div id="calendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                events: [
                    <?php
                    $events = mysqli_query($koneksi, "SELECT tanggal FROM pemesanan WHERE id_user='{$_SESSION['id_user']}'");
                    while ($e = mysqli_fetch_assoc($events)) {
                        echo "{ title: 'Booking', start: '" . $e['tanggal'] . "' },";
                    }
                    ?>
                ]
            });
            calendar.render();
        });
    </script>
</body>
</html>
