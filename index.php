<?php
session_start();
include 'config/koneksi.php';

if (isset($_POST['login'])) {

  $nip = mysqli_real_escape_string($koneksi, $_POST['username']);
  $password = $_POST['password']; // ❗ JANGAN di-hash manual

  // Ambil user berdasarkan NIP
  $query = mysqli_query(
    $koneksi,
    "SELECT * FROM users WHERE nip='$nip' LIMIT 1"
  );

  if (!$query) {
    die("Query error: " . mysqli_error($koneksi));
  }

  if (mysqli_num_rows($query) === 1) {
    $user = mysqli_fetch_assoc($query);

    // ✅ VERIFIKASI PASSWORD
    if (password_verify($password, $user['password'])) {

      $_SESSION['id_user'] = $user['id'];
      $_SESSION['nama']    = $user['nama'];
      $_SESSION['role']    = $user['role'];

      // 🔀 Redirect sesuai role
      switch ($user['role']) {
        case 'admin':
          header('Location: admin/dashboard.php');
          break;
        case 'pegawai':
          header('Location: pegawai/dashboard.php');
          break;
        case 'kepala_bagian':
          header('Location: kepala-bagian/dashboard.php');
          break;
      }
      exit();
    }
  }

  // ❌ Jika gagal
  echo "<script>alert('Login gagal! NIP atau password salah.');</script>";
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Login | Sistem Reservasi DPRKP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Icons -->
  <link href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css" rel="stylesheet">
</head>

<body class="min-h-screen flex items-center justify-center
             bg-gradient-to-br from-blue-50 via-white to-slate-100">

  <!-- CARD -->
  <div class="w-full max-w-md bg-white/80 backdrop-blur
              border border-slate-200 rounded-3xl shadow-xl p-8">

    <!-- HEADER -->
    <div class="text-center mb-8">
      <div class="mx-auto w-14 h-14 rounded-2xl bg-blue-100 flex items-center justify-center mb-4">
        <i class="ph ph-buildings text-2xl text-blue-600"></i>
      </div>

      <h1 class="text-xl font-bold text-slate-800">
        Sistem Reservasi
      </h1>
      <p class="text-sm text-slate-500">
        Ruang Rapat & Aula DPRKP
      </p>
    </div>

    <!-- FORM -->
    <form method="POST" class="space-y-5">

      <!-- NIP -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          NIP
        </label>
        <div class="relative">
          <i class="ph ph-identification-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input
            type="text"
            name="username"
            required
            placeholder="Masukkan NIP"
            class="w-full pl-11 pr-4 py-2.5 rounded-xl
                   border border-slate-300
                   focus:ring-2 focus:ring-blue-200
                   focus:border-blue-500">
        </div>
      </div>

      <!-- PASSWORD -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          Password
        </label>
        <div class="relative">
          <i class="ph ph-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input
            type="password"
            id="password"
            name="password"
            required
            placeholder="Masukkan password"
            class="w-full pl-11 pr-11 py-2.5 rounded-xl
                   border border-slate-300
                   focus:ring-2 focus:ring-blue-200
                   focus:border-blue-500">

          <!-- TOGGLE -->
          <button type="button"
            onclick="togglePassword()"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
            <i id="eyeIcon" class="ph ph-eye"></i>
          </button>
        </div>
      </div>

      <!-- BUTTON -->
      <button
        type="submit"
        name="login"
        class="w-full py-3 rounded-xl
               bg-blue-600 hover:bg-blue-700
               text-white font-semibold
               shadow-lg transition flex items-center justify-center gap-2">
        <i class="ph ph-sign-in"></i>
        Login
      </button>
    </form>

    <!-- FOOTER -->
    <div class="mt-8 text-center text-xs text-slate-400">
      © <?= date('Y'); ?> DPRKP • Sistem Reservasi
    </div>
  </div>

  <!-- SCRIPT -->
  <script>
    function togglePassword() {
      const input = document.getElementById('password');
      const icon = document.getElementById('eyeIcon');

      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ph ph-eye-slash';
      } else {
        input.type = 'password';
        icon.className = 'ph ph-eye';
      }
    }
  </script>

</body>

</html>