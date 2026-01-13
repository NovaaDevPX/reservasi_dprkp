<?php
session_start();
include '../config/koneksi.php';

/* =====================
   AUTH PEGAWAI
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
  header("Location: ../index.php");
  exit;
}

$user_id = $_SESSION['id_user'];

/* =====================
   STATISTIK
===================== */
$stat = mysqli_fetch_assoc(mysqli_query($koneksi, "
  SELECT
    COUNT(*) AS total,
    SUM(status='Disetujui') AS disetujui,
    SUM(status IN ('Menunggu Admin','Menunggu Kepala Bagian')) AS menunggu,
    SUM(status='Ditolak') AS ditolak
  FROM reservasi
  WHERE user_id = $user_id
"));

/* =====================
   RESERVASI TERBARU
===================== */
$reservasi = mysqli_query($koneksi, "
  SELECT r.*, ru.nama_ruangan
  FROM reservasi r
  JOIN ruangan ru ON r.ruangan_id = ru.id
  WHERE r.user_id = $user_id
  ORDER BY r.created_at DESC
  LIMIT 5
");

/* =====================
   NOTIFIKASI
===================== */
$notif = mysqli_query($koneksi, "
  SELECT *
  FROM notifikasi
  WHERE user_id = $user_id
  ORDER BY created_at DESC
  LIMIT 5
");

/* =====================
   HELPER STATUS
===================== */
function badgeStatus($status)
{
  return match ($status) {
    'Disetujui' => 'bg-emerald-100 text-emerald-700',
    'Menunggu Admin', 'Menunggu Kepala Bagian' => 'bg-amber-100 text-amber-700',
    'Ditolak' => 'bg-red-100 text-red-700',
    'Dibatalkan' => 'bg-slate-200 text-slate-600',
    default => 'bg-slate-100 text-slate-600'
  };
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Dashboard Pegawai</title>
  <?php include __DIR__ . '/../includes/module.php'; ?>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="bg-white p-6 rounded-2xl shadow-lg card-shadow mb-6">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">
        <i class="ph ph-chart-line text-blue-600 mr-2 text-3xl"></i>Dashboard Pegawai
      </h1>
      <p class="text-gray-600">Pantau tren reservasi, ruangan, status, dan fasilitas dengan mudah.</p>
    </div>

    <!-- STAT CARD -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

      <div class="bg-white rounded-2xl shadow p-5">
        <p class="text-sm text-slate-500">Total Reservasi</p>
        <p class="text-3xl font-bold text-slate-800"><?= $stat['total'] ?? 0; ?></p>
      </div>

      <div class="bg-white rounded-2xl shadow p-5">
        <p class="text-sm text-slate-500">Disetujui</p>
        <p class="text-3xl font-bold text-emerald-600"><?= $stat['disetujui'] ?? 0; ?></p>
      </div>

      <div class="bg-white rounded-2xl shadow p-5">
        <p class="text-sm text-slate-500">Menunggu</p>
        <p class="text-3xl font-bold text-amber-600"><?= $stat['menunggu'] ?? 0; ?></p>
      </div>

      <div class="bg-white rounded-2xl shadow p-5">
        <p class="text-sm text-slate-500">Ditolak</p>
        <p class="text-3xl font-bold text-red-600"><?= $stat['ditolak'] ?? 0; ?></p>
      </div>

    </div>

    <!-- CONTENT GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

      <!-- RESERVASI -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Reservasi Terbaru</h2>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-slate-500 border-b">
                <th class="py-2">Ruangan</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($reservasi) > 0): ?>
                <?php while ($r = mysqli_fetch_assoc($reservasi)): ?>
                  <tr class="border-b last:border-none">
                    <td class="py-2 font-medium"><?= htmlspecialchars($r['nama_ruangan']); ?></td>
                    <td><?= date('d M Y', strtotime($r['tanggal'])); ?></td>
                    <td>
                      <span class="px-3 py-1 rounded-full text-xs font-semibold <?= badgeStatus($r['status']); ?>">
                        <?= $r['status']; ?>
                      </span>
                    </td>
                    <td class="text-right">
                      <a href="reservation-history/detail.php?id=<?= $r['id']; ?>"
                        class="text-blue-600 hover:underline text-xs">
                        Detail
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="py-4 text-center text-slate-500 italic">
                    Belum ada reservasi
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- NOTIFIKASI -->
      <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Notifikasi</h2>

        <div class="space-y-4">
          <?php if (mysqli_num_rows($notif) > 0): ?>
            <?php while ($n = mysqli_fetch_assoc($notif)): ?>
              <div class="border-l-4 pl-3 <?= $n['is_read'] ? 'border-slate-300' : 'border-blue-500'; ?>">
                <p class="text-sm font-semibold"><?= htmlspecialchars($n['judul']); ?></p>
                <p class="text-xs text-slate-600"><?= htmlspecialchars($n['pesan']); ?></p>
                <p class="text-[11px] text-slate-400 mt-1">
                  <?= date('d M Y H:i', strtotime($n['created_at'])); ?>
                </p>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="text-slate-500 italic text-sm">Tidak ada notifikasi</p>
          <?php endif; ?>
        </div>
      </div>

    </div>

  </div>

</body>

</html>