<?php


session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/discounts.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['id_user'];
$reference = trim((string)($_GET['ref'] ?? ''));

if ($reference === '' || strcasecmp($reference, 'undefined') === 0 || strcasecmp($reference, 'null') === 0) {
    $_SESSION['toast'] = 'Produit invalide.';
    $_SESSION['toast_type'] = 'error';
    header('Location: ../client/index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $pricingContext = get_user_pricing_context($pdo, (int)$userId, true);
    $discountPercent = (int)$pricingContext['effective_discount_percent'];

    // Verrouiller le produit pour éviter les achats concurrents.
    $stmt = $pdo->prepare("SELECT reference, prix, quantite FROM produits WHERE reference = ? FOR UPDATE");
    $stmt->execute([$reference]);
    $prod = $stmt->fetch();

    if (!$prod) {
        throw new RuntimeException('Produit introuvable.');
    }
    if ((int)$prod['quantite'] <= 0) {
        throw new RuntimeException('Produit en rupture de stock.');
    }

    // Verrouiller la ligne utilisateur avant déduction du solde.
    $stmt = $pdo->prepare("SELECT solde FROM users WHERE id_user = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new RuntimeException('Compte utilisateur introuvable.');
    }
    $finalPrice = apply_discount_to_price((float)$prod['prix'], $discountPercent);

    if ((float)$user['solde'] < $finalPrice) {
        throw new RuntimeException('Solde insuffisant pour cet achat.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO commandes (id_user, reference_produit, quantite, prix_unitaire)
        VALUES (?, ?, 1, ?)
    ");
    $stmt->execute([$userId, $reference, $finalPrice]);

    $stmt = $pdo->prepare("
        UPDATE produits
        SET quantite = quantite - 1, nombre_ventes = nombre_ventes + 1
        WHERE reference = ? AND quantite > 0
    ");
    $stmt->execute([$reference]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Stock indisponible, veuillez réessayer.');
    }

    $stmt = $pdo->prepare("UPDATE users SET solde = solde - ? WHERE id_user = ?");
    $stmt->execute([$finalPrice, $userId]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Impossible de mettre à jour le solde utilisateur.');
    }

    if (!empty($pricingContext['coupon_applied']) && !empty($pricingContext['coupon_id'])) {
        $stmt = $pdo->prepare("UPDATE coupons SET utilise = 1 WHERE id_coupon = ? AND id_user = ?");
        $stmt->execute([(int)$pricingContext['coupon_id'], $userId]);
    }

    $stmt = $pdo->prepare("SELECT solde FROM users WHERE id_user = ?");
    $stmt->execute([$userId]);
    $_SESSION['solde'] = (float)($stmt->fetchColumn() ?? 0);

    $pdo->commit();
    $_SESSION['toast'] = 'Achat effectué avec succès !';
    $_SESSION['toast_type'] = 'success';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['toast'] = $e->getMessage();
    $_SESSION['toast_type'] = 'error';
}

header('Location: ../client/index.php');
exit;
