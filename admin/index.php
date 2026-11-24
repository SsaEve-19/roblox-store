<?php
$judul = 'Admin';
require '../includes/header.php';
role_required('admin');

$tab = $_GET['tab'] ?? 'users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($aksi === 'hapus_user' && $id !== (int)$u['id'])
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    if ($aksi === 'hapus_produk')
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    if ($aksi === 'status_order' && in_array($_POST['status'], ['pending','selesai','dibatalkan']))
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$_POST['status'], $id]);
    header('Location: index.php?tab=' . urlencode($tab)); exit;
}

$stat = [
  'User'    => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
  'Produk'  => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
  'Pesanan' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
  'Omzet'   => rupiah($pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'dibatalkan'")->fetchColumn()),
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

<?php if ($tab === 'users'): $rows = $pdo->query("SELECT * FROM users ORDER BY id")->fetchAll(); ?>
<table class="tbl"><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= $r['id'] ?></td><td><?= e($r['username']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td>
<td><?php if ($r['id'] != $u['id']): ?>
  <form method="post" onsubmit="return confirm('Hapus user ini?')">
    <input type="hidden" name="aksi" value="hapus_user"><input type="hidden" name="id" value="<?= $r['id'] ?>">
    <button class="btn small danger">Hapus</button></form>
<?php endif; ?></td></tr>
<?php endforeach; ?></table>

<?php elseif ($tab === 'produk'): $rows = $pdo->query("SELECT p.*, u.username FROM products p JOIN users u ON u.id=p.seller_id ORDER BY p.id DESC")->fetchAll(); ?>
<table class="tbl"><tr><th>ID</th><th>Judul</th><th>Penjual</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= $r['id'] ?></td><td><?= e($r['judul']) ?></td><td><?= e($r['username']) ?></td>
<td><?= rupiah($r['harga']) ?></td><td><?= e($r['status']) ?></td>
<td><form method="post" onsubmit="return confirm('Hapus produk ini?')">
  <input type="hidden" name="aksi" value="hapus_produk"><input type="hidden" name="id" value="<?= $r['id'] ?>">
  <button class="btn small danger">Hapus</button></form></td></tr>
<?php endforeach; ?></table>

<?php else: $rows = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON u.id=o.buyer_id ORDER BY o.id DESC")->fetchAll(); ?>
<table class="tbl"><tr><th>ID</th><th>Pembeli</th><th>Total</th><th>Tanggal</th><th>Status</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td>#<?= $r['id'] ?></td><td><?= e($r['username']) ?></td><td><?= rupiah($r['total']) ?></td><td><?= e($r['created_at']) ?></td>
<td><form method="post">
  <input type="hidden" name="aksi" value="status_order"><input type="hidden" name="id" value="<?= $r['id'] ?>">
  <select name="status" onchange="this.form.submit()">
    <?php foreach (['pending','selesai','dibatalkan'] as $s): ?>
      <option <?= $r['status']===$s?'selected':'' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select></form></td></tr>
<?php endforeach; ?></table>
<?php endif; ?>
<?php require '../includes/footer.php'; ?>
