<?php


// Calcul du nombre d'articles dans le panier (affiché dans la navbar)
$cartCount = 0;
if (isset($_SESSION['id_user'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantite) as total FROM panier WHERE id_user = ? AND expiration > NOW()");
    $stmt->execute([$_SESSION['id_user']]);
    $cartCount = $stmt->fetch()['total'] ?? 0;
}

// URLs de bascule de langue
$currentUri = $_SERVER['REQUEST_URI'];
$langUrlFr = preg_replace('/([?&])lang=[^&]+&?/', '$1', $currentUri);
$langUrlFr .= (strpos($langUrlFr, '?') === false ? '?' : '&') . 'lang=fr';
$langUrlEn = preg_replace('/([?&])lang=[^&]+&?/', '$1', $currentUri);
$langUrlEn .= (strpos($langUrlEn, '?') === false ? '?' : '&') . 'lang=en';
$langUrlFr = rtrim($langUrlFr, '?&');
$langUrlEn = rtrim($langUrlEn, '?&');
?>
<nav class="navbar">
    <div style="display:flex; align-items:center; gap:1.5rem;">
        <a href="<?php echo ($_SESSION['role'] ?? 'client') === 'admin' ? '../admin/dashboard.php' : '../client/index.php'; ?>"
           style="font-size:1.25rem; font-weight:800;
                  background:linear-gradient(to right, #58E1FF, #60a5fa);
                  -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
            AO Shop
        </a>
        <span style="color:var(--text-muted); font-size:0.8125rem;">| Hadramut, Tunisia</span>
    </div>

    <div style="display:flex; align-items:center; gap:1rem;">
        <?php if (isset($_SESSION['id_user'])): ?>
            <!-- Affichage du solde en monospace cyan -->
            <div class="price-tag" style="font-size:0.9375rem;">
                <?php echo number_format($_SESSION['solde'] ?? 0, 2); ?> DT
            </div>

            <!-- Lien vers le panier avec badge de comptage -->
            <?php if (($_SESSION['role'] ?? '') !== 'admin'): ?>
                <a href="../client/panier.php" style="position:relative; color:var(--text-main); font-size:1.25rem; text-decoration:none;">
                    🛒
                    <?php if ($cartCount > 0): ?>
                        <span style="position:absolute; top:-8px; right:-8px; background:var(--primary); color:#090b0c;
                                     font-size:0.6875rem; font-weight:800; width:18px; height:18px;
                                     border-radius:9999px; display:flex; align-items:center; justify-content:center;">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <!-- Menu utilisateur -->
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <span style="color:var(--text-dim); font-size:0.9375rem;">
                    <?php echo htmlspecialchars(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')); ?>
                </span>
                <a href="../client/roulette.php" class="btn-ghost" style="font-size:0.8125rem; padding:0.375rem 0.75rem;">
                    🎡 <?php echo __t('daily_roulette'); ?>
                </a>
                <a href="../client/abonnement.php" class="btn-ghost" style="font-size:0.8125rem; padding:0.375rem 0.75rem;">
                    👑 <?php echo __t('subscription'); ?>
                </a>
                <a href="../client/profil.php" class="btn-ghost" style="font-size:0.8125rem; padding:0.375rem 0.75rem;">
                    <?php echo __t('profile'); ?>
                </a>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="../admin/dashboard.php" class="btn-ghost" style="font-size:0.8125rem; padding:0.375rem 0.75rem;">
                        <?php echo __t('admin_panel'); ?>
                    </a>
                <?php endif; ?>
                <button onclick="cycleTheme()" class="btn-ghost" style="font-size:1.1rem; padding:0.375rem 0.75rem; cursor:pointer;" title="Change Theme">
                    🎨
                </button>
                <a href="../auth/logout.php" class="btn-danger" style="font-size:0.8125rem; padding:0.375rem 0.75rem;">
                    <?php echo __t('logout'); ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Bascule de langue -->
        <div class="view-toggle">
            <a href="<?php echo $langUrlFr; ?>" class="<?php echo $lang === 'fr' ? 'active' : ''; ?>"
               style="text-decoration:none; display:inline-flex; align-items:center; gap:0.25rem; padding:0.375rem 0.625rem; font-weight:700;">
                FR
            </a>
            <a href="<?php echo $langUrlEn; ?>" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"
               style="text-decoration:none; display:inline-flex; align-items:center; gap:0.25rem; padding:0.375rem 0.625rem; font-weight:700;">
                EN
            </a>
        </div>
    </div>
</nav>

<!-- Bannière de solde épuisé -->
<?php if (isset($_SESSION['solde']) && $_SESSION['solde'] <= 0 && ($_SESSION['role'] ?? '') === 'client'): ?>
    <div class="balance-banner">
        ⚠️ <?php echo __t('balance_empty'); ?>
    </div>
<?php endif; ?>

<!-- Container des notifications toast -->
<div class="toast-container" id="toast-container"></div>
