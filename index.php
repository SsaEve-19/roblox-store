<?php
$judul = 'Beranda';
require 'includes/header.php';

$q = trim($_GET['q'] ?? '');
$kat = (int)($_GET['kategori'] ?? 0);

$where = "WHERE p.status='tersedia'";
$params = [];
if ($q !== '') { $where .= " AND p.judul LIKE ?"; $params[] = "%$q%"; }
if ($kat)      { $where .= " AND p.category_id = ?"; $params[] = $kat; }

$per = 8;
$cnt = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$offset = ($page - 1) * $per;

$st = $pdo->prepare("SELECT p.*, c.nama AS kategori, u.username AS penjual,
        (SELECT ROUND(AVG(rating),1) FROM ratings WHERE seller_id=p.seller_id) AS rating
        FROM products p JOIN categories c ON c.id=p.category_id JOIN users u ON u.id=p.seller_id
        $where ORDER BY p.created_at DESC LIMIT $per OFFSET $offset");
$st->execute($params);
$produk = $st->fetchAll();
$kategori = $pdo->query("SELECT * FROM categories")->fetchAll();

$keep = array_filter(['q' => $q, 'kategori' => $kat]);
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
<p class="hint"><?= $total ?> produk ditemukan</p>

<div class="grid">
<?php foreach ($produk as $p): ?>
  <a class="card" href="products/detail.php?id=<?= $p['id'] ?>">
    <img src="<?= gambar_url($p['gambar']) ?>" alt="">
    <div class="card-body">
      <span class="badge"><?= e($p['kategori']) ?></span>
      <h3><?= e($p['judul']) ?></h3>
      <p class="harga"><?= rupiah($p['harga']) ?></p>
      <small>Penjual: <?= e($p['penjual']) ?>
        <?= $p['rating'] ? '<span class="stars">★ ' . e($p['rating']) . '</span>' : '<span class="muted">(belum ada rating)</span>' ?>
      </small>
    </div>
  </a>
<?php endforeach; ?>
<?php if (!$produk): ?><p>Produk tidak ditemukan.</p><?php endif; ?>
</div>
<?= paginasi($page, $pages, $keep) ?>
<?php require 'includes/footer.php'; ?>
