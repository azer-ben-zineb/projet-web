<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { 
    header('Location: ../admin/dashboard.php'); 
    exit; 
}
if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('subscription'); ?> — AO Shop</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<script src="../assets/theme.js"></script>
</head>
<body>
    <div class="fancy-top-gradient"></div>
    <?php 
    include __DIR__ . '/../includes/header.php'; 

    $uid = (int)($_SESSION['id_user'] ?? 0);

    // Abonnement actif courant ?
    $stmt = $pdo->prepare('SELECT * FROM abonnements WHERE id_user = ? AND actif = 1 AND date_fin > NOW() ORDER BY id_abonnement DESC LIMIT 1');
    $stmt->execute([$uid]);
    $current = $stmt->fetch();
    ?>
<div class="container" style="padding-top:2rem; padding-bottom:3rem;">
    <h1 style="margin-bottom:0.5rem;">📦 <?= __t('subscription') ?></h1>
    <p style="color:var(--text-muted); margin-bottom:2rem;">Profitez d'avantages exclusifs avec un abonnement AO Shop.</p>

    <?php if ($current): ?>
        <div class="card glow-card" style="margin-bottom:2rem; padding:2rem;">
            <h3>✓ <?= __t('active_subscription') ?> : <?= htmlspecialchars(ucfirst($current['type_abonnement'])) ?></h3>
            <p><?= __t('subscription_expires') ?> : <strong><?= htmlspecialchars(date('d/m/Y', strtotime($current['date_fin']))) ?></strong></p>
        </div>
    <?php else: ?>
        <div class="product-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div class="card" style="text-align:center; padding:2rem;">
                <h2><?= __t('monthly_plan') ?></h2>
                <div class="price-tag" style="font-size:2.5rem; margin:1rem 0;"><?= __t('monthly_price') ?></div>
                <p style="color:var(--text-muted);"></p>
                <form method="post" action="../actions/subscribe.php" style="margin-top:1.5rem;">
                    <input type="hidden" name="type" value="mensuel">
                    <button class="btn-primary" type="submit" style="width:100%; justify-content:center;"><?= __t('subscribe') ?></button>
                </form>
            </div>
            <div class="card glow-card" style="text-align:center; padding:2rem;">
                <h2><?= __t('yearly_plan') ?> <span class="badge badge-green">-28%</span></h2>
                <div class="price-tag" style="font-size:2.5rem; margin:1rem 0;"><?= __t('yearly_price') ?></div>
                <p style="color:var(--text-muted);"></p>
                <form method="post" action="../actions/subscribe.php" style="margin-top:1.5rem;">
                    <input type="hidden" name="type" value="annuel">
                    <button class="btn-primary" type="submit" style="width:100%; justify-content:center;"><?= __t('subscribe') ?></button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
