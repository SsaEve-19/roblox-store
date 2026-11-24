<?php
$judul = 'Profil Penjual';
require '../includes/header.php';

$st = $pdo->prepare("SELECT id, username, role, bio, whatsapp, created_at FROM users WHERE id=?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$s = $st->fetch();
if (!$s) { echo '<p>User tidak ditemukan.</p>'; require '../includes/footer.php'; exit; }

$r = $pdo->prepare("SELECT ROUND(AVG(rating),1) a, COUNT(*) n FROM ratings WHERE seller_id=?");
$r->execute([$s['id']]); $r = $r->fetch();

$ulasan = $pdo->prepare("SELECT r.rating, r.komentar, r.created_at, u.username, p.judul
    FROM ratings r JOIN users u ON u.id=r.buyer_id
    JOIN order_items oi ON oi.id=r.order_item_id JOIN products p ON p.id=oi.product_id
    WHERE r.seller_id=? ORDER BY r.id DESC LIMIT 10");
$ulasan->execute([$s['id']]);

$produk = $pdo->prepare("SELECT p.*, c.nama AS kategori FROM products p JOIN categories c ON c.id=p.category_id
    WHERE p.seller_id=? AND p.status='tersedia' ORDER BY p.id DESC");
$produk->execute([$s['id']]);
$produk = $produk->fetchAll();
?>
<div class="order">
  <h1><?= e($s['username']) ?> <span class="badge"><?= e($s['role']) ?></span></h1>
  <small class="muted">Bergabung: <?= e($s['created_at']) ?></small>
  <?php if ($s['bio']): ?><p><?= nl2br(e($s['bio'])) ?></p><?php endif; ?>
  <?php if ($s['role'] === 'penjual'): ?>
    <p><?php if ($r['n']): ?><span class="stars big"><?= bintang($r['a']) ?></span> <b><?= e($r['a']) ?></b> / 5 (<?= $r['n'] ?> ulasan)
       <?php else: ?><span class="muted">Belum ada ulasan.</span><?php endif; ?></p>
  <?php endif; ?>
</div>

<?php if ($s['role'] === 'penjual'): ?>
<h2>Produk Tersedia</h2>
<div class="grid">
<?php foreach ($produk as $p): ?>
  <a class="card" href="../products/detail.php?id=<?= $p['id'] ?>">
    <img src="<?= gambar_url($p['gambar']) ?>" alt="">
    <div class="card-body"><span class="badge"><?= e($p['kategori']) ?></span>
      <h3><?= e($p['judul']) ?></h3><p class="harga"><?= rupiah($p['harga']) ?></p></div>
  </a>
<?php endforeach; ?>
<?php if (!$produk): ?><p class="muted">Tidak ada produk tersedia.</p><?php endif; ?>
</div>

<h2>Ulasan Pembeli</h2>
<?php $ada = false; foreach ($ulasan as $x): $ada = true; ?>
  <div class="order">
    <span class="stars"><?= bintang($x['rating']) ?></span> <b><?= e($x['username']) ?></b>
    <small class="muted">- <?= e($x['judul']) ?> - <?= e($x['created_at']) ?></small>
    <?php if ($x['komentar']): ?><p><?= e($x['komentar']) ?></p><?php endif; ?>
  </div>
<?php endforeach; ?>
<?php if (!$ada): ?><p class="muted">Belum ada ulasan.</p><?php endif; ?>
<?php endif; ?>
<?php require '../includes/footer.php'; ?>
