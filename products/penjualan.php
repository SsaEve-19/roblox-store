<?php
$judul = 'Penjualan';
require '../includes/header.php';
role_required('penjual');

$st = $pdo->prepare("SELECT oi.harga, p.judul, o.id AS order_id, o.status, o.created_at,
        ub.username AS pembeli, ub.whatsapp, r.rating, r.komentar
    FROM order_items oi
    JOIN products p ON p.id=oi.product_id
    JOIN orders o ON o.id=oi.order_id
    JOIN users ub ON ub.id=o.buyer_id
    LEFT JOIN ratings r ON r.order_item_id=oi.id
    WHERE p.seller_id=? ORDER BY o.id DESC");
$st->execute([$u['id']]);
$rows = $st->fetchAll();
$pendapatan = 0;
foreach ($rows as $r) if ($r['status'] === 'selesai') $pendapatan += $r['harga'];
?>
<h1>Penjualan Saya</h1>
<div class="stats">
  <div class="stat"><small>Total Transaksi</small><b><?= count($rows) ?></b></div>
  <div class="stat"><small>Pendapatan (selesai)</small><b><?= rupiah($pendapatan) ?></b></div>
</div>
<table class="tbl">
  <tr><th>Pesanan</th><th>Produk</th><th>Harga</th><th>Pembeli</th><th>Status</th><th>Ulasan</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td>#<?= $r['order_id'] ?><br><small class="muted"><?= e($r['created_at']) ?></small></td>
    <td><?= e($r['judul']) ?></td>
    <td><?= rupiah($r['harga']) ?></td>
    <td><?= e($r['pembeli']) ?>
      <?php if ($r['whatsapp'] && in_array($r['status'], ['menunggu_verifikasi','selesai'])): ?>
        <br><small>WA: <?= e($r['whatsapp']) ?></small>
      <?php endif; ?></td>
    <td><span class="badge st-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
    <td><?php if ($r['rating']): ?><span class="stars"><?= bintang($r['rating']) ?></span><br><small><?= e($r['komentar']) ?></small>
        <?php else: ?><span class="muted">-</span><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6">Belum ada penjualan.</td></tr><?php endif; ?>
</table>
<p class="hint">Kontak pembeli (WhatsApp) tampil setelah pembeli mengirim bukti pembayaran, untuk keperluan pengiriman item/akun.</p>
<?php require '../includes/footer.php'; ?>
