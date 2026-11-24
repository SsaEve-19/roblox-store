<?php
$judul = 'Admin';
require '../includes/header.php';
role_required('admin');

$defs = [
  'users'   => ['SELECT COUNT(*) FROM users', 'SELECT * FROM users ORDER BY id'],
  'produk'  => ['SELECT COUNT(*) FROM products', 'SELECT p.*, u.username FROM products p JOIN users u ON u.id=p.seller_id ORDER BY p.id DESC'],
  'pesanan' => ['SELECT COUNT(*) FROM orders', 'SELECT o.*, u.username FROM orders o JOIN users u ON u.id=o.buyer_id ORDER BY o.id DESC'],
];
$tab = isset($defs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'users';
$per = 10;
$total = (int)$pdo->query($defs[$tab][0])->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$self = '?tab=' . $tab . '&page=' . $page;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($aksi === 'hapus_user' && $id !== (int)$u['id'])
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    if ($aksi === 'hapus_produk')
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    if ($aksi === 'status_order') {
        $baru = $_POST['status'] ?? '';
        $cur = $pdo->prepare("SELECT status FROM orders WHERE id=?"); $cur->execute([$id]); $cur = $cur->fetchColumn();
        // pesanan yang sudah dibatalkan bersifat final (produk sudah dikembalikan ke etalase)
        if ($cur && $cur !== 'dibatalkan' && in_array($baru, ['menunggu_pembayaran','menunggu_verifikasi','selesai','dibatalkan'])) {
            $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$baru, $id]);
            if ($baru === 'dibatalkan')
                $pdo->prepare("UPDATE products SET status='tersedia' WHERE id IN (SELECT product_id FROM order_items WHERE order_id=?)")->execute([$id]);
        }
    }
    header('Location: index.php' . $self); exit;
}

$rows = $pdo->query($defs[$tab][1] . " LIMIT $per OFFSET " . (($page - 1) * $per))->fetchAll();

$stat = [
  'User'            => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
  'Produk'          => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
  'Pesanan'         => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
  'Perlu Verifikasi'=> $pdo->query("SELECT COUNT(*) FROM orders WHERE status='menunggu_verifikasi'")->fetchColumn(),
  'Omzet (selesai)' => rupiah($pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='selesai'")->fetchColumn()),
];
?>
<h1>Panel Admin</h1>
<div class="stats">
  <?php foreach ($stat as $k => $v): ?><div class="stat"><small><?= $k ?></small><b><?= $v ?></b></div><?php endforeach; ?>
</div>
<div class="tabs">
  <a class="<?= $tab==='users'?'on':'' ?>" href="?tab=users">User</a>
  <a class="<?= $tab==='produk'?'on':'' ?>" href="?tab=produk">Produk</a>
  <a class="<?= $tab==='pesanan'?'on':'' ?>" href="?tab=pesanan">Pesanan</a>
</div>

<?php if ($tab === 'users'): ?>
<table class="tbl"><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= $r['id'] ?></td><td><?= e($r['username']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td>
<td><?php if ($r['id'] != $u['id']): ?>
  <form method="post" action="<?= e($self) ?>" onsubmit="return confirm('Hapus user ini?')">
    <input type="hidden" name="aksi" value="hapus_user"><input type="hidden" name="id" value="<?= $r['id'] ?>">
    <button class="btn small danger">Hapus</button></form>
<?php endif; ?></td></tr>
<?php endforeach; ?></table>

<?php elseif ($tab === 'produk'): ?>
<table class="tbl"><tr><th>ID</th><th>Judul</th><th>Penjual</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= $r['id'] ?></td><td><?= e($r['judul']) ?></td><td><?= e($r['username']) ?></td>
<td><?= rupiah($r['harga']) ?></td><td><?= e($r['status']) ?></td>
<td><form method="post" action="<?= e($self) ?>" onsubmit="return confirm('Hapus produk ini?')">
  <input type="hidden" name="aksi" value="hapus_produk"><input type="hidden" name="id" value="<?= $r['id'] ?>">
  <button class="btn small danger">Hapus</button></form></td></tr>
<?php endforeach; ?></table>

<?php else: ?>
<table class="tbl"><tr><th>ID</th><th>Pembeli</th><th>Total</th><th>Tanggal</th><th>Bukti Bayar</th><th>Status</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td>#<?= $r['id'] ?></td><td><?= e($r['username']) ?></td><td><?= rupiah($r['total']) ?></td><td><?= e($r['created_at']) ?></td>
<td><?php if ($r['bukti_bayar']): ?><a class="link" target="_blank" href="<?= gambar_url($r['bukti_bayar']) ?>">Lihat</a>
    <?php else: ?><span class="muted">-</span><?php endif; ?></td>
<td><form method="post" action="<?= e($self) ?>">
  <input type="hidden" name="aksi" value="status_order"><input type="hidden" name="id" value="<?= $r['id'] ?>">
  <select name="status" onchange="this.form.submit()" <?= $r['status']==='dibatalkan' ? 'disabled' : '' ?>>
    <?php foreach (['menunggu_pembayaran','menunggu_verifikasi','selesai','dibatalkan'] as $s): ?>
      <option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= e(status_label($s)) ?></option>
    <?php endforeach; ?>
  </select></form></td></tr>
<?php endforeach; ?></table>
<?php endif; ?>

<?= paginasi($page, $pages, ['tab' => $tab]) ?>
<?php require '../includes/footer.php'; ?>
