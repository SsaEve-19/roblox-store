<?php
$judul = 'Profil Saya';
require '../includes/header.php';
login_required();

$ambil = function () use ($pdo, $u) {
    $s = $pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$u['id']]); return $s->fetch();
};
$me = $ambil();
$ok = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['aksi'] ?? '') === 'profil') {
        $email = trim($_POST['email']);
        $wa = trim($_POST['whatsapp']);
        $bio = mb_substr(trim($_POST['bio']), 0, 500);
        $cek = $pdo->prepare("SELECT id FROM users WHERE email=? AND id<>?");
        $cek->execute([$email, $u['id']]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Email tidak valid.';
        elseif ($cek->fetch()) $err = 'Email sudah dipakai user lain.';
        elseif (!preg_match('/^[0-9+]{0,20}$/', $wa)) $err = 'Nomor WhatsApp hanya boleh angka (dan +), maks 20 digit.';
        else {
            $pdo->prepare("UPDATE users SET email=?, whatsapp=?, bio=? WHERE id=?")->execute([$email, $wa ?: null, $bio ?: null, $u['id']]);
            $ok = 'Profil berhasil diperbarui.';
        }
    }
    if (($_POST['aksi'] ?? '') === 'password') {
        if (!password_verify($_POST['lama'], $me['password'])) $err = 'Password lama salah.';
        elseif (strlen($_POST['baru']) < 6) $err = 'Password baru minimal 6 karakter.';
        elseif ($_POST['baru'] !== $_POST['konfirmasi']) $err = 'Konfirmasi password tidak cocok.';
        else {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($_POST['baru'], PASSWORD_DEFAULT), $u['id']]);
            $ok = 'Password berhasil diganti.';
        }
    }
    $me = $ambil();
}

$info = '';
if ($me['role'] === 'penjual') {
    $r = $pdo->prepare("SELECT ROUND(AVG(rating),1) a, COUNT(*) n FROM ratings WHERE seller_id=?");
    $r->execute([$u['id']]); $r = $r->fetch();
    $info = $r['n'] ? "Rating: <span class='stars'>" . bintang($r['a']) . "</span> {$r['a']} ({$r['n']} ulasan) - <a class='link' href='lihat.php?id={$u['id']}'>Lihat profil publik</a>"
                    : "Belum ada ulasan. <a class='link' href='lihat.php?id={$u['id']}'>Lihat profil publik</a>";
} elseif ($me['role'] === 'pembeli') {
    $r = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id=?"); $r->execute([$u['id']]);
    $info = 'Total pesanan: <b>' . (int)$r->fetchColumn() . '</b>';
}
?>
<div class="two-col">
  <div class="form-box">
    <h1>Profil Saya</h1>
    <p><b><?= e($me['username']) ?></b> <span class="badge"><?= e($me['role']) ?></span><br>
       <small class="muted">Bergabung: <?= e($me['created_at']) ?></small></p>
    <?php if ($info): ?><p><?= $info ?></p><?php endif; ?>
    <?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="aksi" value="profil">
      <label>Email</label><input type="email" name="email" value="<?= e($me['email']) ?>" required>
      <label>No. WhatsApp</label><input type="text" name="whatsapp" value="<?= e($me['whatsapp']) ?>" placeholder="08123456789">
      <label>Bio</label><textarea name="bio" rows="4" maxlength="500"><?= e($me['bio']) ?></textarea>
      <button class="btn">Simpan Profil</button>
    </form>
  </div>
  <div class="form-box">
    <h1>Ganti Password</h1>
    <form method="post">
      <input type="hidden" name="aksi" value="password">
      <label>Password Lama</label><input type="password" name="lama" required>
      <label>Password Baru</label><input type="password" name="baru" required>
      <label>Konfirmasi Password Baru</label><input type="password" name="konfirmasi" required>
      <button class="btn">Ganti Password</button>
    </form>
  </div>
</div>
<?php require '../includes/footer.php'; ?>
