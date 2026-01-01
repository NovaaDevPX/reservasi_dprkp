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
   QUERY RUANGAN + FASILITAS
===================== */
$query = mysqli_query($koneksi, "
  SELECT 
    r.*,
    GROUP_CONCAT(f.nama SEPARATOR ', ') AS fasilitas
  FROM ruangan r
  LEFT JOIN ruangan_fasilitas rf ON r.id = rf.ruangan_id
  LEFT JOIN fasilitas f ON rf.fasilitas_id = f.id
  GROUP BY r.id
  ORDER BY r.created_at DESC
");

if (!$query) {
  die("Query error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Data Ruangan | Admin</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100">

  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-6">

    <!-- HEADER -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Manajemen Ruangan</h1>
        <p class="text-sm text-slate-500">Kelola data ruang rapat & aula</p>
      </div>

      <a href="create.php"
        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold shadow transition">
        + Tambah Ruangan
      </a>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-2xl shadow overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
          <tr>
            <th class="px-6 py-4">No</th>
            <th class="px-6 py-4">Nama Ruangan</th>
            <th class="px-6 py-4">Kapasitas</th>
            <th class="px-6 py-4">Fasilitas Default</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-center">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          <?php $no = 1; ?>
          <?php while ($row = mysqli_fetch_assoc($query)): ?>
            <tr class="hover:bg-slate-50">
              <td class="px-6 py-4"><?= $no++; ?></td>

              <td class="px-6 py-4 font-semibold text-slate-800">
                <?= htmlspecialchars($row['nama_ruangan']); ?>
              </td>

              <td class="px-6 py-4">
                <?= $row['kapasitas']; ?> orang
              </td>

              <!-- FASILITAS -->
              <td class="px-6 py-4">
                <?php if ($row['fasilitas']): ?>
                  <?php foreach (explode(', ', $row['fasilitas']) as $f): ?>
                    <span class="inline-block bg-slate-200 text-slate-700 px-2 py-1 rounded-lg text-xs mr-1 mb-1">
                      <?= htmlspecialchars($f); ?>
                    </span>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="text-slate-400 italic">Tidak ada</span>
                <?php endif; ?>
              </td>

              <!-- STATUS -->
              <td class="px-6 py-4">
                <?php
                $statusClass = match ($row['status']) {
                  'Aktif' => 'bg-green-100 text-green-700',
                  'Nonaktif' => 'bg-gray-200 text-gray-700',
                  'Perawatan' => 'bg-yellow-100 text-yellow-700',
                  default => 'bg-slate-100 text-slate-700'
                };
                ?>
                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $statusClass; ?>">
                  <?= $row['status']; ?>
                </span>
              </td>

              <!-- AKSI -->
              <td class="px-6 py-4 text-center space-x-2">
                <a href="edit.php?id=<?= $row['id']; ?>"
                  class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">
                  Edit
                </a>
                <a href="delete.php?id=<?= $row['id']; ?>"
                  onclick="return confirm('Yakin ingin menghapus ruangan ini?')"
                  class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">
                  Hapus
                </a>
              </td>
            </tr>
          <?php endwhile; ?>

          <?php if (mysqli_num_rows($query) === 0): ?>
            <tr>
              <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                Data ruangan belum tersedia.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>

</html>