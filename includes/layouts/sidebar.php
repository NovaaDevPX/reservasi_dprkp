<?php
require_once __DIR__ . '/../base-url.php';

$nama = $_SESSION['nama'] ?? 'User';
$role = $_SESSION['role'] ?? 'pegawai';

/**
 * Active menu berdasarkan URL
 * contoh: active('/admin/room')
 */
$currentUri = $_SERVER['REQUEST_URI'];

function active($path, $currentUri)
{
  return str_contains($currentUri, $path)
    ? 'bg-blue-600 text-white'
    : 'text-slate-300 hover:bg-slate-800 hover:text-white';
}
?>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<aside class="fixed left-0 top-0 h-screen w-64 bg-gradient-to-b from-slate-900 to-slate-950 text-white shadow-xl">

  <!-- HEADER -->
  <div class="px-6 py-6 border-b border-white/10">
    <h1 class="text-lg font-bold tracking-wide">Reservasi DPRKP</h1>
    <p class="text-sm text-slate-400"><?= ucwords(str_replace('_', ' ', $role)); ?> Panel</p>
  </div>

  <!-- MENU -->
  <nav class="px-4 py-6 space-y-2">

    <!-- DASHBOARD -->
    <a href="<?= $baseUrl ?>/<?= $role ?>/dashboard.php"
      class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= active('/dashboard.php', $currentUri); ?>">
      <!-- Home -->
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M3 9.75L12 3l9 6.75V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z" />
      </svg>
      <span>Dashboard</span>
    </a>

    <!-- ================= PEGAWAI ================= -->
    <?php if ($role === 'pegawai'): ?>
      <a href="<?= $baseUrl ?>/pegawai/make-reservation/index.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= active('/pegawai/make-reservation', $currentUri); ?>">
        <!-- Plus -->
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        <span>Ajukan Reservasi</span>
      </a>

      <a href="<?= $baseUrl ?>/pegawai/reservation-history/index.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= active('/pegawai/reservation-history', $currentUri); ?>">
        <!-- Clock -->
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 6v6l4 2M12 22a10 10 0 100-20 10 10 0 000 20z" />
        </svg>
        <span>Riwayat Reservasi</span>
      </a>
    <?php endif; ?>

    <!-- ================= ADMIN ================= -->
    <?php if ($role === 'admin'): ?>

      <!-- Reservasi -->
      <a href="<?= $baseUrl ?>/admin/reservation/index.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= active('/admin/reservation', $currentUri); ?>">
        <!-- Calendar -->
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span>Data Reservasi</span>
      </a>

      <!-- Ruangan -->
      <a href="<?= $baseUrl ?>/admin/room/index.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= active('/admin/room', $currentUri); ?>">
        <!-- Office Building -->
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m-1 4h1m4-4h1m-1 4h1" />
        </svg>
        <span>Data Ruangan</span>
      </a>

      <!-- Fasilitas -->
      <a href="<?= $baseUrl ?>/admin/facility/index.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= active('/admin/facility', $currentUri); ?>">
        <!-- Squares -->
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M4 6h6v6H4zM14 6h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
        </svg>
        <span>Data Fasilitas</span>
      </a>

      <!-- Users -->
      <a href="<?= $baseUrl ?>/admin/users.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= active('/admin/users', $currentUri); ?>">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1" />
          <circle cx="9" cy="7" r="4" />
          <circle cx="17" cy="7" r="4" />
        </svg>
        <span>Data User</span>
      </a>

    <?php endif; ?>
  </nav>

  <!-- FOOTER -->
  <div class="absolute bottom-0 w-full px-4 pb-6">
    <div class="flex items-center gap-3 px-4 py-3 mb-4 bg-slate-800 rounded-xl">
      <div class="h-9 w-9 flex items-center justify-center rounded-full bg-blue-600 font-bold">
        <?= strtoupper(substr($nama, 0, 1)); ?>
      </div>
      <div>
        <p class="text-sm font-semibold"><?= $nama; ?></p>
        <p class="text-xs text-slate-400"><?= $role; ?></p>
      </div>
    </div>

    <a href="<?= $baseUrl ?>/logout.php"
      class="block text-center bg-red-600 hover:bg-red-700 transition text-white py-2.5 rounded-xl font-semibold">
      Logout
    </a>
  </div>
</aside>

<style>
  .main-content {
    margin-left: 16rem;
  }
</style>