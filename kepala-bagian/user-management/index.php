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
$role   = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];

if ($role !== '') {
  $role = mysqli_real_escape_string($koneksi, $role);
  $where[] = "role = '$role'";
}

if ($search !== '') {
  $search = mysqli_real_escape_string($koneksi, $search);
  $where[] = "(nip LIKE '%$search%' OR nama LIKE '%$search%')";
}

$whereSql = '';
if (!empty($where)) {
  $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/* =====================
   PAGINATION
===================== */
$limit = 10;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page  = max($page, 1);
$offset = ($page - 1) * $limit;

/* TOTAL DATA */
$totalQuery = mysqli_query($koneksi, "
  SELECT COUNT(*) as total
  FROM users
  $whereSql
");
$totalData = mysqli_fetch_assoc($totalQuery)['total'];
$totalPage = ceil($totalData / $limit);

/* =====================
   QUERY USERS
===================== */
$query = mysqli_query($koneksi, "
  SELECT id, nip, nama, role, created_at
  FROM users
  $whereSql
  ORDER BY created_at DESC
  LIMIT $limit OFFSET $offset
");

if (!$query) {
  die('Query error: ' . mysqli_error($koneksi));
}

/* PARAMETER UNTUK PAGINATION */
$queryString = $_GET;
unset($queryString['page']);
$baseQuery = http_build_query($queryString);
$baseQuery = $baseQuery ? '&' . $baseQuery : '';
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management | Kepala Bagian</title>

  <?php include __DIR__ . '/../../includes/module.php'; ?>

</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>
  <?php include '../../includes/layouts/notification.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">

    <!-- HEADER -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">
          User Management
        </h1>
        <p class="text-slate-600">
          Kelola akun pengguna berdasarkan peran dan data pegawai.
        </p>
      </div>

      <a href="create.php"
        class="inline-flex items-center gap-2 px-5 py-2.5
               bg-blue-600 hover:bg-blue-700 text-white
               rounded-xl shadow transition">
        <span class="text-lg">＋</span>
        Tambah User
      </a>
    </div>

    <!-- FILTER -->
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
      <input
        type="text"
        name="search"
        placeholder="Cari NIP / Nama"
        value="<?= htmlspecialchars($search); ?>"
        class="border rounded-xl px-4 py-2">

      <select name="role" class="border rounded-xl px-4 py-2">
        <option value="">Semua Role</option>
        <option value="pegawai" <?= ($role === 'pegawai') ? 'selected' : ''; ?>>Pegawai</option>
        <option value="admin" <?= ($role === 'admin') ? 'selected' : ''; ?>>Admin</option>
        <option value="kepala_bagian" <?= ($role === 'kepala_bagian') ? 'selected' : ''; ?>>Kepala Bagian</option>
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
        <h2 class="text-lg font-semibold text-slate-800">
          Daftar Pengguna
        </h2>
      </div>

      <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
          <tr>
            <th class="px-6 py-4 text-center w-14">#</th>
            <th class="px-6 py-4">NIP</th>
            <th class="px-6 py-4">Nama</th>
            <th class="px-6 py-4 text-center">Role</th>
            <th class="px-6 py-4">Dibuat</th>
            <th class="px-6 py-4 text-center w-20">Aksi</th>
          </tr>
        </thead>

        <tbody class="divide-y">
          <?php if (mysqli_num_rows($query) > 0): ?>
            <?php $no = $offset + 1; ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
              <tr class="hover:bg-slate-50 transition">
                <td class="px-6 py-4 text-center"><?= $no++; ?></td>

                <td class="px-6 py-4 font-medium">
                  <?= htmlspecialchars($row['nip']); ?>
                </td>

                <td class="px-6 py-4">
                  <?= htmlspecialchars($row['nama']); ?>
                </td>

                <td class="px-6 py-4 text-center">
                  <?php
                  $roleColor = match ($row['role']) {
                    'admin' => 'bg-indigo-100 text-indigo-700',
                    'kepala_bagian' => 'bg-emerald-100 text-emerald-700',
                    default => 'bg-slate-100 text-slate-700'
                  };
                  ?>
                  <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $roleColor; ?>">
                    <?= ucwords(str_replace('_', ' ', $row['role'])); ?>
                  </span>
                </td>

                <td class="px-6 py-4 text-slate-600">
                  <?= date('d M Y', strtotime($row['created_at'])); ?>
                </td>

                <td class="px-6 py-4 text-center relative" x-data="{ open:false }">
                  <button @click="open=!open"
                    class="w-8 h-8 rounded-lg hover:bg-slate-200">
                    ⋮
                  </button>

                  <div x-show="open" x-cloak @click.outside="open=false"
                    class="absolute right-2 w-44 bg-white rounded-xl shadow border z-50">
                    <a href="edit.php?id=<?= $row['id']; ?>"
                      class="block px-4 py-2 hover:bg-slate-100 rounded-t-xl">
                      Edit User
                    </a>
                    <a href="reset-password.php?id=<?= $row['id']; ?>"
                      class="block px-4 py-2 text-orange-600 hover:bg-orange-50 rounded-b-xl">
                      Reset Password
                    </a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                Data user tidak ditemukan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- PAGINATION -->
      <?php if ($totalPage > 1): ?>
        <div class="px-6 py-4 border-t flex justify-between items-center">
          <span class="text-sm text-slate-600">
            Menampilkan <?= $offset + 1; ?> –
            <?= min($offset + $limit, $totalData); ?>
            dari <?= $totalData; ?> data
          </span>

          <div class="flex gap-1">
            <?php for ($i = 1; $i <= $totalPage; $i++): ?>
              <a href="?page=<?= $i . $baseQuery; ?>"
                class="px-3 py-1 rounded-lg text-sm
                <?= ($i == $page)
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-200 hover:bg-slate-300'; ?>">
                <?= $i; ?>
              </a>
            <?php endfor; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</body>

</html>