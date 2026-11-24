<?php
require_once '../config/koneksi.php';
role_required('pembeli');
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT id FROM products WHERE id=? AND status='tersedia'");
$st->execute([$id]);
if ($st->fetch()) {
    $_SESSION['cart'] = $_SESSION['cart'] ?? [];
    if (!in_array($id, $_SESSION['cart'])) $_SESSION['cart'][] = $id; // item unik, 1 per produk
}
header('Location: index.php');
