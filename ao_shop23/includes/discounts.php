<?php


if (!function_exists('get_subscription_discount_percent')) {
    function get_subscription_discount_percent(?string $subscriptionType): int
    {
        return match ($subscriptionType) {
            'mensuel' => 10,
            'annuel' => 15,
            default => 0,
        };
    }
}

if (!function_exists('apply_discount_to_price')) {
    function apply_discount_to_price(float $basePrice, int $discountPercent): float
    {
        $discountPercent = max(0, min(95, $discountPercent));
        if ($discountPercent === 0) {
            return round($basePrice, 2);
        }

        return round($basePrice * ((100 - $discountPercent) / 100), 2);
    }
}

if (!function_exists('with_discounted_price')) {
    function with_discounted_price(array $product, int $discountPercent): array
    {
        $originalPrice = (float)($product['prix'] ?? 0);
        $product['prix_original'] = $originalPrice;
        $product['discount_percent'] = $discountPercent;
        $product['prix_reduit'] = apply_discount_to_price($originalPrice, $discountPercent);

        return $product;
    }
}

if (!function_exists('get_user_pricing_context')) {
    function get_user_pricing_context(PDO $pdo, int $userId, bool $forUpdate = false): array
    {
        $lockSuffix = $forUpdate ? ' FOR UPDATE' : '';

        $stmt = $pdo->prepare(
            'SELECT type_abonnement
             FROM abonnements
             WHERE id_user = ? AND actif = 1 AND date_fin > NOW()
             ORDER BY date_fin DESC, id_abonnement DESC
             LIMIT 1' . $lockSuffix
        );
        $stmt->execute([$userId]);
        $subscriptionType = $stmt->fetchColumn() ?: null;
        $subscriptionDiscount = get_subscription_discount_percent($subscriptionType ?: null);

        $stmt = $pdo->prepare(
            'SELECT id_coupon, code_coupon, reduction
             FROM coupons
             WHERE id_user = ? AND utilise = 0 AND expiration > NOW()
             ORDER BY reduction DESC, id_coupon DESC
             LIMIT 1' . $lockSuffix
        );
        $stmt->execute([$userId]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $couponDiscount = (int)($coupon['reduction'] ?? 0);
        $effectiveDiscount = max($subscriptionDiscount, $couponDiscount);

        return [
            'subscription_type' => $subscriptionType,
            'subscription_discount_percent' => $subscriptionDiscount,
            'coupon_id' => isset($coupon['id_coupon']) ? (int)$coupon['id_coupon'] : null,
            'coupon_code' => $coupon['code_coupon'] ?? null,
            'coupon_discount_percent' => $couponDiscount,
            'effective_discount_percent' => $effectiveDiscount,
            // Coupon is consumed only when it strictly improves the discount.
            'coupon_applied' => $couponDiscount > $subscriptionDiscount,
        ];
    }
}
