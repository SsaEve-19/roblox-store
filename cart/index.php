<?php
$judul = 'Keranjang';
require '../includes/header.php';
role_required('pembeli');
$items = []; $total = 0;
if (!empty($_SESSION['cart'])) {
    $in = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $st = $pdo->prepare("SELECT * FROM products WHERE id IN ($in) AND status='tersedia'");
    $st->execute($_SESSION['cart']);
    $items = $st->fetchAll();
    foreach ($items as $i) $total += $i['harga'];
}
?>
<h1>Keranjang Belanja</h1>
<?php if ($items): ?>
<table class="tbl">
  <tr><th>Produk</th><th>Harga</th><th></th></tr>
  <?php foreach ($items as $i): ?>
  <tr><td><?= e($i['judul']) ?></td><td><?= rupiah($i['harga']) ?></td>
      <td><a href="hapus.php?id=<?= $i['id'] ?>">Hapus</a></td></tr>
  <?php endforeach; ?>
  <tr><th>Total</th><th colspan="2"><?= rupiah($total) ?></th></tr>
</table>
<form method="post" action="checkout.php"><button class="btn">Checkout (Simulasi)</button></form>
<?php else: ?>
<p>Keranjang kosong. <a href="../index.php">Cari produk</a></p>
<?php endif; ?>
<?php require '../includes/footer.php'; ?>
