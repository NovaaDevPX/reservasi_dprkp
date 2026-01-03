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
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f2f5f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-container {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      width: 380px;
      text-align: center;
    }

    .login-container h2 {
      margin-bottom: 20px;
      font-size: 18px;
    }

    .login-container input {
      width: 95%;
      padding: 10px;
      margin: 8px 0;
      border-radius: 8px;
      border: 1px solid #ccc;
    }

    .login-container button {
      width: 100%;
      background: #007bff;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      margin-top: 10px;
    }

    .login-container button:hover {
      background: #0056b3;
    }
  </style>
</head>

<body>
  <div class="login-container">
    <h2>Sistem Reservasi Ruang Rapat & Aula DPRKP</h2>
    <form method="POST">
      <input type="text" name="username" placeholder="NIP" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="login">Login</button>
    </form>
  </div>
</body>

</html>