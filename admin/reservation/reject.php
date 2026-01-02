<?php
session_start();
include '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan']);

  mysqli_query($koneksi, "
    UPDATE reservasi 
    SET status = 'Ditolak', alasan_tolak = '$alasan'
    WHERE id = $id
  ");

  header("Location: index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Tolak Reservasi</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen">
  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-8">
    <div class="max-w-lg bg-white rounded-2xl shadow p-6">
      <h1 class="text-xl font-bold mb-4 text-red-600">Tolak Reservasi</h1>

      <form method="POST">
        <label class="block text-sm font-medium mb-2">Alasan Penolakan</label>
        <textarea name="alasan" required
          class="w-full border rounded-xl p-3 mb-4"
          placeholder="Masukkan alasan penolakan..."></textarea>

        <div class="flex gap-3">
          <button class="px-4 py-2 bg-red-600 text-white rounded-xl">Tolak</button>
          <a href="index.php" class="px-4 py-2 bg-slate-200 rounded-xl">Batal</a>
        </div>
      </form>
    </div>
  </div>
</body>

</html>