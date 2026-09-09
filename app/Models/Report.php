<?php

namespace App\Models;

use PDO;

class Report extends BaseModel {
    public function __construct() {
        parent::__construct();
        // Xử lý logic cho các bảng: daily_revenue_reports, product_sales_reports
    }

    // --- DAILY_REVENUE_REPORTS ---
    public function createDailyRevenue($data) { return $this->insert('daily_revenue_reports', $data); }
    public function getDailyRevenueById($id) { return $this->getById('daily_revenue_reports', $id); }

    public function getDailyRevenue($date) {
        $stmt = $this->db->prepare("SELECT * FROM daily_revenue_reports WHERE report_date = :report_date");
        $stmt->execute(['report_date' => $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- PRODUCT_SALES_REPORTS ---
    public function createProductSalesReport($data) { return $this->insert('product_sales_reports', $data); }
    public function getProductSalesReportById($id) { return $this->getById('product_sales_reports', $id); }

    public function getSalesByDate($date) {
        $stmt = $this->db->prepare("SELECT * FROM product_sales_reports WHERE report_date = :report_date");
        $stmt->execute(['report_date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- DASHBOARD STATISTICS ---

    public function getDashboardData(): array {
        $revenue = $this->calculateTotalRevenue();
        $latestOrders = $this->db->query("SELECT o.id, o.order_code, o.created_at, o.final_amount, o.status,
                COALESCE(u.full_name, o.shipping_name, 'Khách lẻ') AS customer_name
            FROM orders o
            LEFT JOIN `user` u ON u.id = o.user_id
            ORDER BY o.created_at DESC, o.id DESC
            LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

        $revenueByDay = [];
        $dayStart = new \DateTimeImmutable('-6 days');
        for ($offset = 0; $offset < 7; $offset++) {
            $date = $dayStart->modify("+{$offset} days")->format('Y-m-d');
            $revenueByDay[$date] = 0;
        }

        $paymentJoin = RevenueRecognition::paymentJoin();
        $dateExpr = RevenueRecognition::dateExpression();
        $revenueStmt = $this->db->prepare("SELECT DATE($dateExpr) AS report_date,
                COALESCE(SUM(GREATEST(0, o.final_amount - COALESCE(p.refunded_amount, 0))), 0) AS revenue
            FROM orders o
            $paymentJoin
            WHERE o.status IN ('delivered', 'completed')
              AND DATE($dateExpr) >= :start_date
            GROUP BY DATE($dateExpr)");
        $revenueStmt->execute(['start_date' => $dayStart->format('Y-m-d')]);
        foreach ($revenueStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (array_key_exists($row['report_date'], $revenueByDay)) {
                $revenueByDay[$row['report_date']] = (float)$row['revenue'];
            }
        }

        $statusRows = $this->db->query('SELECT status, COUNT(*) AS total FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
        $customerCount = (int)$this->db->query("SELECT COUNT(*) FROM `user` WHERE role = 'user' AND status = 1")->fetchColumn();
        $productCount = (int)$this->db->query('SELECT COUNT(*) FROM product WHERE status = 1')->fetchColumn();
        $lowStockCount = (int)$this->db->query("SELECT COUNT(*)
            FROM product_variants pv
            JOIN product p ON p.id = pv.product_id
            WHERE p.status = 1 AND pv.status = 1
              AND GREATEST(CAST(pv.stock_quantity AS SIGNED) - CAST(pv.reserved_quantity AS SIGNED), 0) <= 5")->fetchColumn();
        $newOrderCount = (int)$this->db->query("SELECT COUNT(*) FROM orders
            WHERE created_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')")->fetchColumn();
        $newCustomerCount = (int)$this->db->query("SELECT COUNT(*) FROM `user`
            WHERE role = 'user' AND status = 1
              AND created_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')")->fetchColumn();

        return [
            'revenue' => $revenue,
            'latest_orders' => $latestOrders,
            'revenue_by_day' => $revenueByDay,
            'status_counts' => $statusRows,
            'customer_count' => $customerCount,
            'product_count' => $productCount,
            'low_stock_count' => $lowStockCount,
            'new_order_count' => $newOrderCount,
            'new_customer_count' => $newCustomerCount,
        ];
    }

    // 1. Doanh thu thực nhận: chỉ tính đơn đã giao/hoàn thành và trừ hoàn tiền.
    public function calculateTotalRevenue($startDate = null, $endDate = null) {
        $paymentJoin = RevenueRecognition::paymentJoin();
        $dateExpr = RevenueRecognition::dateExpression();
        $query = "SELECT COALESCE(SUM(GREATEST(0, o.final_amount - COALESCE(p.refunded_amount, 0))), 0)
            FROM orders o $paymentJoin
            WHERE o.status IN ('delivered', 'completed')";
        $params = [];
        if ($startDate) {
            $query .= " AND DATE($dateExpr) >= :start_date";
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $query .= " AND DATE($dateExpr) <= :end_date";
            $params['end_date'] = $endDate;
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    // 2. Thống kê / Lọc các sản phẩm bị hủy nhiều nhất (Nằm trong các đơn hàng 'canceled')
    public function getCanceledProductsReport($startDate = null, $endDate = null, $limit = 10) {
        $query = "
            SELECT 
                oi.variant_id, 
                SUM(oi.quantity) as total_canceled_quantity,
                pv.size,
                pv.color,
                p.name as product_name
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN product_variants pv ON oi.variant_id = pv.id
            JOIN product p ON pv.product_id = p.id
            WHERE o.status = 'canceled'
        ";
        
        $params = [];

        if ($startDate) {
            $query .= " AND DATE(o.created_at) >= :start_date";
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $query .= " AND DATE(o.created_at) <= :end_date";
            $params['end_date'] = $endDate;
        }

        $query .= " GROUP BY oi.variant_id, p.name, pv.size, pv.color ORDER BY total_canceled_quantity DESC LIMIT :limit";

        $stmt = $this->db->prepare($query);

        // Bind params tĩnh cho string
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        // Bind limit phải ép kiểu INT
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
