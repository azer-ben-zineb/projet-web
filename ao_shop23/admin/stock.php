<?php


session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../client/index.php');
    exit;
}

$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);

//  Traitement de la vente 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sell'])) {
    $ref = $_POST['reference'] ?? '';
    $qty = intval($_POST['quantity'] ?? 0);

    if ($ref && $qty > 0) {
        $stmt = $pdo->prepare("SELECT quantite, prix FROM produits WHERE reference = ?");
        $stmt->execute([$ref]);
        $prod = $stmt->fetch();

        if ($prod) {
            if ($prod['quantite'] < $qty) {
                $toast = 'Stock insuffisant. Disponible: ' . $prod['quantite'];
            } else {
                try {
                    $pdo->beginTransaction();

                    // Mettre à jour le stock et les ventes
                    $stmt = $pdo->prepare("
                        UPDATE produits
                        SET quantite = quantite - ?,
                            nombre_ventes = nombre_ventes + ?
                        WHERE reference = ?
                    ");
                    $stmt->execute([$qty, $qty, $ref]);

                    // Créer une commande fictive (vente en magasin)
                    $stmt = $pdo->prepare("
                        INSERT INTO commandes (id_user, reference_produit, quantite, prix_unitaire)
                        VALUES (1, ?, ?, ?)
                    ");
                    $stmt->execute([$ref, $qty, $prod['prix']]);

                    $pdo->commit();
                    $toast = 'Vente de ' . $qty . ' unité(s) enregistrée.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $toast = 'Erreur: ' . $e->getMessage();
                }
            }
        }
    }
    header('Location: stock.php');
    exit;
}

//  Récupération des produits avec stock 
$search = trim($_GET['search'] ?? '');
$sql = "
    SELECT p.*, c.nom_categorie
    FROM produits p
    JOIN categories c ON p.id_categorie = c.id_categorie
    WHERE 1=1
";
$params = [];
if ($search) {
    $sql .= " AND (p.reference LIKE ? OR p.designation LIKE ? OR p.marque LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.quantite ASC, p.reference ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Statistiques de stock
$lowStock = count(array_filter($products, fn($p) => $p['quantite'] > 0 && $p['quantite'] <= 5));
$outOfStock = count(array_filter($products, fn($p) => $p['quantite'] == 0));
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('manage_stock'); ?> — AO Shop</title>
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
            <a href="stock.php" class="category-item active">📈 <?php echo __t('manage_stock'); ?></a>
            <a href="comptes.php" class="category-item">👥 <?php echo __t('manage_accounts'); ?></a>
        </aside>

        <main class="main-content">
            <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:1rem;">
                📈 <?php echo __t('manage_stock'); ?>
            </h1>

            <!-- Indicateurs rapides -->
            <div class="stats-grid" style="margin-bottom:1.5rem;">
                <div class="stat-card" style="padding:1rem;">
                    <div class="stat-card-value" style="font-size:1.5rem;"><?php echo count($products); ?></div>
                    <div class="stat-card-label">Références totales</div>
                </div>
                <div class="stat-card" style="padding:1rem;">
                    <div class="stat-card-value" style="font-size:1.5rem; color:#fb923c;"><?php echo $lowStock; ?></div>
                    <div class="stat-card-label">Stock faible (1-5)</div>
                </div>
                <div class="stat-card" style="padding:1rem;">
                    <div class="stat-card-value" style="font-size:1.5rem; color:#f87171;"><?php echo $outOfStock; ?></div>
                    <div class="stat-card-label">Rupture de stock</div>
                </div>
            </div>

            <!-- Recherche -->
            <form method="GET" action="" style="margin-bottom:1.5rem; display:flex; gap:0.5rem; max-width:400px;">
                <input type="text" name="search" class="form-input"
                       placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-primary" style="padding:0.625rem 1rem;">🔍</button>
            </form>

            <!-- Table de stock -->
            <div class="card" style="padding:0; overflow:auto;">
                <table class="data-table" style="min-width:920px;">
                    <thead>
                        <tr>
                            <th><?php echo __t('photo'); ?></th>
                            <th><?php echo __t('reference'); ?></th>
                            <th><?php echo __t('product_name'); ?></th>
                            <th><?php echo __t('brand'); ?></th>
                            <th>Cat.</th>
                            <th><?php echo __t('quantity'); ?></th>
                            <th>Ventes</th>
                            <th>Vendre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod):
                            $stockClass = $prod['quantite'] > 5 ? 'high' : ($prod['quantite'] > 0 ? 'low' : 'zero');
                            $prodImage = get_product_image($prod['reference'], $prod['photo']);
                        ?>
                            <tr>
                                <td>
                                    <?php if ($prodImage): ?>
                                        <img src="<?php echo $prodImage; ?>"
                                             style="width:40px;height:40px;object-fit:contain;border-radius:0.375rem;background:#0f172a;"
                                             onerror="this.style.display='none'">
                                    <?php else: ?>
                                        <span style="color:#334155;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-cyan"><?php echo htmlspecialchars($prod['reference']); ?></span></td>
                                <td><?php echo htmlspecialchars($prod['designation']); ?></td>
                                <td><?php echo htmlspecialchars($prod['marque']); ?></td>
                                <td style="font-size:0.8125rem; color:var(--text-muted);"><?php echo htmlspecialchars($prod['nom_categorie']); ?></td>
                                <td>
                                    <span class="stock-indicator <?php echo $stockClass; ?>" style="font-weight:700;">
                                        ● <?php echo $prod['quantite']; ?>
                                    </span>
                                </td>
                                <td><?php echo $prod['nombre_ventes']; ?></td>
                                <td>
                                    <?php if ($prod['quantite'] > 0): ?>
                                        <form method="POST" action="" style="display:flex; gap:0.375rem; align-items:center;">
                                            <input type="hidden" name="reference" value="<?php echo htmlspecialchars($prod['reference']); ?>">
                                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $prod['quantite']; ?>"
                                                   class="form-input" style="width:60px; padding:0.375rem; font-size:0.875rem;">
                                            <button type="submit" name="sell" class="btn-primary" style="font-size:0.8125rem; padding:0.375rem 0.75rem;">
                                                ✅
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge-red" style="font-size:0.6875rem;">Rupture</span>
                                    <?php endif; ?>
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
