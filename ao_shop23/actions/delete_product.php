<?php


session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../client/index.php');
    exit;
}

if (isset($_GET['ref'])) {
    $ref = $_GET['ref'];
    $stmt = $pdo->prepare("SELECT quantite FROM produits WHERE reference = ?");
    $stmt->execute([$ref]);
    $prod = $stmt->fetch();

    if ($prod && $prod['quantite'] == 0) {
        $stmt = $pdo->prepare("DELETE FROM produits WHERE reference = ?");
        $stmt->execute([$ref]);
        $_SESSION['toast'] = 'Produit supprimé.';
    } else {
        $_SESSION['toast'] = 'Impossible de supprimer: stock non nul.';
    }
}

header('Location: ../admin/produits.php');
exit;
