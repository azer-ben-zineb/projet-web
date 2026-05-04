<?php


session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['id_user'];

//  Validation des références 
$refsParam = $_GET['refs'] ?? '';
$refs = array_filter(array_map('trim', explode(',', $refsParam)));

if (count($refs) < 2) {
    $_SESSION['toast'] = __t('min_compare');
    header('Location: index.php');
    exit;
}

// Vérifier que tous les produits existent
$placeholders = implode(',', array_fill(0, count($refs), '?'));
$stmt = $pdo->prepare("
    SELECT p.*, c.nom_categorie, c.nom_categorie_en
    FROM produits p
    JOIN categories c ON p.id_categorie = c.id_categorie
    WHERE p.reference IN ($placeholders)
");
$stmt->execute($refs);
$products = $stmt->fetchAll();

if (count($products) < 2) {
    $_SESSION['toast'] = __t('min_compare');
    header('Location: index.php');
    exit;
}

// Déterminer le meilleur prix
$minPrice = min(array_column($products, 'prix'));
$maxPrice = max(array_column($products, 'prix'));
$catFilter = $products[0]['id_categorie'];

//  Traitement de l'ajout depuis la page de comparaison 
if (isset($_GET['add_ref'])) {
    $addRef = $_GET['add_ref'];
    // Vérifier que c'est la même catégorie
    $stmt = $pdo->prepare("SELECT id_categorie FROM produits WHERE reference = ?");
    $stmt->execute([$addRef]);
    $addCat = $stmt->fetch()['id_categorie'] ?? null;

    if ($addCat && $addCat == $catFilter && count($refs) < 3) {
        $newRefs = implode(',', array_merge($refs, [$addRef]));
        header("Location: comparer.php?refs=$newRefs");
        exit;
    }
}

//  Traitement achat immédiat depuis la page de comparaison 
if (isset($_GET['buy_ref'])) {
    $buyRef = $_GET['buy_ref'];
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE reference = ?");
    $stmt->execute([$buyRef]);
    $prod = $stmt->fetch();

    if ($prod && $prod['quantite'] > 0) {
        $stmt = $pdo->prepare("SELECT solde FROM users WHERE id_user = ?");
        $stmt->execute([$userId]);
        $solde = $stmt->fetch()['solde'];

        if ($solde >= $prod['prix']) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO commandes (id_user, reference_produit, quantite, prix_unitaire)
                VALUES (?, ?, 1, ?)
            ");
            $stmt->execute([$userId, $buyRef, $prod['prix']]);

            $stmt = $pdo->prepare("
                UPDATE produits SET quantite = quantite - 1, nombre_ventes = nombre_ventes + 1 WHERE reference = ?
            ");
            $stmt->execute([$buyRef]);

            $stmt = $pdo->prepare("UPDATE users SET solde = solde - ? WHERE id_user = ?");
            $stmt->execute([$prod['prix'], $userId]);
            $pdo->commit();

            $_SESSION['solde'] -= $prod['prix'];
            $_SESSION['toast'] = __t('purchase_success');
        } else {
            $_SESSION['toast'] = __t('insufficient_balance');
        }
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?refs=' . $refsParam);
    exit;
}

//  Rafraîchir le solde 
$stmt = $pdo->prepare("SELECT solde FROM users WHERE id_user = ?");
$stmt->execute([$userId]);
$_SESSION['solde'] = $stmt->fetch()['solde'] ?? 0;

// Recharger les produits après un achat
$stmt = $pdo->prepare("
    SELECT p.*, c.nom_categorie, c.nom_categorie_en
    FROM produits p
    JOIN categories c ON p.id_categorie = c.id_categorie
    WHERE p.reference IN ($placeholders)
");
$stmt->execute($refs);
$products = $stmt->fetchAll();

$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);

// Produits disponibles pour l'ajout (même catégorie, non sélectionnés)
$canAddMore = count($products) < 3;
$availableProducts = [];
if ($canAddMore) {
    $existingRefs = array_column($products, 'reference');
    $ph = implode(',', array_fill(0, count($existingRefs), '?'));
    $stmt = $pdo->prepare("
        SELECT reference, designation, designation_en, photo, prix
        FROM produits
        WHERE id_categorie = ? AND reference NOT IN ($ph)
        ORDER BY designation
        LIMIT 20
    ");
    $stmt->execute(array_merge([$catFilter], $existingRefs));
    $availableProducts = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚖️ <?php echo __t('compare_products'); ?> — AO Shop</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<script src="../assets/theme.js"></script>
</head>
<body>
    <div class="fancy-top-gradient"></div>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="compare-page">
        <h1 class="compare-page-title">⚖️ <?php echo __t('compare_products'); ?></h1>
        <a href="index.php" class="compare-back-link"><?php echo __t('back_to_products'); ?></a>

        <!-- Tableau de comparaison -->
        <div class="compare-grid <?php echo count($products) === 2 ? 'two-products' : 'three-products'; ?>">
            <!-- En-têtes de colonnes produit -->
            <div></div>
            <?php foreach ($products as $prod): ?>
                <div class="compare-col-header <?php echo $prod['prix'] == $minPrice ? 'is-best' : ''; ?>">
                    <?php echo $prod['prix'] == $minPrice ? '⭐ ' . __t('best_price') : '&nbsp;'; ?>
                </div>
            <?php endforeach; ?>

            <!-- Photo -->
            <div class="compare-label"><?php echo __t('photo'); ?></div>
            <?php foreach ($products as $prod):
                $prodImage = get_product_image($prod['reference'], $prod['photo']);
            ?>
                <div class="compare-cell <?php echo $prod['prix'] == $minPrice ? 'best-price' : ''; ?>">
                    <?php if ($prodImage): ?>
                        <img src="<?php echo $prodImage; ?>"
                             alt="" class="compare-product-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                        <div class="compare-img-placeholder" style="display:none;">📷</div>
                    <?php else: ?>
                        <div class="compare-img-placeholder">📷</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Désignation -->
            <div class="compare-label"><?php echo __t('product_name'); ?></div>
            <?php foreach ($products as $prod):
                $name = ($lang === 'en' && !empty($prod['designation_en'])) ? $prod['designation_en'] : $prod['designation'];
            ?>
                <div class="compare-cell <?php echo $prod['prix'] == $minPrice ? 'best-price' : ''; ?>"
                     data-label="<?php echo __t('product_name'); ?>">
                    <strong style="font-size:1rem;"><?php echo htmlspecialchars($name); ?></strong>
                </div>
            <?php endforeach; ?>

            <!-- Référence -->
            <div class="compare-label"><?php echo __t('reference'); ?></div>
            <?php foreach ($products as $prod): ?>
                <div class="compare-cell <?php echo $prod['prix'] == $minPrice ? 'best-price' : ''; ?>"
                     data-label="<?php echo __t('reference'); ?>">
                    <span class="badge badge-cyan"><?php echo htmlspecialchars($prod['reference']); ?></span>
                </div>
            <?php endforeach; ?>

            <!-- Marque -->
            <div class="compare-label"><?php echo __t('brand'); ?></div>
            <?php foreach ($products as $prod): ?>
                <div class="compare-cell <?php echo $prod['prix'] == $minPrice ? 'best-price' : ''; ?>"
                     data-label="<?php echo __t('brand'); ?>">
                    <span style="display:inline-flex; align-items:center; gap:0.5rem;">
                        <span style="width:28px;height:28px;border-radius:9999px;background:linear-gradient(to right,#58E1FF,#00a2f8);
                                     display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.75rem;
                                     color:#090b0c;">
                            <?php echo strtoupper(substr($prod['marque'], 0, 1)); ?>
                        </span>
                        <?php echo htmlspecialchars($prod['marque']); ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <!-- Catégorie -->
            <div class="compare-label"><?php echo __t('category'); ?></div>
            <?php foreach ($products as $prod):
                $catName = ($lang === 'en' && !empty($prod['nom_categorie_en'])) ? $prod['nom_categorie_en'] : $prod['nom_categorie'];
            ?>
                <div class="compare-cell <?php echo $prod['prix'] == $minPrice ? 'best-price' : ''; ?>"
                     data-label="<?php echo __t('category'); ?>">
                    <span class="badge badge-gold"><?php echo htmlspecialchars($catName); ?></span>
                </div>
            <?php endforeach; ?>

            <!-- Prix -->
            <div class="compare-label"><?php echo __t('price'); ?></div>
            <?php foreach ($products as $prod): ?>
                <div class="compare-cell <?php echo $prod['prix'] == $minPrice ? 'best-price' : ''; ?>"
                     data-label="<?php echo __t('price'); ?>">
                    <span class="price-tag" style="font-size:1.25rem;">
                        <?php echo number_format($prod['prix'], 2); ?> DT
                    </span>
                    <?php if ($prod['prix'] == $minPrice): ?>
                        <div class="compare-best-badge">✓ <?php echo __t('best_price'); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Quantité -->
            <div class="compare-label"><?php echo __t('quantity'); ?></div>
            <?php foreach ($products as $prod):
                $stockClass = $prod['quantite'] > 5 ? 'high' : ($prod['quantite'] > 0 ? 'low' : 'zero');
            ?>
                <div class="compare-cell <?php echo $prod['prix'] == $minPrice ? 'best-price' : ''; ?>"
                     data-label="<?php echo __t('quantity'); ?>">
                    <span class="stock-indicator <?php echo $stockClass; ?>">
                        ● <?php echo $prod['quantite']; ?> <?php echo __t('available'); ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <!-- Description -->
            <div class="compare-label"><?php echo __t('description'); ?></div>
            <?php foreach ($products as $prod):
                $desc = ($lang === 'en' && !empty($prod['description_en'])) ? $prod['description_en'] : $prod['description'];
            ?>
                <div class="compare-cell <?php echo $prod['prix'] == $minPrice ? 'best-price' : ''; ?>"
                     data-label="<?php echo __t('description'); ?>">
                    <div class="compare-description collapsed" id="desc-<?php echo $prod['reference']; ?>">
                        <?php echo nl2br(htmlspecialchars($desc)); ?>
                    </div>
                    <?php if (strlen($desc) > 100): ?>
                        <button class="voir-plus-btn" onclick="toggleDesc('<?php echo $prod['reference']; ?>', this)">
                            <?php echo __t('see_more'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Actions -->
            <div class="compare-label"><?php echo __t('actions'); ?></div>
            <?php foreach ($products as $prod): ?>
                <div class="compare-cell compare-actions-cell <?php echo $prod['prix'] == $minPrice ? 'best-price' : ''; ?>"
                     data-label="<?php echo __t('actions'); ?>">
                    <?php if ($prod['quantite'] > 0): ?>
                        <a href="comparer.php?refs=<?php echo $refsParam; ?>&buy_ref=<?php echo urlencode($prod['reference']); ?>"
                           class="btn-primary" onclick="return confirm('<?php echo __t('buy_now'); ?> ?')">
                            ⚡ <?php echo __t('buy_now'); ?>
                        </a>
                        <a href="../actions/add_to_cart.php?ref=<?php echo urlencode($prod['reference']); ?>&redirect=comparer.php?refs=<?php echo $refsParam; ?>"
                           class="btn-ghost">
                            🛒 <?php echo __t('add_to_cart'); ?>
                        </a>
                    <?php else: ?>
                        <span class="badge badge-red"><?php echo __t('out_of_stock'); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Ajouter un produit -->
        <?php if ($canAddMore && !empty($availableProducts)): ?>
            <div class="compare-add-section">
                <div class="compare-add-title"><?php echo __t('add_to_compare'); ?></div>
                <div class="compare-search-wrapper">
                    <input type="text" class="form-input" id="searchAdd"
                           placeholder="<?php echo __t('search'); ?>"
                           oninput="filterProducts(this.value)">
                    <div class="compare-search-results" id="searchResults" style="display:none;">
                        <?php foreach ($availableProducts as $ap):
                            $apName = ($lang === 'en' && !empty($ap['designation_en'])) ? $ap['designation_en'] : $ap['designation'];
                            $apImage = get_product_image($ap['reference'], $ap['photo']);
                        ?>
                            <a href="comparer.php?refs=<?php echo $refsParam; ?>&add_ref=<?php echo urlencode($ap['reference']); ?>"
                               class="compare-search-result-item">
                                <?php if ($apImage): ?>
                                    <img src="<?php echo $apImage; ?>"
                                         style="width:36px;height:36px;object-fit:contain;border-radius:0.375rem;background:#0f172a;"
                                         onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($apName); ?></div>
                                    <div class="price-tag" style="font-size:0.8125rem;">
                                        <?php echo number_format($ap['prix'], 2); ?> DT
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
    function toggleDesc(ref, btn) {
        const el = document.getElementById('desc-' + ref);
        el.classList.toggle('collapsed');
        btn.textContent = el.classList.contains('collapsed') ? '<?php echo __t('see_more'); ?>' : '<?php echo __t('see_less'); ?>';
    }

    function filterProducts(term) {
        const results = document.getElementById('searchResults');
        if (term.length < 1) {
            results.style.display = 'none';
            return;
        }
        const items = results.querySelectorAll('.compare-search-result-item');
        let hasMatch = false;
        items.forEach(item => {
            const match = item.textContent.toLowerCase().includes(term.toLowerCase());
            item.style.display = match ? 'flex' : 'none';
            if (match) hasMatch = true;
        });
        results.style.display = hasMatch ? 'block' : 'none';
    }

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
