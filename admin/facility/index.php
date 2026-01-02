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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Fasilitas | Admin</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine.js -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Custom Styles for Enhanced UI -->
  <style>
    .table-hover tr:hover {
      background-color: #f8fafc;
      transform: translateY(-1px);
      transition: all 0.2s ease;
    }

    .btn-primary {
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #2563eb, #1e40af);
      box-shadow: 0 6px 8px -1px rgba(59, 130, 246, 0.2);
    }

    .card-shadow {
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Data Fasilitas</h1>
        <p class="text-slate-600 text-base">
          Kelola fasilitas yang tersedia pada ruangan & aula dengan mudah dan efisien.
        </p>
      </div>

      <a href="create.php"
        class="btn-primary inline-flex items-center gap-2 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 hover:scale-105">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>Tambah Fasilitas</span>
      </a>
    </div>

    <!-- TABLE CARD -->
    <div class="bg-white rounded-2xl shadow overflow-visible">
      <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Daftar Fasilitas</h2>
      </div>
      <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700 uppercase text-xs font-medium">
          <tr>
            <th class="px-6 py-4 text-center w-16">#</th>
            <th class="px-6 py-4 text-left">Nama Fasilitas</th>
            <th class="px-6 py-4 text-left">Digunakan di</th>
            <th class="px-6 py-4 text-center w-20">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          <?php if (mysqli_num_rows($query) > 0): ?>
            <?php $no = 1; ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
              <tr class="transition-all duration-200">
                <td class="px-6 py-4 text-center font-medium text-slate-600">
                  <?= $no++; ?>
                </td>

                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                      <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2L3 7v11a1 1 0 001 1h12a1 1 0 001-1V7l-7-5z" clip-rule="evenodd"></path>
                      </svg>
                    </div>
                    <span class="font-semibold text-slate-800">
                      <?= htmlspecialchars($row['nama']); ?>
                    </span>
                  </div>
                </td>

                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                      <?= $row['total_ruangan'] > 0
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-slate-200 text-slate-600'; ?>">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <?= $row['total_ruangan']; ?> ruangan
                  </span>
                </td>

                <td class="px-6 py-4 text-center relative" x-data="{ open: false }">

                  <button @click="open = !open"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-slate-200 transition">
                    <svg class="w-5 h-5 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M10 3a1.5 1.5 0 110 3a1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3a1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3a1.5 1.5 0 010-3z" />
                    </svg>
                  </button>

                  <div
                    x-show="open"
                    x-cloak
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-2 -mt-3 w-32 bg-white rounded-xl shadow-lg border z-50 origin-top-right">

                    <a href="edit.php?id=<?= $row['id']; ?>"
                      class="block text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-t-xl">
                      Edit
                    </a>

                    <a href="delete.php?id=<?= $row['id']; ?>"
                      onclick="return confirm('Yakin ingin menghapus fasilitas ini?')"
                      class="block text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-b-xl">
                      Hapus
                    </a>
                  </div>

                </td>

              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center gap-3">
                  <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m8-5v2m0 0v2m0-2h2m-2 0h-2"></path>
                  </svg>
                  <p class="text-slate-500 text-base">Belum ada data fasilitas.</p>
                  <p class="text-slate-400 text-sm">Tambahkan fasilitas baru untuk memulai.</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>

</html>