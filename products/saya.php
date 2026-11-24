<?php
$judul = 'Produk Saya';
require '../includes/header.php';
role_required('penjual');
$st = $pdo->prepare("SELECT p.*, c.nama AS kategori FROM products p JOIN categories c ON c.id=p.category_id
                     WHERE seller_id=? ORDER BY p.id DESC");
$st->execute([$u['id']]);
$list = $st->fetchAll();
?>
<h1>Produk Saya</h1>
<a class="btn" href="form.php">+ Tambah Produk</a>
<table class="tbl">
  <tr><th>Judul</th><th>Kategori</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
  <?php foreach ($list as $p): ?>
  <tr>
    <td><?= e($p['judul']) ?></td><td><?= e($p['kategori']) ?></td>
    <td><?= rupiah($p['harga']) ?></td><td><?= e($p['status']) ?></td>
    <td>
      <a href="form.php?id=<?= $p['id'] ?>">Edit</a> |
      <a href="hapus.php?id=<?= $p['id'] ?>" onclick="return confirm('Hapus produk ini?')">Hapus</a>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="5">Belum ada produk.</td></tr><?php endif; ?>
</table>
<?php require '../includes/footer.php'; ?>
