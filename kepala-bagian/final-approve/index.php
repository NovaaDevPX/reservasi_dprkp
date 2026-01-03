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

$where = ["r.status = 'Menunggu Kepala Bagian'"];

if ($tanggal !== '') {
  $tanggal = mysqli_real_escape_string($koneksi, $tanggal);
  $where[] = "r.tanggal = '$tanggal'";
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

/* =====================
   QUERY
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
  ORDER BY r.tanggal DESC, r.jam_mulai DESC
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
  <title>Persetujuan Reservasi | Kepala Bagian</title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>
  <?php include '../../includes/layouts/notification.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">

    <!-- HEADER -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-800 mb-2">
        Persetujuan Reservasi
      </h1>
      <p class="text-slate-600">
        Daftar reservasi yang membutuhkan persetujuan Kepala Bagian.
      </p>
    </div>

    <!-- FILTER -->
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
      <input
        type="date"
        name="tanggal"
        value="<?= htmlspecialchars($_GET['tanggal'] ?? '') ?>"
        class="border rounded-xl px-4 py-2">

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
        <h2 class="text-lg font-semibold text-slate-800">
          Daftar Persetujuan
        </h2>
      </div>

      <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
          <tr>
            <th class="px-6 py-4 text-center w-14">#</th>
            <th class="px-6 py-4">Pemohon</th>
            <th class="px-6 py-4">Ruangan</th>
            <th class="px-6 py-4">Tanggal</th>
            <th class="px-6 py-4">Waktu</th>
            <th class="px-6 py-4 text-center">Status</th>
            <th class="px-6 py-4 text-center w-20">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          <?php if (mysqli_num_rows($query) > 0): ?>
            <?php $no = 1; ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>

              <tr class="hover:bg-slate-50 transition">
                <td class="px-6 py-4 text-center"><?= $no++; ?></td>

                <td class="px-6 py-4">
                  <p class="font-semibold"><?= htmlspecialchars($row['nama_user']); ?></p>
                  <p class="text-xs text-slate-500"><?= $row['nip']; ?></p>
                </td>

                <td class="px-6 py-4">
                  <?= htmlspecialchars($row['nama_ruangan']); ?>
                </td>

                <td class="px-6 py-4">
                  <?= date('d M Y', strtotime($row['tanggal'])); ?>
                </td>

                <td class="px-6 py-4">
                  <?= substr($row['jam_mulai'], 0, 5); ?> -
                  <?= substr($row['jam_selesai'], 0, 5); ?>
                </td>

                <td class="px-6 py-4 text-center">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-700">
                    Menunggu Kepala Bagian
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

                    <a href="approve.php?id=<?= $row['id']; ?>"
                      class="block text-left px-4 py-2 text-emerald-600 hover:bg-emerald-50">
                      Setujui
                    </a>

                    <a href="reject.php?id=<?= $row['id']; ?>"
                      class="block text-left px-4 py-2 text-red-600 hover:bg-red-50 rounded-b-xl">
                      Tolak
                    </a>
                  </div>
                </td>
              </tr>

            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                Tidak ada reservasi menunggu persetujuan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</body>

</html>