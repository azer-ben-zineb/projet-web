<?php


session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['id_user'];

//  Récupération des infos utilisateur 
$stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../auth/logout.php');
    exit;
}

$_SESSION['solde'] = $user['solde'];

//  Récupération de l'historique des commandes 
$stmt = $pdo->prepare("
    SELECT c.*, p.designation, p.designation_en, p.photo
    FROM commandes c
    JOIN produits p ON c.reference_produit = p.reference
    WHERE c.id_user = ?
    ORDER BY c.date_commande DESC
    LIMIT 20
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

//  Récupération des abonnements 
$stmt = $pdo->prepare("
    SELECT * FROM abonnements WHERE id_user = ? ORDER BY date_debut DESC
");
$stmt->execute([$userId]);
$subscriptions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('profile'); ?> — AO Shop</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<script src="../assets/theme.js"></script>
</head>
<body>
    <div class="fancy-top-gradient"></div>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:1.5rem;">
                👤 <?php echo __t('my_profile'); ?>
            </h1>

            <!-- Informations personnelles -->
            <div class="card" style="padding:1.5rem; margin-bottom:1.5rem;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                    <div>
                        <label class="form-label"><?php echo __t('last_name'); ?></label>
                        <div class="form-input" style="background:#1e293b;">
                            <?php echo htmlspecialchars($user['nom']); ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label"><?php echo __t('first_name'); ?></label>
                        <div class="form-input" style="background:#1e293b;">
                            <?php echo htmlspecialchars($user['prenom']); ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label"><?php echo __t('email'); ?></label>
                        <div class="form-input" style="background:#1e293b;">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label"><?php echo __t('balance'); ?></label>
                        <div class="form-input price-tag" style="background:#1e293b; font-size:1rem;">
                            <?php echo number_format($user['solde'], 2); ?> DT
                        </div>
                    </div>
                </div>
                <div style="margin-top:1rem; color:var(--text-muted); font-size:0.875rem;">
                    <?php echo __t('member_since'); ?>:
                    <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
                </div>
            </div>

            <!-- Abonnements -->
            <?php if (!empty($subscriptions)): ?>
                <h2 style="font-size:1.125rem; font-weight:700; margin-bottom:1rem; color:var(--text-muted);">
                    🏷️ <?php echo __t('subscription'); ?>
                </h2>
                <div style="margin-bottom:1.5rem;">
                    <?php foreach ($subscriptions as $sub): ?>
                        <div class="card" style="padding:1rem; margin-bottom:0.75rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong>
                                        <?php echo $sub['type_abonnement'] === 'mensuel' ? __t('monthly_plan') : __t('yearly_plan'); ?>
                                    </strong>
                                    <div style="color:var(--text-muted); font-size:0.875rem;">
                                        <?php echo number_format($sub['prix_abonnement'], 2); ?> DT
                                        • <?php echo __t('subscription_expires'); ?>:
                                        <?php echo date('d/m/Y', strtotime($sub['date_fin'])); ?>
                                    </div>
                                </div>
                                <?php if ($sub['actif'] && strtotime($sub['date_fin']) > time()): ?>
                                    <span class="badge badge-green"><?php echo __t('active_subscription'); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-red">Expiré</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Historique des commandes -->
            <h2 style="font-size:1.125rem; font-weight:700; margin-bottom:1rem; color:var(--text-muted);">
                📦 <?php echo __t('my_orders'); ?>
            </h2>

            <?php if (empty($orders)): ?>
                <div class="card" style="padding:2rem; text-align:center; color:var(--text-muted);">
                    Aucune commande pour le moment.
                </div>
            <?php else: ?>
                <div class="card" style="padding:0; overflow:hidden;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?php echo __t('photo'); ?></th>
                                <th><?php echo __t('reference'); ?></th>
                                <th><?php echo __t('product_name'); ?></th>
                                <th><?php echo __t('quantity'); ?></th>
                                <th><?php echo __t('price'); ?></th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order):
                                $prodName = ($lang === 'en' && !empty($order['designation_en'])) ? $order['designation_en'] : $order['designation'];
                                $orderImage = get_product_image($order['reference_produit'], $order['photo']);
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($orderImage): ?>
                                            <img src="<?php echo $orderImage; ?>"
                                                 style="width:34px;height:34px;object-fit:contain;border-radius:0.375rem;background:#0f172a;"
                                                 onerror="this.style.display='none'">
                                        <?php else: ?>
                                            <span style="color:#334155;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-cyan"><?php echo htmlspecialchars($order['reference_produit']); ?></span></td>
                                    <td><?php echo htmlspecialchars($prodName); ?></td>
                                    <td><?php echo $order['quantite']; ?></td>
                                    <td class="price-tag" style="font-size:0.9375rem;">
                                        <?php echo number_format($order['prix_unitaire'], 2); ?> DT
                                    </td>
                                    <td style="color:var(--text-muted); font-size:0.875rem;">
                                        <?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
