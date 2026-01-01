<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Sistem Reservasi DPRKP</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f4f6fb;
        margin: 0;
        display: flex;
    }

    /* === Sidebar Styling === */
    .sidebar {
        width: 250px;
        background: #1e3a8a;
        color: white;
        height: 100vh;
        position: fixed;
        padding: 30px 0;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.15);
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 40px;
        font-size: 20px;
        letter-spacing: 0.5px;
    }

    .sidebar a {
        display: block;
        padding: 14px 25px;
        color: white;
        text-decoration: none;
        transition: 0.3s;
        font-size: 15px;
        margin-bottom: 8px;
        border-radius: 6px;
    }

    .sidebar a:hover {
        background: #3b82f6;
        border-left: 5px solid #93c5fd;
        padding-left: 20px;
    }

    /* === Main Content Styling === */
    .main-content {
        margin-left: 250px;
        padding: 40px;
        width: calc(100% - 250px);
    }

    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        padding: 25px;
        margin-bottom: 30px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        border-radius: 8px;
        overflow: hidden;
    }

    th {
        background: #5f6063ff;
        color: white;
        text-align: left;
        padding: 12px;
    }

    td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }

    tr:nth-child(even) {
        background: #f9fafb;
    }
  </style>
</head>
<body>
