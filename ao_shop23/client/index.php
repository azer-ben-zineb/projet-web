<?php


session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../includes/discounts.php';

// Protection: redirection si non connecté ou admin
if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}
if ($_SESSION['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Rafraîchir le solde depuis la DB
$stmt = $pdo->prepare("SELECT solde FROM users WHERE id_user = ?");
$stmt->execute([$_SESSION['id_user']]);
$_SESSION['solde'] = $stmt->fetch()['solde'] ?? 0;

$pricingContext = get_user_pricing_context($pdo, (int)$_SESSION['id_user']);
$effectiveDiscountPercent = (int)$pricingContext['effective_discount_percent'];

//  Paramètres de filtrage 
$catId = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? '';
$view = $_GET['view'] ?? 'grid'; // 'grid' ou 'list'

//  Construction de la requête 
$params = [];
$where = ["1=1"];

if ($catId > 0) {
    $where[] = "p.id_categorie = ?";
    $params[] = $catId;
}
if ($search !== '') {
    $where[] = "(p.designation LIKE ? OR p.designation_en LIKE ? OR p.marque LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$orderBy = match($sort) {
    'price_asc'  => 'p.prix ASC',
    'price_desc' => 'p.prix DESC',
    'brand'      => 'p.marque ASC',
    'sales'      => 'p.nombre_ventes DESC',
    default      => 'p.reference ASC',
};

//  Récupération des produits 
$sql = "SELECT p.*, c.nom_categorie, c.nom_categorie_en
        FROM produits p
        JOIN categories c ON p.id_categorie = c.id_categorie
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

foreach ($products as &$product) {
    $product = with_discounted_price($product, $effectiveDiscountPercent);
    $product['image_url'] = get_product_image($product['reference'], $product['photo']);
}
unset($product);

//  Récupération des meilleures ventes en stock (top 5) 
$stmt = $pdo->query("SELECT * FROM produits WHERE quantite > 0 AND TRIM(reference) <> '' ORDER BY nombre_ventes DESC LIMIT 5");
$topProducts = $stmt->fetchAll();

foreach ($topProducts as &$product) {
    $product = with_discounted_price($product, $effectiveDiscountPercent);
    $product['image_url'] = get_product_image($product['reference'], $product['photo']);
}
unset($product);

//  Récupération des catégories pour le menu 
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id_categorie");
$categories = $stmt->fetchAll();

$basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$basePrefix = $basePath === '' ? '' : $basePath;
$appJsVersion = (string)(@filemtime(__DIR__ . '/../assets/App.jsx') ?: time());
$appJsUrl = $basePrefix . '/assets/App.jsx?v=' . rawurlencode($appJsVersion);

$toast = $_SESSION['toast'] ?? '';
$toastType = $_SESSION['toast_type'] ?? 'success';
unset($_SESSION['toast']);
unset($_SESSION['toast_type']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('shop_name'); ?> — <?php echo __t('products'); ?></title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<script src="../assets/theme.js"></script>
</head>
<body>
    <div class="fancy-top-gradient"></div>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Barre de recherche et contrôles -->
            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
                <form method="GET" action="" style="display:flex; flex:1; min-width:200px; gap:0.5rem;">
                    <?php if ($catId > 0): ?>
                        <input type="hidden" name="cat" value="<?php echo $catId; ?>">
                    <?php endif; ?>
                    <input type="text" name="search" class="form-input"
                           placeholder="<?php echo __t('search'); ?>"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-primary" style="padding:0.625rem 1rem;">🔍</button>
                </form>

                <select class="form-select" style="width:auto; min-width:160px;"
                        onchange="window.location.href='?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), sort: this.value}).toString()">
                    <option value=""><?php echo __t('sort_by'); ?></option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>><?php echo __t('price_asc'); ?></option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>><?php echo __t('price_desc'); ?></option>
                    <option value="brand" <?php echo $sort === 'brand' ? 'selected' : ''; ?>><?php echo __t('brand_az'); ?></option>
                    <option value="sales" <?php echo $sort === 'sales' ? 'selected' : ''; ?>><?php echo __t('sales_desc'); ?></option>
                </select>

                <div class="view-toggle">
                    <button onclick="setView('grid')" class="<?php echo $view === 'grid' ? 'active' : ''; ?>" title="Grid">⊞</button>
                    <button onclick="setView('list')" class="<?php echo $view === 'list' ? 'active' : ''; ?>" title="List">☰</button>
                </div>
            </div>

            <!-- Barre de recommandations (top 5) -->
            <div id="reco-root"></div>

            <!-- Résultats -->
            <?php if (empty($products)): ?>
                <div class="text-center" style="padding:4rem 2rem; color:var(--text-muted);">
                    <div style="font-size:3rem; margin-bottom:1rem;">📦</div>
                    <p style="font-size:1.125rem; font-weight:600;"><?php echo __t('no_products'); ?></p>
                </div>
            <?php else: ?>
                <div class="<?php echo $view === 'list' ? 'product-list' : 'product-grid'; ?>">
                    <?php foreach ($products as $i => $product):
                        $name = ($lang === 'en' && !empty($product['designation_en'])) ? $product['designation_en'] : $product['designation'];
                        $desc = ($lang === 'en' && !empty($product['description_en'])) ? $product['description_en'] : $product['description'];
                        $catName = ($lang === 'en' && !empty($product['nom_categorie_en'])) ? $product['nom_categorie_en'] : $product['nom_categorie'];
                        $stockClass = $product['quantite'] > 5 ? 'high' : ($product['quantite'] > 0 ? 'low' : 'zero');
                        $stockText = $product['quantite'] > 5 ? __t('in_stock') : ($product['quantite'] > 0 ? __t('low_stock') : __t('out_of_stock'));
                    ?>
                        <?php if ($view === 'list'): ?>
                            <!-- Vue Liste -->
                            <div class="list-card animate-reveal stagger-<?php echo ($i % 5) + 1; ?>">
                                <div class="list-card-thumb">
                                    <img src="<?php echo $product['image_url']; ?>"
                                         alt="<?php echo htmlspecialchars($name); ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                    <div class="list-card-thumb-fallback" style="display:none;">📦</div>
                                </div>
                                <div class="list-card-body">
                                    <div class="list-card-title"><?php echo htmlspecialchars($name); ?></div>
                                    <p class="list-card-text">
                                        <?php if (!empty($desc)): ?>
                                            <?php echo htmlspecialchars($desc); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($product['marque'])): ?>
                                            <span class="list-card-detail"><?php echo htmlspecialchars($product['marque']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <div class="list-card-bottom">
                                        <span class="list-card-price">
                                            <?php if ((int)$product['discount_percent'] > 0): ?>
                                                <span class="list-card-price-old"><?php echo number_format($product['prix_original'], 2); ?> DT</span>
                                            <?php endif; ?>
                                            <?php echo number_format($product['prix_reduit'], 2); ?> DT
                                        </span>
                                        <span class="stock-indicator <?php echo $stockClass; ?>" style="font-size:0.75rem;">
                                            ● <?php echo $stockText; ?>
                                        </span>
                                    </div>
                                </div>
                                <!-- Hover overlay actions -->
                                <div class="list-card-actions">
                                    <div class="tooltip-wrapper">
                                        <button class="btn-compare" data-ref="<?php echo htmlspecialchars($product['reference']); ?>"
                                                onclick='toggleCompare(<?php echo json_encode($product['reference']); ?>, <?php echo json_encode($name); ?>, <?php echo json_encode($product['image_url']); ?>)'>
                                            ⚖️
                                        </button>
                                        <span class="tooltip-text"><?php echo __t('compare_this'); ?></span>
                                    </div>
                                    <a href="../actions/add_to_cart.php?ref=<?php echo urlencode($product['reference']); ?>" class="btn-ghost" style="font-size:0.8125rem;">
                                        🛒 <?php echo __t('add_to_cart'); ?>
                                    </a>
                                    <a href="../actions/buy_now.php?ref=<?php echo urlencode($product['reference']); ?>" class="btn-primary" style="font-size:0.8125rem;">
                                        ⚡ <?php echo __t('buy_now'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Vue Grille -->
                            <div class="card animate-reveal stagger-<?php echo ($i % 5) + 1; ?>">
                                <img src="<?php echo $product['image_url']; ?>"
                                     alt="" class="product-card-img"
                                     onerror="this.style.display='none'; this.parentElement.querySelector('.img-placeholder').style.display='flex';">
                                <div class="product-card-body">
                                    <div class="product-card-ref"><?php echo htmlspecialchars($product['reference']); ?></div>
                                    <div class="product-card-name"><?php echo htmlspecialchars($name); ?></div>
                                    <div class="product-card-brand"><?php echo htmlspecialchars($product['marque']); ?></div>
                                    <div class="product-card-price">
                                        <?php if ((int)$product['discount_percent'] > 0): ?>
                                            <div class="price-old"><?php echo number_format($product['prix_original'], 2); ?> DT</div>
                                        <?php endif; ?>
                                        <div class="price-tag"><?php echo number_format($product['prix_reduit'], 2); ?> DT</div>
                                    </div>
                                    <div class="product-card-stock stock-indicator <?php echo $stockClass; ?>" style="font-size:0.8125rem;">
                                        ● <?php echo $stockText; ?> (<?php echo $product['quantite']; ?>)
                                    </div>
                                    <div class="product-actions">
                                        <div class="tooltip-wrapper">
                                            <button class="btn-compare" data-ref="<?php echo htmlspecialchars($product['reference']); ?>"
                                                    onclick='toggleCompare(<?php echo json_encode($product['reference']); ?>, <?php echo json_encode($name); ?>, <?php echo json_encode($product['image_url']); ?>)'>
                                                ⚖️
                                            </button>
                                            <span class="tooltip-text"><?php echo __t('compare_this'); ?></span>
                                        </div>
                                        <a href="../actions/add_to_cart.php?ref=<?php echo urlencode($product['reference']); ?>" class="btn-ghost" style="font-size:0.8125rem;">
                                            🛒 <?php echo __t('add_to_cart'); ?>
                                        </a>
                                        <a href="../actions/buy_now.php?ref=<?php echo urlencode($product['reference']); ?>" class="btn-primary" style="font-size:0.8125rem;">
                                            ⚡ <?php echo __t('buy_now'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Barre de comparaison flottante -->
    <div class="compare-bar" id="compareBar">
        <div class="compare-bar-slots" id="compareSlots">
            <div class="compare-slot" data-slot="0">+</div>
            <div class="compare-slot" data-slot="1">+</div>
            <div class="compare-slot" data-slot="2">+</div>
        </div>
        <div class="compare-bar-count" id="compareCount">0 <?php echo __t('selected_products'); ?></div>
        <div class="compare-bar-actions">
            <button class="btn-primary" onclick="goCompare()"><?php echo __t('compare_now'); ?></button>
            <button class="btn-ghost" onclick="clearCompare()"><?php echo __t('clear_all'); ?></button>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script>
      window.AO_RECO = { products: <?= json_encode($topProducts, JSON_UNESCAPED_UNICODE) ?>, lang: <?= json_encode($lang) ?> };
            window.AO_BASE_PATH = <?= json_encode($basePrefix, JSON_UNESCAPED_UNICODE) ?>;
      //  Données pour le composant React 
      window.__RECO_DATA__ = <?php echo json_encode($topProducts); ?>;
      window.__LANG__ = '<?php echo $lang; ?>';
    </script>
        <script type="text/babel" src="<?php echo htmlspecialchars($appJsUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>

    <script>

    //  Vue grille/liste 
    function setView(v) {
        localStorage.setItem('productView', v);
        const url = new URL(window.location.href);
        url.searchParams.set('view', v);
        window.location.href = url.toString();
    }

    //  Système de comparaison (sessionStorage) 
    let compareItems = JSON.parse(sessionStorage.getItem('compareItems') || '[]');
    const MAX_COMPARE = 3;

    function toggleCompare(ref, name, photo) {
        const idx = compareItems.findIndex(item => item.ref === ref);
        if (idx >= 0) {
            compareItems.splice(idx, 1);
            showToast('<?php echo __t('compare'); ?>', 'info');
        } else {
            if (compareItems.length >= MAX_COMPARE) {
                showToast('<?php echo __t('max_compare'); ?>', 'error');
                return;
            }
            compareItems.push({ ref, name, photo });
            showToast('<?php echo __t('compare_this'); ?> ✓', 'success');
        }
        sessionStorage.setItem('compareItems', JSON.stringify(compareItems));
        updateCompareBar();
        updateCompareButtons();
    }

    function removeCompare(idx) {
        compareItems.splice(idx, 1);
        sessionStorage.setItem('compareItems', JSON.stringify(compareItems));
        updateCompareBar();
        updateCompareButtons();
    }

    function clearCompare() {
        compareItems = [];
        sessionStorage.removeItem('compareItems');
        updateCompareBar();
        updateCompareButtons();
    }

    function goCompare() {
        if (compareItems.length < 2) {
            showToast('<?php echo __t('min_compare'); ?>', 'error');
            return;
        }
        const refs = compareItems.map(i => i.ref).join(',');
        window.location.href = 'comparer.php?refs=' + refs;
    }

    function updateCompareBar() {
        const bar = document.getElementById('compareBar');
        const slots = document.getElementById('compareSlots');
        const count = document.getElementById('compareCount');

        if (compareItems.length === 0) {
            bar.classList.remove('visible');
            return;
        }
        bar.classList.add('visible');
        count.textContent = compareItems.length + ' <?php echo __t('selected_products'); ?>';

        let html = '';
        for (let i = 0; i < MAX_COMPARE; i++) {
            if (compareItems[i]) {
                html += `<div class="compare-slot filled">
                    ${compareItems[i].photo ? `<img src="${compareItems[i].photo}" onerror="this.style.display='none'">` : ''}
                    <button class="remove-slot" onclick="event.stopPropagation(); removeCompare(${i})">×</button>
                </div>`;
            } else {
                html += `<div class="compare-slot">+</div>`;
            }
        }
        slots.innerHTML = html;
    }

    function updateCompareButtons() {
        document.querySelectorAll('.btn-compare').forEach(btn => {
            const ref = btn.dataset.ref;
            const isActive = compareItems.some(item => item.ref === ref);
            btn.classList.toggle('active', isActive);
        });
    }

    //  Toast notifications 
    function showToast(message, type) {
        const container = document.getElementById('toast-container') || document.body;
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    //  Suivi de la souris pour les cartes 
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const r = card.getBoundingClientRect();
            card.style.setProperty('--mouse-x', (e.clientX - r.left) + 'px');
            card.style.setProperty('--mouse-y', (e.clientY - r.top) + 'px');
        });
    });

    //  Initialisation 
    updateCompareBar();
    updateCompareButtons();

    <?php if ($toast): ?>
    showToast(<?php echo json_encode($toast); ?>, <?php echo json_encode($toastType); ?>);
    <?php endif; ?>
    </script>
</body>
</html>
