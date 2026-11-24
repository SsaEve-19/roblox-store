<?php
$judul = 'Daftar';
require '../includes/header.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $role = in_array($_POST['role'], ['pembeli','penjual']) ? $_POST['role'] : 'pembeli';

    if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
        $error = 'Isi semua data dengan benar (password minimal 6 karakter).';
    } else {
        $cek = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $cek->execute([$username, $email]);
        if ($cek->fetch()) {
            $error = 'Username atau email sudah dipakai.';
        } else {
            $pdo->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?,?)")
                ->execute([$username, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
            header('Location: login.php?daftar=ok'); exit;
        }
    }
}
?>
<div class="form-box">
  <h1>Daftar Akun</h1>
  <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Username</label><input type="text" name="username" required>
    <label>Email</label><input type="email" name="email" required>
    <label>Password</label><input type="password" name="password" required>
    <label>Daftar sebagai</label>
    <select name="role"><option value="pembeli">Pembeli</option><option value="penjual">Penjual</option></select>
    <button class="btn">Daftar</button>
  </form>
</div>
<?php require '../includes/footer.php'; ?>
