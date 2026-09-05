<?php

namespace App\Controller\Admin;

use App\Middleware\AuthMiddleware;
use App\Models\TaxReport;

class TaxReportController {
    public function index(): void {
        AuthMiddleware::requireAdmin();
        $period = ($_GET['period'] ?? 'month') === 'quarter' ? 'quarter' : 'month';
        $month = trim((string)($_GET['month'] ?? date('Y-m')));
        $year = (int)($_GET['year'] ?? date('Y'));
        $quarter = (int)($_GET['quarter'] ?? ceil((int)date('n') / 3));
        $reportError = null;
        try {
            $report = (new TaxReport())->build($period, $month, $year, $quarter);
        } catch (\Throwable $error) {
            error_log('Tax report failed: ' . $error->getMessage());
            $reportError = 'Không thể đọc dữ liệu báo cáo. Hãy kiểm tra lại các tệp cập nhật và cấu trúc cơ sở dữ liệu.';
            $safeMonth = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) ? $month : date('Y-m');
            $safeQuarter = max(1, min(4, $quarter));
            $report = [
                'period' => $period,
                'month' => $safeMonth,
                'year' => $year,
                'quarter' => $safeQuarter,
                'label' => $period === 'quarter' ? "Quý {$safeQuarter}/{$year}" : 'Tháng ' . date('m/Y', strtotime($safeMonth . '-01')),
                'summary' => [],
                'breakdown' => [],
                'trend' => [],
            ];
        }
        require __DIR__ . '/../../Views/admin/tax-report/index.php';
    }
}
