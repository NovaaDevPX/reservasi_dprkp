<?php
session_start();
include '../../config/koneksi.php';

/* =====================
   AUTH ADMIN
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

/* =====================
   VALIDASI ID
===================== */
$id = intval($_GET['id'] ?? 0);
if (!$id) {
  header("Location: index.php?error=invalid_id");
  exit;
}

/* =====================
   HANDLE SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $alasan = trim($_POST['alasan'] ?? '');

  if ($alasan !== '') {
    $alasan = mysqli_real_escape_string($koneksi, $alasan);

    mysqli_query($koneksi, "
      UPDATE reservasi 
      SET status = 'Ditolak',
          alasan_tolak = '$alasan'
      WHERE id = $id
    ");

    header("Location: index.php?success=reject");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Tolak Reservasi | Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">

    <!-- HEADER -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-800 mb-2">Tolak Reservasi</h1>
      <a href="detail.php?id=<?= $id; ?>" class="text-blue-600 hover:underline text-sm">
        ← Kembali ke detail reservasi
      </a>
    </div>

    <!-- CARD -->
    <div class="bg-white rounded-2xl shadow p-6 space-y-6">

      <div>
        <p class="text-sm text-slate-500 mb-1">Alasan Penolakan</p>
        <p class="text-slate-700 text-sm">
          Silakan isi alasan penolakan agar pemohon mengetahui penyebab reservasi ditolak.
        </p>
      </div>

      <form method="POST" class="space-y-6">

        <textarea
          name="alasan"
          rows="5"
          required
          placeholder="Contoh: Jadwal ruangan bentrok dengan kegiatan lain..."
          class="w-full border border-slate-300 rounded-xl p-4 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>

        <div class="flex items-center gap-3">
          <button
            type="submit"
            class="inline-flex items-center px-5 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition">
            Tolak Reservasi
          </button>

          <a
            href="detail.php?id=<?= $id; ?>"
            class="inline-flex items-center px-5 py-2.5 bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-300 transition">
            Batal
          </a>
        </div>

      </form>

    </div>

  </div>

</body>

</html>