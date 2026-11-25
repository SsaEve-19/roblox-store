<?php
$judul = 'Detail Produk';
require '../includes/header.php';
$st = $pdo->prepare("SELECT p.*, c.nama AS kategori, u.username AS penjual,
    (SELECT ROUND(AVG(rating),1) FROM ratings WHERE seller_id=p.seller_id) AS rating,
    (SELECT COUNT(*) FROM ratings WHERE seller_id=p.seller_id) AS jml_rating
    FROM products p JOIN categories c ON c.id=p.category_id JOIN users u ON u.id=p.seller_id WHERE p.id=?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
if (!$p) { echo '<p>Produk tidak ditemukan.</p>'; require '../includes/footer.php'; exit; }

$fs = $pdo->prepare("SELECT file FROM product_images WHERE product_id=? ORDER BY urutan, id");
$fs->execute([$p['id']]);
$fotos = $fs->fetchAll(PDO::FETCH_COLUMN);
if (!$fotos && $p['gambar']) $fotos = [$p['gambar']];
?>
<div class="detail">
  <div class="gallery">
    <img id="gal-main" class="gal-main" src="<?= gambar_url($fotos[0] ?? null) ?>" alt="">
    <?php if (count($fotos) > 1): ?>
      <div class="gal-thumbs">
        <?php foreach ($fotos as $i => $f): ?>
          <img src="<?= gambar_url($f) ?>" alt="Foto <?= $i + 1 ?>" class="<?= $i === 0 ? 'on' : '' ?>">
        <?php endforeach; ?>
      </div>
      <small class="muted"><?= count($fotos) ?> foto - klik gambar kecil untuk melihatnya</small>
    <?php endif; ?>
  </div>
  <div>
    <span class="badge"><?= e($p['kategori']) ?></span>
    <h1><?= e($p['judul']) ?></h1>
    <p class="harga big"><?= rupiah($p['harga']) ?></p>
    <p>Penjual: <a class="link" href="../profil/lihat.php?id=<?= $p['seller_id'] ?>"><b><?= e($p['penjual']) ?></b></a>
      <?php if ($p['jml_rating']): ?>
        <span class="stars"><?= bintang($p['rating']) ?> <?= e($p['rating']) ?></span>
        <small class="muted">(<?= $p['jml_rating'] ?> ulasan)</small>
      <?php else: ?><small class="muted">(belum ada ulasan)</small><?php endif; ?>
    </p>
    <p>Status: <b><?= e($p['status']) ?></b></p>
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
<script>
(function () {
  var main = document.getElementById('gal-main');
  document.querySelectorAll('.gal-thumbs img').forEach(function (t) {
    t.addEventListener('click', function () {
      main.src = t.src;
      document.querySelectorAll('.gal-thumbs img').forEach(function (x) { x.classList.remove('on'); });
      t.classList.add('on');
    });
  });
})();
</script>
<?php require '../includes/footer.php'; ?>
