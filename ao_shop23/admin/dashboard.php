<?php


session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';

// Protection admin uniquement
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../client/index.php');
    exit;
}

//  Statistiques globales 
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(prix_unitaire * quantite), 0) FROM commandes")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$totalClients = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();

//  Meilleur et pire produit 
$bestProduct = $pdo->query("
    SELECT * FROM produits ORDER BY nombre_ventes DESC LIMIT 1
")->fetch();

$worstProduct = $pdo->query("
    SELECT * FROM produits WHERE nombre_ventes > 0 ORDER BY nombre_ventes ASC LIMIT 1
")->fetch();

//  Meilleure catégorie 
$bestCategory = $pdo->query("
    SELECT c.nom_categorie, c.nom_categorie_en, SUM(p.nombre_ventes) as total_ventes
    FROM categories c
    JOIN produits p ON c.id_categorie = p.id_categorie
    GROUP BY c.id_categorie
    ORDER BY total_ventes DESC
    LIMIT 1
")->fetch();

//  Top 5 produits
$topProducts = $pdo->query("
    SELECT * FROM produits ORDER BY nombre_ventes DESC LIMIT 5
")->fetchAll();

//  10 dernières commandes 
$recentOrders = $pdo->query("
    SELECT c.*, u.nom, u.prenom, p.designation, p.designation_en, p.photo
    FROM commandes c
    JOIN users u ON c.id_user = u.id_user
    JOIN produits p ON c.reference_produit = p.reference
    ORDER BY c.date_commande DESC
    LIMIT 10
")->fetchAll();

//  Données pour le graphique des revenus par mois ───
$revenueData = $pdo->query("
    SELECT DATE_FORMAT(date_commande, '%Y-%m') as mois, COALESCE(SUM(prix_unitaire * quantite), 0) as revenu
    FROM commandes
    WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mois
    ORDER BY mois ASC
")->fetchAll();

$labels = array_column($revenueData, 'mois');
$data = array_column($revenueData, 'revenu');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('dashboard'); ?> — AO Shop</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="../assets/theme.js"></script>
</head>
<body>
    <div class="fancy-top-gradient"></div>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page-layout">
        <!-- Sidebar admin -->
        <aside class="sidebar">
            <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em;
                        color:var(--text-muted); margin-bottom:1rem;">
                <?php echo __t('admin_panel'); ?>
            </div>
            <a href="dashboard.php" class="category-item active">📊 <?php echo __t('dashboard'); ?></a>
            <a href="produits.php" class="category-item">📦 <?php echo __t('manage_products'); ?></a>
            <a href="categories.php" class="category-item">📁 <?php echo __t('manage_categories'); ?></a>
            <a href="stock.php" class="category-item">📈 <?php echo __t('manage_stock'); ?></a>
            <a href="comptes.php" class="category-item">👥 <?php echo __t('manage_accounts'); ?></a>
        </aside>

        <main class="main-content">
            <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:1.5rem;">
                📊 <?php echo __t('dashboard'); ?>
            </h1>

            <!-- Statistiques principales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-value"><?php echo number_format($totalRevenue, 0); ?> DT</div>
                    <div class="stat-card-label"><?php echo __t('total_revenue'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-value"><?php echo $totalOrders; ?></div>
                    <div class="stat-card-label"><?php echo __t('total_orders'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-value"><?php echo $totalClients; ?></div>
                    <div class="stat-card-label"><?php echo __t('total_clients'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-value"><?php echo $totalProducts; ?></div>
                    <div class="stat-card-label"><?php echo __t('products'); ?></div>
                </div>
            </div>

            <!-- Meilleur/pire produit et meilleure catégorie -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
                <?php if ($bestProduct): ?>
                    <div class="card" style="padding:1.25rem;">
                        <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--success); margin-bottom:0.5rem;">
                            ⭐ <?php echo __t('best_product'); ?>
                        </div>
                        <?php $bestImage = get_product_image($bestProduct['reference'], $bestProduct['photo']); ?>
                        <?php if ($bestImage): ?>
                            <img src="<?php echo $bestImage; ?>"
                                 style="width:56px;height:56px;object-fit:contain;border-radius:0.5rem;background:#0f172a;margin-bottom:0.625rem;"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>
                        <div style="font-weight:700; margin-bottom:0.25rem;"><?php echo htmlspecialchars($bestProduct['designation']); ?></div>
                        <div style="color:var(--text-muted); font-size:0.875rem;">
                            <?php echo $bestProduct['nombre_ventes']; ?> ventes • <?php echo number_format($bestProduct['prix'], 2); ?> DT
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($worstProduct): ?>
                    <div class="card" style="padding:1.25rem;">
                        <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--danger); margin-bottom:0.5rem;">
                            📉 <?php echo __t('worst_product'); ?>
                        </div>
                        <?php $worstImage = get_product_image($worstProduct['reference'], $worstProduct['photo']); ?>
                        <?php if ($worstImage): ?>
                            <img src="<?php echo $worstImage; ?>"
                                 style="width:56px;height:56px;object-fit:contain;border-radius:0.5rem;background:#0f172a;margin-bottom:0.625rem;"
                                 onerror="this.style.display='none'">
                        <?php endif; ?>
                        <div style="font-weight:700; margin-bottom:0.25rem;"><?php echo htmlspecialchars($worstProduct['designation']); ?></div>
                        <div style="color:var(--text-muted); font-size:0.875rem;">
                            <?php echo $worstProduct['nombre_ventes']; ?> ventes • <?php echo number_format($worstProduct['prix'], 2); ?> DT
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($bestCategory): ?>
                    <div class="card" style="padding:1.25rem;">
                        <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#facc15; margin-bottom:0.5rem;">
                            🏆 <?php echo __t('best_category'); ?>
                        </div>
                        <div style="font-weight:700; margin-bottom:0.25rem;">
                            <?php echo htmlspecialchars($bestCategory['nom_categorie']); ?>
                        </div>
                        <div style="color:var(--text-muted); font-size:0.875rem;">
                            <?php echo $bestCategory['total_ventes']; ?> ventes totales
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Graphique des revenus -->
            <div class="card" style="padding:1.5rem; margin-bottom:2rem;">
                <div style="font-size:0.8125rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:1rem;">
                    📈 Revenus mensuels (DT)
                </div>
                <canvas id="revenueChart" height="80"></canvas>
            </div>

            <!-- Top 5 produits -->
            <div class="card" style="padding:1.5rem; margin-bottom:2rem;">
                <div style="font-size:0.8125rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:1rem;">
                    🔥 Top 5 — <?php echo __t('best_sellers'); ?>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo __t('photo'); ?></th>
                            <th>Réf.</th>
                            <th><?php echo __t('product_name'); ?></th>
                            <th><?php echo __t('brand'); ?></th>
                            <th><?php echo __t('price'); ?></th>
                            <th>Ventes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $prod):
                            $prodImage = get_product_image($prod['reference'], $prod['photo']);
                        ?>
                            <tr>
                                <td>
                                    <?php if ($prodImage): ?>
                                        <img src="<?php echo $prodImage; ?>"
                                             style="width:34px;height:34px;object-fit:contain;border-radius:0.375rem;background:#0f172a;"
                                             onerror="this.style.display='none'">
                                    <?php else: ?>
                                        <span style="color:#334155;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-cyan"><?php echo htmlspecialchars($prod['reference']); ?></span></td>
                                <td><?php echo htmlspecialchars($prod['designation']); ?></td>
                                <td><?php echo htmlspecialchars($prod['marque']); ?></td>
                                <td class="price-tag" style="font-size:0.9375rem;"><?php echo number_format($prod['prix'], 2); ?> DT</td>
                                <td><?php echo $prod['nombre_ventes']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Dernières commandes -->
            <div class="card" style="padding:1.5rem;">
                <div style="font-size:0.8125rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:1rem;">
                    🛒 <?php echo __t('recent_orders'); ?>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th><?php echo __t('photo'); ?></th>
                            <th>Produit</th>
                            <th>Qté</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order):
                            $prodName = ($lang === 'en' && !empty($order['designation_en'])) ? $order['designation_en'] : $order['designation'];
                            $orderImage = get_product_image($order['reference_produit'], $order['photo']);
                        ?>
                            <tr>
                                <td style="color:var(--text-muted); font-size:0.875rem;">
                                    <?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($order['prenom'] . ' ' . $order['nom']); ?></td>
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
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
    //  Graphique des revenus 
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Revenus (DT)',
                data: <?php echo json_encode($data); ?>,
                backgroundColor: 'rgba(88, 225, 255, 0.3)',
                borderColor: '#58E1FF',
                borderWidth: 1,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(96, 165, 250, 0.1)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
    </script>
</body>
</html>
