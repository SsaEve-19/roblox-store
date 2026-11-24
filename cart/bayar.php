<?php
$judul = 'Pembayaran';
require '../includes/header.php';
role_required('pembeli');

$st = $pdo->prepare("SELECT * FROM orders WHERE id=? AND buyer_id=?");
$st->execute([(int)($_GET['id'] ?? 0), $u['id']]);
$o = $st->fetch();
if (!$o) die('Pesanan tidak ditemukan.');
if (!in_array($o['status'], ['menunggu_pembayaran', 'menunggu_verifikasi'])) { header('Location: pesanan.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f = upload_gambar($_FILES['bukti'] ?? [], 'bukti');
    if ($f) {
        $pdo->prepare("UPDATE orders SET bukti_bayar=?, status='menunggu_verifikasi' WHERE id=?")->execute([$f, $o['id']]);
        header('Location: pesanan.php?bayar=1'); exit;
    }
    $error = 'Upload gagal. Gunakan file jpg/png/webp maksimal 2MB.';
}
?>
<div class="form-box">
  <h1>Pembayaran Pesanan #<?= $o['id'] ?></h1>
  <p>Total yang harus dibayar:</p>
  <p class="harga big"><?= rupiah($o['total']) ?></p>
  <p>Transfer ke salah satu rekening berikut:</p>
  <ul>
    <?php foreach (REKENING as $r): ?>
      <li><b><?= e($r[0]) ?></b> - <?= e($r[1]) ?> a.n. <?= e($r[2]) ?></li>
    <?php endforeach; ?>
  </ul>
  <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
  <?php if ($o['bukti_bayar']): ?>
    <p>Bukti saat ini: <a class="link" target="_blank" href="<?= gambar_url($o['bukti_bayar']) ?>">lihat</a> (upload ulang untuk mengganti)</p>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <label>Upload bukti transfer (jpg/png/webp, maks 2MB)</label>
    <input type="file" name="bukti" accept="image/*" required>
    <button class="btn">Kirim Bukti Pembayaran</button>
  </form>
  <p class="hint">Setelah bukti dikirim, admin akan memverifikasi pesananmu.</p>
</div>
<?php require '../includes/footer.php'; ?>
