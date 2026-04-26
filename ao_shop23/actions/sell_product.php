<?php


session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../client/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = $_POST['reference'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);

    if ($reference && $quantity > 0) {
        $stmt = $pdo->prepare("SELECT quantite, prix FROM produits WHERE reference = ?");
        $stmt->execute([$reference]);
        $prod = $stmt->fetch();

        if ($prod && $prod['quantite'] >= $quantity) {
            try {
                $pdo->beginTransaction();

                // Mise à jour du stock
                $stmt = $pdo->prepare("
                    UPDATE produits
                    SET quantite = quantite - ?, nombre_ventes = nombre_ventes + ?
                    WHERE reference = ?
                ");
                $stmt->execute([$quantity, $quantity, $reference]);

                // Créer une commande (id_user=1 = admin système)
                $stmt = $pdo->prepare("
                    INSERT INTO commandes (id_user, reference_produit, quantite, prix_unitaire)
                    VALUES (1, ?, ?, ?)
                ");
                $stmt->execute([$reference, $quantity, $prod['prix']]);

                $pdo->commit();
                $_SESSION['toast'] = 'Vente enregistrée.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['toast'] = 'Erreur: ' . $e->getMessage();
            }
        } else {
            $_SESSION['toast'] = 'Stock insuffisant.';
        }
    }
}

header('Location: ../admin/stock.php');
exit;
