<?php
session_start();

$pdo = new PDO(
    "mysql:host=localhost;dbname=roblox_store;charset=utf8mb4",
    "root", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Ubah jika nama folder di htdocs berbeda
define('BASE_URL', '/roblox-store');

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format($n, 0, ',', '.'); }
function login_required() {
    if (empty($_SESSION['user'])) { header('Location: ' . BASE_URL . '/auth/login.php'); exit; }
}
function role_required(...$roles) {
    login_required();
    if (!in_array($_SESSION['user']['role'], $roles)) { http_response_code(403); die('Akses ditolak.'); }
}
function gambar_url($g) {
    return $g ? BASE_URL . '/uploads/' . e($g) : BASE_URL . '/assets/noimage.svg';
}
function upload_gambar($file) {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp']) || $file['size'] > 2*1024*1024) return null;
    $nama = uniqid('img_') . '.' . $ext;
    move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $nama);
    return $nama;
}
