<?php


session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['id_user'];
$reference = $_GET['ref'] ?? '';
$redirect = $_GET['redirect'] ?? '../client/panier.php';

if ($reference) {
    // Vérifier que le produit existe et est en stock
    $stmt = $pdo->prepare("SELECT quantite FROM produits WHERE reference = ?");
    $stmt->execute([$reference]);
    $prod = $stmt->fetch();

    if ($prod && $prod['quantite'] > 0) {
        // Vérifier si déjà dans le panier
        $stmt = $pdo->prepare("
            SELECT id_panier, quantite FROM panier WHERE id_user = ? AND reference_produit = ?
        ");
        $stmt->execute([$userId, $reference]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Mettre à jour la quantité et l'expiration
            $stmt = $pdo->prepare("
                UPDATE panier
                SET quantite = quantite + 1, expiration = DATE_ADD(NOW(), INTERVAL 7 DAY)
                WHERE id_panier = ?
            ");
            $stmt->execute([$existing['id_panier']]);
        } else {
            // Insérer un nouvel article
            $stmt = $pdo->prepare("
                INSERT INTO panier (id_user, reference_produit, quantite, expiration)
                VALUES (?, ?, 1, DATE_ADD(NOW(), INTERVAL 7 DAY))
            ");
            $stmt->execute([$userId, $reference]);
        }

        $_SESSION['toast'] = 'Produit ajouté au panier ✓';
    } else {
        $_SESSION['toast'] = 'Produit en rupture de stock.';
    }
}

// Redirection avec support du paramètre redirect pour la page de comparaison
if (strpos($redirect, 'comparer.php') !== false) {
    header('Location: ../client/' . $redirect);
} else {
    header('Location: ../client/panier.php');
}
exit;
