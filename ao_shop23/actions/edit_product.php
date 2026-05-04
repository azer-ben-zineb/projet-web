<?php


session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../client/index.php');
    exit;
}

// La logique d'édition est gérée directement dans add_product.php avec is_edit=1
header('Location: ../admin/produits.php');
exit;
