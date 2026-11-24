<?php
require_once '../config/koneksi.php';
role_required('pembeli');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare("SELECT id FROM orders WHERE id=? AND buyer_id=? AND status='menunggu_pembayaran'");
    $st->execute([$id, $_SESSION['user']['id']]);
    if ($st->fetch()) {
        $pdo->prepare("UPDATE orders SET status='dibatalkan' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE products SET status='tersedia' WHERE id IN (SELECT product_id FROM order_items WHERE order_id=?)")->execute([$id]);
    }
}
header('Location: pesanan.php');
