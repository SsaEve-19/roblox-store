<?php
$judul = 'Penarikan Dana';
require '../includes/header.php';
role_required('penjual');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah = angka($_POST['jumlah'] ?? '');
    $metode = mb_substr(trim($_POST['metode'] ?? ''), 0, 60);       // bebas: bank, e-wallet, dll
    $tujuan = mb_substr(trim($_POST['no_tujuan'] ?? ''), 0, 60);
    $nama   = mb_substr(trim($_POST['atas_nama'] ?? ''), 0, 100);

    if ($metode === '' || $tujuan === '' || $nama === '') {
        $err = 'Metode, nomor tujuan, dan atas nama wajib diisi.';
    } elseif ($jumlah < MIN_TARIK) {
        $err = 'Minimal penarikan ' . rupiah(MIN_TARIK) . '.';
    } else {
        $pdo->beginTransaction();
        try {
            // kunci baris penjual agar dua permintaan bersamaan tidak melebihi saldo
            $pdo->prepare("SELECT id FROM users WHERE id=? FOR UPDATE")->execute([$u['id']]);
            $s = saldo_penjual($pdo, $u['id']);
            if ($jumlah > $s['saldo']) {
                $pdo->rollBack();
                $err = 'Saldo tidak cukup. Saldo tersedia ' . rupiah(max(0, $s['saldo'])) . '.';
            } else {
                $pdo->prepare("INSERT INTO withdrawals (seller_id,jumlah,metode,no_tujuan,atas_nama) VALUES (?,?,?,?,?)")
                    ->execute([$u['id'], $jumlah, $metode, $tujuan, $nama]);
                $pdo->commit();
                header('Location: penarikan.php?ok=1'); exit;
            }
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = 'Permintaan gagal diproses, coba lagi.';
        }
    }
}

$s = saldo_penjual($pdo, $u['id']);
$h = $pdo->prepare("SELECT * FROM withdrawals WHERE seller_id=? ORDER BY id DESC");
$h->execute([$u['id']]);
$riwayat = $h->fetchAll();
?>
<h1>Penarikan Dana</h1>
<?php if (isset($_GET['ok'])): ?><div class="alert ok">Permintaan penarikan terkirim. Menunggu diproses admin.</div><?php endif; ?>

<div class="stats">
  <div class="stat"><small>Total Pendapatan (selesai)</small><b><?= rupiah($s['pendapatan']) ?></b></div>
  <div class="stat"><small>Sudah Dicairkan</small><b><?= rupiah($s['cair']) ?></b></div>
  <div class="stat"><small>Menunggu Diproses</small><b><?= rupiah($s['tunggu']) ?></b></div>
  <div class="stat"><small>Saldo Tersedia</small><b><?= rupiah(max(0, $s['saldo'])) ?></b></div>
</div>

<div class="form-box wide">
  <h2 style="margin-top:0">Ajukan Penarikan</h2>
  <?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <label>Jumlah</label>
    <div class="rp-wrap"><span>Rp</span>
      <input type="text" id="jumlah" name="jumlah" inputmode="numeric" data-rupiah maxlength="11"
             value="<?= e($_POST['jumlah'] ?? '') ?>" placeholder="minimal <?= number_format(MIN_TARIK, 0, ',', '.') ?>" required>
    </div>
    <small><a href="#" id="semua" class="link" data-val="<?= max(0, $s['saldo']) ?>">Tarik semua saldo</a></small>

    <label>Metode penarikan (bebas)</label>
    <input type="text" name="metode" list="metode-list" maxlength="60" value="<?= e($_POST['metode'] ?? '') ?>"
           placeholder="Pilih atau ketik sendiri, mis. DANA / BCA / Pulsa" required>
    <datalist id="metode-list">
      <?php foreach (['BCA','BRI','BNI','Mandiri','BSI','CIMB Niaga','Jago','SeaBank','DANA','OVO','GoPay','ShopeePay','LinkAja'] as $m): ?>
        <option value="<?= e($m) ?>">
      <?php endforeach; ?>
    </datalist>

    <label>Nomor rekening / HP tujuan</label>
    <input type="text" name="no_tujuan" maxlength="60" value="<?= e($_POST['no_tujuan'] ?? '') ?>" required>

    <label>Atas nama</label>
    <input type="text" name="atas_nama" maxlength="100" value="<?= e($_POST['atas_nama'] ?? '') ?>" required>

    <button class="btn">Ajukan Penarikan</button>
  </form>
  <p class="hint">Saldo berasal dari pesanan berstatus <b>Selesai</b>. Admin akan mentransfer ke tujuan di atas, lalu status berubah menjadi Disetujui.</p>
</div>

<h2>Riwayat Penarikan</h2>
<table class="tbl">
  <tr><th>ID</th><th>Tanggal</th><th>Jumlah</th><th>Tujuan</th><th>Status</th><th>Keterangan Admin</th></tr>
  <?php foreach ($riwayat as $r): ?>
  <tr>
    <td>#<?= $r['id'] ?></td>
    <td><small class="muted"><?= e($r['created_at']) ?></small></td>
    <td><?= rupiah($r['jumlah']) ?></td>
    <td><b><?= e($r['metode']) ?></b><br><?= e($r['no_tujuan']) ?> <small class="muted">a.n. <?= e($r['atas_nama']) ?></small></td>
    <td><span class="badge st-<?= e($r['status']) ?>"><?= e(tarik_label($r['status'])) ?></span></td>
    <td><?php if ($r['metode_admin']): ?>Dikirim via <b><?= e($r['metode_admin']) ?></b><br><?php endif; ?>
        <?php if ($r['catatan_admin']): ?><small><?= e($r['catatan_admin']) ?></small>
        <?php elseif (!$r['metode_admin']): ?><span class="muted">-</span><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$riwayat): ?><tr><td colspan="6">Belum ada penarikan.</td></tr><?php endif; ?>
</table>
<script>
document.getElementById('semua').addEventListener('click', function (ev) {
  ev.preventDefault();
  var i = document.getElementById('jumlah');
  i.value = this.dataset.val;
  i.dispatchEvent(new Event('input'));
});
</script>
<?php require '../includes/footer.php'; ?>
