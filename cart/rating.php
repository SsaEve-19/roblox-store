<?php
require_once '../config/koneksi.php';
role_required('pembeli');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = (int)($_POST['order_item_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $komentar = mb_substr(trim($_POST['komentar'] ?? ''), 0, 255);

    // hanya item dari pesanan milik sendiri yang sudah selesai
    $st = $pdo->prepare("SELECT oi.id, p.seller_id FROM order_items oi
        JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id
        WHERE oi.id=? AND o.buyer_id=? AND o.status='selesai'");
    $st->execute([$itemId, $_SESSION['user']['id']]);
    $item = $st->fetch();

    if ($item && $rating >= 1 && $rating <= 5) {
        // order_item_id UNIQUE: satu item hanya bisa dinilai sekali
        $pdo->prepare("INSERT IGNORE INTO ratings (order_item_id,buyer_id,seller_id,rating,komentar) VALUES (?,?,?,?,?)")
            ->execute([$itemId, $_SESSION['user']['id'], $item['seller_id'], $rating, $komentar ?: null]);
    }
}
header('Location: pesanan.php?rating=1');
