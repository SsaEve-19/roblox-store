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

define('MAKS_FOTO', 10);      // maksimal foto per produk
define('MIN_TARIK', 10000);    // minimal penarikan saldo penjual (Rp)

// "15.000", "Rp 15000", "15,000" -> 15000
function angka($s) { return (int)preg_replace('/\D/', '', (string)$s); }

// Ubah $_FILES (satu file ATAU banyak file name="x[]") menjadi daftar file tunggal.
// Input yang dikosongkan (tidak ada file dipilih) dilewati.
function normalisasi_files($f) {
    $out = [];
    if (empty($f) || !isset($f['name'])) return $out;
    if (!is_array($f['name'])) $f = array_map(fn($v) => [$v], $f);
    foreach ($f['name'] as $i => $n) {
        if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i],
                  'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

// Validasi 1 file gambar. Mengembalikan null jika OK, atau pesan error.
function cek_gambar($file) {
    $nm = $file['name'] ?? 'file';
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) return "$nm: ukuran terlalu besar.";
    if ($err !== UPLOAD_ERR_OK) return "$nm: gagal diunggah.";
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return "$nm: format harus jpg/png/webp.";
    if ($file['size'] > 2 * 1024 * 1024) return "$nm: maksimal 2MB.";
    if (@getimagesize($file['tmp_name']) === false) return "$nm: bukan file gambar yang valid.";
    return null;
}

// Simpan file yang SUDAH lolos cek_gambar. $sub = subfolder di dalam uploads/ (mis. 'bukti').
// Mengembalikan path relatif atau null.
function simpan_gambar($file, $sub = '') {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dir = __DIR__ . '/../uploads/' . ($sub ? $sub . '/' : '');
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $nama = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $nama)) return null;
    return ($sub ? $sub . '/' : '') . $nama;
}

// Upload 1 gambar (dipakai untuk bukti pembayaran). Mengembalikan path relatif atau null.
function upload_gambar($file, $sub = '') {
    if (empty($file['name']) || cek_gambar($file) !== null) return null;
    return simpan_gambar($file, $sub);
}

// Hapus file-file di folder uploads/ (path relatif dari DB)
function hapus_berkas($paths) {
    foreach ($paths as $p) {
        $f = __DIR__ . '/../uploads/' . $p;
        if ($p && is_file($f)) @unlink($f);
    }
}
function berkas_produk($pdo, $productId) {
    $s = $pdo->prepare("SELECT file FROM product_images WHERE product_id=?");
    $s->execute([$productId]);
    return $s->fetchAll(PDO::FETCH_COLUMN);
}
function berkas_penjual($pdo, $sellerId) {
    $s = $pdo->prepare("SELECT pi.file FROM product_images pi JOIN products p ON p.id=pi.product_id WHERE p.seller_id=?");
    $s->execute([$sellerId]);
    return $s->fetchAll(PDO::FETCH_COLUMN);
}

// Kolom data RAHASIA yang berbeda untuk tiap kategori (dikirim ke pembeli setelah pesanan selesai).
// 'opt' => true berarti boleh dikosongkan. Kategori yang tidak dikenal memakai kolom umum.
function kategori_fields($nama) {
    $map = [
        'akun' => [
            'hint' => 'Data login akun. Pembeli akan menerimanya setelah pembayaran diverifikasi.',
            'fields' => [
                ['key' => 'username', 'label' => 'Username Roblox', 'type' => 'text', 'ph' => 'akunku123'],
                ['key' => 'password', 'label' => 'Password', 'type' => 'text', 'ph' => 'rahasia456'],
                ['key' => 'email',    'label' => 'Email akun', 'type' => 'text', 'ph' => 'email@akun.com', 'opt' => true],
                ['key' => 'catatan',  'label' => 'Catatan (cookie, 2FA, dll)', 'type' => 'textarea', 'ph' => 'Info tambahan untuk pembeli', 'opt' => true],
            ],
        ],
        'item' => [
            'hint' => 'Detail item dalam game dan cara penyerahannya ke pembeli.',
            'fields' => [
                ['key' => 'game',       'label' => 'Nama game', 'type' => 'text', 'ph' => 'Adopt Me'],
                ['key' => 'nama_item',  'label' => 'Nama item & jumlah', 'type' => 'text', 'ph' => 'Neon Dragon x1'],
                ['key' => 'pengiriman', 'label' => 'Cara pengiriman', 'type' => 'select',
                    'opts' => ['Trade in-game', 'Gift / hadiah in-game', 'Lewat private server', 'Lainnya (jelaskan di catatan)']],
                ['key' => 'catatan',    'label' => 'Instruksi untuk pembeli', 'type' => 'textarea', 'ph' => 'Mis. kirim username Roblox kamu ke WhatsApp penjual', 'opt' => true],
            ],
        ],
        'robux' => [
            'hint' => 'Rincian pengiriman Robux ke pembeli.',
            'fields' => [
                ['key' => 'jumlah',   'label' => 'Jumlah Robux', 'type' => 'text', 'ph' => '1000'],
                ['key' => 'metode',   'label' => 'Metode pengiriman', 'type' => 'select',
                    'opts' => ['Gamepass', 'Group payout', 'Transfer langsung', 'Lainnya (jelaskan di catatan)']],
                ['key' => 'estimasi', 'label' => 'Estimasi pengiriman', 'type' => 'text', 'ph' => '1x24 jam', 'opt' => true],
                ['key' => 'catatan',  'label' => 'Instruksi untuk pembeli', 'type' => 'textarea', 'ph' => 'Mis. siapkan link gamepass harga sesuai jumlah', 'opt' => true],
            ],
        ],
        'voucher' => [
            'hint' => 'Kode voucher dan cara memakainya.',
            'fields' => [
                ['key' => 'kode',    'label' => 'Kode voucher', 'type' => 'text', 'ph' => 'ABCD-1234-EFGH'],
                ['key' => 'nominal', 'label' => 'Nominal voucher', 'type' => 'text', 'ph' => 'Rp100.000'],
                ['key' => 'berlaku', 'label' => 'Masa berlaku', 'type' => 'text', 'ph' => '31 Desember 2026', 'opt' => true],
                ['key' => 'cara',    'label' => 'Cara redeem', 'type' => 'textarea', 'ph' => 'Buka roblox.com/redeem lalu masukkan kode', 'opt' => true],
            ],
        ],
    ];
    return $map[strtolower(trim($nama))] ?? [
        'hint' => 'Data yang akan dikirim ke pembeli setelah pembayaran diverifikasi.',
        'fields' => [['key' => 'catatan', 'label' => 'Data / catatan', 'type' => 'textarea', 'ph' => 'Isi data untuk pembeli']],
    ];
}

// Gabungkan nilai kolom kategori menjadi teks "Label: isi" (disimpan di products.data_item
// dan ditampilkan apa adanya di halaman pesanan pembeli).
function susun_data_item($fields, $vals) {
    $baris = [];
    foreach ($fields as $f) {
        $v = trim((string)($vals[$f['key']] ?? ''));
        if ($v !== '') $baris[] = $f['label'] . ': ' . $v;
    }
    return implode("\n", $baris);
}

// Ringkasan keuangan penjual. saldo = pendapatan (pesanan selesai) - penarikan yang menunggu/disetujui
function saldo_penjual($pdo, $sellerId) {
    $a = $pdo->prepare("SELECT COALESCE(SUM(oi.harga),0) FROM order_items oi
        JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id
        WHERE p.seller_id=? AND o.status='selesai'");
    $a->execute([$sellerId]);
    $pendapatan = (int)$a->fetchColumn();

    $b = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN status='disetujui' THEN jumlah END),0) AS cair,
                               COALESCE(SUM(CASE WHEN status='menunggu'  THEN jumlah END),0) AS tunggu
                        FROM withdrawals WHERE seller_id=?");
    $b->execute([$sellerId]);
    $w = $b->fetch();
    return ['pendapatan' => $pendapatan, 'cair' => (int)$w['cair'], 'tunggu' => (int)$w['tunggu'],
            'saldo' => $pendapatan - (int)$w['cair'] - (int)$w['tunggu']];
}

function tarik_label($s) {
    return ['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'][$s] ?? $s;
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
