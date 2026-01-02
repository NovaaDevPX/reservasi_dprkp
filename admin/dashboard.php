<?php
// ================================
// KONEKSI DATABASE
// ================================
include '../config/koneksi.php';

// ================================
// DATA RUANGAN (FILTER)
// ================================
$ruangan_q = mysqli_query($koneksi, "SELECT id, nama_ruangan FROM ruangan ORDER BY nama_ruangan ASC");
$ruangan_list = [];
while ($r = mysqli_fetch_assoc($ruangan_q)) {
  $ruangan_list[] = $r;
}

// ================================
// DATA RESERVASI
// ================================
$query = "
  SELECT 
    r.id,
    r.tanggal,
    r.jam_mulai,
    r.jam_selesai,
    r.status,
    r.keperluan,
    r.ruangan_id,
    ru.nama_ruangan
  FROM reservasi r
  JOIN ruangan ru ON r.ruangan_id = ru.id
  WHERE r.status != 'Ditolak'
";

$result = mysqli_query($koneksi, $query);

$events = [];
while ($row = mysqli_fetch_assoc($result)) {

  switch ($row['status']) {
    case 'Disetujui':
      $color = '#16a34a';
      break;
    case 'Menunggu Admin':
      $color = '#f59e0b';
      break;
    case 'Menunggu Kepala Bagian':
      $color = '#0ea5e9';
      break;
    case 'Dibatalkan':
      $color = '#6b7280';
      break;
    default:
      $color = '#64748b';
  }

  $events[] = [
    'id'    => $row['id'],
    'title' => $row['nama_ruangan'],
    'start' => $row['tanggal'] . 'T' . $row['jam_mulai'],
    'end'   => $row['tanggal'] . 'T' . $row['jam_selesai'],
    'backgroundColor' => $color,
    'borderColor' => $color,
    'extendedProps' => [
      'ruangan' => $row['nama_ruangan'],
      'ruangan_id' => $row['ruangan_id'],
      'keperluan' => $row['keperluan'],
      'status' => $row['status'],
      'jam_mulai' => $row['jam_mulai'],
      'jam_selesai' => $row['jam_selesai']
    ]
  ];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Kalender Reservasi</title>

  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">

  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f8fafc;
    }

    .main-content {
      margin-left: 260px;
      padding: 24px;
    }

    .card {
      background: #fff;
      border-radius: 14px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .05);
    }

    h1 {
      font-size: 22px;
      margin-bottom: 12px;
      color: #1e293b;
    }

    /* FILTER */
    .filter-bar {
      display: flex;
      gap: 12px;
      margin-bottom: 16px;
    }

    .filter-bar select {
      padding: 8px 12px;
      border-radius: 10px;
      border: 1px solid #e5e7eb;
      font-size: 14px;
      background: #fff;
    }

    /* EVENT BULAT */
    .fc-event {
      border-radius: 999px !important;
      padding: 4px 10px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
    }

    /* TOOLTIP */
    .fc-tooltip {
      position: fixed;
      z-index: 99999;
      background: #0f172a;
      color: #fff;
      padding: 10px 12px;
      border-radius: 10px;
      font-size: 12px;
      line-height: 1.5;
      box-shadow: 0 12px 30px rgba(0, 0, 0, .35);
      pointer-events: none;
      max-width: 280px;
    }

    .fc-tooltip strong {
      color: #38bdf8;
    }
  </style>
</head>

<body>

  <?php include '../includes/layouts/sidebar.php'; ?>

  <div class="main-content">
    <div class="card">
      <h1>📅 Kalender Reservasi Ruangan</h1>

      <!-- FILTER -->
      <div class="filter-bar">
        <select id="filterRuangan">
          <option value="all">Semua Ruangan</option>
          <?php foreach ($ruangan_list as $r): ?>
            <option value="<?= $r['id'] ?>">
              <?= htmlspecialchars($r['nama_ruangan']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="calendar"></div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const calendarEl = document.getElementById('calendar');
      const filterRuangan = document.getElementById('filterRuangan');
      let tooltip;

      const allEvents = <?php echo json_encode($events); ?>;

      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'id',
        height: 'auto',
        dayMaxEvents: 3,

        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },

        events: allEvents,

        eventDidMount: function(info) {
          const p = info.event.extendedProps;

          info.el.addEventListener('mouseenter', () => {
            tooltip = document.createElement('div');
            tooltip.className = 'fc-tooltip';
            tooltip.innerHTML = `
          <strong>${p.ruangan}</strong><br>
          ${p.keperluan}<br><br>
          🕒 ${p.jam_mulai} - ${p.jam_selesai}<br>
          📌 Status: ${p.status}
        `;
            document.body.appendChild(tooltip);
          });

          info.el.addEventListener('mousemove', (e) => {
            if (!tooltip) return;

            let x = e.clientX + 14;
            let y = e.clientY + 14;
            const rect = tooltip.getBoundingClientRect();

            if (x + rect.width > window.innerWidth) {
              x = window.innerWidth - rect.width - 12;
            }
            if (y + rect.height > window.innerHeight) {
              y = window.innerHeight - rect.height - 12;
            }

            tooltip.style.left = x + 'px';
            tooltip.style.top = y + 'px';
          });

          info.el.addEventListener('mouseleave', () => {
            if (tooltip) {
              tooltip.remove();
              tooltip = null;
            }
          });
        }
      });

      calendar.render();

      // FILTER RUANGAN
      filterRuangan.addEventListener('change', function() {
        const value = this.value;

        calendar.removeAllEvents();

        const filtered = value === 'all' ?
          allEvents :
          allEvents.filter(e => e.extendedProps.ruangan_id == value);

        calendar.addEventSource(filtered);
      });
    });
  </script>

</body>

</html>