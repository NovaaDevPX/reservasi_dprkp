<?php
session_start();
include '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php");
  exit;
}

$id = (int) $_GET['id'];

/* ======================
   DATA RUANGAN
====================== */
$ruanganQ = mysqli_query($koneksi, "SELECT * FROM ruangan WHERE id = $id");
$ruangan  = mysqli_fetch_assoc($ruanganQ);

if (!$ruangan) {
  header("Location: index.php");
  exit;
}

/* ======================
   FASILITAS TERPILIH
====================== */
$selectedFasilitas = [];
$qSelected = mysqli_query($koneksi, "
  SELECT f.id, f.nama
  FROM ruangan_fasilitas rf
  JOIN fasilitas f ON rf.fasilitas_id = f.id
  WHERE rf.ruangan_id = $id
");

while ($f = mysqli_fetch_assoc($qSelected)) {
  $selectedFasilitas[$f['id']] = $f['nama'];
}

/* ======================
   SEMUA FASILITAS
====================== */
$fasilitas = mysqli_query($koneksi, "SELECT * FROM fasilitas ORDER BY nama ASC");

/* ======================
   UPDATE
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nama      = $_POST['nama_ruangan'];
  $kapasitas = (int) $_POST['kapasitas'];
  $status    = $_POST['status'];
  $dipilih   = $_POST['fasilitas'] ?? [];

  mysqli_begin_transaction($koneksi);
  try {

    mysqli_query($koneksi, "
      UPDATE ruangan SET
        nama_ruangan = '$nama',
        kapasitas    = $kapasitas,
        status       = '$status'
      WHERE id = $id
    ");

    // reset fasilitas
    mysqli_query($koneksi, "
      DELETE FROM ruangan_fasilitas WHERE ruangan_id = $id
    ");

    foreach ($dipilih as $fid) {
      mysqli_query($koneksi, "
        INSERT INTO ruangan_fasilitas (ruangan_id, fasilitas_id)
        VALUES ($id, $fid)
      ");
    }

    mysqli_commit($koneksi);
    header("Location: index.php?success=update");
    exit;
  } catch (Exception $e) {
    mysqli_rollback($koneksi);
    die("Gagal update data");
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Edit Ruangan</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100">
  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-6 max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Edit Ruangan</h1>

    <form method="POST" class="bg-white p-6 rounded-2xl shadow space-y-6">

      <input
        name="nama_ruangan"
        value="<?= htmlspecialchars($ruangan['nama_ruangan']); ?>"
        required
        class="w-full px-4 py-2 border rounded-xl">

      <input
        type="number"
        name="kapasitas"
        value="<?= $ruangan['kapasitas']; ?>"
        required
        class="w-full px-4 py-2 border rounded-xl">

      <select name="status" class="w-full px-4 py-2 border rounded-xl">
        <?php foreach (['Aktif', 'Nonaktif', 'Perawatan'] as $s): ?>
          <option <?= $ruangan['status'] === $s ? 'selected' : ''; ?>>
            <?= $s; ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- MULTI SELECT FASILITAS -->
      <div>
        <label class="font-semibold block mb-2">Fasilitas Default</label>

        <!-- SELECTED -->
        <div id="selected" class="flex flex-wrap gap-2 mb-2"></div>

        <!-- DROPDOWN -->
        <div class="relative">
          <input
            type="text"
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
        Update
      </button>

    </form>
  </div>

  <script>
    const selected = <?= json_encode($selectedFasilitas); ?>;
    const selectedBox = document.getElementById('selected');
    const dropdown = document.getElementById('dropdown');
    const search = document.getElementById('search');
    const hiddenInputs = document.getElementById('hiddenInputs');

    /* ======================
       INIT SELECTED
    ====================== */
    Object.entries(selected).forEach(([id, name]) => {
      renderTag(id, name);
      hiddenInputs.innerHTML += `<input type="hidden" name="fasilitas[]" id="f-${id}" value="${id}">`;
    });

    /* ======================
       UI FUNCTIONS
    ====================== */
    function openDropdown() {
      dropdown.classList.remove('hidden');
      filterFacility();
    }

    document.addEventListener('click', e => {
      if (!e.target.closest('.relative')) dropdown.classList.add('hidden');
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

        item.style.display = name.includes(q) ? 'block' : 'none';
      });
    }

    function selectFacility(el) {
      const id = el.dataset.id;
      const name = el.dataset.name;

      if (selected[id]) return;

      selected[id] = name;
      renderTag(id, name);

      hiddenInputs.innerHTML += `<input type="hidden" name="fasilitas[]" id="f-${id}" value="${id}">`;

      el.style.display = 'none';
      search.value = '';
      dropdown.classList.add('hidden');
    }

    function removeFacility(id) {
      delete selected[id];
      document.getElementById(`f-${id}`).remove();
      renderSelected();

      document.querySelectorAll('.facility-item').forEach(item => {
        if (item.dataset.id == id) item.style.display = 'block';
      });
    }

    function renderTag(id, name) {
      selectedBox.innerHTML += `
    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm flex items-center gap-2">
      ${name}
      <button type="button" onclick="removeFacility(${id})">×</button>
    </span>
  `;
    }

    function renderSelected() {
      selectedBox.innerHTML = '';
      Object.entries(selected).forEach(([id, name]) => renderTag(id, name));
    }
  </script>

</body>

</html>