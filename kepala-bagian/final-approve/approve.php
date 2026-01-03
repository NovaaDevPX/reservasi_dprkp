<?php
session_start();
include '../../config/koneksi.php';

/* =====================
   AUTH
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepala_bagian') {
  header("Location: ../../index.php");
  exit;
}

$id = (int)($_GET['id'] ?? 0);
$kabag_id = $_SESSION['id_user'] ?? 0;

/* =====================
   DATA RESERVASI
===================== */
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
  SELECT r.*, u.nama, ru.nama_ruangan
  FROM reservasi r
  JOIN users u ON r.user_id = u.id
  JOIN ruangan ru ON r.ruangan_id = ru.id
  WHERE r.id = $id
"));

if (!$data) {
  die('Data tidak ditemukan');
}

/* =====================
   SUBMIT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $ttdFileName = null;

  /* UPLOAD FOTO */
  if ($_POST['metode_ttd'] === 'upload') {
    if (!empty($_FILES['ttd_file']['name'])) {
      $ext = pathinfo($_FILES['ttd_file']['name'], PATHINFO_EXTENSION);
      $ttdFileName = 'ttd_kabag_' . $kabag_id . '_' . time() . '.' . $ext;
      move_uploaded_file(
        $_FILES['ttd_file']['tmp_name'],
        "../../uploads/ttd/" . $ttdFileName
      );
    }
  }

  /* CANVAS */
  if ($_POST['metode_ttd'] === 'canvas') {
    if (!empty($_POST['ttd_canvas'])) {
      $img = str_replace('data:image/png;base64,', '', $_POST['ttd_canvas']);
      $img = base64_decode($img);
      $ttdFileName = 'ttd_kabag_' . $kabag_id . '_' . time() . '.png';
      file_put_contents("../../uploads/ttd/" . $ttdFileName, $img);
    }
  }

  if (!$ttdFileName) {
    die('TTD wajib diisi');
  }

  mysqli_query($koneksi, "
    UPDATE reservasi SET
      status = 'Disetujui',
      kabag_id = $kabag_id,
      ttd_kabag = '$ttdFileName'
    WHERE id = $id
  ");

  header("Location: index.php?success=approve");
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setujui Reservasi</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .card-shadow {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .gradient-bg {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .btn-hover {
      transition: all 0.3s ease;
    }

    .btn-hover:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .radio-custom {
      appearance: none;
      width: 20px;
      height: 20px;
      border: 2px solid #d1d5db;
      border-radius: 50%;
      position: relative;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .radio-custom:checked {
      background-color: #3b82f6;
      border-color: #3b82f6;
    }

    .radio-custom:checked::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 8px;
      height: 8px;
      background-color: white;
      border-radius: 50%;
      transform: translate(-50%, -50%);
    }
  </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">
    <div class="max-w-full mx-auto bg-white rounded-2xl shadow-lg card-shadow p-8 space-y-8">

      <!-- HEADER -->
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
          <i class="fas fa-check-circle text-green-600 mr-2"></i>Setujui Reservasi
        </h1>
        <p class="text-gray-600">Konfirmasi persetujuan reservasi dengan tanda tangan Anda.</p>
      </div>

      <!-- INFO RESERVASI -->
      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border-l-4 border-blue-500">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
          <i class="fas fa-info-circle text-blue-600 mr-2"></i>Detail Reservasi
        </h2>
        <div class="space-y-2 text-sm">
          <p><span class="font-medium text-gray-700">Pemohon:</span> <span class="text-gray-900"><?= htmlspecialchars($data['nama']) ?></span></p>
          <p><span class="font-medium text-gray-700">Ruangan:</span> <span class="text-gray-900"><?= htmlspecialchars($data['nama_ruangan']) ?></span></p>
          <p><span class="font-medium text-gray-700">Tanggal:</span> <span class="text-gray-900"><?= date('d F Y', strtotime($data['tanggal'])) ?></span></p>
          <p><span class="font-medium text-gray-700">Waktu:</span> <span class="text-gray-900"><?= htmlspecialchars($data['jam_mulai']) ?> - <?= htmlspecialchars($data['jam_selesai']) ?></span></p>
          <p><span class="font-medium text-gray-700">Keperluan:</span> <span class="text-gray-900"><?= htmlspecialchars($data['keperluan']) ?></span></p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" class="space-y-8">

        <!-- METODE TTD -->
        <div>
          <label class="font-semibold text-gray-800 block mb-4 text-lg">
            <i class="fas fa-signature text-indigo-600 mr-2"></i>Metode Tanda Tangan
          </label>
          <div class="flex flex-col sm:flex-row gap-6">
            <label class="flex items-center gap-3 cursor-pointer p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
              <input type="radio" name="metode_ttd" value="upload" class="radio-custom" checked>
              <div>
                <span class="font-medium text-gray-700">Upload Foto</span>
                <p class="text-sm text-gray-500">Unggah gambar tanda tangan Anda</p>
              </div>
            </label>
            <label class="flex items-center gap-3 cursor-pointer p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
              <input type="radio" name="metode_ttd" value="canvas" class="radio-custom">
              <div>
                <span class="font-medium text-gray-700">Tanda Tangan Langsung</span>
                <p class="text-sm text-gray-500">Buat tanda tangan di sini</p>
              </div>
            </label>
          </div>
        </div>

        <!-- UPLOAD BOX -->
        <div id="uploadBox" class="space-y-4">
          <label class="block text-sm font-medium text-gray-700">
            <i class="fas fa-upload text-gray-600 mr-2"></i>Pilih File Tanda Tangan
          </label>
          <input type="file" name="ttd_file" accept="image/*"
            class="w-full border-2 border-dashed border-gray-300 rounded-xl px-4 py-6 text-center hover:border-blue-400 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500">
          <p class="text-xs text-gray-500">Format: JPG, PNG, atau GIF. Maksimal 5MB.</p>
        </div>

        <!-- CANVAS BOX -->
        <div id="canvasBox" class="hidden space-y-4">
          <label class="block text-sm font-medium text-gray-700">
            <i class="fas fa-pen text-gray-600 mr-2"></i>Tanda Tangan Langsung
          </label>
          <p class="text-sm text-gray-600 mb-2">Gambar tanda tangan Anda di area bawah ini:</p>

          <div class="border-2 border-gray-300 rounded-xl overflow-hidden bg-white">
            <canvas id="signature-pad" class="bg-white block w-full" height="200"></canvas>
          </div>

          <button type="button" id="clear"
            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition btn-hover">
            <i class="fas fa-trash mr-2"></i>Hapus TTD
          </button>

          <input type="hidden" name="ttd_canvas" id="ttd_canvas">
        </div>

        <!-- BUTTONS -->
        <div class="flex flex-col sm:flex-row gap-4">
          <button type="submit" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white py-3 rounded-xl font-semibold btn-hover shadow-md">
            <i class="fas fa-check mr-2"></i>Setujui Reservasi
          </button>
          <a href="index.php" class="flex-1 bg-gray-300 text-gray-700 text-center py-3 rounded-xl font-semibold hover:bg-gray-400 transition btn-hover">
            <i class="fas fa-times mr-2"></i>Batal
          </a>
        </div>

      </form>
    </div>
  </div>

  <!-- =====================
     SCRIPT CANVAS (FIX)
===================== -->
  <script>
    const uploadBox = document.getElementById('uploadBox');
    const canvasBox = document.getElementById('canvasBox');
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');

    let drawing = false;

    function resizeCanvas() {
      const rect = canvas.getBoundingClientRect();
      canvas.width = rect.width;
      canvas.height = 200; // FIX tinggi pasti
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.strokeStyle = '#000';
    }

    // === TOGGLE METODE ===
    document.querySelectorAll('input[name="metode_ttd"]').forEach(radio => {
      radio.addEventListener('change', e => {
        const isCanvas = e.target.value === 'canvas';

        uploadBox.classList.toggle('hidden', isCanvas);
        canvasBox.classList.toggle('hidden', !isCanvas);

        // 🔥 INI KUNCI NYA
        if (isCanvas) {
          setTimeout(resizeCanvas, 50);
        }
      });
    });

    // === POSISI ===
    function getPos(e) {
      const r = canvas.getBoundingClientRect();
      const touch = e.touches ? e.touches[0] : e;
      return {
        x: touch.clientX - r.left,
        y: touch.clientY - r.top
      };
    }

    function start(e) {
      e.preventDefault();
      drawing = true;
      const p = getPos(e);
      ctx.beginPath();
      ctx.moveTo(p.x, p.y);
    }

    function draw(e) {
      if (!drawing) return;
      e.preventDefault();
      const p = getPos(e);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
    }

    function stop() {
      drawing = false;
    }

    // === MOUSE ===
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stop);
    canvas.addEventListener('mouseleave', stop);

    // === TOUCH (HP) ===
    canvas.addEventListener('touchstart', start, {
      passive: false
    });
    canvas.addEventListener('touchmove', draw, {
      passive: false
    });
    canvas.addEventListener('touchend', stop);

    // === CLEAR ===
    document.getElementById('clear').onclick = () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
    };

    // === SUBMIT ===
    document.querySelector('form').addEventListener('submit', () => {
      if (document.querySelector('input[value="canvas"]').checked) {
        document.getElementById('ttd_canvas').value = canvas.toDataURL('image/png');
      }
    });
  </script>

</body>

</html>