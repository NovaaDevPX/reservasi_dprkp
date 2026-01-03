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
    GROUP_CONCAT(
      CONCAT(f.nama, ' (', rf.qty, ')')
      ORDER BY f.nama ASC
      SEPARATOR ', '
    ) AS fasilitas
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Ruangan | Admin</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine.js -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Custom UI -->
  <style>
    .btn-primary {
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #2563eb, #1e40af);
      box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.2);
    }
  </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>
  <?php include '../../includes/layouts/notification.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Data Ruangan</h1>
        <p class="text-slate-600">
          Kelola ruang rapat dan aula beserta fasilitas & statusnya.
        </p>
      </div>

      <a href="create.php"
        class="btn-primary inline-flex items-center gap-2 text-white px-6 py-3 rounded-xl font-semibold transition hover:scale-105">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 4v16m8-8H4" />
        </svg>
        Tambah Ruangan
      </a>
    </div>

    <!-- TABLE CARD -->
    <div class="bg-white rounded-2xl shadow overflow-visible">
      <div class="px-6 py-4 bg-slate-50 border-b">
        <h2 class="text-lg font-semibold text-slate-800">Daftar Ruangan</h2>
      </div>

      <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
          <tr>
            <th class="px-6 py-4 text-center w-16">#</th>
            <th class="px-6 py-4">Nama Ruangan</th>
            <th class="px-6 py-4">Kapasitas</th>
            <th class="px-6 py-4">Fasilitas</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-center w-20">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          <?php if (mysqli_num_rows($query) > 0): ?>
            <?php $no = 1; ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
              <tr class="hover:bg-slate-50 transition">

                <td class="px-6 py-4 text-center font-medium text-slate-600">
                  <?= $no++; ?>
                </td>

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
                      <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 mr-1 mb-1">
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
                    'Aktif' => 'bg-emerald-100 text-emerald-700',
                    'Nonaktif' => 'bg-slate-200 text-slate-600',
                    'Perawatan' => 'bg-amber-100 text-amber-700',
                    default => 'bg-slate-100 text-slate-600'
                  };
                  ?>
                  <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?= $statusClass; ?>">
                    <?= $row['status']; ?>
                  </span>
                </td>

                <!-- AKSI DROPDOWN -->
                <td class="px-6 py-4 text-center relative" x-data="{ open:false }">
                  <button @click="open=!open"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-200">
                    <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        d="M10 3a1.5 1.5 0 110 3a1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3a1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3a1.5 1.5 0 010-3z" />
                    </svg>
                  </button>

                  <div x-show="open" x-cloak @click.outside="open=false" x-transition
                    class="absolute right-2 -mt-3 w-32 bg-white rounded-xl shadow-lg border z-50">
                    <a href="edit.php?id=<?= $row['id']; ?>"
                      class="block text-left px-4 py-2 text-sm hover:bg-slate-100 rounded-t-xl">
                      Edit
                    </a>
                    <a href="delete.php?id=<?= $row['id']; ?>"
                      onclick="return confirm('Yakin ingin menghapus ruangan ini?')"
                      class="block text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-b-xl">
                      Hapus
                    </a>
                  </div>
                </td>

              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                Belum ada data ruangan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>

</html>