<?php
session_start();
include '../../config/koneksi.php';

/* =====================
   AUTH KEPALA BAGIAN
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepala_bagian') {
  header("Location: ../../index.php");
  exit;
}

/* =====================
   FILTER
===================== */
$tanggal = $_GET['tanggal'] ?? '';
$status  = $_GET['status'] ?? '';

$where = [];

if ($tanggal !== '') {
  $tanggal = mysqli_real_escape_string($koneksi, $tanggal);
  $where[] = "r.tanggal = '$tanggal'";
}

if ($status !== '') {
  $status = mysqli_real_escape_string($koneksi, $status);
  $where[] = "r.status = '$status'";
}

$whereSql = '';
if (!empty($where)) {
  $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/* =====================
   PAGINATION
===================== */
$limit = 15;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

/* =====================
   TOTAL DATA
===================== */
$countQuery = mysqli_query($koneksi, "
  SELECT COUNT(*) AS total
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  $whereSql
");

$totalData  = mysqli_fetch_assoc($countQuery)['total'];
$totalPages = ceil($totalData / $limit);


/* =====================
   QUERY RESERVASI
===================== */
$query = mysqli_query($koneksi, "
  SELECT 
    r.*,
    u.nama AS nama_user,
    u.nip,
    ru.nama_ruangan
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  $whereSql
  ORDER BY r.created_at DESC
  LIMIT $limit OFFSET $offset
");

if (!$query) {
  die('Query error: ' . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Reservasi | Kepala Bagian</title>

  <?php include __DIR__ . '/../../includes/module.php'; ?>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>
  <?php include '../../includes/layouts/notification.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">

    <!-- HEADER -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-800 mb-2">Data Reservasi</h1>
      <p class="text-slate-600">
        Kelola permintaan pemesanan ruangan dari pegawai.
      </p>
    </div>

    <!-- FILTER -->
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
      <input
        type="date"
        name="tanggal"
        value="<?= htmlspecialchars($_GET['tanggal'] ?? '') ?>"
        class="border rounded-xl px-4 py-2">

      <select name="status" class="border rounded-xl px-4 py-2">
        <option value="">Semua Status</option>
        <?php
        $statuses = [
          'Menunggu Admin',
          'Menunggu Kepala Bagian',
          'Disetujui',
          'Ditolak',
          'Dibatalkan'
        ];
        foreach ($statuses as $s):
        ?>
          <option value="<?= $s; ?>"
            <?= (($_GET['status'] ?? '') === $s) ? 'selected' : ''; ?>>
            <?= $s; ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button
        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl">
        Filter
      </button>

      <?php if (!empty($_GET)): ?>
        <a href="index.php"
          class="px-5 py-2 bg-slate-200 hover:bg-slate-300 rounded-xl">
          Reset
        </a>
      <?php endif; ?>
    </form>

    <!-- TABLE -->
    <div class="bg-white rounded-2xl shadow overflow-visible">
      <div class="px-6 py-4 bg-slate-50 border-b">
        <h2 class="text-lg font-semibold text-slate-800">Daftar Reservasi</h2>
      </div>

      <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
          <tr>
            <th class="px-6 py-4 text-center w-14">#</th>
            <th class="px-6 py-4">Pemohon</th>
            <th class="px-6 py-4">Ruangan</th>
            <th class="px-6 py-4">Tanggal</th>
            <th class="px-6 py-4">Waktu</th>
            <th class="px-6 py-4">Keperluan</th>
            <th class="px-6 py-4 text-center">Status</th>
            <th class="px-6 py-4 text-center w-20">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          <?php if (mysqli_num_rows($query) > 0): ?>
            <?php $no = $offset + 1; ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>

              <?php
              $statusClass = match ($row['status']) {
                'Disetujui' => 'bg-emerald-100 text-emerald-700',
                'Menunggu Admin' => 'bg-amber-100 text-amber-700',
                'Menunggu Kepala Bagian' => 'bg-sky-100 text-sky-700',
                'Ditolak' => 'bg-red-100 text-red-700',
                'Dibatalkan' => 'bg-slate-200 text-slate-600',
                default => 'bg-slate-100 text-slate-600'
              };
              ?>

              <tr class="hover:bg-slate-50 transition">
                <td class="px-6 py-4 text-center"><?= $no++; ?></td>

                <td class="px-6 py-4">
                  <p class="font-semibold"><?= htmlspecialchars($row['nama_user']); ?></p>
                  <p class="text-xs text-slate-500"><?= $row['nip']; ?></p>
                </td>

                <td class="px-6 py-4"><?= htmlspecialchars($row['nama_ruangan']); ?></td>

                <td class="px-6 py-4">
                  <?= date('d M Y', strtotime($row['tanggal'])); ?>
                </td>

                <td class="px-6 py-4">
                  <?= substr($row['jam_mulai'], 0, 5); ?> -
                  <?= substr($row['jam_selesai'], 0, 5); ?>
                </td>

                <td class="px-6 py-4 max-w-xs truncate">
                  <?= htmlspecialchars($row['keperluan']); ?>
                </td>

                <td class="px-6 py-4 text-center">
                  <span class="px-3 py-1 whitespace-nowrap rounded-full text-xs font-semibold <?= $statusClass; ?>">
                    <?= $row['status']; ?>
                  </span>
                </td>

                <!-- AKSI -->
                <td class="px-6 py-4 text-center relative" x-data="{ open:false }">
                  <button @click="open=!open"
                    class="w-8 h-8 rounded-lg hover:bg-slate-200">
                    ⋮
                  </button>

                  <div x-show="open" x-cloak @click.outside="open=false"
                    class="absolute right-2 w-40 bg-white rounded-xl shadow border z-50">
                    <a href="detail.php?id=<?= $row['id']; ?>"
                      class="block text-left px-4 py-2 hover:bg-slate-100 rounded-t-xl">
                      Detail
                    </a>

                    <?php if ($row['status'] === 'Menunggu Kepala Bagian'): ?>
                      <a href="<?php echo $baseUrl; ?>/kepala-bagian/final-approve/approve.php?id=<?= $row['id']; ?>"
                        class="block text-left px-4 py-2 text-emerald-600 hover:bg-emerald-50">
                        Setujui
                      </a>
                      <a href="<?php echo $baseUrl; ?>/kepala-bagian/final-approve/reject.php?id=<?= $row['id']; ?>"
                        class="block text-left px-4 py-2 text-red-600 hover:bg-red-50 rounded-b-xl">
                        Tolak
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>

            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="px-6 py-10 text-center text-slate-500">
                Data reservasi tidak ditemukan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>

      </table>
      <!-- PAGINATION (ELLIPSIS) -->
      <?php if ($totalPages > 1): ?>
        <div class="flex justify-center items-center gap-2 p-6 text-sm">

          <!-- PREV -->
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>"
            class="px-3 py-2 rounded-lg border
      <?= $page == 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-100' ?>">
            ‹ Prev
          </a>

          <?php
          $range = 2;
          $start = max(2, $page - $range);
          $end   = min($totalPages - 1, $page + $range);
          ?>

          <!-- PAGE 1 -->
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>"
            class="px-3 py-2 rounded-lg
      <?= $page == 1 ? 'bg-blue-600 text-white' : 'border hover:bg-slate-100' ?>">
            1
          </a>

          <!-- ELLIPSIS KIRI -->
          <?php if ($start > 2): ?>
            <span class="px-2 text-slate-400">…</span>
          <?php endif; ?>

          <!-- PAGE TENGAH -->
          <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
              class="px-3 py-2 rounded-lg
        <?= $page == $i ? 'bg-blue-600 text-white' : 'border hover:bg-slate-100' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <!-- ELLIPSIS KANAN -->
          <?php if ($end < $totalPages - 1): ?>
            <span class="px-2 text-slate-400">…</span>
          <?php endif; ?>

          <!-- PAGE TERAKHIR -->
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>"
            class="px-3 py-2 rounded-lg
      <?= $page == $totalPages ? 'bg-blue-600 text-white' : 'border hover:bg-slate-100' ?>">
            <?= $totalPages ?>
          </a>

          <!-- NEXT -->
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)])) ?>"
            class="px-3 py-2 rounded-lg border
      <?= $page == $totalPages ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-100' ?>">
            Next ›
          </a>

        </div>
      <?php endif; ?>

    </div>

  </div>

</body>

</html>