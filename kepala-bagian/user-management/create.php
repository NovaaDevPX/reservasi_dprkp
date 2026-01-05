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
   GENERATE PASSWORD
===================== */
function generatePassword($length = 8)
{
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
  return substr(str_shuffle($chars), 0, $length);
}

$error = '';
$success = '';
$newPassword = '';

/* =====================
   PROSES SIMPAN
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nip  = trim($_POST['nip']);
  $nama = trim($_POST['nama']);
  $role = $_POST['role'];

  if ($nip === '' || $nama === '' || $role === '') {
    $error = 'Semua field wajib diisi.';
  }

  // Cek NIP unik
  $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE nip='$nip'");
  if (mysqli_num_rows($cek) > 0) {
    $error = 'NIP sudah terdaftar.';
  }

  if ($error === '') {

    // Generate & hash password
    $newPassword = generatePassword(8);
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    $nip  = mysqli_real_escape_string($koneksi, $nip);
    $nama = mysqli_real_escape_string($koneksi, $nama);
    $role = mysqli_real_escape_string($koneksi, $role);

    $insert = mysqli_query($koneksi, "
      INSERT INTO users (nip, nama, password, role)
      VALUES ('$nip', '$nama', '$hashed', '$role')
    ");

    if ($insert) {
      $success = 'User berhasil ditambahkan.';
    } else {
      $error = 'Gagal menambahkan user.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah User | Kepala Bagian</title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8 max-w-full mx-auto">

    <!-- HEADER -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-800 mb-1">
        Tambah User
      </h1>
      <p class="text-slate-600">
        Tambahkan akun pengguna baru ke sistem.
      </p>
    </div>

    <!-- ALERT -->
    <?php if ($error): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-100 text-red-700">
        <?= $error; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-100 text-emerald-700">
        <?= $success; ?>
      </div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" class="bg-white rounded-2xl shadow p-6 space-y-5">

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          NIP
        </label>
        <input
          type="text"
          name="nip"
          required
          value="<?= htmlspecialchars($_POST['nip'] ?? ''); ?>"
          class="w-full border rounded-xl px-4 py-2 focus:ring focus:ring-blue-200">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          Nama
        </label>
        <input
          type="text"
          name="nama"
          required
          value="<?= htmlspecialchars($_POST['nama'] ?? ''); ?>"
          class="w-full border rounded-xl px-4 py-2 focus:ring focus:ring-blue-200">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          Role
        </label>
        <select
          name="role"
          required
          class="w-full border rounded-xl px-4 py-2 focus:ring focus:ring-blue-200">

          <option value="">Pilih Role</option>
          <option value="pegawai" <?= ($_POST['role'] ?? '') === 'pegawai' ? 'selected' : ''; ?>>
            Pegawai
          </option>
          <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>
            Admin
          </option>
          <option value="kepala_bagian" <?= ($_POST['role'] ?? '') === 'kepala_bagian' ? 'selected' : ''; ?>>
            Kepala Bagian
          </option>
        </select>
      </div>

      <?php if ($newPassword): ?>
        <!-- PASSWORD BARU -->
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
          <p class="text-sm text-yellow-700 mb-1">
            Password Awal (simpan & berikan ke user):
          </p>
          <p class="text-xl font-mono font-bold tracking-widest">
            <?= $newPassword; ?>
          </p>
        </div>
      <?php endif; ?>

      <!-- ACTION -->
      <div class="flex justify-end gap-3 pt-4">
        <a href="index.php"
          class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300">
          Kembali
        </a>

        <button
          class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white">
          Simpan User
        </button>
      </div>

    </form>
  </div>

</body>

</html>