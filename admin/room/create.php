<?php
session_start();
include '../../config/koneksi.php';
include '../../includes/notification-helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

$fasilitas = mysqli_query($koneksi, "SELECT * FROM fasilitas ORDER BY nama ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nama      = trim($_POST['nama_ruangan']);
  $kapasitas = (int) $_POST['kapasitas'];
  $status    = $_POST['status'];
  $dipilih   = $_POST['fasilitas'] ?? [];

  mysqli_begin_transaction($koneksi);

  try {

    // insert ruangan
    mysqli_query($koneksi, "
      INSERT INTO ruangan (nama_ruangan, kapasitas, status)
      VALUES ('$nama', $kapasitas, '$status')
    ");

    $rid = mysqli_insert_id($koneksi);

    // insert fasilitas ruangan
    foreach ($dipilih as $fid) {
      mysqli_query($koneksi, "
        INSERT INTO ruangan_fasilitas (ruangan_id, fasilitas_id)
        VALUES ($rid, $fid)
      ");
    }

    mysqli_commit($koneksi);

    /* =====================
       KIRIM NOTIFIKASI
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
    die("Gagal menyimpan data");
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
    <!-- HEADER -->
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

      <input name="nama_ruangan" required placeholder="Nama Ruangan"
        class="w-full px-4 py-2 border rounded-xl">

      <input type="number" name="kapasitas" required placeholder="Kapasitas"
        class="w-full px-4 py-2 border rounded-xl">

      <select name="status" class="w-full px-4 py-2 border rounded-xl">
        <option>Aktif</option>
        <option>Nonaktif</option>
        <option>Perawatan</option>
      </select>

      <!-- MULTI SELECT FASILITAS -->
      <div>
        <label class="font-semibold block mb-2">Fasilitas Default</label>

        <!-- SELECTED TAGS -->
        <div id="selected" class="flex flex-wrap gap-2 mb-2"></div>

        <!-- SEARCH DROPDOWN -->
        <div class="relative">
          <input type="text"
            id="search"
            placeholder="Cari & pilih fasilitas..."
            autocomplete="off"
            onfocus="openDropdown()"
            onkeyup="filterFacility()"
            class="w-full px-4 py-2 border rounded-xl">

          <div id="dropdown"
            class="absolute z-20 mt-1 w-full bg-white border rounded-xl shadow max-h-48 overflow-y-auto hidden">

            <?php while ($f = mysqli_fetch_assoc($fasilitas)): ?>
              <div
                data-id="<?= $f['id']; ?>"
                data-name="<?= htmlspecialchars($f['nama']); ?>"
                onclick="selectFacility(this)"
                class="facility-item px-4 py-2 hover:bg-slate-100 cursor-pointer">
                <?= htmlspecialchars($f['nama']); ?>
              </div>
            <?php endwhile; ?>
          </div>
        </div>

        <div id="hiddenInputs"></div>
      </div>

      <button class="px-6 py-2 bg-blue-600 text-white rounded-xl font-semibold">
        Simpan
      </button>

    </form>
  </div>

  <script>
    const selected = {};
    const selectedBox = document.getElementById('selected');
    const dropdown = document.getElementById('dropdown');
    const search = document.getElementById('search');
    const hiddenInputs = document.getElementById('hiddenInputs');

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
        const name = item.innerText.toLowerCase();

        if (selected[id]) {
          item.style.display = 'none';
          return;
        }

        // FILTER NORMAL
        item.style.display = name.includes(q) ? 'block' : 'none';
      });
    }

    function selectFacility(el) {
      const id = el.dataset.id;
      const name = el.dataset.name;

      if (selected[id]) return;

      selected[id] = name;

      // TAG
      selectedBox.innerHTML += `
      <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm flex items-center gap-2">
        ${name}
        <button type="button" onclick="removeFacility(${id})">×</button>
      </span>
    `;

      // HIDDEN INPUT
      hiddenInputs.innerHTML += `
      <input type="hidden" name="fasilitas[]" id="f-${id}" value="${id}">
    `;

      // REMOVE FROM DROPDOWN
      el.style.display = 'none';

      search.value = '';
      dropdown.classList.add('hidden');
    }

    function removeFacility(id) {
      delete selected[id];
      document.getElementById(`f-${id}`).remove();
      renderSelected();

      // SHOW BACK TO DROPDOWN
      document.querySelectorAll('.facility-item').forEach(item => {
        if (item.dataset.id == id) {
          item.style.display = 'block';
        }
      });
    }

    function renderSelected() {
      selectedBox.innerHTML = '';
      Object.entries(selected).forEach(([id, name]) => {
        selectedBox.innerHTML += `
        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm flex items-center gap-2">
          ${name}
          <button type="button" onclick="removeFacility(${id})">×</button>
        </span>
      `;
      });
    }
  </script>

</body>

</html>