<?php
$judul = 'Pesanan Saya';
require '../includes/header.php';
role_required('pembeli');
$st = $pdo->prepare("SELECT * FROM orders WHERE buyer_id=? ORDER BY id DESC");
$st->execute([$u['id']]);
$orders = $st->fetchAll();
$detail = $pdo->prepare("SELECT oi.harga, p.judul FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
?>
<h1>Riwayat Pesanan</h1>
<?php if (isset($_GET['sukses'])): ?><div class="alert ok">Pesanan berhasil dibuat!</div><?php endif; ?>
<?php foreach ($orders as $o): $detail->execute([$o['id']]); ?>
  <div class="order">
    <b>Pesanan #<?= $o['id'] ?></b> - <?= e($o['created_at']) ?> -
    <span class="badge"><?= e($o['status']) ?></span>
    <ul>
      <?php foreach ($detail->fetchAll() as $d): ?>
        <li><?= e($d['judul']) ?> (<?= rupiah($d['harga']) ?>)</li>
      <?php endforeach; ?>
    </ul>
    <b>Total: <?= rupiah($o['total']) ?></b>
  </div>
<?php endforeach; ?>
<?php if (!$orders): ?><p>Belum ada pesanan.</p><?php endif; ?>
<?php require '../includes/footer.php'; ?>
