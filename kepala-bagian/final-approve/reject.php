<?php
session_start();
include '../../config/koneksi.php';
include '../../includes/notification-helper.php';

/* =====================
   AUTH
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepala_bagian') {
  header("Location: ../../index.php");
  exit;
}

$id = (int)($_GET['id'] ?? 0);
$kabag_id = $_SESSION['id_user'] ?? 0;

/* =====================
   DATA RESERVASI
===================== */
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
  SELECT 
    r.*,
    u.nama AS nama_pemohon,
    ru.nama_ruangan
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  WHERE r.id = $id
"));

if (!$data) {
  die('Data tidak ditemukan');
}

/* =====================
   SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan']);

  // update reservasi
  mysqli_query($koneksi, "
    UPDATE reservasi SET
      status = 'Ditolak',
      alasan_tolak = '$alasan',
      kabag_id = $kabag_id
    WHERE id = $id
  ");

  /* =====================
     NOTIFIKASI
  ===================== */

  // 1. NOTIFIKASI KE PEGAWAI (PEMOHON)
  mysqli_query($koneksi, "
    INSERT INTO notifikasi (user_id, reservasi_id, judul, pesan)
    VALUES (
      {$data['user_id']},
      $id,
      'Reservasi Ditolak',
      'Reservasi ruangan {$data['nama_ruangan']} pada tanggal " . date('d F Y', strtotime($data['tanggal'])) . " ditolak. Alasan: $alasan'
    )
  ");

  // 2. NOTIFIKASI KE ADMIN
  kirimNotifikasiByRole(
    $koneksi,
    ['admin'],
    'Reservasi Ditolak oleh Kepala Bagian',
    "Reservasi ruangan {$data['nama_ruangan']} telah ditolak oleh Kepala Bagian.",
    $id
  );

  // 3. NOTIFIKASI KE KEPALA BAGIAN 
  mysqli_query($koneksi, "
    INSERT INTO notifikasi (user_id, reservasi_id, judul, pesan)
    VALUES (
      $kabag_id,
      $id,
      'Reservasi Ditolak',
      'Anda telah menolak reservasi ini.'
    )
  ");


  header("Location: index.php?success=reject");
  exit;
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tolak Reservasi</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .card-shadow {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .gradient-bg {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .btn-hover {
      transition: all 0.3s ease;
    }

    .btn-hover:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .textarea-focus:focus {
      border-color: #ef4444;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
  </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">
    <div class="max-w-full mx-auto bg-white rounded-2xl shadow-lg card-shadow p-8 space-y-8">

      <!-- HEADER -->
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
          <i class="fas fa-times-circle text-red-600 mr-2"></i>Tolak Reservasi
        </h1>
        <p class="text-gray-600">Berikan alasan penolakan untuk reservasi ini.</p>
      </div>

      <!-- INFO RESERVASI -->
      <div class="bg-gradient-to-r from-red-50 to-pink-50 p-6 rounded-xl border-l-4 border-red-500">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
          <i class="fas fa-info-circle text-red-600 mr-2"></i>Detail Reservasi
        </h2>
        <div class="space-y-2 text-sm">
          <p><span class="font-medium text-gray-700">Pemohon:</span> <span class="text-gray-900"><?= htmlspecialchars($data['nama_pemohon']) ?></span></p>
          <p><span class="font-medium text-gray-700">Ruangan:</span> <span class="text-gray-900"><?= htmlspecialchars($data['nama_ruangan']) ?></span></p>
          <p><span class="font-medium text-gray-700">Tanggal:</span> <span class="text-gray-900"><?= date('d F Y', strtotime($data['tanggal'])) ?></span></p>
          <p><span class="font-medium text-gray-700">Waktu:</span> <span class="text-gray-900"><?= htmlspecialchars($data['jam_mulai']) ?> - <?= htmlspecialchars($data['jam_selesai']) ?></span></p>
          <p><span class="font-medium text-gray-700">Keperluan:</span> <span class="text-gray-900"><?= htmlspecialchars($data['keperluan']) ?></span></p>
        </div>
      </div>

      <form method="POST" class="space-y-6">

        <!-- ALASAN -->
        <div>
          <label for="alasan" class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fas fa-comment-dots text-red-600 mr-2"></i>Alasan Penolakan
          </label>
          <textarea name="alasan" id="alasan" required
            class="w-full border-2 border-gray-300 rounded-xl p-4 h-32 resize-none textarea-focus focus:outline-none"
            placeholder="Masukkan alasan penolakan reservasi ini..."></textarea>
          <p class="text-xs text-gray-500 mt-1">Alasan ini akan dikirimkan ke pemohon.</p>
        </div>

        <!-- BUTTONS -->
        <div class="flex flex-col sm:flex-row gap-4">
          <button type="submit" class="flex-1 bg-gradient-to-r from-red-500 to-red-600 text-white py-3 rounded-xl font-semibold btn-hover shadow-md">
            <i class="fas fa-times mr-2"></i>Tolak Reservasi
          </button>
          <a href="index.php" class="flex-1 bg-gray-300 text-gray-700 text-center py-3 rounded-xl font-semibold hover:bg-gray-400 transition btn-hover">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
          </a>
        </div>

      </form>
    </div>
  </div>

</body>

</html>