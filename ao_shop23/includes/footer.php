<?php

?>

<!-- Feature Bar -->
<div class="footer-feature-bar">
    <div class="footer-feature-grid">
        <div class="footer-feature-item">
            <span class="footer-feature-icon">🚚</span>
            <div class="footer-feature-text">
                <strong><?php echo __t('fast_delivery'); ?></strong>
                <span><?php echo __t('delivery_text'); ?></span>
            </div>
        </div>
        <div class="footer-feature-item">
            <span class="footer-feature-icon">📞</span>
            <div class="footer-feature-text">
                <strong><?php echo __t('support_24_7'); ?></strong>
                <span><?php echo __t('support_text'); ?></span>
            </div>
        </div>
        <div class="footer-feature-item">
            <span class="footer-feature-icon">💳</span>
            <div class="footer-feature-text">
                <strong><?php echo __t('cash_on_delivery'); ?></strong>
                <span><?php echo __t('cash_text'); ?></span>
            </div>
        </div>
        <div class="footer-feature-item">
            <span class="footer-feature-icon">⚡</span>
            <div class="footer-feature-text">
                <strong><?php echo __t('fast_shipping'); ?></strong>
                <span><?php echo __t('shipping_text'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Footer principal -->
<footer>
    <div class="footer-inner">
        <!-- Colonne 1: Informations -->
        <div class="footer-col">
            <div class="footer-col-title"><?php echo __t('shop_name'); ?></div>
            <a href="#" class="footer-link"><?php echo __t('about'); ?></a>
            <a href="#" class="footer-link"><?php echo __t('terms'); ?></a>
            <a href="#" class="footer-link"><?php echo __t('privacy'); ?></a>
            <a href="#" class="footer-link"><?php echo __t('returns'); ?></a>
            <a href="#" class="footer-link"><?php echo __t('tracking'); ?></a>
        </div>

        <!-- Colonne 2: Services -->
        <div class="footer-col">
            <div class="footer-col-title"><?php echo __t('products'); ?></div>
            <a href="#" class="footer-link"><?php echo __t('pro_service'); ?></a>
            <a href="#" class="footer-link"><?php echo __t('sav'); ?></a>
            <a href="#" class="footer-link"><?php echo __t('contact'); ?></a>
            <a href="#" class="footer-link"><?php echo __t('fast_delivery'); ?></a>
            <a href="#" class="footer-link"><?php echo __t('order_tracking'); ?></a>
        </div>

        <!-- Colonne 3: Contact -->
        <div class="footer-col">
            <div class="footer-col-title"><?php echo __t('contact'); ?></div>
            <div class="footer-link">📍 <?php echo __t('address'); ?></div>
            <div class="footer-link">📞 <?php echo __t('phone'); ?></div>
            <div class="footer-link">📧 <?php echo __t('email_contact'); ?></div>
            <div class="footer-link">🕐 <?php echo __t('shop_hours'); ?></div>
        </div>

        <!-- Colonne 4: Newsletter -->
        <div class="footer-col">
            <div class="footer-col-title"><?php echo __t('newsletter'); ?></div>
            <p style="color:var(--text-dim); font-size:0.9375rem; margin-bottom:1rem; line-height:1.5;">
                <?php echo __t('newsletter_text'); ?>
            </p>
            <div style="display:flex; gap:0.5rem;">
                <input type="email" class="form-input" placeholder="Email"
                       style="flex:1; font-size:0.875rem; padding:0.5rem 0.75rem;">
                <button type="button" class="btn-primary" style="padding:0.5rem 1rem; font-size:0.875rem;">
                    <?php echo __t('subscribe_btn'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Copyright & réseaux sociaux -->
    <div class="footer-bottom">
        <div class="footer-copy"><?php echo __t('copyright'); ?></div>
        <div class="social-icons">
            <a href="#" class="social-icon" title="Facebook">f</a>
            <a href="#" class="social-icon" title="Instagram">📷</a>
            <a href="#" class="social-icon" title="YouTube">▶</a>
            <a href="#" class="social-icon" title="Email">✉</a>
        </div>
    </div>
</footer>
