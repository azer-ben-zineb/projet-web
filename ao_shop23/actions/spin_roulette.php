<?php
session_start();
require_once __DIR__ . '/../db.php';
if (!isset($_SESSION['id_user']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')) { header('Location: ../auth/login.php'); exit; }

$uid  = (int)$_SESSION['id_user'];
$cost = 10.00;

try {
    $pdo->beginTransaction();

    // Détermine si le spin gratuit est dispo (24h depuis le dernier)
    $stmt = $pdo->prepare('SELECT * FROM roulette_log WHERE id_user = ? ORDER BY id_log DESC LIMIT 1');
    $stmt->execute([$uid]);
    $last = $stmt->fetch();
    $freeAvailable = !$last || (time() - strtotime($last['derniere_spin']) >= 86400);

    if (!$freeAvailable) {
        // Spin payant : on déduit 10 DT
        $stmt = $pdo->prepare('SELECT solde FROM users WHERE id_user = ? FOR UPDATE');
        $stmt->execute([$uid]);
        $solde = (float)$stmt->fetchColumn();
        if ($solde < $cost) throw new Exception('Solde insuffisant pour un spin payant.');
        $pdo->prepare('UPDATE users SET solde = solde - ? WHERE id_user = ?')->execute([$cost, $uid]);
        $_SESSION['solde'] = $solde - $cost;
    }

    // Tirage : 5/10/15/20/25%
    $reductions = [5, 10, 15, 20, 25];
    $reduction  = $reductions[array_rand($reductions)];

    // Code coupon unique
    $code = 'AO' . strtoupper(bin2hex(random_bytes(4)));

    $pdo->prepare('INSERT INTO coupons (id_user, code_coupon, reduction, expiration) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 3 HOUR))')
        ->execute([$uid, $code, $reduction]);

    // Une seule ligne de log par utilisateur (clé unique unique_user_spin)
    $pdo->prepare(
        'INSERT INTO roulette_log (id_user, derniere_spin, gratuit_utilise)
         VALUES (?, NOW(), ?)
         ON DUPLICATE KEY UPDATE
            derniere_spin = VALUES(derniere_spin),
            gratuit_utilise = VALUES(gratuit_utilise)'
    )->execute([$uid, $freeAvailable ? 1 : 0]);

    $pdo->commit();
    header('Location: ../client/roulette.php?flash=success:' . urlencode("Bravo ! Coupon $code (-$reduction%)"));
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../client/roulette.php?flash=error:' . urlencode($e->getMessage()));
}
exit;
