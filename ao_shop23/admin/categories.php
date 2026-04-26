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

// ─── Traitement du formulaire ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $nomFr = trim($_POST['nom_categorie'] ?? '');
    $nomEn = trim($_POST['nom_categorie_en'] ?? '');

    if (empty($nomFr)) {
        $toast = 'Le nom français est obligatoire.';
    } else {
        // Vérifier les doublons
        $stmt = $pdo->prepare("SELECT id_categorie FROM categories WHERE nom_categorie = ? OR nom_categorie_en = ?");
        $stmt->execute([$nomFr, $nomEn ?: $nomFr]);
        if ($stmt->fetch()) {
            $toast = __t('category_exists');
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (nom_categorie, nom_categorie_en) VALUES (?, ?)");
            $stmt->execute([$nomFr, $nomEn ?: null]);

            // Envoyer un email aux abonnés actifs
            $stmt = $pdo->query("
                SELECT u.email FROM users u
                JOIN abonnements a ON u.id_user = a.id_user
                WHERE a.actif = 1 AND a.date_fin > NOW()
                GROUP BY u.id_user
            ");
            $subscribers = $stmt->fetchAll();
            foreach ($subscribers as $sub) {
                @mail($sub['email'], 'Nouvelle catégorie chez AO Shop',
                    "Une nouvelle catégorie vient d'être ajoutée : $nomFr\n\nDécouvrez-la sur AO Shop!");
            }

            $toast = __t('category_added');
            header('Location: categories.php');
            exit;
        }
    }
}

//  Suppression 
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Vérifier qu'il n'y a pas de produits dans cette catégorie
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE id_categorie = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id_categorie = ?");
        $stmt->execute([$id]);
        $toast = 'Catégorie supprimée.';
    } else {
        $toast = 'Impossible: des produits utilisent cette catégorie.';
    }
    header('Location: categories.php');
    exit;
}

//  Récupération des catégories 
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.reference) as nb_produits
    FROM categories c
    LEFT JOIN produits p ON c.id_categorie = p.id_categorie
    GROUP BY c.id_categorie
    ORDER BY c.id_categorie
");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('manage_categories'); ?> — AO Shop</title>
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
            <a href="categories.php" class="category-item active">📁 <?php echo __t('manage_categories'); ?></a>
            <a href="stock.php" class="category-item">📈 <?php echo __t('manage_stock'); ?></a>
            <a href="comptes.php" class="category-item">👥 <?php echo __t('manage_accounts'); ?></a>
        </aside>

        <main class="main-content">
            <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:1.5rem;">
                📁 <?php echo __t('manage_categories'); ?>
            </h1>

            <!-- Formulaire d'ajout -->
            <div class="card" style="padding:1.5rem; margin-bottom:2rem;">
                <h2 style="font-size:1.125rem; font-weight:700; margin-bottom:1rem;">➕ <?php echo __t('add'); ?></h2>
                <form method="POST" action="">
                    <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:1rem; align-items:end;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Nom (FR) *</label>
                            <input type="text" name="nom_categorie" class="form-input" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Nom (EN)</label>
                            <input type="text" name="nom_categorie_en" class="form-input">
                        </div>
                        <button type="submit" name="add_category" class="btn-primary" style="margin-bottom:0;">
                            <?php echo __t('add'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Liste des catégories -->
            <div class="card" style="padding:0; overflow:hidden;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom (FR)</th>
                            <th>Nom (EN)</th>
                            <th>Produits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id_categorie']; ?></td>
                                <td><strong><?php echo htmlspecialchars($cat['nom_categorie']); ?></strong></td>
                                <td style="color:var(--text-muted);"><?php echo htmlspecialchars($cat['nom_categorie_en'] ?? '—'); ?></td>
                                <td>
                                    <span class="badge badge-cyan"><?php echo $cat['nb_produits']; ?></span>
                                </td>
                                <td>
                                    <?php if ($cat['nb_produits'] == 0): ?>
                                        <a href="?delete=<?php echo $cat['id_categorie']; ?>"
                                           class="btn-danger" style="font-size:0.8125rem; padding:0.375rem 0.75rem;"
                                           onclick="return confirm('Supprimer cette catégorie ?')">
                                            🗑️
                                        </a>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.8125rem;">—</span>
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
