<?php
$judul = 'Form Produk';
require '../includes/header.php';
role_required('penjual');

$id = (int)($_GET['id'] ?? 0);
$p = ['judul'=>'','deskripsi'=>'','harga'=>'','category_id'=>'','status'=>'tersedia','data_item'=>'','data_fields'=>null];
if ($id) {
    $st = $pdo->prepare("SELECT * FROM products WHERE id=? AND seller_id=?");
    $st->execute([$id, $u['id']]);
    $p = $st->fetch();
    if (!$p) die('Produk tidak ditemukan.');
}
$kategori = $pdo->query("SELECT * FROM categories")->fetchAll();
$katInfo = [];                                   // id kategori => ['hint'=>..., 'fields'=>[...]]
foreach ($kategori as $k) $katInfo[$k['id']] = kategori_fields($k['nama']);

// Foto yang sudah tersimpan
$fotos = [];
if ($id) {
    $s = $pdo->prepare("SELECT id, file FROM product_images WHERE product_id=? ORDER BY urutan, id");
    $s->execute([$id]);
    $fotos = $s->fetchAll();
}

// Nilai kolom data per kategori (untuk mengisi ulang form)
$nilai = [];
if ($id && $p['data_fields']) $nilai[$p['category_id']] = json_decode($p['data_fields'], true) ?: [];
$legacy = ($id && !$p['data_fields'] && $p['data_item']) ? $p['data_item'] : '';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
        $error = 'Total ukuran upload melebihi batas server. Kurangi jumlah atau ukuran foto.';
    } else {
        $jdl = trim($_POST['judul'] ?? ''); $desk = trim($_POST['deskripsi'] ?? '');
        $harga = angka($_POST['harga'] ?? ''); $kat = (int)($_POST['category_id'] ?? 0);
        $status = ($_POST['status'] ?? '') === 'terjual' ? 'terjual' : 'tersedia';
        $kiriman = $_POST['df'][$kat] ?? [];
        $nilai[$kat] = is_array($kiriman) ? $kiriman : [];
        // isi ulang form bila ada error
        $p['judul'] = $jdl; $p['deskripsi'] = $desk; $p['harga'] = $harga ?: ''; $p['category_id'] = $kat; $p['status'] = $status;
        $legacy = '';

        if ($jdl === '' || !isset($katInfo[$kat])) {
            $error = 'Judul dan kategori wajib diisi.';
        } elseif ($harga <= 0 || $harga > 99999999) {
            $error = 'Harga harus berupa angka antara Rp 1 dan Rp 99.999.999.';
        }

        // Kolom data sesuai kategori
        $vals = [];
        if (!$error) {
            foreach ($katInfo[$kat]['fields'] as $f) {
                $v = mb_substr(trim((string)($nilai[$kat][$f['key']] ?? '')), 0, 1000);
                if ($f['type'] === 'select' && $v !== '' && !in_array($v, $f['opts'], true)) $v = '';
                if ($v === '' && empty($f['opt'])) { $error = 'Kolom "' . $f['label'] . '" wajib diisi.'; break; }
                $vals[$f['key']] = $v;
            }
        }

        // Foto
        $hapus = array_map('intval', (array)($_POST['hapus_foto'] ?? []));
        $sisa = 0;
        foreach ($fotos as $f) if (!in_array((int)$f['id'], $hapus, true)) $sisa++;
        $baru = normalisasi_files($_FILES['gambar'] ?? []);
        if (!$error) {
            if (count($baru) > MAKS_FOTO - $sisa) {
                $error = 'Maksimal ' . MAKS_FOTO . ' foto per produk (slot tersisa: ' . (MAKS_FOTO - $sisa) . ').';
            } else {
                foreach ($baru as $f) { if ($er = cek_gambar($f)) { $error = $er; break; } }
            }
        }

        if (!$error) {
            $dataItem = susun_data_item($katInfo[$kat]['fields'], $vals);
            $dataJson = json_encode($vals, JSON_UNESCAPED_UNICODE);
            $tersimpan = []; $dihapusFile = []; $idAwal = $id;
            $pdo->beginTransaction();
            try {
                if ($id) {
                    $pdo->prepare("UPDATE products SET judul=?,deskripsi=?,harga=?,category_id=?,status=?,data_item=?,data_fields=? WHERE id=? AND seller_id=?")
                        ->execute([$jdl,$desk,$harga,$kat,$status,$dataItem ?: null,$dataJson,$id,$u['id']]);
                } else {
                    $pdo->prepare("INSERT INTO products (seller_id,category_id,judul,deskripsi,harga,data_item,data_fields) VALUES (?,?,?,?,?,?,?)")
                        ->execute([$u['id'],$kat,$jdl,$desk,$harga,$dataItem ?: null,$dataJson]);
                    $id = (int)$pdo->lastInsertId();
                }
                // hapus foto yang dicentang
                foreach ($fotos as $f) {
                    if (in_array((int)$f['id'], $hapus, true)) {
                        $pdo->prepare("DELETE FROM product_images WHERE id=? AND product_id=?")->execute([$f['id'], $id]);
                        $dihapusFile[] = $f['file'];
                    }
                }
                // tambah foto baru
                $m = $pdo->prepare("SELECT COALESCE(MAX(urutan),0) FROM product_images WHERE product_id=?");
                $m->execute([$id]);
                $urut = (int)$m->fetchColumn();
                foreach ($baru as $f) {
                    $path = simpan_gambar($f);
                    if (!$path) throw new Exception('Gagal menyimpan file ' . $f['name']);
                    $tersimpan[] = $path;
                    $pdo->prepare("INSERT INTO product_images (product_id,file,urutan) VALUES (?,?,?)")->execute([$id, $path, ++$urut]);
                }
                // foto pertama = sampul (products.gambar)
                $c = $pdo->prepare("SELECT file FROM product_images WHERE product_id=? ORDER BY urutan, id LIMIT 1");
                $c->execute([$id]);
                $pdo->prepare("UPDATE products SET gambar=? WHERE id=?")->execute([$c->fetchColumn() ?: null, $id]);
                $pdo->commit();
                hapus_berkas($dihapusFile);
                header('Location: saya.php'); exit;
            } catch (Exception $ex) {
                $pdo->rollBack();
                $id = $idAwal;
                hapus_berkas($tersimpan);
                $error = 'Gagal menyimpan: ' . $ex->getMessage();
            }
        }
    }
}
?>
<div class="form-box wide">
  <h1><?= $id ? 'Edit' : 'Tambah' ?> Produk</h1>
  <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <label>Judul</label><input type="text" name="judul" value="<?= e($p['judul']) ?>" required>

    <label>Kategori</label>
    <select name="category_id" id="kat" required>
      <?php foreach ($kategori as $k): ?>
        <option value="<?= $k['id'] ?>" <?= $p['category_id'] == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Harga</label>
    <div class="rp-wrap"><span>Rp</span>
      <input type="text" name="harga" inputmode="numeric" data-rupiah maxlength="11"
             value="<?= $p['harga'] !== '' ? e($p['harga']) : '' ?>" placeholder="bebas, mis. 10.000 atau 15.000" required>
    </div>
    <small class="muted">Isi angka berapa pun sesuai produkmu (titik pemisah ribuan otomatis).</small>

    <label>Deskripsi</label><textarea name="deskripsi" rows="5"><?= e($p['deskripsi']) ?></textarea>

    <?php foreach ($kategori as $k): $info = $katInfo[$k['id']]; ?>
    <div class="kat-fields" data-kat="<?= $k['id'] ?>">
      <h3 class="sec">🔒 Data Rahasia - <?= e($k['nama']) ?></h3>
      <small class="muted"><?= e($info['hint']) ?> Tidak tampil di publik; hanya dikirim ke pembeli setelah admin memverifikasi pembayaran.</small>
      <?php if ($legacy && $p['category_id'] == $k['id']): ?>
        <div class="alert warn"><b>Data lama (format sebelumnya):</b><pre><?= e($legacy) ?></pre>
          Salin ke kolom di bawah, lalu simpan agar memakai format baru.</div>
      <?php endif; ?>
      <?php foreach ($info['fields'] as $f):
            $nm = 'df[' . $k['id'] . '][' . $f['key'] . ']';
            $val = $nilai[$k['id']][$f['key']] ?? ''; ?>
        <label><?= e($f['label']) ?><?= !empty($f['opt']) ? ' <span class="muted">(opsional)</span>' : '' ?></label>
        <?php if ($f['type'] === 'textarea'): ?>
          <textarea name="<?= e($nm) ?>" rows="3" placeholder="<?= e($f['ph'] ?? '') ?>"><?= e($val) ?></textarea>
        <?php elseif ($f['type'] === 'select'): ?>
          <select name="<?= e($nm) ?>" <?= empty($f['opt']) ? 'required' : '' ?>>
            <?php foreach ($f['opts'] as $o): ?><option value="<?= e($o) ?>" <?= $val === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
          </select>
        <?php else: ?>
          <input type="text" name="<?= e($nm) ?>" value="<?= e($val) ?>" placeholder="<?= e($f['ph'] ?? '') ?>" <?= empty($f['opt']) ? 'required' : '' ?>>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <h3 class="sec">📷 Foto Produk</h3>
    <small class="muted">Maksimal <?= MAKS_FOTO ?> foto (jpg/png/webp, maks 2MB per foto). Foto pertama menjadi sampul.</small>
    <?php if ($fotos): ?>
      <div class="foto-grid">
        <?php foreach ($fotos as $i => $f): ?>
          <div class="foto-item">
            <img src="<?= gambar_url($f['file']) ?>" alt="">
            <?php if ($i === 0): ?><em>Sampul</em><?php endif; ?>
            <label><input type="checkbox" name="hapus_foto[]" value="<?= $f['id'] ?>"> Hapus</label>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <label>Tambah foto</label>
    <input type="file" id="foto-baru" name="gambar[]" accept="image/*" multiple>
    <small id="foto-info" class="muted"></small>
    <div class="foto-grid" id="foto-prev"></div>

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
<script>
(function () {
  var MAKS = <?= MAKS_FOTO ?>;

  // Kolom data rahasia mengikuti kategori yang dipilih
  var sel = document.getElementById('kat');
  function sync() {
    document.querySelectorAll('.kat-fields').forEach(function (g) {
      var on = g.dataset.kat === sel.value;
      g.hidden = !on;
      g.querySelectorAll('input,select,textarea').forEach(function (i) { i.disabled = !on; });
    });
  }
  sel.addEventListener('change', sync);
  sync();

  // Foto: batas total + pratinjau
  var inp = document.getElementById('foto-baru'),
      info = document.getElementById('foto-info'),
      prev = document.getElementById('foto-prev'),
      cek = document.querySelectorAll('input[name="hapus_foto[]"]');
  function slot() {
    var n = 0;
    cek.forEach(function (c) { if (!c.checked) n++; });
    return MAKS - n;
  }
  function update() {
    prev.innerHTML = '';
    Array.prototype.forEach.call(inp.files, function (f) {
      var d = document.createElement('div'); d.className = 'foto-item';
      var im = document.createElement('img'); im.src = URL.createObjectURL(f);
      d.appendChild(im); prev.appendChild(d);
    });
    info.textContent = inp.files.length + ' foto dipilih - slot tersisa: ' + slot() + ' dari ' + MAKS;
  }
  cek.forEach(function (c) {
    c.addEventListener('change', function () { c.closest('.foto-item').classList.toggle('del', c.checked); update(); });
  });
  inp.addEventListener('change', function () {
    if (inp.files.length > slot()) {
      alert('Maksimal ' + MAKS + ' foto per produk. Slot tersisa: ' + slot() + '. Pilih ulang file.');
      inp.value = '';
    }
    update();
  });
  update();
})();
</script>
<?php require '../includes/footer.php'; ?>
