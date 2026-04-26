<?php


// Récupération de toutes les catégories depuis la base
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id_categorie");
$allCategories = $stmt->fetchAll();

// Catégorie active depuis l'URL (0 = toutes)
$activeCategory = isset($_GET['cat']) ? intval($_GET['cat']) : 0;

// Préservation des autres paramètres GET pour les liens
function buildCatUrl($catId) {
    $params = $_GET;
    if ($catId === 0) {
        unset($params['cat']);
    } else {
        $params['cat'] = $catId;
    }
    return '?' . http_build_query($params);
}
?>
<aside class="sidebar">
    <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em;
                color:var(--text-muted); margin-bottom:1rem;">
        <?php echo __t('category'); ?>
    </div>

    <!-- Lien "Tous" -->
    <a href="<?php echo buildCatUrl(0); ?>" class="category-item <?php echo $activeCategory === 0 ? 'active' : ''; ?>">
        <?php echo __t('all'); ?>
    </a>

    <!-- Liste des catégories -->
    <?php foreach ($allCategories as $cat):
        $catName = $lang === 'en' && !empty($cat['nom_categorie_en']) ? $cat['nom_categorie_en'] : $cat['nom_categorie'];
    ?>
        <a href="<?php echo buildCatUrl($cat['id_categorie']); ?>"
           class="category-item <?php echo $activeCategory === $cat['id_categorie'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($catName); ?>
        </a>
    <?php endforeach; ?>
</aside>
