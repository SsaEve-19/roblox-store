<?php
require_once '../config/koneksi.php';
role_required('pembeli');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) { header('Location: index.php'); exit; }

$pdo->beginTransaction();
try {
    $in = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $st = $pdo->prepare("SELECT * FROM products WHERE id IN ($in) AND status='tersedia' FOR UPDATE");
    $st->execute($_SESSION['cart']);
    $items = $st->fetchAll();
    if (!$items) throw new Exception('Produk tidak tersedia.');

    $total = array_sum(array_column($items, 'harga'));
    $pdo->prepare("INSERT INTO orders (buyer_id,total) VALUES (?,?)")->execute([$_SESSION['user']['id'], $total]);
    $orderId = $pdo->lastInsertId();

    foreach ($items as $i) {
        $pdo->prepare("INSERT INTO order_items (order_id,product_id,harga) VALUES (?,?,?)")->execute([$orderId, $i['id'], $i['harga']]);
        $pdo->prepare("UPDATE products SET status='terjual' WHERE id=?")->execute([$i['id']]);
    }
    $pdo->commit();
    $_SESSION['cart'] = [];
    header('Location: pesanan.php?sukses=1');
} catch (Exception $ex) {
    $pdo->rollBack();
    die('Checkout gagal: ' . e($ex->getMessage()));
}
