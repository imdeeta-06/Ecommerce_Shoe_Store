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

    // 1. Tính tổng doanh thu linh động theo khoảng thời gian (Bỏ qua các đơn hàng bị 'canceled')
    public function calculateTotalRevenue($startDate = null, $endDate = null) {
        $query = "SELECT SUM(final_amount) as total_revenue FROM orders WHERE status != 'canceled'";
        $params = [];

        if ($startDate) {
            $query .= " AND DATE(created_at) >= :start_date";
            $params['start_date'] = $startDate;
        }
        if ($endDate) {
            $query .= " AND DATE(created_at) <= :end_date";
            $params['end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total_revenue'] ? (float)$result['total_revenue'] : 0.00;
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
