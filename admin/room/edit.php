<?php
session_start();
include '../../config/koneksi.php';
include '../../includes/notification-helper.php';

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
$qRuangan = mysqli_query($koneksi, "SELECT * FROM ruangan WHERE id = $id");
$ruangan  = mysqli_fetch_assoc($qRuangan);
if (!$ruangan) {
  header("Location: index.php");
  exit;
}

/* ======================
   FASILITAS LAMA + QTY
====================== */
$selectedFasilitas = [];
$qSelected = mysqli_query($koneksi, "
  SELECT f.id, f.nama, rf.qty
  FROM ruangan_fasilitas rf
  JOIN fasilitas f ON rf.fasilitas_id = f.id
  WHERE rf.ruangan_id = $id
");
while ($f = mysqli_fetch_assoc($qSelected)) {
  $selectedFasilitas[$f['id']] = [
    'nama' => $f['nama'],
    'qty'  => $f['qty']
  ];
}

/* ======================
   SEMUA FASILITAS
====================== */
$fasilitas = mysqli_query($koneksi, "SELECT * FROM fasilitas ORDER BY nama ASC");

/* ======================
   UPDATE
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $nama      = trim($_POST['nama_ruangan']);
  $kapasitas = (int) $_POST['kapasitas'];
  $status    = $_POST['status'];
  $fasilitasDipilih = $_POST['fasilitas'] ?? [];

  $perubahan = [];

  /* ========= RUANGAN ========= */
  if ($nama !== $ruangan['nama_ruangan']) {
    $perubahan[] = "Nama ruangan: \"{$ruangan['nama_ruangan']}\" → \"$nama\"";
  }
  if ($kapasitas != $ruangan['kapasitas']) {
    $perubahan[] = "Kapasitas: {$ruangan['kapasitas']} → $kapasitas orang";
  }
  if ($status !== $ruangan['status']) {
    $perubahan[] = "Status: {$ruangan['status']} → $status";
  }

  /* ========= FASILITAS ========= */
  $perubahanFasilitas = [];

  // Fasilitas dihapus / qty berubah
  foreach ($selectedFasilitas as $fid => $lama) {
    if (!isset($fasilitasDipilih[$fid])) {
      $perubahanFasilitas[] = "❌ {$lama['nama']} dihapus";
    } elseif ((int)$fasilitasDipilih[$fid] !== (int)$lama['qty']) {
      $perubahanFasilitas[] =
        "🔄 {$lama['nama']} qty {$lama['qty']} → {$fasilitasDipilih[$fid]}";
    }
  }

  // Fasilitas ditambahkan
  foreach ($fasilitasDipilih as $fid => $qty) {
    if (!isset($selectedFasilitas[$fid])) {
      $qNama = mysqli_query($koneksi, "SELECT nama FROM fasilitas WHERE id = $fid");
      $fn = mysqli_fetch_assoc($qNama);
      $perubahanFasilitas[] =
        "➕ {$fn['nama']} ditambahkan (qty $qty)";
    }
  }

  if (!empty($perubahanFasilitas)) {
    $perubahan[] = "Fasilitas:\n- " . implode("\n- ", $perubahanFasilitas);
  }

  mysqli_begin_transaction($koneksi);
  try {

    /* UPDATE RUANGAN */
    mysqli_query($koneksi, "
      UPDATE ruangan SET
        nama_ruangan = '$nama',
        kapasitas    = $kapasitas,
        status       = '$status'
      WHERE id = $id
    ");

    /* RESET & INSERT FASILITAS */
    mysqli_query($koneksi, "DELETE FROM ruangan_fasilitas WHERE ruangan_id = $id");

    foreach ($fasilitasDipilih as $fid => $qty) {
      $qty = (int)$qty;
      if ($qty < 1) continue;

      mysqli_query($koneksi, "
        INSERT INTO ruangan_fasilitas (ruangan_id, fasilitas_id, qty)
        VALUES ($id, $fid, $qty)
      ");
    }

    mysqli_commit($koneksi);

    /* ======================
       NOTIFIKASI FINAL
    ====================== */
    $pesan = "Data ruangan \"$nama\" telah diperbarui.\n\n ";
    if (!empty($perubahan)) {
      $pesan .= "Perubahan yang dilakukan:\n- " . implode("\n- ", $perubahan);
    } else {
      $pesan .= "Tidak ada perubahan data.";
    }

    kirimNotifikasiByRole(
      $koneksi,
      ['admin', 'kepala_bagian'],
      'Perubahan Data Ruangan',
      $pesan
    );

    header("Location: index.php?success=update");
    exit;
  } catch (Exception $e) {
    mysqli_rollback($koneksi);
    die("Gagal update data ruangan");
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Edit Ruangan</title>
  <?php include __DIR__ . '/../../includes/module.php'; ?>
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
          <option value="<?= $s; ?>" <?= $ruangan['status'] === $s ? 'selected' : ''; ?>>
            <?= $s; ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- FASILITAS -->
      <div>
        <label class="font-semibold block mb-2">Fasilitas Ruangan</label>

        <div id="selected" class="flex flex-wrap gap-3 mb-3"></div>

        <div class="relative">
          <input
            type="text"
            id="search"
            placeholder="Cari fasilitas..."
            onfocus="openDropdown()"
            onkeyup="filterFacility()"
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
        Update Ruangan
      </button>

    </form>
  </div>

  <script>
    const selected = <?= json_encode($selectedFasilitas); ?>;
    const selectedBox = document.getElementById('selected');
    const dropdown = document.getElementById('dropdown');
    const search = document.getElementById('search');

    /* INIT */
    Object.entries(selected).forEach(([id, data]) => {
      renderCard(id, data.nama, data.qty);
    });

    /* UI */
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
        const name = item.dataset.name.toLowerCase();
        item.style.display =
          (!selected[id] && name.includes(q)) ? 'block' : 'none';
      });
    }

    function selectFacility(el) {
      const id = el.dataset.id;
      const name = el.dataset.name;

      if (selected[id]) return;

      selected[id] = {
        nama: name,
        qty: 1
      };
      renderCard(id, name, 1);

      el.style.display = 'none';
      search.value = '';
      dropdown.classList.add('hidden');
    }

    function removeFacility(id) {
      delete selected[id];
      document.getElementById(`sf-${id}`).remove();

      document.querySelectorAll('.facility-item').forEach(item => {
        if (item.dataset.id == id) item.style.display = 'block';
      });
    }

    function renderCard(id, name, qty) {
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
    <input type="number"
          min="1"
          value="${qty}"
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
  `;
    }
  </script>

</body>

</html>