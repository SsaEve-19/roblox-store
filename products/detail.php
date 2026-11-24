<?php
$judul = 'Detail Produk';
require '../includes/header.php';
$st = $pdo->prepare("SELECT p.*, c.nama AS kategori, u.username AS penjual FROM products p
    JOIN categories c ON c.id=p.category_id JOIN users u ON u.id=p.seller_id WHERE p.id=?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
if (!$p) { echo '<p>Produk tidak ditemukan.</p>'; require '../includes/footer.php'; exit; }
?>
<div class="detail">
  <img src="<?= gambar_url($p['gambar']) ?>" alt="">
  <div>
    <span class="badge"><?= e($p['kategori']) ?></span>
    <h1><?= e($p['judul']) ?></h1>
    <p class="harga big"><?= rupiah($p['harga']) ?></p>
    <p>Penjual: <b><?= e($p['penjual']) ?></b> | Status: <b><?= e($p['status']) ?></b></p>
    <p><?= nl2br(e($p['deskripsi'])) ?></p>
    <?php if ($p['status'] === 'tersedia'): ?>
      <?php if (!$u): ?>
        <a class="btn" href="../auth/login.php">Login untuk membeli</a>
      <?php elseif ($u['role'] === 'pembeli'): ?>
        <a class="btn" href="../cart/tambah.php?id=<?= $p['id'] ?>">+ Tambah ke Keranjang</a>
      <?php endif; ?>
    <?php else: ?>
      <div class="alert err">Produk sudah terjual.</div>
    <?php endif; ?>
  </div>
</div>
<?php require '../includes/footer.php'; ?>
