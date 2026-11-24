<?php
session_start();
ob_start(); // supaya header('Location') tetap jalan walau HTML sudah dicetak

$pdo = new PDO(
    "mysql:host=localhost;dbname=roblox_store;charset=utf8mb4",
    "root", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Ubah jika nama folder di htdocs berbeda
define('BASE_URL', '/roblox-store');

// Rekening tujuan pembayaran (dummy)
define('REKENING', [
    ['Bank BCA', '1234567890', 'Roblox Store'],
    ['DANA',     '081234567890', 'Roblox Store'],
]);

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

// $sub = subfolder di dalam uploads/ (mis. 'bukti'). Mengembalikan path relatif atau null.
function upload_gambar($file, $sub = '') {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp']) || $file['size'] > 2*1024*1024) return null;
    $dir = __DIR__ . '/../uploads/' . ($sub ? $sub . '/' : '');
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $nama = uniqid('img_') . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $dir . $nama);
    return ($sub ? $sub . '/' : '') . $nama;
}

function status_label($s) {
    return [
        'menunggu_pembayaran' => 'Menunggu Pembayaran',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'selesai'             => 'Selesai',
        'dibatalkan'          => 'Dibatalkan',
    ][$s] ?? $s;
}

function bintang($n) {
    $n = max(0, min(5, (int)round($n)));
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

// Navigasi halaman: $params = parameter GET lain yang harus dipertahankan
function paginasi($page, $pages, $params = []) {
    if ($pages <= 1) return '';
    $link = function ($i, $label, $cls = '') use ($params) {
        return '<a class="' . $cls . '" href="?' . e(http_build_query($params + ['page' => $i])) . '">' . $label . '</a>';
    };
    $h = '<div class="paging">';
    if ($page > 1) $h .= $link($page - 1, '«');
    for ($i = 1; $i <= $pages; $i++) $h .= $link($i, $i, $i == $page ? 'on' : '');
    if ($page < $pages) $h .= $link($page + 1, '»');
    return $h . '</div>';
}
