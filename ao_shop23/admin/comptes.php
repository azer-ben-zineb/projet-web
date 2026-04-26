<?php

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../client/index.php');
    exit;
}

//  Récupération des clients avec stats 
$stmt = $pdo->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM commandes WHERE id_user = u.id_user) as nb_achats,
           (SELECT COUNT(*) FROM abonnements WHERE id_user = u.id_user AND actif = 1 AND date_fin > NOW()) as abonnement_actif
    FROM users u
    WHERE u.role = 'client'
    ORDER BY u.date_inscription DESC
");
$clients = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT id_user, nom, prenom, email, solde, date_inscription
    FROM users
    WHERE role = 'admin'
    ORDER BY date_inscription ASC
");
$admins = $stmt->fetchAll();

$adminSectionTitle = $lang === 'en' ? 'Admin Accounts' : 'Comptes administrateurs';
$adminRoleLabel = $lang === 'en' ? 'Administrator' : 'Administrateur';
$clientsSectionTitle = $lang === 'en' ? 'Client Accounts' : 'Comptes clients';

//  Modal: historique d'un client 
$modalClient = null;
$modalOrders = [];
if (isset($_GET['view'])) {
    $clientId = intval($_GET['view']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ? AND role = 'client'");
    $stmt->execute([$clientId]);
    $modalClient = $stmt->fetch();

    if ($modalClient) {
        $stmt = $pdo->prepare("
            SELECT c.*, p.designation, p.designation_en, p.photo
            FROM commandes c
            JOIN produits p ON c.reference_produit = p.reference
            WHERE c.id_user = ?
            ORDER BY c.date_commande DESC
        ");
        $stmt->execute([$clientId]);
        $modalOrders = $stmt->fetchAll();
    }
}

$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('manage_accounts'); ?> — AO Shop</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<script src="../assets/theme.js"></script>
</head>
<body>
    <div class="fancy-top-gradient"></div>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page-layout">
        <aside class="sidebar">
            <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted); margin-bottom:1rem;">
                <?php echo __t('admin_panel'); ?>
            </div>
            <a href="dashboard.php" class="category-item">📊 <?php echo __t('dashboard'); ?></a>
            <a href="produits.php" class="category-item">📦 <?php echo __t('manage_products'); ?></a>
            <a href="categories.php" class="category-item">📁 <?php echo __t('manage_categories'); ?></a>
            <a href="stock.php" class="category-item">📈 <?php echo __t('manage_stock'); ?></a>
            <a href="comptes.php" class="category-item active">👥 <?php echo __t('manage_accounts'); ?></a>
        </aside>

        <main class="main-content">
            <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:1.5rem;">
                👥 <?php echo __t('manage_accounts'); ?>
            </h1>

            <h2 style="font-size:1rem; font-weight:700; margin-bottom:0.75rem; color:var(--text-muted);">
                🛡️ <?php echo htmlspecialchars($adminSectionTitle); ?>
            </h2>

            <div class="card" style="padding:0; overflow:auto; margin-bottom:1.5rem;">
                <table class="data-table" style="min-width:760px;">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th><?php echo __t('balance'); ?></th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="6" style="color:var(--text-muted); text-align:center; padding:1rem;">—</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $ad): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ad['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($ad['prenom']); ?></td>
                                    <td style="color:var(--text-dim);"><?php echo htmlspecialchars($ad['email']); ?></td>
                                    <td><span class="badge badge-gold"><?php echo htmlspecialchars($adminRoleLabel); ?></span></td>
                                    <td class="price-tag" style="font-size:0.9375rem;">
                                        <?php echo number_format($ad['solde'], 2); ?> DT
                                    </td>
                                    <td style="color:var(--text-muted); font-size:0.875rem;">
                                        <?php echo date('d/m/Y', strtotime($ad['date_inscription'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 style="font-size:1rem; font-weight:700; margin-bottom:0.75rem; color:var(--text-muted);">
                👤 <?php echo htmlspecialchars($clientsSectionTitle); ?>
            </h2>

            <div class="card" style="padding:0; overflow:auto;">
                <table class="data-table" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th><?php echo __t('balance'); ?></th>
                            <th>Date</th>
                            <th>Abonnement</th>
                            <th>Achats</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $cl): ?>
                            <tr style="cursor:pointer;" onclick="window.location.href='?view=<?php echo $cl['id_user']; ?>'">
                                <td><?php echo htmlspecialchars($cl['nom']); ?></td>
                                <td><?php echo htmlspecialchars($cl['prenom']); ?></td>
                                <td style="color:var(--text-dim);"><?php echo htmlspecialchars($cl['email']); ?></td>
                                <td class="price-tag" style="font-size:0.9375rem;">
                                    <?php echo number_format($cl['solde'], 2); ?> DT
                                </td>
                                <td style="color:var(--text-muted); font-size:0.875rem;">
                                    <?php echo date('d/m/Y', strtotime($cl['date_inscription'])); ?>
                                </td>
                                <td>
                                    <?php if ($cl['abonnement_actif'] > 0): ?>
                                        <span class="badge badge-green">Actif</span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8125rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-cyan"><?php echo $cl['nb_achats']; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal d'historique -->
    <?php if ($modalClient): ?>
        <div class="modal-overlay" onclick="if(event.target===this)window.location.href='comptes.php'">
            <div class="modal-content" style="max-width:700px;" onclick="event.stopPropagation()">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 style="font-size:1.25rem; font-weight:800;">
                        📋 <?php echo htmlspecialchars($modalClient['prenom'] . ' ' . $modalClient['nom']); ?>
                    </h2>
                    <a href="comptes.php" class="btn-danger" style="font-size:0.8125rem; padding:0.375rem 0.75rem;">✕</a>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                    <div class="card" style="padding:1rem;">
                        <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;">Email</div>
                        <div style="font-weight:600;"><?php echo htmlspecialchars($modalClient['email']); ?></div>
                    </div>
                    <div class="card" style="padding:1rem;">
                        <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em;"><?php echo __t('balance'); ?></div>
                        <div class="price-tag"><?php echo number_format($modalClient['solde'], 2); ?> DT</div>
                    </div>
                </div>

                <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem; color:var(--text-muted);">
                    📦 <?php echo __t('my_orders'); ?> (<?php echo count($modalOrders); ?>)
                </h3>

                <?php if (empty($modalOrders)): ?>
                    <div style="text-align:center; color:var(--text-muted); padding:2rem;">
                        Aucune commande.
                    </div>
                <?php else: ?>
                    <div style="max-height:400px; overflow-y:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th><?php echo __t('photo'); ?></th>
                                    <th>Produit</th>
                                    <th>Qté</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modalOrders as $order):
                                    $prodName = ($lang === 'en' && !empty($order['designation_en'])) ? $order['designation_en'] : $order['designation'];
                                    $orderImage = get_product_image($order['reference_produit'], $order['photo']);
                                ?>
                                    <tr>
                                        <td style="color:var(--text-muted); font-size:0.875rem;">
                                            <?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($orderImage): ?>
                                                <img src="<?php echo $orderImage; ?>"
                                                     style="width:34px;height:34px;object-fit:contain;border-radius:0.375rem;background:#0f172a;"
                                                     onerror="this.style.display='none'">
                                            <?php else: ?>
                                                <span style="color:#334155;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($prodName); ?></td>
                                        <td><?php echo $order['quantite']; ?></td>
                                        <td class="price-tag" style="font-size:0.9375rem;">
                                            <?php echo number_format($order['prix_unitaire'] * $order['quantite'], 2); ?> DT
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
    function showToast(message, type) {
        const container = document.getElementById('toast-container') || document.body;
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    <?php if ($toast): ?>showToast(<?php echo json_encode($toast); ?>, 'success');<?php endif; ?>
    </script>
</body>
</html>
