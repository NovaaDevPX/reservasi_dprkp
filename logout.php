<?php
include_once __DIR__ . '/includes/base-url.php';
session_start();
session_destroy();
header('Location: ' . $baseUrl . '/index.php');
exit;
