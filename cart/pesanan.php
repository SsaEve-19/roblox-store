<?php
$judul = 'Pesanan Saya';
require '../includes/header.php';
role_required('pembeli');

$st = $pdo->prepare("SELECT * FROM orders WHERE buyer_id=? ORDER BY id DESC");
$st->execute([$u['id']]);
$orders = $st->fetchAll();

$detail = $pdo->prepare("SELECT oi.id, oi.harga, p.judul, p.seller_id, us.username AS penjual, us.whatsapp AS wa_penjual, p.data_item, r.rating, r.komentar
    FROM order_items oi JOIN products p ON p.id=oi.product_id JOIN users us ON us.id=p.seller_id
    LEFT JOIN ratings r ON r.order_item_id=oi.id WHERE oi.order_id=?");
?>
<h1>Riwayat Pesanan</h1>
<?php if (isset($_GET['bayar'])): ?><div class="alert ok">Bukti pembayaran terkirim. Menunggu verifikasi admin.</div><?php endif; ?>
<?php if (isset($_GET['rating'])): ?><div class="alert ok">Terima kasih atas ulasanmu!</div><?php endif; ?>

<?php foreach ($orders as $o): $detail->execute([$o['id']]); $items = $detail->fetchAll(); ?>
  <div class="order">
    <b>Pesanan #<?= $o['id'] ?></b> - <?= e($o['created_at']) ?> -
    <span class="badge st-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span>

    <ul class="items">
      <?php foreach ($items as $d): ?>
        <li>
          <?= e($d['judul']) ?> (<?= rupiah($d['harga']) ?>)
          <small class="muted">- penjual: <a class="link" href="../profil/lihat.php?id=<?= $d['seller_id'] ?>"><?= e($d['penjual']) ?></a></small>
          <?php if ($o['status'] === 'selesai'): ?>
            <div class="data-item">
              <b>🔑 Data Pesanan Kamu</b>
              <?php if ($d['data_item']): ?>
                <pre><?= e($d['data_item']) ?></pre>
                <small class="muted">Segera login dan ganti password akun setelah menerima data ini.</small>
              <?php else: ?>
                <p class="muted">Penjual belum mengisi data item. Hubungi penjual<?= $d['wa_penjual'] ? ' via WhatsApp: <b>' . e($d['wa_penjual']) . '</b>' : '' ?>.</p>
              <?php endif; ?>
            </div>
            <?php if ($d['rating']): ?>
              <div><span class="stars"><?= bintang($d['rating']) ?></span> <small><?= e($d['komentar']) ?></small></div>
            <?php else: ?>
              <form class="rate-form" method="post" action="rating.php">
                <input type="hidden" name="order_item_id" value="<?= $d['id'] ?>">
                <select name="rating">
                  <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= bintang($i) ?></option><?php endfor; ?>
                </select>
                <input type="text" name="komentar" placeholder="Ulasan untuk penjual (opsional)" maxlength="255">
                <button class="btn small">Kirim</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <b>Total: <?= rupiah($o['total']) ?></b>

    <?php if ($o['status'] === 'menunggu_pembayaran'): ?>
      <div class="actions">
        <a class="btn small" href="bayar.php?id=<?= $o['id'] ?>">Bayar Sekarang</a>
        <form method="post" action="batal.php" onsubmit="return confirm('Batalkan pesanan ini?')">
          <input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="btn small danger">Batalkan</button>
        </form>
      </div>
    <?php elseif ($o['status'] === 'menunggu_verifikasi'): ?>
      <div class="actions"><small class="muted">Bukti terkirim, menunggu verifikasi admin. Data akun/item akan tampil di sini setelah pembayaran diverifikasi.</small>
        <a class="link" target="_blank" href="<?= gambar_url($o['bukti_bayar']) ?>">Lihat bukti</a> |
        <a class="link" href="bayar.php?id=<?= $o['id'] ?>">Ganti bukti</a></div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
<?php if (!$orders): ?><p>Belum ada pesanan.</p><?php endif; ?>
<?php require '../includes/footer.php'; ?>
