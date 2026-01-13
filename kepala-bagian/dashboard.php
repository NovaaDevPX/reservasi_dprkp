<?php
session_start();
include '../config/koneksi.php';

/* =====================
   AUTH
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepala_bagian') {
  header("Location: ../index.php");
  exit;
}

/* =====================
   NOTIFIKASI KEPALA BAGIAN
===================== */
$kabag_id = $_SESSION['id_user'];

$notif_unread = mysqli_fetch_assoc(mysqli_query($koneksi, "
  SELECT COUNT(*) total
  FROM notifikasi
  WHERE user_id = $kabag_id
    AND is_read = 0
"))['total'];

$notif_q = mysqli_query($koneksi, "
  SELECT id, judul, pesan, is_read, created_at, reservasi_id
  FROM notifikasi
  WHERE user_id = $kabag_id
  ORDER BY is_read ASC, created_at DESC
  LIMIT 10
");


/* =====================
   FILTER
===================== */
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? '';

$whereTanggal = "YEAR(r.tanggal) = '$tahun'";
if ($bulan !== '') {
  $whereTanggal .= " AND MONTH(r.tanggal) = '$bulan'";
}

/* =====================
   CHART 1 - TREN
===================== */
$labels = [];
$data = [];
$detailRuangan = [];

if ($bulan == '') {
  $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  $values = array_fill(1, 12, 0);
  $detailRuangan = array_fill(1, 12, []);

  $q = mysqli_query($koneksi, "
    SELECT 
      MONTH(r.tanggal) bln,
      ru.nama_ruangan,
      COUNT(*) total
    FROM reservasi r
    JOIN ruangan ru ON ru.id = r.ruangan_id
    WHERE $whereTanggal
      AND r.status <> 'Ditolak'
    GROUP BY bln, ru.id
  ");

  while ($row = mysqli_fetch_assoc($q)) {
    $b = (int)$row['bln'];
    $values[$b] += $row['total'];
    $detailRuangan[$b][] = $row['nama_ruangan'] . ' (' . $row['total'] . ')';
  }

  $data = array_values($values);
  $detailRuangan = array_values($detailRuangan);
} else {
  $days = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
  $values = [];
  $detailRuangan = [];

  for ($i = 1; $i <= $days; $i++) {
    $labels[] = $i;
    $values[$i] = 0;
    $detailRuangan[$i] = [];
  }

  $q = mysqli_query($koneksi, "
    SELECT 
      DAY(r.tanggal) tgl,
      ru.nama_ruangan,
      COUNT(*) total
    FROM reservasi r
    JOIN ruangan ru ON ru.id = r.ruangan_id
    WHERE $whereTanggal
      AND r.status <> 'Ditolak'
    GROUP BY tgl, ru.id
  ");

  while ($row = mysqli_fetch_assoc($q)) {
    $d = (int)$row['tgl'];
    $values[$d] += $row['total'];
    $detailRuangan[$d][] = $row['nama_ruangan'] . ' (' . $row['total'] . ')';
  }

  $data = array_values($values);
  $detailRuangan = array_values($detailRuangan);
}

/* =====================
   CHART 2 - RUANGAN TERFAVORIT
===================== */
$ruanganLabel = [];
$ruanganData = [];

$q = mysqli_query($koneksi, "
  SELECT ru.nama_ruangan, COUNT(*) total
  FROM reservasi r
  JOIN ruangan ru ON ru.id = r.ruangan_id
  WHERE $whereTanggal
    AND r.status <> 'Ditolak'
  GROUP BY ru.id
  ORDER BY total DESC
");

while ($r = mysqli_fetch_assoc($q)) {
  $ruanganLabel[] = $r['nama_ruangan'];
  $ruanganData[] = $r['total'];
}

/* =====================
   CHART 3 - STATUS
===================== */
$statusLabel = [];
$statusData = [];

$q = mysqli_query($koneksi, "
  SELECT r.status, COUNT(*) total
  FROM reservasi r
  WHERE $whereTanggal
  GROUP BY r.status
");

while ($r = mysqli_fetch_assoc($q)) {
  $statusLabel[] = $r['status'];
  $statusData[] = $r['total'];
}

/* =====================
   CHART 4 - FASILITAS
===================== */
$fasilitasLabel = [];
$fasilitasData = [];

$q = mysqli_query($koneksi, "
  SELECT f.nama, SUM(rf.qty) total
  FROM reservasi r
  JOIN reservasi_fasilitas rf ON rf.reservasi_id = r.id
  JOIN fasilitas f ON f.id = rf.fasilitas_id
  WHERE $whereTanggal
    AND r.status <> 'Ditolak'
  GROUP BY f.id
  ORDER BY total DESC
");

while ($r = mysqli_fetch_assoc($q)) {
  $fasilitasLabel[] = $r['nama'];
  $fasilitasData[] = $r['total'];
}

/* =====================
   JUDUL
===================== */
$judulTrend = $bulan
  ? "Tren Reservasi per Tanggal – " . date('F', mktime(0, 0, 0, $bulan, 1)) . " $tahun"
  : "Tren Reservasi per Bulan – Tahun $tahun";
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Kepala Bagian</title>
  <?php include __DIR__ . '/../includes/module.php'; ?>
  <style>
    .chart-container {
      transition: transform 0.2s ease-in-out;
    }

    .chart-container:hover {
      transform: scale(1.02);
    }

    .gradient-bg {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .card-shadow {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen">

  <!-- SIDEBAR -->
  <div class="fixed w-64 h-screen bg-white shadow-lg z-10">
    <?php include '../includes/layouts/sidebar.php'; ?>
  </div>

  <!-- CONTENT -->
  <div class="ml-64 p-8 space-y-8">

    <!-- HEADER -->
    <div class="bg-white p-6 rounded-2xl shadow-lg card-shadow flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
          <i class="ph ph-chart-line text-blue-600 mr-2"></i></i>Dashboard Kepala Bagian
        </h1>
        <p class="text-gray-600">Pantau tren reservasi, ruangan, status, dan fasilitas dengan mudah.</p>
      </div>

      <!-- NOTIFIKASI -->
      <div class="relative">
        <button id="notifBtn" class="relative text-gray-600 hover:text-blue-600">
          <i class="ph ph-bell text-2xl"></i>

          <?php if ($notif_unread > 0): ?>
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
              <?= $notif_unread ?>
            </span>
          <?php endif; ?>
        </button>

        <!-- DROPDOWN -->
        <div id="notifDropdown"
          class="hidden absolute right-0 mt-3 w-96 bg-white rounded-xl shadow-xl border overflow-hidden z-50">

          <div class="px-4 py-3 font-semibold text-gray-700 border-b">
            Notifikasi
          </div>

          <?php if (mysqli_num_rows($notif_q) > 0): ?>
            <?php while ($n = mysqli_fetch_assoc($notif_q)): ?>
              <a href="<?= $n['reservasi_id'] ? '/reservasi_dprkp/kepala-bagian/reservation/detail.php?id=' . $n['reservasi_id'] : '#' ?>"
                data-id="<?= $n['id'] ?>"
                class="notif-item block px-4 py-3 border-b hover:bg-gray-50
             <?= $n['is_read'] ? 'text-gray-500' : 'font-semibold text-gray-800 bg-blue-50' ?>">
                <div class="text-sm"><?= htmlspecialchars($n['judul']) ?></div>
                <div class="text-xs text-gray-500"><?= htmlspecialchars($n['pesan']) ?></div>
              </a>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="px-4 py-6 text-center text-gray-500 text-sm">
              Tidak ada notifikasi
            </div>
          <?php endif; ?>

          <div class="px-4 py-2 text-xs text-gray-500 text-center bg-gray-50">
            Menampilkan maksimal 10 notifikasi terbaru
          </div>
        </div>
      </div>
    </div>

    <!-- FILTER FORM -->
    <form method="GET" class="bg-white p-6 rounded-2xl shadow-lg card-shadow flex flex-wrap gap-4 items-center w-fit">
      <div class="flex items-center gap-2">
        <i class="ph ph-calendar text-gray-600"></i>
        <label class="font-medium text-gray-700">Tahun:</label>
        <select name="tahun" class="border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <?php for ($t = date('Y') - 2; $t <= date('Y') + 1; $t++): ?>
            <option value="<?= $t ?>" <?= $tahun == $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="flex items-center gap-2">
        <label class="font-medium text-gray-700">Bulan:</label>
        <select name="bulan" class="border border-gray-300 px-4 py-2 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="">Semua Bulan</option>
          <?php for ($b = 1; $b <= 12; $b++):
            $val = str_pad($b, 2, '0', STR_PAD_LEFT); ?>
            <option value="<?= $val ?>" <?= $bulan == $val ? 'selected' : '' ?>>
              <?= date('F', mktime(0, 0, 0, $b, 1)) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>

      <button class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-2 rounded-lg hover:from-blue-600 hover:to-blue-700 transition duration-300 shadow-md">
        <i class="ph ph-magnifying-glass mr-2"></i>Terapkan
      </button>
    </form>

    <!-- CHARTS GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

      <!-- CHART 1 - TREND -->
      <div class="bg-white p-6 rounded-2xl shadow-lg card-shadow col-span-2 chart-container">
        <canvas id="trend" class="w-full h-80"></canvas>
      </div>

      <!-- CHART 2 - RUANGAN -->
      <div class="bg-white p-6 rounded-2xl shadow-lg card-shadow chart-container">
        <canvas id="ruangan" class="w-full h-64"></canvas>
      </div>

      <!-- CHART 3 - STATUS -->
      <div class="bg-white p-6 rounded-2xl shadow-lg card-shadow chart-container">
        <canvas id="status" class="w-full h-64"></canvas>
      </div>

      <!-- CHART 4 - FASILITAS -->
      <div class="bg-white p-6 rounded-2xl shadow-lg card-shadow col-span-2 chart-container">
        <canvas id="fasilitas" class="w-full h-80"></canvas>
      </div>

    </div>
  </div>

  <script>
    const detailRuangan = <?= json_encode($detailRuangan) ?>;

    // CHART 1 - TREND
    new Chart(document.getElementById('trend'), {
      type: 'line',
      data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
          label: 'Jumlah Reservasi',
          data: <?= json_encode($data) ?>,
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          borderWidth: 3,
          tension: 0.4,
          pointBackgroundColor: '#3B82F6',
          pointBorderColor: '#FFFFFF',
          pointBorderWidth: 2,
          pointRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top'
          },
          title: {
            display: true,
            text: "<?= $judulTrend ?>",
            font: {
              size: 18,
              weight: 'bold'
            },
            color: '#374151'
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#FFFFFF',
            bodyColor: '#FFFFFF',
            callbacks: {
              afterBody: ctx => {
                const d = detailRuangan[ctx[0].dataIndex];
                return d && d.length ? ['Ruangan:', ...d.map(r => '• ' + r)] : '';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.1)'
            }
          },
          x: {
            grid: {
              color: 'rgba(0, 0, 0, 0.1)'
            }
          }
        }
      }
    });

    // CHART 2 - RUANGAN
    new Chart(document.getElementById('ruangan'), {
      type: 'bar',
      data: {
        labels: <?= json_encode($ruanganLabel) ?>,
        datasets: [{
          label: 'Jumlah Penggunaan',
          data: <?= json_encode($ruanganData) ?>,
          backgroundColor: 'rgba(34, 197, 94, 0.8)',
          borderColor: '#22C55E',
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Ruangan Paling Sering Digunakan',
            font: {
              size: 16,
              weight: 'bold'
            },
            color: '#374151'
          },
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.1)'
            }
          },
          x: {
            grid: {
              color: 'rgba(0, 0, 0, 0.1)'
            }
          }
        }
      }
    });

    // CHART 3 - STATUS
    new Chart(document.getElementById('status'), {
      type: 'pie',
      data: {
        labels: <?= json_encode($statusLabel) ?>,
        datasets: [{
          data: <?= json_encode($statusData) ?>,
          backgroundColor: [
            'rgba(234, 179, 8, 0.8)', // Menunggu Admin (Yellow)
            'rgba(249, 115, 22, 0.8)', // Menunggu Kepala Bagian (Orange)
            'rgba(34, 197, 94, 0.8)', // Disetujui (Green)
            'rgba(239, 68, 68, 0.8)', // Ditolak (Red)
            'rgba(107, 114, 128, 0.8)' // Dibatalkan (Gray)
          ],
          borderColor: [
            '#EAB308',
            '#F97316',
            '#22C55E',
            '#EF4444',
            '#6B7280'
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Status Reservasi',
            font: {
              size: 16,
              weight: 'bold'
            },
            color: '#374151'
          },
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              padding: 20
            }
          }
        }
      }
    });


    // CHART 4 - FASILITAS
    new Chart(document.getElementById('fasilitas'), {
      type: 'bar',
      data: {
        labels: <?= json_encode($fasilitasLabel) ?>,
        datasets: [{
          label: 'Jumlah Penggunaan',
          data: <?= json_encode($fasilitasData) ?>,
          backgroundColor: 'rgba(168, 85, 247, 0.8)',
          borderColor: '#A855F7',
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Fasilitas Paling Sering Digunakan',
            font: {
              size: 16,
              weight: 'bold'
            },
            color: '#374151'
          },
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.1)'
            }
          },
          x: {
            grid: {
              color: 'rgba(0, 0, 0, 0.1)'
            }
          }
        }
      }
    });
  </script>

  <script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');

    notifBtn.addEventListener('click', () => {
      notifDropdown.classList.toggle('hidden');
    });

    document.querySelectorAll('.notif-item').forEach(item => {
      item.addEventListener('click', function() {

        const notifId = this.dataset.id;

        fetch('notification-read.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'id=' + notifId
        });

        // UI update
        this.classList.remove('font-semibold', 'bg-blue-50');
        this.classList.add('text-gray-500');

        // badge update
        const badge = document.querySelector('#notifBtn span');
        if (badge) {
          let count = parseInt(badge.innerText) - 1;
          count <= 0 ? badge.remove() : badge.innerText = count;
        }
      });
    });

    // close when click outside
    document.addEventListener('click', e => {
      if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
        notifDropdown.classList.add('hidden');
      }
    });
  </script>


</body>

</html>