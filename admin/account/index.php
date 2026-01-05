<?php
session_start();
include '../../config/koneksi.php';

/* =====================
   AUTH ADMIN
===================== */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

$user_id = $_SESSION['id_user'];
$success = '';
$error   = '';

/* =====================
   AMBIL DATA USER
===================== */
$userQuery = mysqli_query($koneksi, "
  SELECT nip, nama, role, created_at
  FROM users
  WHERE id = '$user_id'
  LIMIT 1
");

$user = mysqli_fetch_assoc($userQuery);

/* =====================
   SUBMIT GANTI PASSWORD
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password         = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';

  if ($password === '' || $password_confirm === '') {
    $error = 'Password wajib diisi.';
  } elseif (strlen($password) < 6) {
    $error = 'Password minimal 6 karakter.';
  } elseif ($password !== $password_confirm) {
    $error = 'Konfirmasi password tidak cocok.';
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $update = mysqli_query($koneksi, "
      UPDATE users 
      SET password = '$hash'
      WHERE id = '$user_id'
    ");

    if ($update) {
      $success = 'Password berhasil diperbarui.';
    } else {
      $error = 'Gagal memperbarui password.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Akun Saya | Admin</title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Icon -->
  <link href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css" rel="stylesheet">
</head>

<body class="bg-gradient-to-br from-slate-100 via-white to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>
  <?php include '../../includes/layouts/notification.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-10 max-w-full">

    <!-- HEADER -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-800 mb-2">
        Akun Saya
      </h1>
      <p class="text-slate-500">
        Kelola informasi akun dan keamanan password Anda.
      </p>
    </div>

    <!-- ALERT -->
    <?php if ($success): ?>
      <div class="mb-6 px-5 py-4 rounded-2xl bg-green-50 border border-green-200 text-green-700">
        <?= $success; ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="mb-6 px-5 py-4 rounded-2xl bg-red-50 border border-red-200 text-red-700">
        <?= $error; ?>
      </div>
    <?php endif; ?>

    <!-- =====================
         PROFIL / DATA DIRI
    ====================== -->
    <div class="bg-white/80 backdrop-blur rounded-3xl shadow-sm border border-slate-200 p-6 mb-8">

      <!-- HEADER CARD -->
      <div class="flex items-center gap-4 mb-6">
        <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
          <i class="ph ph-user text-2xl text-blue-600"></i>
        </div>

        <div>
          <h2 class="text-lg font-semibold text-slate-800">
            Data Diri
          </h2>
          <p class="text-sm text-slate-500">
            Informasi dasar akun pegawai
          </p>
        </div>
      </div>

      <!-- DATA -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
        <div>
          <p class="text-slate-500 mb-1">NIP</p>
          <p class="font-semibold text-slate-800">
            <?= htmlspecialchars($user['nip']); ?>
          </p>
        </div>

        <div>
          <p class="text-slate-500 mb-1">Nama Lengkap</p>
          <p class="font-semibold text-slate-800">
            <?= htmlspecialchars($user['nama']); ?>
          </p>
        </div>

        <div>
          <p class="text-slate-500 mb-1">Role</p>
          <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full
            bg-blue-100 text-blue-700 text-xs font-semibold">
            <i class="ph ph-shield-check"></i>
            <?= ucfirst(str_replace('_', ' ', $user['role'])); ?>
          </span>
        </div>

        <div>
          <p class="text-slate-500 mb-1">Akun Dibuat</p>
          <p class="font-semibold text-slate-800">
            <?= date('d M Y', strtotime($user['created_at'])); ?>
          </p>
        </div>
      </div>
    </div>

    <!-- =====================
         GANTI PASSWORD
    ====================== -->
    <form method="POST"
      class="bg-white/80 backdrop-blur rounded-3xl shadow-sm border border-slate-200 p-6 space-y-6">

      <!-- HEADER -->
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
          <i class="ph ph-lock-key text-xl text-red-600"></i>
        </div>

        <div>
          <h2 class="text-lg font-semibold text-slate-800">
            Keamanan Akun
          </h2>
          <p class="text-sm text-slate-500">
            Perbarui password untuk menjaga keamanan akun
          </p>
        </div>
      </div>

      <!-- INPUT -->
      <div>
        <label class="block mb-1 text-sm font-medium text-slate-700">
          Password Baru
        </label>
        <input
          type="password"
          name="password"
          required
          class="w-full border border-slate-300 rounded-xl px-4 py-2.5
                 focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
          placeholder="Minimal 6 karakter">
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-slate-700">
          Konfirmasi Password
        </label>
        <input
          type="password"
          name="password_confirm"
          required
          class="w-full border border-slate-300 rounded-xl px-4 py-2.5
                 focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
          placeholder="Ulangi password">
      </div>

      <!-- ACTION -->
      <div class="flex justify-end pt-4">
        <button
          type="submit"
          class="inline-flex items-center gap-2 px-6 py-2.5
                 bg-blue-600 hover:bg-blue-700 text-white
                 rounded-xl shadow transition">
          <i class="ph ph-floppy-disk"></i>
          Simpan Password
        </button>
      </div>
    </form>

  </div>
</body>

</html>