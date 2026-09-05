<?php

namespace App\Services;

final class TaxService {
    public const CATEGORIES = [
        'standard_reduced' => ['label' => 'Chịu thuế 8% (được giảm từ 10% đến 31/12/2026)', 'rate' => 8.0, 'taxable' => true],
        'standard' => ['label' => 'Chịu thuế 10% (không thuộc diện giảm)', 'rate' => 10.0, 'taxable' => true],
        'reduced' => ['label' => 'Chịu thuế 5%', 'rate' => 5.0, 'taxable' => true],
        'zero' => ['label' => 'Thuế suất 0%', 'rate' => 0.0, 'taxable' => true],
        'not_subject' => ['label' => 'Không chịu thuế GTGT', 'rate' => 0.0, 'taxable' => false],
    ];

    public static function categories(): array {
        $categories = self::CATEGORIES;
        if (!self::standardReductionActive()) {
            $categories['standard_reduced']['label'] = 'Thuế suất chuẩn 10% (chính sách giảm 8% đã hết hiệu lực)';
            $categories['standard_reduced']['rate'] = 10.0;
        }
        return $categories;
    }

    public static function normalizeCategory(string $category): string {
        return array_key_exists($category, self::CATEGORIES) ? $category : 'standard_reduced';
    }

    public static function rateFor(string $category, $rate = null): float {
        $category = self::normalizeCategory($category);
        $expected = (float)self::categories()[$category]['rate'];
        if ($category === 'standard_reduced') return $expected;
        $rate = $rate === null || $rate === '' ? $expected : (float)$rate;
        return round(max(0, min(100, $rate)), 2);
    }

    /**
     * Giá bán trên website là giá đã có thuế. Giảm giá được trừ trước khi
     * tách giá tính thuế để số khách trả không thay đổi khi bật VAT.
     */
    public static function splitInclusive(float $grossAfterDiscount, string $category, float $rate): array {
        $grossAfterDiscount = max(0, round($grossAfterDiscount, 2));
        $category = self::normalizeCategory($category);
        // `$rate` là snapshot đã được chốt khi tạo đơn. Không tự thay nó theo
        // ngày hiện tại, nếu không một đơn 8% cũ có thể biến thành 10% về sau.
        $rate = round(max(0, min(100, $rate)), 2);
        if (!self::CATEGORIES[$category]['taxable']) {
            return ['taxable_amount' => 0.0, 'tax_amount' => 0.0, 'non_taxable_amount' => $grossAfterDiscount];
        }
        $taxable = $rate > 0 ? round($grossAfterDiscount / (1 + $rate / 100), 2) : $grossAfterDiscount;
        return [
            'taxable_amount' => $taxable,
            'tax_amount' => round($grossAfterDiscount - $taxable, 2),
            'non_taxable_amount' => 0.0,
        ];
    }

    public static function shippingCategory(): string {
        return self::normalizeCategory((string)((require __DIR__ . '/../../config/tax.php')['shipping_category'] ?? 'standard_reduced'));
    }

    private static function standardReductionActive(): bool {
        $end = (string)((require __DIR__ . '/../../config/tax.php')['standard_reduction_ends_on'] ?? '2026-12-31');
        return date('Y-m-d') <= $end;
    }
}
