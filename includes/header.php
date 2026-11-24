<?php require_once __DIR__ . '/../config/koneksi.php';
$u = $_SESSION['user'] ?? null;
$jmlCart = count($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul ?? 'Roblox Store') ?> - Roblox Store</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="nav">
  <a class="brand" href="<?= BASE_URL ?>/index.php">🎮 Roblox Store</a>
  <div class="links">
    <a href="<?= BASE_URL ?>/index.php">Beranda</a>
    <?php if ($u): ?>
      <?php if ($u['role'] === 'penjual'): ?>
        <a href="<?= BASE_URL ?>/products/saya.php">Produk Saya</a>
        <a href="<?= BASE_URL ?>/products/penjualan.php">Penjualan</a>
      <?php endif; ?>
      <?php if ($u['role'] === 'admin'): ?><a href="<?= BASE_URL ?>/admin/index.php">Admin</a><?php endif; ?>
      <?php if ($u['role'] === 'pembeli'): ?>
        <a href="<?= BASE_URL ?>/cart/index.php">Keranjang (<?= $jmlCart ?>)</a>
        <a href="<?= BASE_URL ?>/cart/pesanan.php">Pesanan</a>
      <?php endif; ?>
      <a class="user" href="<?= BASE_URL ?>/profil/index.php">👤 <?= e($u['username']) ?></a>
      <a href="<?= BASE_URL ?>/auth/logout.php">Logout</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
      <a class="btn small" href="<?= BASE_URL ?>/auth/register.php">Daftar</a>
    <?php endif; ?>
  </div>
</nav>
<main class="container">
