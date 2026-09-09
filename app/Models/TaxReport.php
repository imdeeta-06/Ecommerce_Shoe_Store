<?php

namespace App\Models;

use DateTimeImmutable;
use PDO;

class TaxReport extends BaseModel {
    public function build(string $period, string $month, int $year, int $quarter): array {
        [$start, $end, $label] = $this->resolvePeriod($period, $month, $year, $quarter);

        $paymentJoin = RevenueRecognition::paymentJoin();
        $dateExpr = RevenueRecognition::dateExpression();
        $factor = "CASE WHEN o.final_amount>0 THEN
                GREATEST(0,o.final_amount-LEAST(o.final_amount,COALESCE(p.refunded_amount,0)))/o.final_amount
            ELSE 0 END";

        $summaryStmt = $this->db->prepare("SELECT COUNT(*) AS order_count,
                COALESCE(SUM(o.final_amount),0) AS gross_revenue,
                COALESCE(SUM(LEAST(o.final_amount,COALESCE(p.refunded_amount,0))),0) AS refunded_amount,
                COALESCE(SUM(GREATEST(0,o.final_amount-COALESCE(p.refunded_amount,0))),0) AS net_revenue,
                COALESCE(SUM(o.taxable_amount*($factor)),0) AS taxable_amount,
                COALESCE(SUM(o.non_taxable_amount*($factor)),0) AS non_taxable_amount,
                COALESCE(SUM(o.tax_amount*($factor)),0) AS tax_amount
            FROM orders o
            $paymentJoin
            WHERE o.status IN ('delivered','completed')
              AND $dateExpr >= ? AND $dateExpr < ?");
        $summaryStmt->execute([$start, $end]);

        $itemStmt = $this->db->prepare("SELECT oi.tax_category_snapshot AS tax_category,
                oi.tax_rate_snapshot AS tax_rate,
                COALESCE(SUM(oi.taxable_amount*($factor)),0) AS taxable_amount,
                COALESCE(SUM(oi.tax_amount*($factor)),0) AS tax_amount,
                COALESCE(SUM(GREATEST(0,oi.price_at_time*oi.quantity-COALESCE(oi.discount_amount,0))*($factor)),0) AS gross_amount
            FROM orders o
            $paymentJoin
            JOIN order_items oi ON oi.order_id=o.id
            WHERE o.status IN ('delivered','completed')
              AND $dateExpr >= ? AND $dateExpr < ?
            GROUP BY oi.tax_category_snapshot,oi.tax_rate_snapshot");
        $itemStmt->execute([$start, $end]);

        $shippingStmt = $this->db->prepare("SELECT o.shipping_tax_category AS tax_category,
                o.shipping_tax_rate AS tax_rate,
                COALESCE(SUM(GREATEST(0,o.shipping_fee-o.shipping_tax_amount)*($factor)),0) AS taxable_amount,
                COALESCE(SUM(o.shipping_tax_amount*($factor)),0) AS tax_amount,
                COALESCE(SUM(o.shipping_fee*($factor)),0) AS gross_amount
            FROM orders o
            $paymentJoin
            WHERE o.status IN ('delivered','completed') AND o.shipping_fee>0
              AND $dateExpr >= ? AND $dateExpr < ?
            GROUP BY o.shipping_tax_category,o.shipping_tax_rate");
        $shippingStmt->execute([$start, $end]);

        $breakdown = $this->mergeBreakdown(array_merge(
            $itemStmt->fetchAll(PDO::FETCH_ASSOC),
            $shippingStmt->fetchAll(PDO::FETCH_ASSOC)
        ));

        $trendStmt = $this->db->prepare("SELECT DATE_FORMAT($dateExpr,'%Y-%m') AS period_key,
                COUNT(*) AS order_count,
                COALESCE(SUM(GREATEST(0,o.final_amount-COALESCE(p.refunded_amount,0))),0) AS net_revenue,
                COALESCE(SUM(o.tax_amount*($factor)),0) AS tax_amount
            FROM orders o
            $paymentJoin
            WHERE o.status IN ('delivered','completed')
              AND $dateExpr >= ? AND $dateExpr < ?
            GROUP BY DATE_FORMAT($dateExpr,'%Y-%m')
            ORDER BY period_key");
        $trendStmt->execute([$start, $end]);

        return [
            'period' => $period,
            'month' => $month,
            'year' => $year,
            'quarter' => $quarter,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'summary' => $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [],
            'breakdown' => $breakdown,
            'trend' => $trendStmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    private function mergeBreakdown(array $rows): array {
        $merged = [];
        foreach ($rows as $row) {
            $category = (string)($row['tax_category'] ?? 'standard_reduced');
            $rate = (float)($row['tax_rate'] ?? 0);
            $key = $category === 'not_subject' ? 'not_subject' : number_format($rate, 2, '.', '');
            if (!isset($merged[$key])) {
                $rateLabel = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');
                $merged[$key] = [
                    'tax_label' => $category === 'not_subject' ? 'Không chịu thuế' : $rateLabel . '%',
                    'tax_sort' => $category === 'not_subject' ? 999 : $rate,
                    'taxable_amount' => 0,
                    'tax_amount' => 0,
                    'gross_amount' => 0,
                ];
            }
            $merged[$key]['taxable_amount'] += (float)($row['taxable_amount'] ?? 0);
            $merged[$key]['tax_amount'] += (float)($row['tax_amount'] ?? 0);
            $merged[$key]['gross_amount'] += (float)($row['gross_amount'] ?? 0);
        }
        usort($merged, static fn(array $a, array $b): int => $a['tax_sort'] <=> $b['tax_sort']);
        return array_values($merged);
    }

    private function resolvePeriod(string $period, string &$month, int &$year, int &$quarter): array {
        if ($period === 'quarter') {
            $year = max(2020, min(2100, $year));
            $quarter = max(1, min(4, $quarter));
            $startMonth = (($quarter - 1) * 3) + 1;
            $startDate = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $startMonth));
            return [$startDate->format('Y-m-d 00:00:00'), $startDate->modify('+3 months')->format('Y-m-d 00:00:00'), "Quý {$quarter}/{$year}"];
        }
        $period = 'month';
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = date('Y-m');
        }
        $startDate = new DateTimeImmutable($month . '-01');
        $year = (int)$startDate->format('Y');
        $quarter = (int)ceil((int)$startDate->format('n') / 3);
        return [$startDate->format('Y-m-d 00:00:00'), $startDate->modify('+1 month')->format('Y-m-d 00:00:00'), 'Tháng ' . $startDate->format('m/Y')];
    }
}
