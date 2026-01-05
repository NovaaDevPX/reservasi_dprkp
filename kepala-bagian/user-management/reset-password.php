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
   PROTEKSI
===================== */
// Tidak boleh reset password diri sendiri
if ($_SESSION['id_user'] == $user['id']) {
  die('Tidak diperbolehkan mereset password akun sendiri.');
}

// Tidak boleh reset password kepala bagian lain
if ($user['role'] === 'kepala_bagian') {
  die('Tidak diperbolehkan mereset password Kepala Bagian.');
}

/* =====================
   GENERATE PASSWORD
===================== */
function generatePassword($length = 8)
{
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
  return substr(str_shuffle($chars), 0, $length);
}

$newPassword = '';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1️⃣ Generate password
  $newPassword = generatePassword(8);

  // 2️⃣ Hash bcrypt (sesuai login kamu)
  $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

  // 3️⃣ Update DB
  $update = mysqli_query($koneksi, "
    UPDATE users SET password = '$hashed'
    WHERE id = {$user['id']}
  ");

  if ($update) {
    $success = 'Password berhasil direset.';
  } else {
    $error = 'Gagal mereset password.';
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Kepala Bagian</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Heroicons untuk ikon -->
  <script src="https://unpkg.com/heroicons@2.0.18/24/outline/index.js" type="module"></script>

  <!-- Custom CSS untuk animasi halus -->
  <style>
    .fade-in {
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .hover-lift {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hover-lift:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen font-sans">

  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8 max-w-full mx-auto fade-in">

    <!-- HEADER -->
    <div class="mb-8">
      <h1 class="text-3xl text-left font-bold text-gray-800 mb-2">
        Reset Password
      </h1>
      <p class="text-gray-600 text-left text-lg">
        Reset password user dan generate password baru secara otomatis.
      </p>
    </div>

    <!-- ALERT -->
    <?php if ($error): ?>
      <div class="mb-6 px-6 py-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 flex items-center gap-3 shadow-sm fade-in">
        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
        <?= $error; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="mb-6 px-6 py-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3 shadow-sm fade-in">
        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <?= $success; ?>
      </div>
    <?php endif; ?>

    <!-- CARD -->
    <div class="bg-white rounded-3xl shadow-xl p-8 space-y-6 hover-lift">

      <!-- User Info -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm text-gray-500">NIP</p>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($user['nip']); ?></p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm text-gray-500">Nama</p>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($user['nama']); ?></p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm text-gray-500">Role</p>
            <p class="font-semibold text-gray-800 capitalize"><?= str_replace('_', ' ', $user['role']); ?></p>
          </div>
        </div>
      </div>

      <?php if ($newPassword): ?>
        <!-- PASSWORD BARU -->
        <div class="mt-6 p-6 bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-2xl shadow-inner">
          <div class="flex items-center gap-3 mb-3">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <p class="text-sm font-medium text-yellow-700">
              Password Baru (simpan & berikan ke user)
            </p>
          </div>
          <div class="flex items-center justify-between bg-white p-4 rounded-xl border">
            <p class="text-2xl font-mono font-bold tracking-widest text-gray-800">
              <?= $newPassword; ?>
            </p>
            <button onclick="navigator.clipboard.writeText('<?= $newPassword; ?>'); alert('Password disalin!');" class="ml-4 px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-sm transition">
              Salin
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- ACTION -->
      <form method="POST" class="flex justify-end gap-4 pt-6 border-t border-gray-100">
        <a href="index.php"
          class="flex items-center gap-2 px-6 py-3 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium transition shadow-sm">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
          Kembali
        </a>

        <button
          onclick="return confirm('Yakin reset password user ini?')"
          class="flex items-center gap-2 px-6 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-medium transition shadow-sm">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Reset Password
        </button>
      </form>

    </div>

  </div>
</body>

</html>