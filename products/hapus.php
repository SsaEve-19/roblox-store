<?php
require_once '../config/koneksi.php';
role_required('penjual');
$pdo->prepare("DELETE FROM products WHERE id=? AND seller_id=?")->execute([(int)($_GET['id'] ?? 0), $_SESSION['user']['id']]);
header('Location: saya.php');
