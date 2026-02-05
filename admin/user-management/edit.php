<?php
session_start();
include '../../config/koneksi.php';

/* =====================
   AUTH KEPALA BAGIAN
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

/* =====================
   VALIDASI ID
===================== */
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  header("Location: index.php");
  exit;
}

/* =====================
   AMBIL DATA USER
===================== */
$user_q = mysqli_query($koneksi, "
  SELECT id, nip, nama, role
  FROM users
  WHERE id = $id
");

$user = mysqli_fetch_assoc($user_q);

if (!$user) {
  header("Location: index.php");
  exit;
}

/* =====================
   PROSES UPDATE
===================== */
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim($_POST['nama']);
  $role = $_POST['role'];

  if ($nama === '' || $role === '') {
    $error = 'Nama dan role wajib diisi.';
  }

  /* Tidak boleh ubah role diri sendiri */
  if ($_SESSION['id_user'] == $user['id'] && $role !== $user['role']) {
    $error = 'Anda tidak dapat mengubah role akun sendiri.';
  }

  /* Kepala bagian tidak boleh menurunkan admin */
  if ($user['role'] === 'admin' && $role === 'pegawai') {
    $error = 'Role admin tidak boleh diturunkan menjadi pegawai.';
  }

  if ($error === '') {
    $nama = mysqli_real_escape_string($koneksi, $nama);
    $role = mysqli_real_escape_string($koneksi, $role);

    $update = mysqli_query($koneksi, "
      UPDATE users SET
        nama = '$nama',
        role = '$role'
      WHERE id = {$user['id']}
    ");

    if ($update) {
      $success = 'Data user berhasil diperbarui.';
      $user['nama'] = $nama;
      $user['role'] = $role;
    } else {
      $error = 'Gagal memperbarui data.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User | Kepala Bagian</title>

  <?php include __DIR__ . '/../../includes/module.php'; ?>

</head>

<body class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">

  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="max-w-full p-4 mx-auto main-content sm:p-6 lg:p-8">

    <!-- HEADER -->
    <div class="mb-6">
      <h1 class="mb-1 text-2xl font-bold text-slate-800">
        Edit Data User
      </h1>
      <p class="text-slate-600">
        Perbarui data pengguna sistem.
      </p>
    </div>

    <!-- ALERT -->
    <?php if ($error): ?>
      <div class="px-4 py-3 mb-4 text-red-700 bg-red-100 rounded-xl">
        <?= $error; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="px-4 py-3 mb-4 rounded-xl bg-emerald-100 text-emerald-700">
        <?= $success; ?>
      </div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" class="p-6 space-y-5 bg-white shadow rounded-2xl">

      <div>
        <label class="block mb-1 text-sm font-medium text-slate-700">
          NIP
        </label>
        <input
          type="text"
          value="<?= htmlspecialchars($user['nip']); ?>"
          disabled
          class="w-full px-4 py-2 border cursor-not-allowed bg-slate-100 rounded-xl">
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-slate-700">
          Nama
        </label>
        <input
          type="text"
          name="nama"
          required
          value="<?= htmlspecialchars($user['nama']); ?>"
          class="w-full px-4 py-2 border rounded-xl focus:ring focus:ring-blue-200">
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-slate-700">
          Role
        </label>
        <select
          name="role"
          required
          class="w-full px-4 py-2 border rounded-xl focus:ring focus:ring-blue-200">

          <option value="pegawai" <?= $user['role'] === 'pegawai' ? 'selected' : ''; ?>>
            Pegawai
          </option>

          <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>
            Admin
          </option>

          <option value="kepala_bagian" <?= $user['role'] === 'kepala_bagian' ? 'selected' : ''; ?>>
            Kepala Bagian
          </option>
        </select>
      </div>

      <!-- ACTION -->
      <div class="flex justify-end gap-3 pt-4">
        <a href="index.php"
          class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300">
          Kembali
        </a>

        <button
          class="px-5 py-2 text-white bg-blue-600 rounded-xl hover:bg-blue-700">
          Simpan Perubahan
        </button>
      </div>

    </form>
  </div>

</body>

</html>