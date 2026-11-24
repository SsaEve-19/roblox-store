<?php
$judul = 'Form Produk';
require '../includes/header.php';
role_required('penjual');

$id = (int)($_GET['id'] ?? 0);
$p = ['judul'=>'','deskripsi'=>'','harga'=>'','category_id'=>'','gambar'=>null,'status'=>'tersedia'];
if ($id) {
    $st = $pdo->prepare("SELECT * FROM products WHERE id=? AND seller_id=?");
    $st->execute([$id, $u['id']]);
    $p = $st->fetch();
    if (!$p) die('Produk tidak ditemukan.');
}
$kategori = $pdo->query("SELECT * FROM categories")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jdl = trim($_POST['judul']); $desk = trim($_POST['deskripsi']);
    $harga = (int)$_POST['harga']; $kat = (int)$_POST['category_id'];
    $status = ($_POST['status'] ?? '') === 'terjual' ? 'terjual' : 'tersedia';
    if ($jdl === '' || $harga <= 0 || !$kat) {
        $error = 'Judul, kategori, dan harga wajib diisi dengan benar.';
    } else {
        $gambar = upload_gambar($_FILES['gambar'] ?? []) ?? $p['gambar'];
        if ($id) {
            $pdo->prepare("UPDATE products SET judul=?,deskripsi=?,harga=?,category_id=?,gambar=?,status=? WHERE id=? AND seller_id=?")
                ->execute([$jdl,$desk,$harga,$kat,$gambar,$status,$id,$u['id']]);
        } else {
            $pdo->prepare("INSERT INTO products (seller_id,category_id,judul,deskripsi,harga,gambar) VALUES (?,?,?,?,?,?)")
                ->execute([$u['id'],$kat,$jdl,$desk,$harga,$gambar]);
        }
        header('Location: saya.php'); exit;
    }
}
?>
<div class="form-box">
  <h1><?= $id ? 'Edit' : 'Tambah' ?> Produk</h1>
  <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <label>Judul</label><input type="text" name="judul" value="<?= e($p['judul']) ?>" required>
    <label>Kategori</label>
    <select name="category_id" required>
      <?php foreach ($kategori as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $p['category_id'] == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Harga (Rp)</label><input type="number" name="harga" min="1" value="<?= e($p['harga']) ?>" required>
    <label>Deskripsi</label><textarea name="deskripsi" rows="5"><?= e($p['deskripsi']) ?></textarea>
    <label>Gambar (jpg/png/webp, maks 2MB)</label><input type="file" name="gambar" accept="image/*">
    <?php if ($id): ?>
      <label>Status</label>
      <select name="status">
        <option value="tersedia" <?= $p['status']==='tersedia'?'selected':'' ?>>Tersedia</option>
        <option value="terjual" <?= $p['status']==='terjual'?'selected':'' ?>>Terjual</option>
      </select>
    <?php endif; ?>
    <button class="btn">Simpan</button>
  </form>
</div>
<?php require '../includes/footer.php'; ?>
