<?php
session_start();
include '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

$id = (int) ($_GET['id'] ?? 0);

$q = mysqli_query($koneksi, "
  SELECT r.*, u.nama, u.nip, ru.nama_ruangan
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  WHERE r.id = $id
");

$data = mysqli_fetch_assoc($q);
if (!$data) die('Data tidak ditemukan');
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Detail Reservasi</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen">
  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-8">
    <div class="max-w-2xl bg-white rounded-2xl shadow p-6">
      <h1 class="text-2xl font-bold mb-4">Detail Reservasi</h1>

      <dl class="space-y-3 text-sm">
        <div><strong>Pemohon:</strong> <?= $data['nama']; ?> (<?= $data['nip']; ?>)</div>
        <div><strong>Ruangan:</strong> <?= $data['nama_ruangan']; ?></div>
        <div><strong>Tanggal:</strong> <?= date('d M Y', strtotime($data['tanggal'])); ?></div>
        <div><strong>Waktu:</strong> <?= substr($data['jam_mulai'], 0, 5); ?> - <?= substr($data['jam_selesai'], 0, 5); ?></div>
        <div><strong>Peserta:</strong> <?= $data['jumlah_peserta']; ?> orang</div>
        <div><strong>Status:</strong> <?= $data['status']; ?></div>
        <div>
          <strong>Keperluan:</strong>
          <p class="mt-1 text-slate-600"><?= nl2br(htmlspecialchars($data['keperluan'])); ?></p>
        </div>

        <?php if ($data['alasan_tolak']): ?>
          <div class="text-red-600">
            <strong>Alasan Ditolak:</strong>
            <?= nl2br(htmlspecialchars($data['alasan_tolak'])); ?>
          </div>
        <?php endif; ?>
      </dl>

      <div class="mt-6 flex gap-3">
        <?php if ($data['status'] === 'Menunggu Admin'): ?>
          <a href="approve.php?id=<?= $id; ?>"
            class="px-4 py-2 bg-emerald-600 text-white rounded-xl">Setujui</a>
          <a href="reject.php?id=<?= $id; ?>"
            class="px-4 py-2 bg-red-600 text-white rounded-xl">Tolak</a>
        <?php endif; ?>

        <a href="index.php" class="px-4 py-2 bg-slate-200 rounded-xl">Kembali</a>
      </div>
    </div>
  </div>
</body>

</html>