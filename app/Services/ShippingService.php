<?php

namespace App\Services;

use App\Models\Database;
use PDO;

class ShippingService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function quotes(array $items, string $province, float $subtotal): array {
        $region = $this->regionForProvince($province);
        $stmt = $this->db->prepare('SELECT * FROM shipping_rates WHERE region_code = :region AND status = 1 ORDER BY base_fee ASC');
        $stmt->execute(['region' => $region]);
        $quotes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $rate) {
            $weight = $this->chargeableWeight($items, max(1, (int)$rate['volumetric_divisor']));
            $extraWeight = max(0, $weight - (int)$rate['base_weight_grams']);
            $fee = (float)$rate['base_fee'] + ceil($extraWeight / 500) * (float)$rate['extra_fee_per_500g'];
            if ((float)$rate['free_shipping_threshold'] > 0 && $subtotal >= (float)$rate['free_shipping_threshold']) {
                $fee = 0;
            }
            $quotes[] = [
                'carrier_code' => $rate['carrier_code'],
                'carrier_name' => $rate['carrier_name'],
                'region_code' => $region,
                'fee' => round($fee, 2),
                'chargeable_weight_grams' => $weight,
                'estimated_days' => $rate['estimated_days'],
                'free_shipping_threshold' => (float)$rate['free_shipping_threshold']
            ];
        }
        return $quotes;
    }

    public function selectedQuote(array $items, string $province, float $subtotal, string $carrierCode): array {
        foreach ($this->quotes($items, $province, $subtotal) as $quote) {
            if ($quote['carrier_code'] === $carrierCode) return $quote;
        }
        throw new \RuntimeException('Phương thức vận chuyển không hợp lệ cho tỉnh/thành đã chọn.');
    }

    private function chargeableWeight(array $items, int $divisor): int {
        $total = 0;
        foreach ($items as $item) {
            $quantity = max(1, (int)($item['quantity'] ?? $item['qty'] ?? 1));
            $actual = max(1, (int)($item['weight_grams'] ?? 500));
            $volume = max(1, (float)($item['length_cm'] ?? 25) * (float)($item['width_cm'] ?? 20) * (float)($item['height_cm'] ?? 5));
            $volumetric = (int)ceil(($volume / $divisor) * 1000);
            $total += max($actual, $volumetric) * $quantity;
        }
        return max(1, $total);
    }

    private function regionForProvince(string $province): string {
        $value = mb_strtolower(trim($province), 'UTF-8');
        // iconv trên một số bản MAMP làm rơi chữ tiếng Việt (ví dụ "Hà Nội"
        // thành "h`a ni"), nên chuẩn hóa chủ động để phân vùng không phụ thuộc OS.
        $ascii = strtr($value, [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d'
        ]);
        $ascii = preg_replace('/\s+/', ' ', $ascii) ?: $ascii;
        if (str_contains($ascii, 'ho chi minh') || str_contains($ascii, 'hcm')) return 'hcm';
        foreach (['ha noi', 'da nang', 'hai phong', 'can tho'] as $major) {
            if (str_contains($ascii, $major)) return 'major_city';
        }
        return 'nationwide';
    }
}
