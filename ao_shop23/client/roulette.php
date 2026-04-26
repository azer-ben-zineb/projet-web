<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { 
    header('Location: ../admin/dashboard.php'); 
    exit; 
}
if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}

$uid = (int)$_SESSION['id_user'];

// Vérifie disponibilité du spin gratuit (24h)
$stmt = $pdo->prepare('SELECT * FROM roulette_log WHERE id_user = ? ORDER BY id_log DESC LIMIT 1');
$stmt->execute([$uid]);
$lastLog = $stmt->fetch();
$freeAvailable = !$lastLog || (time() - strtotime($lastLog['derniere_spin']) >= 86400);

// Dernier coupon valide
$stmt = $pdo->prepare('SELECT * FROM coupons WHERE id_user = ? AND utilise = 0 AND expiration > NOW() ORDER BY id_coupon DESC LIMIT 1');
$stmt->execute([$uid]);
$activeCoupon = $stmt->fetch();

$spinCost = 10.00;
$rewards = [5, 10, 15, 20, 25];
$wheelSlices = [5, 10, 15, 20, 25, 10];

$flashType = '';
$flashMessage = '';
$flashRaw = $_GET['flash'] ?? '';
if (is_string($flashRaw) && str_contains($flashRaw, ':')) {
    [$maybeType, $maybeMessage] = explode(':', $flashRaw, 2);
    if (in_array($maybeType, ['success', 'error', 'info'], true) && trim($maybeMessage) !== '') {
        $flashType = $maybeType;
        $flashMessage = trim($maybeMessage);
    }
}

$styleVersion = (string)(@filemtime(__DIR__ . '/../assets/style.css') ?: time());
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('daily_roulette'); ?> — AO Shop</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo rawurlencode($styleVersion); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<script src="../assets/theme.js"></script>
</head>
<body>
    <div class="fancy-top-gradient"></div>
    <?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container roulette-page">
    <section class="roulette-hero card animate-reveal">
        <div class="roulette-hero-head">
            <div>
                <h1 class="roulette-title">🎡 <?php echo __t('daily_roulette'); ?></h1>
                <p class="roulette-subtitle"><?php echo __t('roulette_subtitle'); ?></p>
            </div>
            <span class="roulette-status <?php echo $freeAvailable ? 'free' : 'paid'; ?>">
                <?php echo $freeAvailable ? '🎁 ' . __t('free_spin') : '💰 ' . __t('paid_spin'); ?>
            </span>
        </div>

        <div class="roulette-meta-grid">
            <div class="roulette-meta-card">
                <span class="roulette-meta-label"><?php echo __t('spin_cost'); ?></span>
                <strong class="roulette-meta-value"><?php echo $freeAvailable ? '0 DT' : number_format($spinCost, 2) . ' DT'; ?></strong>
            </div>
            <div class="roulette-meta-card">
                <span class="roulette-meta-label"><?php echo __t('roulette_possible_rewards'); ?></span>
                <strong class="roulette-meta-value">5% / 10% / 15% / 20% / 25%</strong>
            </div>
            <div class="roulette-meta-card">
                <span class="roulette-meta-label"><?php echo __t('roulette_active_coupon'); ?></span>
                <strong class="roulette-meta-value"><?php echo $activeCoupon ? '-' . (int)$activeCoupon['reduction'] . '%' : '---'; ?></strong>
            </div>
        </div>
    </section>

    <section class="roulette-panel card animate-pop">
        <div class="roulette-container">
            <div class="roulette-pointer"></div>
            <div class="roulette-wheel" id="wheel">
                <?php foreach ($wheelSlices as $idx => $reward): ?>
                    <div class="roulette-wheel-label" style="--i: <?php echo $idx; ?>;">-<?php echo $reward; ?>%</div>
                <?php endforeach; ?>
                <div class="roulette-hub"><?php echo __t('spin'); ?></div>
            </div>
            <div class="roulette-wheel-glow"></div>
        </div>

        <div class="roulette-prize-row">
            <?php foreach ($rewards as $reward): ?>
                <span class="roulette-prize-pill">-<?php echo $reward; ?>%</span>
            <?php endforeach; ?>
        </div>

        <form method="post" action="../actions/spin_roulette.php" id="spin-form" class="roulette-action-form">
            <button class="btn-primary roulette-spin-btn" type="button" id="spin-btn"
                    data-default="<?php echo htmlspecialchars($freeAvailable ? __t('free_spin_btn') : __t('paid_spin_btn'), ENT_QUOTES, 'UTF-8'); ?>"
                    data-loading="<?php echo htmlspecialchars(__t('roulette_spinning'), ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo $freeAvailable ? __t('free_spin_btn') : __t('paid_spin_btn'); ?>
            </button>
            <p class="roulette-note"><?php echo $freeAvailable ? __t('free_spin_note') : __t('paid_spin_note'); ?></p>
        </form>
    </section>

    <?php if ($activeCoupon): ?>
        <section class="coupon-display animate-reveal">
            <div class="coupon-head">
                <div class="coupon-label"><?php echo __t('your_coupon'); ?></div>
                <span class="badge badge-green">-<?php echo (int)$activeCoupon['reduction']; ?>%</span>
            </div>

            <div class="coupon-code-wrap">
                <div class="coupon-code" id="couponCode"><?php echo htmlspecialchars($activeCoupon['code_coupon']); ?></div>
                <button class="btn-ghost coupon-copy-btn" type="button" id="copyCouponBtn">
                    <?php echo __t('roulette_copy_code'); ?>
                </button>
            </div>

            <div class="coupon-timer" id="timer"
                 data-exp="<?php echo htmlspecialchars($activeCoupon['expiration']); ?>"
                 data-label="<?php echo htmlspecialchars(__t('coupon_expires'), ENT_QUOTES, 'UTF-8'); ?>"
                 data-hours="<?php echo htmlspecialchars(__t('hours'), ENT_QUOTES, 'UTF-8'); ?>"
                 data-minutes="<?php echo htmlspecialchars(__t('minutes'), ENT_QUOTES, 'UTF-8'); ?>"
                 data-seconds="<?php echo htmlspecialchars(__t('seconds'), ENT_QUOTES, 'UTF-8'); ?>"></div>
        </section>
    <?php endif; ?>
</div>

<script>
(function () {
    const spinButton = document.getElementById('spin-btn');
    const spinForm = document.getElementById('spin-form');
    const wheel = document.getElementById('wheel');

    function showToast(message, type) {
        if (!message) return;
        const container = document.getElementById('toast-container') || document.body;
        const toast = document.createElement('div');
        toast.className = 'toast ' + (type || 'info');
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    const flashType = <?php echo json_encode($flashType, JSON_UNESCAPED_UNICODE); ?>;
    const flashMessage = <?php echo json_encode($flashMessage, JSON_UNESCAPED_UNICODE); ?>;
    if (flashMessage) {
        showToast(flashMessage, flashType || 'info');
    }

    if (spinButton && spinForm && wheel) {
        spinButton.addEventListener('click', () => {
            if (spinButton.disabled) return;
            spinButton.disabled = true;
            spinButton.textContent = spinButton.dataset.loading || '...';
            wheel.classList.add('is-spinning');

            const turns = 6 + Math.random() * 2.5;
            const offset = Math.random() * 360;
            wheel.style.transform = `rotate(${turns * 360 + offset}deg)`;

            setTimeout(() => spinForm.submit(), 3900);
        });
    }

    const timer = document.getElementById('timer');
    if (timer) {
        const expiresAt = new Date(timer.dataset.exp.replace(' ', 'T')).getTime();

        const render = () => {
            const diff = Math.max(0, expiresAt - Date.now());
            const hours = Math.floor(diff / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            timer.textContent = `${timer.dataset.label} ${hours}${timer.dataset.hours} ${minutes}${timer.dataset.minutes} ${seconds}${timer.dataset.seconds}`;
        };

        render();
        setInterval(render, 1000);
    }

    const copyButton = document.getElementById('copyCouponBtn');
    const couponCode = document.getElementById('couponCode');
    if (copyButton && couponCode) {
        copyButton.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(couponCode.textContent.trim());
                showToast(<?php echo json_encode(__t('roulette_code_copied'), JSON_UNESCAPED_UNICODE); ?>, 'success');
            } catch (err) {
                showToast(couponCode.textContent.trim(), 'info');
            }
        });
    }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
