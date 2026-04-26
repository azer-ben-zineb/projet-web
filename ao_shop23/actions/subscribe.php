<?php
require_once __DIR__ . '/../db.php';
if (!is_logged_in() || is_admin()) { header('Location: ../auth/login.php'); exit; }

$uid  = (int)$_SESSION['user']['id_user'];
$type = $_POST['type'] ?? '';

if (!in_array($type, ['mensuel', 'annuel'], true)) {
    header('Location: ../client/abonnement.php?flash=error:' . urlencode('Type invalide'));
    exit;
}
$prix   = $type === 'mensuel' ? 29.00 : 249.00;
$dureeM = $type === 'mensuel' ? 1 : 12;

try {
    $pdo->beginTransaction();

    // Vérifie qu'aucun abonnement actif n'existe déjà
    $stmt = $pdo->prepare('SELECT 1 FROM abonnements WHERE id_user = ? AND actif = 1 AND date_fin > NOW()');
    $stmt->execute([$uid]);
    if ($stmt->fetch()) throw new Exception('Vous avez déjà un abonnement actif.');

    // Vérifie le solde
    $stmt = $pdo->prepare('SELECT solde FROM users WHERE id_user = ? FOR UPDATE');
    $stmt->execute([$uid]);
    $solde = (float)$stmt->fetchColumn();
    if ($solde < $prix) throw new Exception('Solde insuffisant.');

    $pdo->prepare('UPDATE users SET solde = solde - ? WHERE id_user = ?')->execute([$prix, $uid]);
    $pdo->prepare('INSERT INTO abonnements (id_user, type_abonnement, prix_abonnement, date_debut, date_fin, actif)
                   VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MONTH), 1)')
        ->execute([$uid, $type, $prix, $dureeM]);
    $pdo->commit();

    $_SESSION['user']['solde'] = $solde - $prix;

    // Email de confirmation
    @mail($_SESSION['user']['email'], 'Abonnement AO Shop confirmé', "Votre abonnement $type est actif.\nMerci !");

    header('Location: ../client/abonnement.php?flash=success:' . urlencode('Abonnement activé ✓'));
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../client/abonnement.php?flash=error:' . urlencode($e->getMessage()));
}
exit;
