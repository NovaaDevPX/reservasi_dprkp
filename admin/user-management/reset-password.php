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
   PROTEKSI
===================== */
if ($_SESSION['id_user'] == $user['id']) {
  die('Tidak diperbolehkan mereset password akun sendiri.');
}

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

  $newPassword = generatePassword(8);
  $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

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

  <?php include __DIR__ . '/../../includes/module.php'; ?>

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

<body class="min-h-screen font-sans bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">

  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="max-w-full p-4 mx-auto main-content sm:p-6 lg:p-8 fade-in">

    <!-- HEADER -->
    <div class="mb-8">
      <h1 class="mb-2 text-3xl font-bold text-gray-800">Reset Password</h1>
      <p class="text-lg text-gray-600">
        Reset password user dan generate password baru secara otomatis.
      </p>
    </div>

    <!-- ALERT ERROR -->
    <?php if ($error): ?>
      <div class="flex items-center gap-3 px-6 py-4 mb-6 text-red-700 border border-red-200 shadow-sm rounded-2xl bg-red-50">
        <i class="text-2xl text-red-500 ph ph-warning-circle"></i>
        <?= $error; ?>
      </div>
    <?php endif; ?>

    <!-- ALERT SUCCESS -->
    <?php if ($success): ?>
      <div class="flex items-center gap-3 px-6 py-4 mb-6 border shadow-sm rounded-2xl bg-emerald-50 border-emerald-200 text-emerald-700">
        <i class="text-2xl ph ph-check-circle text-emerald-500"></i>
        <?= $success; ?>
      </div>
    <?php endif; ?>

    <!-- CARD -->
    <div class="p-8 space-y-6 bg-white shadow-xl rounded-3xl hover-lift">

      <!-- USER INFO -->
      <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
            <i class="text-xl text-blue-600 ph ph-identification-card"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">NIP</p>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($user['nip']); ?></p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full">
            <i class="text-xl text-green-600 ph ph-user"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Nama</p>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($user['nama']); ?></p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-full">
            <i class="text-xl text-purple-600 ph ph-file-text"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Role</p>
            <p class="font-semibold text-gray-800 capitalize">
              <?= str_replace('_', ' ', $user['role']); ?>
            </p>
          </div>
        </div>

      </div>

      <!-- PASSWORD BARU -->
      <?php if ($newPassword): ?>
        <div class="p-6 mt-6 border border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl">
          <div class="flex items-center gap-3 mb-3">
            <i class="text-2xl text-yellow-600 ph ph-lock-key"></i>
            <p class="text-sm font-medium text-yellow-700">
              Password Baru (simpan & berikan ke user)
            </p>
          </div>

          <div class="flex items-center justify-between p-4 bg-white border rounded-xl">
            <p class="font-mono text-2xl font-bold tracking-widest text-gray-800">
              <?= $newPassword; ?>
            </p>
            <button
              onclick="navigator.clipboard.writeText('<?= $newPassword; ?>'); alert('Password disalin!');"
              class="px-3 py-1 ml-4 text-sm text-white transition bg-yellow-500 rounded-lg hover:bg-yellow-600">
              Salin
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- ACTION -->
      <form method="POST" class="flex justify-end gap-4 pt-6 border-t border-gray-100">

        <a href="index.php"
          class="flex items-center gap-2 px-6 py-3 font-medium text-gray-700 transition bg-gray-200 rounded-xl hover:bg-gray-300">
          <i class="text-xl ph ph-arrow-left"></i>
          Kembali
        </a>

        <button
          onclick="return confirm('Yakin reset password user ini?')"
          class="flex items-center gap-2 px-6 py-3 font-medium text-white transition bg-red-600 rounded-xl hover:bg-red-700">
          <i class="text-xl ph ph-arrow-counter-clockwise"></i>
          Reset Password
        </button>

      </form>

    </div>

  </div>
</body>

</html>