<?php


session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['id_user'];

//  Nettoyage des articles expirés 
$stmt = $pdo->prepare("DELETE FROM panier WHERE id_user = ? AND expiration < NOW()");
$stmt->execute([$userId]);
$deletedCount = $stmt->rowCount();

//  Suppression d'un article spécifique 
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $stmt = $pdo->prepare("DELETE FROM panier WHERE id_panier = ? AND id_user = ?");
    $stmt->execute([$_GET['remove'], $userId]);
    $_SESSION['toast'] = __t('item_removed');
    header('Location: panier.php');
    exit;
}

//  Passer la commande (tous les articles) 
if (isset($_POST['checkout'])) {
    // Récupérer tous les articles du panier avec les prix actuels
    $stmt = $pdo->prepare("
        SELECT p.*, pr.prix, pr.quantite as stock, pr.nombre_ventes
        FROM panier p
        JOIN produits pr ON p.reference_produit = pr.reference
        WHERE p.id_user = ? AND p.expiration > NOW()
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    if (!empty($items)) {
        $total = array_sum(array_column($items, 'prix'));

        // Vérifier le solde
        $stmt = $pdo->prepare("SELECT solde FROM users WHERE id_user = ?");
        $stmt->execute([$userId]);
        $solde = $stmt->fetch()['solde'];

        if ($solde < $total) {
            $_SESSION['toast'] = __t('insufficient_balance');
        } else {
            try {
                $pdo->beginTransaction();

                foreach ($items as $item) {
                    // Vérifier le stock
                    if ($item['stock'] < $item['quantite']) {
                        throw new Exception("Stock insuffisant pour " . $item['reference_produit']);
                    }

                    // Insérer la commande
                    $stmt = $pdo->prepare("
                        INSERT INTO commandes (id_user, reference_produit, quantite, prix_unitaire)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, $item['reference_produit'], $item['quantite'], $item['prix']]);

                    // Mettre à jour le stock et les ventes
                    $stmt = $pdo->prepare("
                        UPDATE produits
                        SET quantite = quantite - ?,
                            nombre_ventes = nombre_ventes + ?
                        WHERE reference = ?
                    ");
                    $stmt->execute([$item['quantite'], $item['quantite'], $item['reference_produit']]);
                }

                // Déduire le solde
                $stmt = $pdo->prepare("UPDATE users SET solde = solde - ? WHERE id_user = ?");
                $stmt->execute([$total, $userId]);
                $_SESSION['solde'] -= $total;

                // Vider le panier
                $stmt = $pdo->prepare("DELETE FROM panier WHERE id_user = ?");
                $stmt->execute([$userId]);

                $pdo->commit();
                $_SESSION['toast'] = __t('order_placed');
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['toast'] = 'Erreur: ' . $e->getMessage();
            }
        }
    }

    header('Location: panier.php');
    exit;
}

//  Rafraîchir le solde 
$stmt = $pdo->prepare("SELECT solde FROM users WHERE id_user = ?");
$stmt->execute([$userId]);
$_SESSION['solde'] = $stmt->fetch()['solde'] ?? 0;

//  Récupération des articles du panier 
$stmt = $pdo->prepare("
    SELECT p.*, pr.designation, pr.designation_en, pr.prix, pr.photo, pr.quantite as stock
    FROM panier p
    JOIN produits pr ON p.reference_produit = pr.reference
    WHERE p.id_user = ? AND p.expiration > NOW()
    ORDER BY p.date_ajout DESC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$total = array_sum(array_column($cartItems, 'prix'));
$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('cart'); ?> — AO Shop</title>
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
            <h1 style="font-size:1.5rem; font-weight:800; margin-bottom:1.5rem;">🛒 <?php echo __t('your_cart'); ?></h1>

            <?php if ($deletedCount > 0): ?>
                <div class="toast info" style="margin-bottom:1rem;">
                    <?php echo __t('cart_expired'); ?> (<?php echo $deletedCount; ?>)
                </div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <div class="card" style="text-align:center; padding:4rem 2rem;">
                    <div style="font-size:4rem; margin-bottom:1rem;">🛒</div>
                    <p style="color:var(--text-muted); font-size:1.125rem; font-weight:600;">
                        <?php echo __t('empty_cart'); ?>
                    </p>
                    <a href="index.php" class="btn-primary" style="margin-top:1.5rem;">
                        ← <?php echo __t('products'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="card" style="padding:0; overflow:hidden;">
                    <?php foreach ($cartItems as $item):
                        $name = ($lang === 'en' && !empty($item['designation_en'])) ? $item['designation_en'] : $item['designation'];
                    ?>
                        <div class="cart-item">
                            <img src="<?php echo get_product_image($item['reference'], $item['photo']); ?>"
                                 alt="" class="cart-item-img" onerror="this.style.display='none'">
                            <div class="cart-item-info">
                                <div class="cart-item-name"><?php echo htmlspecialchars($name); ?></div>
                                <div class="cart-item-qty">Qté: <?php echo $item['quantite']; ?></div>
                            </div>
                            <div class="price-tag" style="white-space:nowrap;">
                                <?php echo number_format($item['prix'], 2); ?> DT
                            </div>
                            <a href="?remove=<?php echo $item['id_panier']; ?>" class="btn-danger" style="font-size:0.8125rem;">
                                ✕
                            </a>
                        </div>
                    <?php endforeach; ?>

                    <div class="cart-total-bar">
                        <span style="color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">
                            <?php echo __t('total'); ?>
                        </span>
                        <span class="price-tag" style="font-size:1.5rem;">
                            <?php echo number_format($total, 2); ?> DT
                        </span>
                    </div>

                    <div style="padding:1.5rem;">
                        <form method="POST" action="">
                            <button type="submit" name="checkout" class="btn-primary" style="width:100%; justify-content:center; font-size:1rem;">
                                <?php echo __t('checkout'); ?> →
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
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
