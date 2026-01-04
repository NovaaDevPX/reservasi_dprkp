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
$id = $_GET['id'] ?? '';
$id = intval($id);

if (!$id) {
  header("Location: index.php");
  exit;
}

/* =====================
   DATA RESERVASI
===================== */
$reservasi = mysqli_query($koneksi, "
  SELECT 
    r.*,
    u.nama AS nama_user,
    u.nip,
    ru.nama_ruangan,
    ru.kapasitas
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  WHERE r.id = $id
  LIMIT 1
");

$data = mysqli_fetch_assoc($reservasi);

if (!$data) {
  header("Location: index.php");
  exit;
}

/* =====================
   FASILITAS RUANGAN
===================== */
$fasilitas_ruangan = mysqli_query($koneksi, "
  SELECT f.nama, rf.qty
  FROM ruangan_fasilitas rf
  JOIN fasilitas f ON rf.fasilitas_id = f.id
  WHERE rf.ruangan_id = {$data['ruangan_id']}
");

/* =====================
   FASILITAS RESERVASI
===================== */
$fasilitas_reservasi = mysqli_query($koneksi, "
  SELECT f.nama, rf.qty
  FROM reservasi_fasilitas rf
  JOIN fasilitas f ON rf.fasilitas_id = f.id
  WHERE rf.reservasi_id = $id
");

/* =====================
   STATUS BADGE
===================== */
$statusClass = match ($data['status']) {
  'Disetujui' => 'bg-emerald-100 text-emerald-700',
  'Menunggu Admin' => 'bg-amber-100 text-amber-700',
  'Menunggu Kepala Bagian' => 'bg-sky-100 text-sky-700',
  'Ditolak' => 'bg-red-100 text-red-700',
  'Dibatalkan' => 'bg-slate-200 text-slate-600',
  default => 'bg-slate-100 text-slate-600'
};
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Detail Reservasi | Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>
  <?php include '../../includes/layouts/notification.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">

    <!-- HEADER -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-800 mb-2">Detail Reservasi</h1>
      <a href="index.php" class="text-blue-600 hover:underline text-sm">
        ← Kembali ke daftar
      </a>
    </div>

    <!-- CARD -->
    <div class="bg-white rounded-2xl shadow p-6 space-y-8">

      <!-- INFO UTAMA -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div>
          <p class="text-sm text-slate-500">Pemohon</p>
          <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nama_user']); ?></p>
          <p class="text-xs text-slate-500"><?= $data['nip']; ?></p>
        </div>

        <div>
          <p class="text-sm text-slate-500">Status</p>
          <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?= $statusClass; ?>">
            <?= $data['status']; ?>
          </span>
        </div>

        <div>
          <p class="text-sm text-slate-500">Ruangan</p>
          <p class="font-semibold"><?= htmlspecialchars($data['nama_ruangan']); ?></p>
          <p class="text-xs text-slate-500">Kapasitas: <?= $data['kapasitas']; ?> orang</p>
        </div>

        <div>
          <p class="text-sm text-slate-500">Tanggal & Waktu</p>
          <p class="font-semibold">
            <?= date('d M Y', strtotime($data['tanggal'])); ?>
          </p>
          <p class="text-sm text-slate-600">
            <?= substr($data['jam_mulai'], 0, 5); ?> -
            <?= substr($data['jam_selesai'], 0, 5); ?>
          </p>
        </div>

        <div>
          <p class="text-sm text-slate-500">Jumlah Peserta</p>
          <p class="font-semibold"><?= $data['jumlah_peserta'] ?? '-'; ?> orang</p>
        </div>

      </div>

      <!-- KEPERLUAN -->
      <div>
        <p class="text-sm text-slate-500 mb-1">Keperluan</p>
        <div class="bg-slate-50 border rounded-xl p-4 text-slate-700">
          <?= nl2br(htmlspecialchars($data['keperluan'])); ?>
        </div>
      </div>

      <!-- FASILITAS RUANGAN -->
      <div>
        <h3 class="font-semibold text-slate-800 mb-3">Fasilitas Ruangan</h3>
        <div class="flex flex-wrap gap-2">
          <?php if (mysqli_num_rows($fasilitas_ruangan) > 0): ?>
            <?php while ($f = mysqli_fetch_assoc($fasilitas_ruangan)): ?>
              <span class="px-3 py-1 bg-slate-100 rounded-full text-sm">
                <?= htmlspecialchars($f['nama']); ?> (<?= $f['qty']; ?>)
              </span>
            <?php endwhile; ?>
          <?php else: ?>
            <span class="text-slate-500 italic">Tidak ada fasilitas</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- FASILITAS DIPESAN -->
      <div>
        <h3 class="font-semibold text-slate-800 mb-3">Fasilitas Digunakan</h3>
        <div class="flex flex-wrap gap-2">
          <?php if (mysqli_num_rows($fasilitas_reservasi) > 0): ?>
            <?php while ($f = mysqli_fetch_assoc($fasilitas_reservasi)): ?>
              <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                <?= htmlspecialchars($f['nama']); ?> (<?= $f['qty']; ?>)
              </span>
            <?php endwhile; ?>
          <?php else: ?>
            <span class="text-slate-500 italic">Tidak ada fasilitas khusus</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- ALASAN TOLAK -->
      <?php if ($data['status'] === 'Ditolak' && $data['alasan_tolak']): ?>
        <div>
          <h3 class="font-semibold text-red-700 mb-2">Alasan Penolakan</h3>
          <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700">
            <?= nl2br(htmlspecialchars($data['alasan_tolak'])); ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- TANDA TANGAN KEPALA BAGIAN -->
      <?php if ($data['status'] === 'Disetujui' && !empty($data['ttd_kabag'])): ?>
        <div class="pt-6 border-t">
          <h3 class="font-semibold text-slate-800 mb-3">
            Tanda Tangan Kepala Bagian
          </h3>

          <div class="flex items-center gap-6">
            <div class="border rounded-xl p-4 bg-slate-50">
              <img
                src="../../uploads/ttd/<?= htmlspecialchars($data['ttd_kabag']); ?>"
                alt="TTD Kepala Bagian"
                class="max-h-32 object-contain"
                onerror="this.style.display='none'">
            </div>

            <div class="text-sm text-slate-600">
              <p class="font-semibold text-slate-800">
                Disetujui oleh Kepala Bagian
              </p>
              <p class="text-xs text-slate-500">
                Tanggal persetujuan:
                <?= date('d M Y', strtotime($data['updated_at'] ?? $data['created_at'])); ?>
              </p>
            </div>
          </div>
        </div>
      <?php endif; ?>


      <div class="flex gap-4 justify-end">
        <?php if ($data['status'] === 'Menunggu Admin'): ?>
          <a href="approve.php?id=<?= $data['id']; ?>"
            class="block border border-emerald-600 rounded-xl text-left px-4 py-2 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all duration-300 ease-in-out">
            Setujui
          </a>
          <a href="reject.php?id=<?= $data['id']; ?>"
            class="block text-left rounded-xl bg-red-600 border border-red-600 px-4 py-2 text-white rounded-b-xl hover:bg-white hover:text-red-600 hover:border-red-600 transition-all duration-300 ease-in-out">
            Tolak
          </a>
        <?php endif; ?>
      </div>

    </div>
  </div>

</body>

</html>