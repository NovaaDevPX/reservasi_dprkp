<?php
session_start();
include '../../config/koneksi.php';

/* =====================
   AUTH PEGAWAI
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
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
  <title>Detail Reservasi | Pegawai</title>
  <?php include __DIR__ . '/../../includes/module.php'; ?>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">

  <?php include '../../includes/layouts/sidebar.php'; ?>
  <?php include '../../includes/layouts/notification.php'; ?>

  <div class="max-w-5xl p-4 mx-auto main-content sm:p-6 lg:p-8">

    <!-- HEADER -->
    <div class="flex flex-col gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">

      <div>
        <h1 class="mb-2 text-3xl font-bold text-slate-800">Detail Reservasi</h1>
        <a href="index.php" class="text-sm text-blue-600 hover:underline">
          ← Kembali ke daftar
        </a>
      </div>

      <!-- BUTTON EXPORT PDF -->
      <a
        href="<?php echo $baseUrl; ?>/pegawai/export/single-export-pdf.php?id=<?= $data['id']; ?>"
        target="_blank"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white transition bg-red-600 shadow rounded-xl hover:bg-red-700">

        <!-- ICON -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 4v12m0 0l-3-3m3 3l3-3M6 20h12" />
        </svg>

        Export PDF
      </a>

    </div>

    <!-- CARD -->
    <div class="p-6 space-y-8 bg-white shadow rounded-2xl">

      <!-- INFO UTAMA -->
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

        <div>
          <p class="text-sm text-slate-500">Pemohon</p>
          <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nama_user']); ?></p>
          <p class="text-xs text-slate-500"><?= $data['nip']; ?></p>
        </div>

        <div>
          <p class="text-sm text-slate-500">Status</p>

          <div class="flex items-center gap-3 mt-1">
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?= $statusClass; ?>">
              <?= $data['status']; ?>
            </span>

            <?php if ($data['status'] == 'Menunggu Kepala Bagian' || $data['status'] == 'Menunggu Admin'): ?>
              <form
                action="batalkan.php"
                method="POST"
                onsubmit="return confirm('Yakin ingin membatalkan reservasi ini?');">
                <input type="hidden" name="id" value="<?= $data['id']; ?>">

                <button
                  type="submit"
                  class="px-3 py-1 text-xs font-semibold text-white transition bg-red-600 rounded-lg hover:bg-red-700">
                  Batalkan
                </button>
              </form>
            <?php endif; ?>
          </div>
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
        <p class="mb-1 text-sm text-slate-500">Keperluan</p>
        <div class="p-4 border bg-slate-50 rounded-xl text-slate-700">
          <?= nl2br(htmlspecialchars($data['keperluan'])); ?>
        </div>
      </div>

      <!-- FASILITAS RUANGAN -->
      <div>
        <h3 class="mb-3 font-semibold text-slate-800">Fasilitas Ruangan</h3>
        <div class="flex flex-wrap gap-2">
          <?php if (mysqli_num_rows($fasilitas_ruangan) > 0): ?>
            <?php while ($f = mysqli_fetch_assoc($fasilitas_ruangan)): ?>
              <span class="px-3 py-1 text-sm rounded-full bg-slate-100">
                <?= htmlspecialchars($f['nama']); ?> (<?= $f['qty']; ?>)
              </span>
            <?php endwhile; ?>
          <?php else: ?>
            <span class="italic text-slate-500">Tidak ada fasilitas</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- FASILITAS DIPESAN -->
      <div>
        <h3 class="mb-3 font-semibold text-slate-800">Fasilitas Digunakan</h3>
        <div class="flex flex-wrap gap-2">
          <?php if (mysqli_num_rows($fasilitas_reservasi) > 0): ?>
            <?php while ($f = mysqli_fetch_assoc($fasilitas_reservasi)): ?>
              <span class="px-3 py-1 text-sm text-blue-700 bg-blue-100 rounded-full">
                <?= htmlspecialchars($f['nama']); ?> (<?= $f['qty']; ?>)
              </span>
            <?php endwhile; ?>
          <?php else: ?>
            <span class="italic text-slate-500">Tidak ada fasilitas khusus</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- SURAT PENGANTAR -->
      <?php if (!empty($data['surat_pengantar'])): ?>
        <div>
          <p class="mb-2 text-sm text-slate-500">Surat Pengantar</p>

          <div class="flex items-center gap-3 p-4 border bg-slate-50 rounded-xl">
            <!-- ICON -->
            <svg xmlns="http://www.w3.org/2000/svg"
              class="w-6 h-6 text-red-600"
              fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 4v12m0 0l-3-3m3 3l3-3M6 20h12" />
            </svg>

            <div class="flex-1">
              <p class="text-sm font-semibold text-slate-800">
                <?= htmlspecialchars($data['surat_pengantar']); ?>
              </p>
              <p class="text-xs text-slate-500">Dokumen pendukung reservasi</p>
            </div>

            <a
              href="../../uploads/surat/<?= urlencode($data['surat_pengantar']); ?>"
              target="_blank"
              class="px-3 py-1 text-sm font-semibold text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
              Lihat
            </a>
          </div>
        </div>
      <?php else: ?>
        <div>
          <p class="mb-1 text-sm text-slate-500">Surat Pengantar</p>
          <p class="italic text-slate-400">Tidak ada surat pengantar</p>
        </div>
      <?php endif; ?>

      <!-- ALASAN TOLAK -->
      <?php if ($data['status'] === 'Ditolak' && $data['alasan_tolak']): ?>
        <div>
          <h3 class="mb-2 font-semibold text-red-700">Alasan Penolakan</h3>
          <div class="p-4 text-red-700 border border-red-200 bg-red-50 rounded-xl">
            <?= nl2br(htmlspecialchars($data['alasan_tolak'])); ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- TANDA TANGAN KEPALA BAGIAN -->
      <?php if ($data['status'] === 'Disetujui' && !empty($data['ttd_kabag'])): ?>
        <div class="pt-6 border-t">
          <h3 class="mb-3 font-semibold text-slate-800">
            Tanda Tangan Kepala Bagian
          </h3>

          <div class="flex items-center gap-6">
            <div class="p-4 border rounded-xl bg-slate-50">
              <img
                src="../../uploads/ttd/<?= htmlspecialchars($data['ttd_kabag']); ?>"
                alt="TTD Kepala Bagian"
                class="object-contain max-h-32"
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

    </div>
  </div>

</body>

</html>