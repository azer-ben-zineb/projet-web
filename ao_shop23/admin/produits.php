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

//  Traitement des formulaires 

// Suppression
if (isset($_GET['delete'])) {
    $ref = $_GET['delete'];
    // Vérifier que le stock est nul
    $stmt = $pdo->prepare("SELECT quantite FROM produits WHERE reference = ?");
    $stmt->execute([$ref]);
    $prod = $stmt->fetch();

    if ($prod && $prod['quantite'] == 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM produits WHERE reference = ?");
            $stmt->execute([$ref]);
            $_SESSION['toast'] = __t('product_deleted');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['toast'] = __t('cannot_delete_linked');
            } else {
                $_SESSION['toast'] = "Erreur: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['toast'] = __t('cannot_delete');
    }
    header('Location: produits.php');
    exit;
}

// Récupération des catégories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id_categorie");
$categories = $stmt->fetchAll();

// Récupération des produits
$stmt = $pdo->query("
    SELECT p.*, c.nom_categorie
    FROM produits p
    JOIN categories c ON p.id_categorie = c.id_categorie
    ORDER BY p.reference
");
$products = $stmt->fetchAll();

// Édition en cours ?
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.nom_categorie
        FROM produits p
        JOIN categories c ON p.id_categorie = c.id_categorie
        WHERE p.reference = ?
    ");
    $stmt->execute([$_GET['edit']]);
    $editProduct = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('manage_products'); ?> — AO Shop</title>
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
            <a href="produits.php" class="category-item active">📦 <?php echo __t('manage_products'); ?></a>
            <a href="categories.php" class="category-item">📁 <?php echo __t('manage_categories'); ?></a>
            <a href="stock.php" class="category-item">📈 <?php echo __t('manage_stock'); ?></a>
            <a href="comptes.php" class="category-item">👥 <?php echo __t('manage_accounts'); ?></a>
        </aside>

        <main class="main-content">
            <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:1.5rem;">
                📦 <?php echo __t('manage_products'); ?>
            </h1>

            <!-- Formulaire Ajouter / Modifier -->
            <div class="card" style="padding:1.5rem; margin-bottom:2rem;">
                <h2 style="font-size:1.125rem; font-weight:700; margin-bottom:1rem;">
                    <?php echo $editProduct ? '✏️ ' . __t('edit') : '➕ ' . __t('add'); ?>
                </h2>
                <form method="POST" action="../actions/add_product.php" enctype="multipart/form-data">
                    <input type="hidden" name="is_edit" value="<?php echo $editProduct ? '1' : '0'; ?>">

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label"><?php echo __t('reference'); ?></label>
                            <input type="text" name="reference" class="form-input" required
                                   value="<?php echo htmlspecialchars($editProduct['reference'] ?? ''); ?>"
                                   <?php echo $editProduct ? 'readonly style="background:#1e293b; opacity:0.7;"' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo __t('brand'); ?></label>
                            <input type="text" name="marque" class="form-input" required
                                   value="<?php echo htmlspecialchars($editProduct['marque'] ?? ''); ?>">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Désignation (FR)</label>
                            <input type="text" name="designation" class="form-input" required
                                   value="<?php echo htmlspecialchars($editProduct['designation'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Désignation (EN)</label>
                            <input type="text" name="designation_en" class="form-input"
                                   value="<?php echo htmlspecialchars($editProduct['designation_en'] ?? ''); ?>">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Description (FR)</label>
                            <textarea name="description" class="form-input" rows="2"
                                      style="resize:vertical;"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description (EN)</label>
                            <textarea name="description_en" class="form-input" rows="2"
                                      style="resize:vertical;"><?php echo htmlspecialchars($editProduct['description_en'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label"><?php echo __t('price'); ?> (DT)</label>
                            <input type="number" name="prix" class="form-input" step="0.01" min="0.01" required
                                   value="<?php echo $editProduct['prix'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo __t('quantity'); ?></label>
                            <input type="number" name="quantite" class="form-input" min="0" required
                                   value="<?php echo $editProduct['quantite'] ?? '0'; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo __t('category'); ?></label>
                            <select name="id_categorie" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id_categorie']; ?>"
                                        <?php echo ($editProduct['id_categorie'] ?? '') == $cat['id_categorie'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?php echo __t('photo'); ?></label>
                        <input type="file" name="photo" class="form-input" accept="image/*">
                        <?php if ($editProduct): ?>
                            <?php $editImage = get_product_image($editProduct['reference'], $editProduct['photo']); ?>
                            <?php if ($editImage): ?>
                                <div style="margin-top:0.5rem;">
                                    <img src="<?php echo $editImage; ?>"
                                         style="width:56px;height:56px;object-fit:contain;border-radius:0.5rem;background:#0f172a;"
                                         onerror="this.style.display='none'">
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($editProduct['photo'])): ?>
                                <div style="margin-top:0.5rem; font-size:0.8125rem; color:var(--text-muted);">
                                    Actuelle: <?php echo htmlspecialchars($editProduct['photo']); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-primary">
                        <?php echo $editProduct ? __t('save') : __t('add'); ?>
                    </button>
                    <?php if ($editProduct): ?>
                        <a href="produits.php" class="btn-ghost" style="margin-left:0.5rem;"><?php echo __t('cancel'); ?></a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Liste des produits -->
            <div class="card" style="padding:1.5rem; overflow:auto;">
                <table class="data-table" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th><?php echo __t('photo'); ?></th>
                            <th><?php echo __t('reference'); ?></th>
                            <th><?php echo __t('product_name'); ?></th>
                            <th><?php echo __t('brand'); ?></th>
                            <th><?php echo __t('price'); ?></th>
                            <th><?php echo __t('quantity'); ?></th>
                            <th>Ventes</th>
                            <th><?php echo __t('actions'); ?></th>
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
                                <td class="price-tag" style="font-size:0.9375rem;"><?php echo number_format($prod['prix'], 2); ?> DT</td>
                                <td>
                                    <span class="stock-indicator <?php echo $stockClass; ?>" style="font-size:0.8125rem;">
                                        ● <?php echo $prod['quantite']; ?>
                                    </span>
                                </td>
                                <td><?php echo $prod['nombre_ventes']; ?></td>
                                <td>
                                    <a href="?edit=<?php echo urlencode($prod['reference']); ?>" class="btn-ghost" style="font-size:0.8125rem; padding:0.375rem 0.75rem;">
                                        ✏️
                                    </a>
                                    <a href="?delete=<?php echo urlencode($prod['reference']); ?>"
                                       class="btn-danger" style="font-size:0.8125rem; padding:0.375rem 0.75rem;"
                                       onclick="return confirm('Supprimer ce produit ?')">
                                        🗑️
                                    </a>
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
