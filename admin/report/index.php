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
   FILTER
===================== */
$tgl_awal  = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';
$status    = $_GET['status'] ?? '';

$where = [];

if ($tgl_awal && $tgl_akhir) {
  $where[] = "r.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir'";
}

if ($status) {
  $where[] = "r.status = '$status'";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/* =====================
   PAGINATION
===================== */
$limit = 15;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page  = $page < 1 ? 1 : $page;
$offset = ($page - 1) * $limit;

/* =====================
   TOTAL DATA
===================== */
$countQuery = mysqli_query($koneksi, "
  SELECT COUNT(*) AS total
  FROM reservasi r
  $whereSQL
");
$totalData  = mysqli_fetch_assoc($countQuery)['total'];
$totalPages = ceil($totalData / $limit);

/* =====================
   QUERY LAPORAN
===================== */
$query = mysqli_query($koneksi, "
  SELECT
    r.id,
    u.nama AS nama_user,
    ru.nama_ruangan,
    r.tanggal,
    r.jam_mulai,
    r.jam_selesai,
    r.jumlah_peserta,
    r.status
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  $whereSQL
  ORDER BY r.id DESC
  LIMIT $limit OFFSET $offset
");

/* =====================
   STATISTIK
===================== */
function countStatus($koneksi, $status)
{
  $q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM reservasi WHERE status='$status'");
  return mysqli_fetch_assoc($q)['total'];
}

$total_reservasi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM reservasi"))['total'];
$disetujui  = countStatus($koneksi, 'Disetujui');
$ditolak    = countStatus($koneksi, 'Ditolak');
$dibatalkan = countStatus($koneksi, 'Dibatalkan');
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Laporan Reservasi | Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <?php include __DIR__ . '/../../includes/module.php'; ?>

  <style>
    .btn-primary {
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    }

    .card-shadow {
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .1);
    }
  </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>
  <?php include '../../includes/layouts/notification.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">

    <!-- HEADER -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-800 mb-2">Laporan Reservasi</h1>
      <p class="text-slate-600">
        Rekap seluruh aktivitas pemesanan ruangan.
      </p>
    </div>

    <!-- STAT CARD -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-2xl p-6 card-shadow">
        <p class="text-slate-500 text-sm">Total Reservasi</p>
        <h2 class="text-3xl font-bold"><?= $total_reservasi ?></h2>
      </div>
      <div class="bg-emerald-50 rounded-2xl p-6 card-shadow">
        <p class="text-emerald-700 text-sm">Disetujui</p>
        <h2 class="text-3xl font-bold"><?= $disetujui ?></h2>
      </div>
      <div class="bg-red-50 rounded-2xl p-6 card-shadow">
        <p class="text-red-700 text-sm">Ditolak</p>
        <h2 class="text-3xl font-bold"><?= $ditolak ?></h2>
      </div>
      <div class="bg-slate-200 rounded-2xl p-6 card-shadow">
        <p class="text-slate-700 text-sm">Dibatalkan</p>
        <h2 class="text-3xl font-bold"><?= $dibatalkan ?></h2>
      </div>
    </div>

    <!-- FILTER -->
    <form method="GET" class="bg-white rounded-2xl p-6 mb-8 card-shadow grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="text-sm font-medium">Tanggal Awal</label>
        <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>" class="w-full mt-1 rounded-lg border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium">Tanggal Akhir</label>
        <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>" class="w-full mt-1 rounded-lg border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium">Status</label>
        <select name="status" class="w-full mt-1 rounded-lg border-slate-300">
          <option value="">Semua</option>
          <?php foreach (['Menunggu Admin', 'Menunggu Kepala Bagian', 'Disetujui', 'Ditolak', 'Dibatalkan'] as $s): ?>
            <option value="<?= $s ?>" <?= $status == $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-end">
        <button class="btn-primary text-white px-6 py-3 rounded-xl font-semibold w-full">
          Terapkan Filter
        </button>
      </div>
    </form>

    <!-- EXPORT PDF -->
    <div class="flex justify-end mb-6">
      <a
        href="export-pdf.php?<?= http_build_query($_GET) ?>"
        target="_blank"
        class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-semibold shadow">
        Export PDF
      </a>
    </div>


    <!-- TABLE -->
    <div class="bg-white rounded-2xl shadow overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-xs uppercase">
          <tr>
            <th class="px-6 py-4">Pegawai</th>
            <th class="px-6 py-4">Ruangan</th>
            <th class="px-6 py-4">Tanggal</th>
            <th class="px-6 py-4">Jam</th>
            <th class="px-6 py-4 text-center">Peserta</th>
            <th class="px-6 py-4 text-center">Status</th>
            <th class="px-6 py-4 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php if (mysqli_num_rows($query) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
              <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($row['nama_user']) ?></td>
                <td class="px-6 py-4"><?= $row['nama_ruangan'] ?></td>
                <td class="px-6 py-4"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                <td class="px-6 py-4"><?= $row['jam_mulai'] ?> - <?= $row['jam_selesai'] ?></td>
                <td class="px-6 py-4 text-center"><?= $row['jumlah_peserta'] ?></td>
                <td class="px-6 py-4 text-center">
                  <span class="px-3 py-1 rounded-full text-xs font-medium
                  <?= match ($row['status']) {
                    'Disetujui' => 'bg-emerald-100 text-emerald-700',
                    'Ditolak' => 'bg-red-100 text-red-700',
                    'Dibatalkan' => 'bg-slate-300 text-slate-700',
                    default => 'bg-yellow-100 text-yellow-700'
                  }; ?>">
                    <?= $row['status'] ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <a
                    href="single-export-pdf.php?id=<?= $row['id'] ?>"
                    target="_blank"
                    class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-xs font-semibold">
                    EXPORT
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="py-10 text-center text-slate-500">
                Tidak ada data laporan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- PAGINATION -->
      <?php if ($totalPages > 1): ?>
        <div class="flex justify-center items-center gap-2 p-6">
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>"
            class="px-4 py-2 rounded-lg border text-sm <?= $page == 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-100' ?>">
            ‹ Prev
          </a>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
              class="px-4 py-2 rounded-lg text-sm font-medium
             <?= $page == $i ? 'bg-blue-600 text-white' : 'border hover:bg-slate-100' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <a href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)])) ?>"
            class="px-4 py-2 rounded-lg border text-sm <?= $page == $totalPages ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-100' ?>">
            Next ›
          </a>
        </div>
      <?php endif; ?>
    </div>

  </div>
</body>

</html>