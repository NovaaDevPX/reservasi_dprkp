<div class="sidebar">
    <h2>
        <img src="../assets/img/logo.png" alt="Logo" width="70"><br>
        Dinas Perumahan<br>DKI Jakarta
    </h2>
    <ul>
        <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a></li>
        <li><a href="reservasi.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reservasi.php' ? 'active' : '' ?>">🗓️ Reservasi</a></li>
        <li><a href="ruang.php" class="<?= basename($_SERVER['PHP_SELF']) == 'ruang.php' ? 'active' : '' ?>">🏢 Ruang</a></li>
        <li><a href="../logout.php">🔒 Logout</a></li>
    </ul>
</div>
