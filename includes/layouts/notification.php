<?php

/**
 * NOTIFICATION HANDLER
 * Pakai via URL:
 * ?success=add | edit | delete
 * ?error=used | custom
 */

$type  = null;
$title = '';
$message = '';

/* =========================
   SUCCESS
========================= */
if (isset($_GET['success'])) {
  $type = 'success';

  switch ($_GET['success']) {
    case 'add':
      $title = 'Berhasil';
      $message = 'Data berhasil ditambahkan.';
      break;
    case 'edit':
      $title = 'Berhasil';
      $message = 'Data berhasil diperbarui.';
      break;
    case 'delete':
      $title = 'Berhasil';
      $message = 'Data berhasil dihapus.';
      break;
    case 'reject':
      $title = 'Berhasil';
      $message = 'Data berhasil ditolak.';
      break;
    default:
      $title = 'Berhasil';
      $message = 'Aksi berhasil dilakukan.';
  }
}

/* =========================
   ERROR
========================= */
if (isset($_GET['error'])) {
  $type = 'error';

  switch ($_GET['error']) {
    case 'used':
      $title = 'Gagal';
      $message = 'Data tidak dapat dihapus karena masih digunakan.';
      break;
    case 'invalid_id':
      $title = 'Berhasil';
      $message = 'Data Tidak berhasil.';
      break;
    default:
      $title = 'Gagal';
      $message = 'Terjadi kesalahan.';
  }
}

/* =========================
   CUSTOM (OPTIONAL)
========================= */
if (isset($_SESSION['notif'])) {
  $type    = $_SESSION['notif']['type'];
  $title   = $_SESSION['notif']['title'];
  $message = $_SESSION['notif']['message'];
  unset($_SESSION['notif']);
}

if (!$type) return;

/* =========================
   COLOR MAP
========================= */
$styles = [
  'success' => 'bg-emerald-50 border-emerald-500 text-emerald-700',
  'error'   => 'bg-red-50 border-red-500 text-red-700',
  'info'    => 'bg-blue-50 border-blue-500 text-blue-700',
  'warning' => 'bg-yellow-50 border-yellow-500 text-yellow-700'
];

$icons = [
  'success' => '✔️',
  'error'   => '❌',
  'info'    => 'ℹ️',
  'warning' => '⚠️'
];
?>

<!-- NOTIFICATION -->
<div id="notification"
  class="fixed top-5 right-5 z-[9999999] w-[360px] animate-slide-in">

  <div class="border-l-4 <?= $styles[$type]; ?> rounded-xl shadow-lg p-4 flex gap-3">

    <div class="text-xl">
      <?= $icons[$type]; ?>
    </div>

    <div class="flex-1">
      <h4 class="font-bold"><?= $title; ?></h4>
      <p class="text-sm"><?= $message; ?></p>
    </div>

    <button onclick="closeNotif()"
      class="text-lg font-bold hover:opacity-70">
      ×
    </button>
  </div>
</div>

<!-- ANIMATION -->
<style>
  @keyframes slide-in {
    from {
      transform: translateX(120%);
      opacity: 0;
    }

    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  .animate-slide-in {
    animation: slide-in .4s ease-out;
  }
</style>

<!-- AUTO CLOSE -->
<script>
  function closeNotif() {
    const notif = document.getElementById('notification');
    if (notif) notif.remove();
  }

  setTimeout(closeNotif, 4000);
</script>