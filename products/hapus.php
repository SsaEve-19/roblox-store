<?php
require_once '../config/koneksi.php';
role_required('penjual');
$id = (int)($_GET['id'] ?? 0);
$files = berkas_produk($pdo, $id);   // ambil sebelum baris dihapus
$st = $pdo->prepare("DELETE FROM products WHERE id=? AND seller_id=? AND status='tersedia'");
$st->execute([$id, $_SESSION['user']['id']]);
if ($st->rowCount()) hapus_berkas($files);
header('Location: saya.php');
