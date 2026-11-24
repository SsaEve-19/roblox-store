<?php
$judul = 'Beranda';
require 'includes/header.php';

$q = trim($_GET['q'] ?? '');
$kat = (int)($_GET['kategori'] ?? 0);

$sql = "SELECT p.*, c.nama AS kategori, u.username AS penjual FROM products p
        JOIN categories c ON c.id=p.category_id JOIN users u ON u.id=p.seller_id
        WHERE p.status='tersedia'";
$params = [];
if ($q !== '') { $sql .= " AND p.judul LIKE ?"; $params[] = "%$q%"; }
if ($kat)      { $sql .= " AND p.category_id = ?"; $params[] = $kat; }
$sql .= " ORDER BY p.created_at DESC";
$st = $pdo->prepare($sql); $st->execute($params);
$produk = $st->fetchAll();
$kategori = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<h1>Marketplace Roblox</h1>
<form class="filter" method="get">
  <input type="text" name="q" placeholder="Cari produk..." value="<?= e($q) ?>">
  <select name="kategori">
    <option value="0">Semua Kategori</option>
    <?php foreach ($kategori as $k): ?>
      <option value="<?= $k['id'] ?>" <?= $kat == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn">Cari</button>
</form>

<div class="grid">
<?php foreach ($produk as $p): ?>
  <a class="card" href="products/detail.php?id=<?= $p['id'] ?>">
    <img src="<?= gambar_url($p['gambar']) ?>" alt="">
    <div class="card-body">
      <span class="badge"><?= e($p['kategori']) ?></span>
      <h3><?= e($p['judul']) ?></h3>
      <p class="harga"><?= rupiah($p['harga']) ?></p>
      <small>Penjual: <?= e($p['penjual']) ?></small>
    </div>
  </a>
<?php endforeach; ?>
<?php if (!$produk): ?><p>Produk tidak ditemukan.</p><?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
