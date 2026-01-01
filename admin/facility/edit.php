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
   VALIDASI ID
===================== */
if (!isset($_GET['id'])) {
  header("Location: index.php");
  exit;
}

$id = (int) $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM fasilitas WHERE id=$id"));

if (!$data) {
  header("Location: index.php");
  exit;
}

$error = '';

/* =====================
   HANDLE UPDATE
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim($_POST['nama']);

  if ($nama === '') {
    $error = 'Nama fasilitas wajib diisi.';
  } else {
    mysqli_query($koneksi, "UPDATE fasilitas SET nama='$nama' WHERE id=$id");
    header("Location: index.php?success=edit");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Edit Fasilitas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100">
  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-6 max-w-xl">

    <h1 class="text-2xl font-bold mb-6">Edit Fasilitas</h1>

    <?php if ($error): ?>
      <div class="mb-4 bg-red-100 text-red-700 px-4 py-2 rounded-lg">
        <?= $error; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-2xl shadow p-6 space-y-5">
      <div>
        <label class="block text-sm font-semibold mb-1">Nama Fasilitas</label>
        <input type="text" name="nama"
          value="<?= htmlspecialchars($data['nama']); ?>"
          class="w-full border rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div class="flex justify-end gap-2">
        <a href="index.php"
          class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 font-semibold">
          Batal
        </a>
        <button type="submit"
          class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold">
          Update
        </button>
      </div>
    </form>

  </div>
</body>

</html>