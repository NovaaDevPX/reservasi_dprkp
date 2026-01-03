<?php
session_start();
include '../../config/koneksi.php';
include '../../includes/notification-helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

/* ======================
   AMBIL DATA FASILITAS
====================== */
$fasilitas = mysqli_query($koneksi, "SELECT * FROM fasilitas ORDER BY nama ASC");

/* ======================
   PROSES SIMPAN
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nama      = trim($_POST['nama_ruangan']);
  $kapasitas = (int) $_POST['kapasitas'];
  $status    = $_POST['status'];
  $fasilitasDipilih = $_POST['fasilitas'] ?? [];

  mysqli_begin_transaction($koneksi);

  try {

    // INSERT RUANGAN
    mysqli_query($koneksi, "
      INSERT INTO ruangan (nama_ruangan, kapasitas, status)
      VALUES ('$nama', $kapasitas, '$status')
    ");

    $ruangan_id = mysqli_insert_id($koneksi);

    // INSERT FASILITAS + QTY
    foreach ($fasilitasDipilih as $fasilitas_id => $qty) {
      $qty = (int)$qty;
      if ($qty < 1) continue;

      mysqli_query($koneksi, "
        INSERT INTO ruangan_fasilitas (ruangan_id, fasilitas_id, qty)
        VALUES ($ruangan_id, $fasilitas_id, $qty)
      ");
    }

    mysqli_commit($koneksi);

    /* =====================
       NOTIFIKASI
    ===================== */
    kirimNotifikasiByRole(
      $koneksi,
      ['admin', 'kepala_bagian'],
      'Ruangan Baru Ditambahkan',
      "Ruangan \"$nama\" telah ditambahkan dengan kapasitas $kapasitas orang."
    );

    header("Location: index.php?success=create");
    exit;
  } catch (Exception $e) {
    mysqli_rollback($koneksi);
    die("Gagal menyimpan data ruangan");
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Tambah Ruangan</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">
  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Tambah Data Ruangan</h1>
        <p class="text-slate-600">
          Kelola ruang rapat dan aula beserta fasilitas & statusnya.
        </p>
      </div>

      <a href="create.php"
        class="btn-primary inline-flex items-center gap-2 text-white px-6 py-3 rounded-xl font-semibold transition hover:scale-105">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 4v16m8-8H4" />
        </svg>
        Tambah Ruangan
      </a>
    </div>

    <form method="POST" class="bg-white p-6 rounded-2xl shadow space-y-6">

      <!-- NAMA -->
      <input
        name="nama_ruangan"
        required
        placeholder="Nama Ruangan"
        class="w-full px-4 py-2 border rounded-xl">

      <!-- KAPASITAS -->
      <input
        type="number"
        name="kapasitas"
        min="1"
        required
        placeholder="Kapasitas"
        class="w-full px-4 py-2 border rounded-xl">

      <!-- STATUS -->
      <select name="status" class="w-full px-4 py-2 border rounded-xl">
        <option value="Aktif">Aktif</option>
        <option value="Nonaktif">Nonaktif</option>
        <option value="Perawatan">Perawatan</option>
      </select>

      <!-- FASILITAS -->
      <div>
        <label class="font-semibold block mb-2">
          Fasilitas Ruangan
        </label>

        <!-- TAG TERPILIH -->
        <div id="selected" class="flex flex-wrap gap-2 mb-2"></div>

        <!-- SEARCH -->
        <div class="relative">
          <input
            type="text"
            id="search"
            placeholder="Cari fasilitas..."
            onfocus="openDropdown()"
            onkeyup="filterFacility()"
            autocomplete="off"
            class="w-full px-4 py-2 border rounded-xl">

          <div
            id="dropdown"
            class="absolute z-20 mt-1 w-full bg-white border rounded-xl shadow max-h-48 overflow-y-auto hidden">

            <?php while ($f = mysqli_fetch_assoc($fasilitas)): ?>
              <div
                class="facility-item px-4 py-2 hover:bg-slate-100 cursor-pointer"
                data-id="<?= $f['id']; ?>"
                data-name="<?= htmlspecialchars($f['nama']); ?>"
                onclick="selectFacility(this)">
                <?= htmlspecialchars($f['nama']); ?>
              </div>
            <?php endwhile; ?>

          </div>
        </div>
      </div>

      <button class="px-6 py-2 bg-blue-600 text-white rounded-xl font-semibold">
        Simpan Ruangan
      </button>

    </form>
  </div>

  <script>
    const selected = {};
    const selectedBox = document.getElementById('selected');
    const dropdown = document.getElementById('dropdown');
    const search = document.getElementById('search');

    function openDropdown() {
      dropdown.classList.remove('hidden');
      filterFacility();
    }

    document.addEventListener('click', e => {
      if (!e.target.closest('.relative')) {
        dropdown.classList.add('hidden');
      }
    });

    function filterFacility() {
      const q = search.value.toLowerCase();
      document.querySelectorAll('.facility-item').forEach(item => {
        const id = item.dataset.id;
        const name = item.dataset.name.toLowerCase();
        item.style.display =
          (!selected[id] && name.includes(q)) ? 'block' : 'none';
      });
    }

    function selectFacility(el) {
      const id = el.dataset.id;
      const name = el.dataset.name;

      if (selected[id]) return;
      selected[id] = true;

      selectedBox.innerHTML += `
    <div id="sf-${id}"
  class="flex items-center gap-3 bg-white border border-slate-200
         px-4 py-2 rounded-2xl shadow-sm
         hover:shadow transition">

  <!-- NAMA FASILITAS -->
  <span class="font-medium text-slate-700 whitespace-nowrap">
    ${name}
  </span>

  <span>-</span>

  <!-- QTY -->
  <div class="flex items-center gap-2">
    <input
      type="number"
      min="1"
      value="1"
      name="fasilitas[${id}]"
      class="w-16 px-2 py-1 text-sm text-center
             border border-slate-300 rounded-lg
             focus:ring-2 focus:ring-blue-500 focus:outline-none">
  </div>

  <!-- REMOVE -->
  <button
    type="button"
    onclick="removeFacility(${id})"
    title="Hapus fasilitas"
    class="ml-auto text-slate-400 hover:text-red-500
           text-lg font-bold transition">
    ×
  </button>
</div>

  `;

      el.style.display = 'none';
      search.value = '';
      dropdown.classList.add('hidden');
    }

    function removeFacility(id) {
      delete selected[id];
      document.getElementById(`sf-${id}`).remove();

      document.querySelectorAll('.facility-item').forEach(item => {
        if (item.dataset.id == id) {
          item.style.display = 'block';
        }
      });
    }
  </script>

</body>

</html>