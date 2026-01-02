<?php
// ================================
// KONEKSI DATABASE
// ================================
session_start();
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #f8fafc;
      --card: rgba(255, 255, 255, .82);
      --border: rgba(226, 232, 240, .8);
      --text: #0f172a;
      --muted: #64748b;
      --primary: #2563eb;
      --primary-soft: #dbeafe;
      --glass: blur(16px);
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(14px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .filter-bar {
      display: flex;
      gap: 14px;
      margin-bottom: 22px;
    }

    .filter-bar select {
      padding: 10px 16px;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: var(--card);
      backdrop-filter: var(--glass);
      font-size: 14px;
      font-weight: 500;
      color: var(--text);
      transition: .25s;
    }

    .filter-bar select:hover {
      border-color: var(--primary);
    }

    .fc {
      background: var(--card);
      backdrop-filter: var(--glass);
      border-radius: 20px;
      padding: 18px;
      border: 1px solid var(--border);
      animation: fadeUp .35s ease;
    }

    .fc-toolbar {
      margin-bottom: 18px !important;
    }

    .fc-toolbar-title {
      font-size: 20px !important;
      font-weight: 700;
      color: var(--text);
    }

    .fc-button {
      border: 1px solid var(--border) !important;
      background: #fff !important;
      color: var(--text) !important;
      padding: 7px 14px !important;
      font-weight: 600;
      text-transform: capitalize !important;
      border-radius: 12px !important;
      transition: .2s;
    }

    .fc-button:hover {
      background: var(--primary-soft) !important;
      color: var(--primary) !important;
    }

    .fc-button-active {
      background: var(--primary) !important;
      color: #fff !important;
      border-color: var(--primary) !important;
    }

    .fc-theme-standard td,
    .fc-theme-standard th {
      border-color: var(--border);
    }

    .fc-col-header-cell {
      padding: 10px 0;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
    }

    .fc-day-today {
      background: rgba(37, 99, 235, .06) !important;
    }

    .fc-daygrid-day-number {
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
    }

    .fc-event {
      border-radius: 999px !important;
      padding: 5px 12px !important;
      font-size: 11.5px !important;
      font-weight: 600;
      border: none !important;
      color: #fff !important;
      box-shadow: 0 6px 18px rgba(37, 99, 235, .35);
      transition: transform .15s ease, box-shadow .15s ease;
    }

    .fc-event:hover {
      transform: translateY(-2px) scale(1.05);
      box-shadow: 0 16px 36px rgba(37, 99, 235, .45);
    }

    .fc-tooltip {
      position: fixed;
      z-index: 99999;
      background: linear-gradient(135deg, #020617, #0f172a);
      color: #fff;
      padding: 14px 16px;
      border-radius: 14px;
      font-size: 12px;
      line-height: 1.6;
      box-shadow: 0 30px 60px rgba(0, 0, 0, .45);
      pointer-events: none;
      max-width: 260px;
      animation: pop .15s ease;
    }

    @keyframes pop {
      from {
        transform: scale(.95);
        opacity: 0;
      }

      to {
        transform: scale(1);
        opacity: 1;
      }
    }

    .fc-tooltip strong {
      color: #38bdf8;
      font-size: 13px;
    }
  </style>


</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">

  <?php include '../includes/layouts/sidebar.php'; ?>

  <div class="main-content p-4 sm:p-6 lg:p-8">
    <div class="card">
      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-bold text-slate-800 mb-2">Dashboard</h1>
          <p class="text-slate-600 text-base">
            Pantau dan kelola seluruh data reservasi ruangan dan aula secara terpusat dan efisien.
          </p>
        </div>
      </div>

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

      <div class="max-w-5xl mx-auto">
        <div id="calendar"></div>
      </div>

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
        eventDisplay: 'dot',
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