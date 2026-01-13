<?php
session_start();
include '../../config/koneksi.php';
include '../../includes/notification-helper.php';

/* =====================
   AUTH PEGAWAI
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pegawai') {
  header("Location: ../../index.php");
  exit;
}

$user_id = $_SESSION['id_user'];

/* =====================
   AJAX: JADWAL
===================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'jadwal') {
  $ruangan_id = (int)$_GET['ruangan_id'];
  $tanggal    = $_GET['tanggal'];

  $q = mysqli_query($koneksi, "
    SELECT jam_mulai, jam_selesai
    FROM reservasi
    WHERE ruangan_id=$ruangan_id
      AND tanggal='$tanggal'
      AND status!='Ditolak'
    ORDER BY jam_mulai
  ");

  $data = [];
  while ($r = mysqli_fetch_assoc($q)) {
    $data[] = $r;
  }

  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

/* =====================
   AJAX: FASILITAS DEFAULT
===================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fasilitas_default') {
  $ruangan_id = (int)$_GET['ruangan_id'];

  $q = mysqli_query($koneksi, "
    SELECT f.nama, rf.qty
    FROM ruangan_fasilitas rf
    JOIN fasilitas f ON rf.fasilitas_id = f.id
    WHERE rf.ruangan_id=$ruangan_id
  ");

  $data = [];
  while ($r = mysqli_fetch_assoc($q)) {
    $data[] = $r;
  }

  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

/* =====================
   DATA
===================== */
$ruangan   = mysqli_query($koneksi, "SELECT * FROM ruangan WHERE status='Aktif'");
$fasilitas = mysqli_query($koneksi, "SELECT * FROM fasilitas ORDER BY nama");

/* =====================
   SUBMIT
===================== */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $ruangan_id  = (int)$_POST['ruangan_id'];
  $tanggal     = $_POST['tanggal'];
  $jam_mulai   = $_POST['jam_mulai'];
  $jam_selesai = $_POST['jam_selesai'];
  $jumlah      = (int)$_POST['jumlah_peserta'];
  $keperluan   = trim($_POST['keperluan']);
  $mode        = $_POST['mode_fasilitas'];

  /* VALIDASI */
  $r = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT kapasitas FROM ruangan WHERE id=$ruangan_id"
  ));

  if ($jumlah > $r['kapasitas']) {
    $error = "Jumlah peserta melebihi kapasitas ruangan ({$r['kapasitas']} orang).";
  }

  if ($jam_mulai >= $jam_selesai) {
    $error = "Jam selesai harus lebih besar dari jam mulai.";
  }

  $cek = mysqli_query($koneksi, "
    SELECT id FROM reservasi
    WHERE ruangan_id=$ruangan_id
      AND tanggal='$tanggal'
      AND status!='Ditolak'
      AND ('$jam_mulai' < jam_selesai AND '$jam_selesai' > jam_mulai)
  ");

  if (mysqli_num_rows($cek) > 0) {
    $error = "Ruangan sudah digunakan pada jam tersebut.";
  }

  if (!$error) {
    mysqli_begin_transaction($koneksi);

    try {
      /* =====================
         INSERT RESERVASI
      ===================== */
      mysqli_query($koneksi, "
        INSERT INTO reservasi
        (user_id, ruangan_id, tanggal, jam_mulai, jam_selesai, keperluan, jumlah_peserta)
        VALUES
        ($user_id, $ruangan_id, '$tanggal', '$jam_mulai', '$jam_selesai', '$keperluan', $jumlah)
      ");

      $reservasi_id = mysqli_insert_id($koneksi);

      /* =====================
         FASILITAS
      ===================== */
      if ($mode === 'default') {

        $df = mysqli_query($koneksi, "
          SELECT fasilitas_id, qty
          FROM ruangan_fasilitas
          WHERE ruangan_id = $ruangan_id
        ");

        while ($f = mysqli_fetch_assoc($df)) {
          mysqli_query($koneksi, "
            INSERT INTO reservasi_fasilitas (reservasi_id, fasilitas_id, qty)
            VALUES ($reservasi_id, {$f['fasilitas_id']}, {$f['qty']})
          ");
        }
      } else {

        if (empty($_POST['fasilitas'])) {
          throw new Exception('Fasilitas custom belum dipilih');
        }

        foreach ($_POST['fasilitas'] as $fid) {
          $fid = (int)$fid;
          $qty = isset($_POST['qty'][$fid]) ? (int)$_POST['qty'][$fid] : 1;

          if ($qty < 1) {
            throw new Exception('Qty fasilitas tidak valid');
          }

          mysqli_query($koneksi, "
            INSERT INTO reservasi_fasilitas (reservasi_id, fasilitas_id, qty)
            VALUES ($reservasi_id, $fid, $qty)
          ");
        }
      }

      /* =====================
         NOTIFIKASI
      ===================== */

      // 1️⃣ Pegawai (pemohon)
      mysqli_query($koneksi, "
        INSERT INTO notifikasi (user_id, reservasi_id, judul, pesan)
        VALUES (
          $user_id,
          $reservasi_id,
          'Reservasi Berhasil Dikirim',
          'Reservasi Anda berhasil dibuat dan sedang menunggu persetujuan admin.'
        )
      ");

      // 2️⃣ Admin
      kirimNotifikasiByRole(
        $koneksi,
        ['admin'],
        'Reservasi Baru',
        'Terdapat pengajuan reservasi baru yang menunggu persetujuan.',
        $reservasi_id
      );

      // 3️⃣ Kepala Bagian (info awal)
      kirimNotifikasiByRole(
        $koneksi,
        ['kepala_bagian'],
        'Reservasi Baru',
        'Terdapat pengajuan reservasi baru dari pegawai.',
        $reservasi_id
      );

      mysqli_commit($koneksi);

      header("Location: /reservasi_dprkp/pegawai/reservation-history/detail.php?id=$reservasi_id&success=add");
      exit;
    } catch (Exception $e) {
      mysqli_rollback($koneksi);
      $error = "Gagal menyimpan data.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Buat Reservasi</title>
  <?php include __DIR__ . '/../../includes/module.php'; ?>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">
  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">
    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Ajukan Reservasi Sekarang !</h1>
        <p class="text-slate-600">
          Pilih ruangan, tentukan jadwal, dan lengkapi dengan fasilitas yang dibutuhkan dalam satu langkah mudah.
        </p>
      </div>
    </div>
    <form method="POST" class="bg-white p-6 rounded-2xl space-y-6">

      <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded"><?= $error ?></div>
      <?php endif; ?>

      <!-- RUANGAN -->
      <div>
        <label class="font-semibold block mb-1">Ruangan</label>
        <select name="ruangan_id" id="ruangan" required class="w-full border rounded-xl px-4 py-2">
          <option value="">-- Pilih Ruangan --</option>
          <?php while ($r = mysqli_fetch_assoc($ruangan)): ?>
            <option value="<?= $r['id'] ?>">
              <?= $r['nama_ruangan'] ?> (<?= $r['kapasitas'] ?> org)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- TANGGAL -->
      <div>
        <label class="font-semibold block mb-1">Tanggal</label>
        <input type="date" name="tanggal" id="tanggal" required class="w-full border rounded-xl px-4 py-2">
      </div>

      <!-- JAM -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="font-semibold block mb-1">Jam Mulai</label>
          <input type="time" name="jam_mulai" required class="w-full border rounded-xl px-4 py-2">
        </div>
        <div>
          <label class="font-semibold block mb-1">Jam Selesai</label>
          <input type="time" name="jam_selesai" required class="w-full border rounded-xl px-4 py-2">
        </div>
      </div>

      <!-- JADWAL -->
      <div>
        <label class="font-semibold block mb-1">Ketersediaan Ruangan</label>
        <div id="jadwal" class="text-sm italic text-slate-600">
          Pilih ruangan & tanggal
        </div>
      </div>

      <!-- PESERTA -->
      <div>
        <label class="font-semibold block mb-1">Jumlah Peserta</label>
        <input type="number" name="jumlah_peserta" required class="w-full border rounded-xl px-4 py-2">
      </div>

      <!-- KEPERLUAN -->
      <div>
        <label class="font-semibold block mb-1">Keperluan</label>
        <textarea name="keperluan" required class="w-full border rounded-xl px-4 py-3"></textarea>
      </div>

      <!-- MODE -->
      <div>
        <label class="font-semibold block mb-2">Fasilitas</label>
        <select name="mode_fasilitas" onchange="toggleMode(this.value)"
          class="w-full border rounded-xl px-4 py-2">
          <option value="default">Gunakan Fasilitas Default</option>
          <option value="custom">Pilih Fasilitas Custom</option>
        </select>
      </div>

      <!-- DEFAULT -->
      <div id="defaultBox" class="bg-slate-50 border rounded-xl p-4">
        <div class="font-semibold text-sm mb-2">Fasilitas Default Ruangan</div>
        <div id="defaultList" class="flex flex-wrap gap-2 text-sm">
          Pilih ruangan terlebih dahulu
        </div>
      </div>

      <!-- CUSTOM -->
      <div id="customBox" class="hidden">
        <label class="font-semibold block mb-2">Fasilitas Custom</label>

        <div class="relative mb-3">
          <input type="text" id="search" placeholder="Cari & pilih fasilitas..."
            autocomplete="off"
            onfocus="openDropdown()" onkeyup="filterFacility()"
            class="w-full px-4 py-2 border rounded-xl">

          <div id="dropdown"
            class="absolute z-20 mt-1 w-full bg-white border rounded-xl shadow max-h-48 overflow-y-auto hidden">
            <?php mysqli_data_seek($fasilitas, 0);
            while ($f = mysqli_fetch_assoc($fasilitas)): ?>
              <div class="facility-item px-4 py-2 hover:bg-slate-100 cursor-pointer"
                data-id="<?= $f['id'] ?>"
                data-name="<?= htmlspecialchars($f['nama']) ?>"
                onclick="addFacility(this)">
                <?= htmlspecialchars($f['nama']) ?>
              </div>
            <?php endwhile; ?>
          </div>
        </div>

        <div id="customList" class="space-y-2"></div>
      </div>

      <button class="bg-blue-600 text-white px-6 py-2 rounded-xl font-semibold">
        Ajukan Reservasi
      </button>

    </form>
  </div>

  <script>
    /* =====================
   ELEMENT
===================== */
    const ruangan = document.getElementById('ruangan');
    const tanggal = document.getElementById('tanggal');
    const jadwal = document.getElementById('jadwal');
    const defaultList = document.getElementById('defaultList');
    const customBox = document.getElementById('customBox');
    const defaultBox = document.getElementById('defaultBox');
    const dropdown = document.getElementById('dropdown');
    const search = document.getElementById('search');
    const customList = document.getElementById('customList');

    /* =====================
       MODE
    ===================== */
    function toggleMode(v) {
      customBox.classList.toggle('hidden', v !== 'custom');
      defaultBox.classList.toggle('hidden', v === 'custom');
    }

    /* =====================
       JADWAL
    ===================== */
    function loadJadwal() {
      if (!ruangan.value || !tanggal.value) return;

      fetch(`?ajax=jadwal&ruangan_id=${ruangan.value}&tanggal=${tanggal.value}`)
        .then(res => res.json())
        .then(data => {
          jadwal.innerHTML = data.length ?
            '<span class="text-red-600">Terpakai:</span><br>' +
            data.map(j => `• ${j.jam_mulai} - ${j.jam_selesai}`).join('<br>') :
            '<span class="text-green-600">🟢 Sepanjang hari tersedia</span>';
        });
    }

    /* =====================
       DEFAULT FASILITAS
    ===================== */
    function loadDefault() {
      if (!ruangan.value) return;

      fetch(`?ajax=fasilitas_default&ruangan_id=${ruangan.value}`)
        .then(res => res.json())
        .then(data => {
          defaultList.innerHTML = data.length ?
            data.map(f =>
              `<span class="px-3 py-1 bg-slate-200 rounded-full">
              ${f.nama} (${f.qty})
            </span>`
            ).join('') :
            '<span class="italic">Tidak ada fasilitas</span>';
        });
    }

    ruangan.addEventListener('change', () => {
      loadJadwal();
      loadDefault();
    });
    tanggal.addEventListener('change', loadJadwal);

    /* =====================
       CUSTOM FASILITAS
    ===================== */
    const selected = {}; // fasilitas yang aktif
    const qtyMemory = {}; // penyimpan qty terakhir

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
      const keyword = search.value.toLowerCase();

      document.querySelectorAll('.facility-item').forEach(item => {
        const id = item.dataset.id;
        const name = item.innerText.toLowerCase();

        if (selected[id]) {
          item.style.display = 'none';
        } else {
          item.style.display = name.includes(keyword) ? 'block' : 'none';
        }
      });
    }

    function addFacility(el) {
      const id = el.dataset.id;
      const name = el.dataset.name;

      if (selected[id]) return;

      selected[id] = true;

      const qty = qtyMemory[id] ?? 1;

      customList.insertAdjacentHTML('beforeend', `
    <div id="row-${id}" class="flex items-center gap-3 border rounded-xl p-3">
      <input type="hidden" name="fasilitas[]" value="${id}">
      <div class="flex-1 font-medium">${name}</div>

      <input type="number"
             name="qty[${id}]"
             value="${qty}"
             min="1"
             onchange="rememberQty(${id}, this.value)"
             class="w-20 border rounded px-2 py-1">

      <button type="button"
              onclick="removeFacility(${id})"
              class="text-red-500 font-bold text-xl leading-none">
        ×
      </button>
    </div>
  `);

      el.style.display = 'none';
      search.value = '';
      dropdown.classList.add('hidden');
    }

    function rememberQty(id, val) {
      qtyMemory[id] = val;
    }

    function removeFacility(id) {
      const row = document.getElementById(`row-${id}`);
      if (!row) return;

      const qtyInput = row.querySelector('input[type=number]');
      if (qtyInput) {
        qtyMemory[id] = qtyInput.value;
      }

      delete selected[id];
      row.remove();

      document.querySelectorAll('.facility-item').forEach(item => {
        if (item.dataset.id === String(id)) {
          item.style.display = 'block';
        }
      });
    }
  </script>

</body>

</html>