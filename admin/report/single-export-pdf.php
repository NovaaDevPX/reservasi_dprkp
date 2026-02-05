<?php
session_start();
require '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  exit('Akses ditolak');
}

require_once '../../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

/* ==========================
   VALIDASI ID
========================== */

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit('ID tidak valid');

/* ==========================
   QUERY DATA RESERVASI
========================== */
$q = mysqli_query($koneksi, "
    SELECT 
        r.*,
        u.nip,
        u.nama AS nama_user,
        ru.nama_ruangan,
        ru.kapasitas,
        kb.nama AS nama_kabag
    FROM reservasi r
    JOIN users u ON r.user_id = u.id
    JOIN ruangan ru ON r.ruangan_id = ru.id
    LEFT JOIN users kb ON r.kabag_id = kb.id
    WHERE r.id = $id
    LIMIT 1
");

$data = mysqli_fetch_assoc($q);
if (!$data) exit('Data tidak ditemukan');

/* ==========================
   QUERY FASILITAS
========================== */
$qFasilitas = mysqli_query($koneksi, "
    SELECT f.nama, rf.qty
    FROM reservasi_fasilitas rf
    JOIN fasilitas f ON rf.fasilitas_id = f.id
    WHERE rf.reservasi_id = $id
");

/* ==========================
   PATH FILE
========================== */
$tmpDir     = '../../uploads/tmp/';
$laporanPdf = $tmpDir . "laporan_$id.pdf";
$suratSrc   = '../../uploads/surat/' . $data['surat_pengantar'];
$suratPdf   = $tmpDir . "surat_$id.pdf";
$ttdPath    = '../../uploads/ttd/' . $data['ttd_kabag'];

if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

/* ==========================
   1️⃣ PDF LAPORAN
========================== */
$pdfLap = new FPDF();
$pdfLap->AddPage();
$pdfLap->SetFont('Arial', 'B', 15);
$pdfLap->Cell(0, 10, 'LAPORAN RESERVASI RUANGAN', 0, 1, 'C');
$pdfLap->Ln(5);

$pdfLap->SetFont('Arial', '', 11);

function row($pdf, $label, $value)
{
  $pdf->Cell(50, 7, $label, 0, 0);
  $pdf->MultiCell(0, 7, ': ' . ($value ?: '-'));
}

row($pdfLap, 'NIP Pegawai', $data['nip']);
row($pdfLap, 'Nama Pegawai', $data['nama_user']);
row($pdfLap, 'Ruangan', $data['nama_ruangan']);
row($pdfLap, 'Kapasitas', $data['kapasitas'] . ' orang');
row($pdfLap, 'Tanggal', date('d M Y', strtotime($data['tanggal'])));
row($pdfLap, 'Waktu', $data['jam_mulai'] . ' - ' . $data['jam_selesai']);
row($pdfLap, 'Jumlah Peserta', $data['jumlah_peserta'] . ' orang');
row($pdfLap, 'Status Reservasi', $data['status']);

if ($data['status'] === 'Ditolak') {
  row($pdfLap, 'Alasan Ditolak', $data['alasan_tolak']);
}

row($pdfLap, 'Kepala Bagian', $data['nama_kabag']);

$pdfLap->Ln(4);
$pdfLap->MultiCell(0, 7, "Keperluan:\n" . $data['keperluan']);

$pdfLap->Ln(5);
$pdfLap->SetFont('Arial', 'B', 11);
$pdfLap->Cell(0, 7, 'Fasilitas Digunakan:', 0, 1);

$pdfLap->SetFont('Arial', '', 11);
if (mysqli_num_rows($qFasilitas) > 0) {
  while ($f = mysqli_fetch_assoc($qFasilitas)) {
    $pdfLap->Cell(0, 6, '- ' . $f['nama'] . ' (' . $f['qty'] . ')', 0, 1);
  }
} else {
  $pdfLap->Cell(0, 6, '- Tidak ada fasilitas', 0, 1);
}

/* ==========================
   TTD KEPALA BAGIAN (KANAN BANGET)
========================== */
if (
  $data['status'] === 'Disetujui' &&
  !empty($data['ttd_kabag']) &&
  file_exists($ttdPath)
) {
  $pdfLap->Ln(15);

  $pdfLap->SetFont('Arial', '', 11);
  $pdfLap->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
  $pdfLap->Cell(0, 6, 'Kepala Bagian', 0, 1, 'R');

  // ===== hitung posisi kanan halaman =====
  $ttdWidth   = 45; // lebar TTD (mm)
  $rightGap   = 0; // jarak dari tepi kanan (mm)
  $pageWidth  = $pdfLap->GetPageWidth();

  $x = $pageWidth - $ttdWidth - $rightGap;
  $y = $pdfLap->GetY() + 3;

  // ===== gambar TTD =====
  $pdfLap->Image($ttdPath, $x, $y, $ttdWidth);

  // spasi ke bawah setelah gambar
  $pdfLap->Ln(15);

  $pdfLap->SetFont('Arial', 'B', 11);
  $pdfLap->Cell(0, 6, $data['nama_kabag'], 0, 1, 'R');
}


$pdfLap->Output('F', $laporanPdf);

/* ==========================
   2️⃣ SURAT → PDF
========================== */
if (!file_exists($suratSrc)) exit('File surat tidak ditemukan');

$ext = strtolower(pathinfo($suratSrc, PATHINFO_EXTENSION));

if ($ext === 'pdf') {
  copy($suratSrc, $suratPdf);
} else {
  $imgPdf = new FPDF();
  $imgPdf->AddPage();
  $imgPdf->Image($suratSrc, 10, 10, 190);
  $imgPdf->Output('F', $suratPdf);
}

/* ==========================
   3️⃣ GABUNG PDF
========================== */
$pdf = new Fpdi();

/* laporan */
$pageCount = $pdf->setSourceFile($laporanPdf);
for ($i = 1; $i <= $pageCount; $i++) {
  $tpl = $pdf->importPage($i);
  $size = $pdf->getTemplateSize($tpl);
  $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
  $pdf->useTemplate($tpl);
}

/* lampiran */
$pageCount = $pdf->setSourceFile($suratPdf);
for ($i = 1; $i <= $pageCount; $i++) {
  $tpl = $pdf->importPage($i);
  $size = $pdf->getTemplateSize($tpl);
  $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
  $pdf->useTemplate($tpl);

  if ($i === 1) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(10, 10);
    $pdf->Cell(0, 10, 'LAMPIRAN SURAT', 0, 1);
  }

  $pdf->SetFont('Arial', 'B', 40);
  $pdf->SetTextColor(230, 230, 230);
  $pdf->SetXY(20, 180);
  $pdf->Cell(0, 20, 'LAMPIRAN', 0, 0, 'C');
}

/* ==========================
   OUTPUT
========================== */
$pdf->Output('I', "Reservasi-$id.pdf");
