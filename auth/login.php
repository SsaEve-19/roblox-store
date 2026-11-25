<?php
$judul = 'Login';
require '../includes/header.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare("SELECT * FROM users WHERE username=? OR email=?");
    $st->execute([trim($_POST['username']), trim($_POST['username'])]);
    $user = $st->fetch();
    if ($user && password_verify($_POST['password'], $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id'=>$user['id'], 'username'=>$user['username'], 'role'=>$user['role']];
        header('Location: ' . BASE_URL . '/index.php'); exit;
    }
    $error = 'Username/email atau password salah.';
}
?>
<div class="form-box">
  <h1>Login</h1>
  <?php if (isset($_GET['daftar'])): ?><div class="alert ok">Pendaftaran berhasil, silakan login.</div><?php endif; ?>
  <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Username / Email</label><input type="text" name="username" required>
    <label>Password</label><input type="password" name="password" required>
    <button class="btn">Masuk</button>
  </form>
</div>
<?php require '../includes/footer.php'; ?>
