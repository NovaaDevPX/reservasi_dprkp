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
   QUERY FASILITAS
===================== */
$query = mysqli_query($koneksi, "
  SELECT f.id, f.nama, COUNT(rf.ruangan_id) AS total_ruangan
  FROM fasilitas f
  LEFT JOIN ruangan_fasilitas rf ON f.id = rf.fasilitas_id
  GROUP BY f.id
  ORDER BY f.nama ASC
");

if (!$query) {
  die("Query error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Master Fasilitas | Admin</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100">

  <?php include '../../includes/layouts/sidebar.php'; ?>
  <?php include '../../includes/layouts/notification.php'; ?>


  <div class="main-content p-6">

    <!-- HEADER -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Master Fasilitas</h1>
        <p class="text-sm text-slate-500">
          Kelola daftar fasilitas ruangan & aula
        </p>
      </div>

      <a href="create.php"
        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold shadow transition">
        + Tambah Fasilitas
      </a>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-2xl shadow overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
          <tr>
            <th class="px-6 py-4">No</th>
            <th class="px-6 py-4">Nama Fasilitas</th>
            <th class="px-6 py-4">Digunakan di Ruangan</th>
            <th class="px-6 py-4 text-center">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          <?php $no = 1; ?>
          <?php while ($row = mysqli_fetch_assoc($query)): ?>
            <tr class="hover:bg-slate-50">
              <td class="px-6 py-4"><?= $no++; ?></td>

              <td class="px-6 py-4 font-semibold text-slate-800">
                <?= htmlspecialchars($row['nama']); ?>
              </td>

              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full
                <?= $row['total_ruangan'] > 0
                  ? 'bg-green-100 text-green-700'
                  : 'bg-slate-200 text-slate-600'; ?>
                text-xs font-semibold">
                  <?= $row['total_ruangan']; ?> ruangan
                </span>
              </td>

              <!-- AKSI -->
              <td class="px-6 py-4 text-center space-x-2">
                <a href="edit.php?id=<?= $row['id']; ?>"
                  class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">
                  Edit
                </a>

                <a href="delete.php?id=<?= $row['id']; ?>"
                  onclick="return confirm('Yakin ingin menghapus fasilitas ini?')"
                  class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">
                  Hapus
                </a>
              </td>
            </tr>
          <?php endwhile; ?>

          <?php if (mysqli_num_rows($query) === 0): ?>
            <tr>
              <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                Belum ada data fasilitas.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>

</html>