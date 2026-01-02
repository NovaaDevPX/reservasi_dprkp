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

$user_id = $_SESSION['id_user'];

/* =====================
   DATA RESERVASI
===================== */
$reservasi = mysqli_query($koneksi, "
  SELECT 
    r.*,
    ru.nama_ruangan,
    ru.kapasitas
  FROM reservasi r
  JOIN ruangan ru ON r.ruangan_id = ru.id
  WHERE r.user_id = $user_id
  ORDER BY r.tanggal DESC, r.jam_mulai DESC
");

/* =====================
   HELPER STATUS
===================== */
function statusBadge($status)
{
  return match ($status) {
    'Disetujui' => 'bg-emerald-100 text-emerald-700',
    'Menunggu Admin' => 'bg-amber-100 text-amber-700',
    'Menunggu Kepala Bagian' => 'bg-sky-100 text-sky-700',
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
  <title>Riwayat Reservasi</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">

    <!-- HEADER -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-800 mb-2">Riwayat Reservasi</h1>
      <p class="text-slate-600">
        Daftar seluruh pengajuan reservasi ruangan yang pernah Anda buat.
      </p>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-2xl shadow overflow-hidden">

      <table class="w-full text-sm">
        <thead class="bg-slate-100 text-slate-600">
          <tr>
            <th class="px-4 py-3 text-left">Tanggal</th>
            <th class="px-4 py-3 text-left">Waktu</th>
            <th class="px-4 py-3 text-left">Ruangan</th>
            <th class="px-4 py-3 text-left">Peserta</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y">

          <?php if (mysqli_num_rows($reservasi) > 0): ?>
            <?php while ($r = mysqli_fetch_assoc($reservasi)): ?>
              <tr class="hover:bg-slate-50">

                <td class="px-4 py-3">
                  <?= date('d M Y', strtotime($r['tanggal'])); ?>
                </td>

                <td class="px-4 py-3">
                  <?= substr($r['jam_mulai'], 0, 5); ?> -
                  <?= substr($r['jam_selesai'], 0, 5); ?>
                </td>

                <td class="px-4 py-3">
                  <div class="font-medium"><?= htmlspecialchars($r['nama_ruangan']); ?></div>
                  <div class="text-xs text-slate-500">
                    Kapasitas <?= $r['kapasitas']; ?> org
                  </div>
                </td>

                <td class="px-4 py-3">
                  <?= $r['jumlah_peserta'] ?? '-'; ?> org
                </td>

                <td class="px-4 py-3">
                  <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?= statusBadge($r['status']); ?>">
                    <?= $r['status']; ?>
                  </span>
                </td>

                <td class="px-4 py-3 text-center">
                  <a href="<?= $baseUrl ?>/pegawai/make-reservation/detail.php?id=<?= $r['id']; ?>"
                    class="text-blue-600 hover:underline text-sm font-medium">
                    Detail
                  </a>
                </td>

              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="px-6 py-10 text-center text-slate-500 italic">
                Belum ada riwayat reservasi.
              </td>
            </tr>
          <?php endif; ?>

        </tbody>
      </table>

    </div>

  </div>

</body>

</html>