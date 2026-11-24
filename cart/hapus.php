<?php
require_once '../config/koneksi.php';
role_required('pembeli');
$id = (int)($_GET['id'] ?? 0);
$_SESSION['cart'] = array_values(array_diff($_SESSION['cart'] ?? [], [$id]));
header('Location: index.php');
