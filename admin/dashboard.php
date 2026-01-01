<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
  header("Location: ../index.php");
  exit;
}

// ============================
// TOTAL RESERVASI
// ============================
$qTotal = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM reservasi");
if (!$qTotal) {
  die("Query error total: " . mysqli_error($koneksi));
}
$total = mysqli_fetch_assoc($qTotal);

// ============================
// RESERVASI HARI INI
// ============================
$today = date('Y-m-d');
$qHariIni = mysqli_query(
  $koneksi,
  "SELECT COUNT(*) AS total FROM reservasi WHERE tanggal='$today'"
);
if (!$qHariIni) {
  die("Query error hari ini: " . mysqli_error($koneksi));
}
$hari_ini = mysqli_fetch_assoc($qHariIni);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>

  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

  <?php include '../includes/layouts/sidebar.php'; ?>

  <div class="main-content">
    <h1>Dashboard Admin</h1>

    <div class="cards">
      <div class="card">
        <h3>Total Reservasi</h3>
        <p><?= $total['total']; ?></p>
      </div>

      <div class="card">
        <h3>Reservasi Hari Ini</h3>
        <p><?= $hari_ini['total']; ?></p>
      </div>
    </div>

    <div id="calendar" style="margin-top:30px;"></div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var calendarEl = document.getElementById('calendar');
      var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'id',
        events: [
          <?php
          $events = mysqli_query($koneksi, "SELECT tanggal FROM reservasi");
          if (!$events) {
            die("Query error events: " . mysqli_error($koneksi));
          }

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