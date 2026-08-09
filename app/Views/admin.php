<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . 'login' : '/login'));
    exit;
}

$admin_success = $_SESSION['admin_success'] ?? null;
unset($_SESSION['admin_success']);
$admin_error = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_error']);

require_once __DIR__ . '/../../config/db.php';

function adminOrderImagePath($image): string {
    $image = trim((string)$image);
    if ($image === '') return 'assets/images/placeholder.jpg';
    if (str_starts_with($image, 'http')) return $image;
    if (str_starts_with($image, 'public/uploads/')) return $image;
    if (str_starts_with($image, 'uploads/')) return 'public/' . $image;
    if (str_starts_with($image, 'assets/')) return $image;
    return 'assets/images/' . $image;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 1;
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, status) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $status]);
            header("Location: ?page=categories&success=1");
            exit;
        }
    } elseif ($action === 'add_coupon') {
        $code = trim($_POST['code'] ?? '');
        $usage_limit = $_POST['usage_limit'] ?? 100;
        $discount_type = $_POST['discount_type'] ?? 'percent';
        $discount_value = $_POST['discount_value'] ?? 0;
        $min_order_amount = $_POST['min_order_amount'] ?? 0;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d H:i:s');
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d H:i:s', strtotime('+30 days'));

        $discount_percent = null;
        $max_discount = null;
        if ($discount_type === 'percent') {
            $discount_percent = $discount_value;
        } else {
            $max_discount = $discount_value;
        }

        if (!empty($code)) {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_percent, max_discount, min_order_amount, usage_limit, start_date, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $discount_percent, $max_discount, $min_order_amount, $usage_limit, $start_date, $expiry_date]);
            header("Location: ?page=coupons&success=1");
            exit;
        }
    } elseif ($action === 'edit_coupon') {
        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $usage_limit = (int)($_POST['usage_limit'] ?? 100);
        $discount_type = $_POST['discount_type'] ?? 'percent';
        $discount_value = (float)($_POST['discount_value'] ?? 0);
        $min_order_amount = (float)($_POST['min_order_amount'] ?? 0);
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d H:i:s');
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d H:i:s', strtotime('+30 days'));

        $discount_percent = null;
        $max_discount = null;
        if ($discount_type === 'percent') {
            $discount_percent = $discount_value;
        } else {
            $max_discount = $discount_value;
        }

        if ($id > 0 && !empty($code)) {
            $stmt = $pdo->prepare("UPDATE coupons SET code = ?, discount_percent = ?, max_discount = ?, min_order_amount = ?, usage_limit = ?, start_date = ?, expiry_date = ? WHERE id = ?");
            $stmt->execute([$code, $discount_percent, $max_discount, $min_order_amount, $usage_limit, $start_date, $expiry_date, $id]);
        }
        header("Location: ?page=coupons&success=1");
        exit;
    } elseif ($action === 'delete_coupon') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$id]);
        }
        header("Location: ?page=coupons&success=1");
        exit;
    } elseif ($action === 'add_inventory') {
        $variant_id = $_POST['variant_id'] ?? 0;
        $transaction_type = $_POST['transaction_type'] ?? 'in';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        
        if ($transaction_type === 'out') {
            $quantity = -$quantity;
        }

        if ($variant_id > 0 && $quantity !== 0) {
            $stmt = $pdo->prepare("INSERT INTO inventory_logs (variant_id, quantity_changed, reason) VALUES (?, ?, ?)");
            $stmt->execute([$variant_id, $quantity, $reason]);
            header("Location: ?page=inventory&success=1");
            exit;
        }
    } elseif ($action === 'update_order_status') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $note = trim($_POST['note'] ?? 'Admin updated order status');
        $changed_by = $_SESSION['user_id'] ?? null;
        $order_model = new \App\Models\Order();
        $result = $order_model->updateStatus($order_id, $status, $note, $changed_by);
        $_SESSION[$result['success'] ? 'admin_success' : 'admin_error'] = $result['message'];

        header("Location: ?page=order_detail&id=$order_id&success=1");
        exit;
    } elseif ($action === 'delete_user') {
        $user_id = $_POST['delete_id'] ?? 0;
        if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
            $stmt->execute([$user_id]);
        }
        header("Location: ?page=users&success=1");
        exit;
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Fetch data for specific pages
if ($page === 'users') {
    $stmt = $pdo->query("SELECT * FROM user ORDER BY id DESC");
    $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($page === 'orders') {
    $stmt = $pdo->query("
        SELECT o.*, u.full_name as user_name 
        FROM orders o 
        LEFT JOIN user u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ");
    $admin_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate order stats
    $total_orders_count = count($admin_orders);
    $total_revenue = 0;
    $pending_count = 0;
    foreach ($admin_orders as $o) {
        if (in_array($o['status'], ['delivered', 'completed'], true)) {
            $total_revenue += $o['final_amount'];
        }
        if ($o['status'] === 'pending') {
            $pending_count++;
        }
    }
    $refund_stmt = $pdo->query("SELECT COALESCE(SUM(refund_amount), 0) FROM after_sale_requests WHERE status IN ('refunded', 'completed')");
    $total_revenue -= (float)$refund_stmt->fetchColumn();
    $total_revenue = max(0, $total_revenue);
    $stmt_cust = $pdo->query("SELECT COUNT(*) FROM user");
    $total_customers = $stmt_cust->fetchColumn();

} elseif ($page === 'order_detail') {
    $id = $_GET['id'] ?? 0;
    
    // Fetch order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header("Location: ?page=orders");
        exit;
    }
    
    // Fetch user info
    $customer = null;
    if ($order['user_id']) {
        $stmt_user = $pdo->prepare("SELECT * FROM user WHERE id = ?");
        $stmt_user->execute([$order['user_id']]);
        $customer = $stmt_user->fetch(PDO::FETCH_ASSOC);
    }
    
    $stmt_items = $pdo->prepare("
        SELECT
            oi.*,
            p.name as product_name,
            p.slug as product_slug,
            pv.size,
            pv.color,
            pi.image_url as product_image
        FROM order_items oi
        LEFT JOIN product_variants pv ON oi.variant_id = pv.id
        LEFT JOIN product p ON pv.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE oi.order_id = ?
    ");
    $stmt_items->execute([$id]);
    $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $stmt_logs = $pdo->prepare("SELECT * FROM order_status_logs WHERE order_id = ? ORDER BY created_at ASC, id ASC");
    $stmt_logs->execute([$id]);
    $order_status_logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($page === 'user_detail') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        header("Location: ?page=users");
        exit;
    }
    
    $stmt_addr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
    $stmt_addr->execute([$id]);
    $customer_addresses = $stmt_addr->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt_orders->execute([$id]);
    $customer_orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);
    
    $total_spent = 0;
    foreach ($customer_orders as $o) {
        if (!in_array($o['status'], ['cancelled'])) {
            $total_spent += $o['total_amount'];
        }
    }
} elseif ($page === 'coupons') {
    $stmt = $pdo->query("SELECT * FROM coupons ORDER BY id DESC");
    $admin_coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/admin/_helpers.php';
adminStart($page === 'dashboard' ? 'Bảng điều khiển' : ucfirst($page), $page, ['type' => $admin_error ? 'error' : ($admin_success ? 'success' : ''), 'message' => $admin_error ?: $admin_success]);
?>

        <?php if ($page === 'dashboard'): ?>
            
            <div class="admin-grid" style="margin-bottom: 2rem;">
                <!-- Card 1: Doanh thu -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Tổng doanh thu</div>
                            <div class="stat-value" style="white-space: nowrap;">24.5M ₫</div>
                        </div>
                        <div class="stat-icon" style="background: #E8F5E9; color: #4CAF50;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </div>
                    </div>
                    <div class="stat-change up">↑ +12% so với tháng trước</div>
                </div>

                <!-- Card 2: Đơn hàng -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Đơn hàng mới</div>
                            <div class="stat-value">48</div>
                        </div>
                        <div class="stat-icon" style="background: #E3F2FD; color: #2196F3;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"></path></svg>
                        </div>
                    </div>
                    <div class="stat-change up">↑ +5% so với tháng trước</div>
                </div>

                <!-- Card 3: Khách hàng -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Khách hàng</div>
                            <div class="stat-value">1,024</div>
                        </div>
                        <div class="stat-icon" style="background: #F3E5F5; color: #9C27B0;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                    <div class="stat-change up">↑ +28 thành viên mới</div>
                </div>

                <!-- Card 4: Sản phẩm -->
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Sản phẩm</div>
                            <div class="stat-value">156</div>
                        </div>
                        <div class="stat-icon" style="background: #FFF3E0; color: #FF9800;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        </div>
                    </div>
                    <div class="stat-change down">↓ 12 sản phẩm sắp hết</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="admin-panel" style="margin-bottom: 0;">
                    <div class="admin-panel-title">Biểu đồ doanh thu (7 ngày)</div>
                    <canvas id="revenueChart" height="120"></canvas>
                </div>
                <div class="admin-panel" style="margin-bottom: 0; display: flex; flex-direction: column;">
                    <div class="admin-panel-title">Trạng thái đơn hàng</div>
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center; position: relative;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="admin-table-wrapper">
                <div class="admin-panel-title" style="padding: 1.5rem 1.5rem 0 1.5rem; border: none; margin: 0;">Đơn hàng mới nhất</div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#ORD-001</td>
                            <td style="font-weight: 600;">Nguyễn Văn A</td>
                            <td style="color: #6b7280;">25/06/2026</td>
                            <td style="font-weight: 600;">3.800.000 ₫</td>
                            <td><span class="admin-badge neutral">MỚI</span></td>
                        </tr>
                        <tr>
                            <td>#ORD-002</td>
                            <td style="font-weight: 600;">Trần Thị B</td>
                            <td style="color: #6b7280;">24/06/2026</td>
                            <td style="font-weight: 600;">4.200.000 ₫</td>
                            <td><span class="admin-badge warning">ĐANG GIAO</span></td>
                        </tr>
                        <tr>
                            <td>#ORD-003</td>
                            <td style="font-weight: 600;">Lê Văn C</td>
                            <td style="color: #6b7280;">23/06/2026</td>
                            <td style="font-weight: 600;">2.100.000 ₫</td>
                            <td><span class="admin-badge success">HOÀN THÀNH</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Revenue Chart
                const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
                const gradientRevenue = ctxRevenue.createLinearGradient(0, 0, 0, 400);
                gradientRevenue.addColorStop(0, 'rgba(17, 17, 17, 0.8)');
                gradientRevenue.addColorStop(1, 'rgba(17, 17, 17, 0.1)');
                
                new Chart(ctxRevenue, {
                    type: 'bar',
                    data: {
                        labels: ['19/06', '20/06', '21/06', '22/06', '23/06', '24/06', '25/06'],
                        datasets: [{
                            label: 'Doanh thu (VNĐ)',
                            data: [3500000, 5200000, 4800000, 2100000, 7500000, 4200000, 8900000],
                            backgroundColor: '#111',
                            borderRadius: 6,
                            barThickness: 32
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { 
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#111',
                                titleFont: { family: 'Inter', size: 13 },
                                bodyFont: { family: 'Inter', size: 14, weight: 'bold' },
                                padding: 12,
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                                    }
                                }
                            }
                        },
                        scales: { 
                            y: { 
                                beginAtZero: true,
                                grid: { color: '#f3f4f6', drawBorder: false },
                                border: { display: false },
                                ticks: { font: { family: 'Inter' }, color: '#6b7280' }
                            },
                            x: {
                                grid: { display: false, drawBorder: false },
                                border: { display: false },
                                ticks: { font: { family: 'Inter', weight: '500' }, color: '#374151' }
                            }
                        }
                    }
                });

                // Status Chart
                const ctxStatus = document.getElementById('statusChart').getContext('2d');
                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: ['Mới', 'Đang giao', 'Hoàn thành', 'Đã huỷ'],
                        datasets: [{
                            data: [15, 8, 22, 3],
                            backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#ef4444'],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { 
                                position: 'bottom',
                                labels: { 
                                    font: { family: 'Inter', size: 12, weight: '500' },
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: '#111',
                                bodyFont: { family: 'Inter', size: 14, weight: 'bold' },
                                padding: 12,
                                cornerRadius: 8
                            }
                        },
                        cutout: '70%'
                    }
                });
            });
            </script>

        <?php elseif ($page === 'orders'): ?>
            <div class="admin-title" style="margin-bottom: 1.5rem;">
                <div>
                    <h1>Quản lý đơn hàng</h1>
                    <p>Theo dõi và cập nhật trạng thái các đơn đặt hàng mới nhất.</p>
                </div>
                <div>
                    <select style="padding: 10px 14px; border-radius: 6px; border: 1px solid var(--admin-border); font-family: var(--font-ui); font-size: 0.9rem; font-weight: 600; outline: none;">
                        <option>Tất cả trạng thái</option>
                        <option>Mới</option>
                        <option>Đang xử lý</option>
                        <option>Đang giao</option>
                        <option>Hoàn thành</option>
                        <option>Đã huỷ</option>
                    </select>
                </div>
            </div>

            <div class="admin-grid" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Tổng đơn hàng</div>
                            <div class="stat-value"><?= $total_orders_count ?></div>
                        </div>
                        <div class="stat-icon" style="background: #E3F2FD; color: #2196F3;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"></path></svg>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Doanh thu</div>
                            <div class="stat-value" style="white-space: nowrap;"><?= $total_revenue >= 1000000 ? rtrim(rtrim(number_format($total_revenue / 1000000, 1, ',', '.'), '0'), ',') . 'M' : number_format($total_revenue, 0, ',', '.') ?> ₫</div>
                        </div>
                        <div class="stat-icon" style="background: #E8F5E9; color: #4CAF50;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Chờ xử lý</div>
                            <div class="stat-value"><?= $pending_count ?></div>
                        </div>
                        <div class="stat-icon" style="background: #FFF3E0; color: #FF9800;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Khách hàng</div>
                            <div class="stat-value"><?= $total_customers ?></div>
                        </div>
                        <div class="stat-icon" style="background: #F3E5F5; color: #9C27B0;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Thanh toán</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admin_orders as $o): ?>
                        <tr>
                            <td style="color: #6b7280; font-family: monospace;">#ORD-<?= str_pad($o['id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td style="font-weight: 600; color: #111;"><?= htmlspecialchars($o['user_name'] ?? $o['shipping_name'] ?? 'Khách lẻ') ?></td>
                            <td style="color: #6b7280; font-size: 0.9rem;"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                            <td><span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 700; color: #374151;"><?= htmlspecialchars($o['payment_method'] ?? 'COD') ?></span></td>
                            <td style="font-weight: 700; color: #111;"><?= number_format($o['final_amount'], 0, ',', '.') ?> ₫</td>
                            <td>
                                <?php
                                $status_classes = [
                                    'pending' => 'neutral', // Hoặc tạo class mới cho pending (màu vàng nhạt)
                                    'confirmed' => 'success',
                                    'preparing' => 'warning',
                                    'shipping' => 'warning',
                                    'delivered' => 'success',
                                    'completed' => 'success',
                                    'canceled' => 'error'
                                ];
                                $status_labels = [
                                    'pending' => 'Mới',
                                    'confirmed' => 'Đã xác nhận',
                                    'preparing' => 'Đang chuẩn bị',
                                    'shipping' => 'Đang giao',
                                    'delivered' => 'Giao thành công',
                                    'completed' => 'Hoàn thành',
                                    'canceled' => 'Đã huỷ'
                                ];
                                $cls = $status_classes[$o['status']] ?? 'neutral';
                                $lbl = $status_labels[$o['status']] ?? 'Khác';
                                ?>
                                <span class="admin-badge <?= $cls ?>"><?= mb_strtoupper($lbl, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <a href="?page=order_detail&id=<?= $o['id'] ?>" class="admin-btn-sm admin-btn light">Chi tiết</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($admin_orders)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: #6b7280;">Chưa có đơn hàng nào.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page === 'order_detail'): ?>
            <div class="admin-title" style="margin-bottom: 2rem;">
                <div>
                    <h1>Chi tiết đơn hàng</h1>
                    <p>Mã đơn: #ORD-<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></p>
                </div>
                <a href="?page=orders" class="admin-btn light">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Quay lại
                </a>
            </div>

            <div class="admin-form-layout" style="margin-bottom: 2.5rem;">
                <!-- Left Column -->
                <div>
                    <div class="admin-panel">
                        <div class="admin-panel-title" style="border-bottom: 1px solid var(--admin-border); padding-bottom: 1rem; margin-bottom: 1.5rem;">Sản phẩm đã đặt</div>
                        <div class="admin-table-scroll">
                            <table class="admin-table" style="min-width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Đơn giá</th>
                                        <th>SL</th>
                                        <th style="text-align: right;">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td class="admin-product-cell">
                                            <div class="admin-thumb" style="width: 50px; height: 50px;">
                                                <img src="<?= BASE_URL . htmlspecialchars(adminOrderImagePath($item['product_image'] ?? '')) ?>" style="width: 100%; height: 100%; object-fit: contain;">
                                            </div>
                                            <div>
                                                <strong style="color: #111;"><?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm') ?></strong>
                                                <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                                                    <div style="font-size: 0.8rem; color: #666; margin-top: 4px;"><?= htmlspecialchars(trim(($item['size'] ?? '') . ' ' . ($item['color'] ?? ''))) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="color: #666;"><?= number_format($item['price_at_time'], 0, ',', '.') ?> ₫</td>
                                        <td style="font-weight: 600;"><?= $item['quantity'] ?></td>
                                        <td style="text-align: right; font-weight: 700; color: #111;"><?= number_format($item['price_at_time'] * $item['quantity'], 0, ',', '.') ?> ₫</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="margin-top: 1.5rem; border-top: 1px solid var(--admin-border); padding-top: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: #444;">
                                <span>Tạm tính</span>
                                <span style="font-weight: 600; color: #111;"><?= number_format($order['total_amount'], 0, ',', '.') ?> ₫</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: #444;">
                                <span>Phí giao hàng</span>
                                <span style="font-weight: 600; color: #10b981;">MIỄN PHÍ</span>
                            </div>
                            <?php 
                            $discount = $order['total_amount'] - $order['final_amount']; 
                            if ($discount > 0): 
                            ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: #444;">
                                <span>Giảm giá</span>
                                <span style="font-weight: 600; color: #ef4444;">- <?= number_format($discount, 0, ',', '.') ?> ₫</span>
                            </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between; margin-top: 1rem; border-top: 1px dashed var(--admin-border); padding-top: 1rem;">
                                <span style="font-weight: 800; font-size: 1.2rem; color: #111;">Tổng cộng</span>
                                <span style="font-weight: 850; font-size: 1.5rem; color: #111;"><?= number_format($order['final_amount'], 0, ',', '.') ?> ₫</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div class="admin-panel">
                        <div class="admin-panel-title">Thông tin giao dịch</div>
                        <div style="margin-bottom: 12px;">
                            <div class="admin-muted" style="font-size: 0.75rem; text-transform: uppercase;">Mã đơn</div>
                            <div style="font-weight: 600;">#ORD-<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></div>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <div class="admin-muted" style="font-size: 0.75rem; text-transform: uppercase;">Ngày đặt</div>
                            <div style="font-weight: 600;"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                        </div>
                        <div>
                            <div class="admin-muted" style="font-size: 0.75rem; text-transform: uppercase;">Phương thức thanh toán</div>
                            <div style="font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($order['payment_method'] ?? 'COD') ?></div>
                        </div>
                    </div>

                    <div class="admin-panel">
                        <div class="admin-panel-title">Khách hàng</div>
                        <div style="margin-bottom: 12px;">
                            <div class="admin-muted" style="font-size: 0.75rem; text-transform: uppercase;">Họ tên & Email</div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($order['shipping_name'] ?? $customer['full_name'] ?? '') ?></div>
                            <div style="color: #666; font-size: 0.9rem; margin-top: 2px;"><?= htmlspecialchars($order['shipping_email'] ?? $customer['email'] ?? 'N/A') ?></div>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <div class="admin-muted" style="font-size: 0.75rem; text-transform: uppercase;">Điện thoại</div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($order['shipping_phone'] ?? 'N/A') ?></div>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <div class="admin-muted" style="font-size: 0.75rem; text-transform: uppercase;">Địa chỉ giao hàng</div>
                            <div style="font-weight: 600; font-size: 0.9rem; line-height: 1.5;"><?= htmlspecialchars($order['shipping_address'] ?? 'N/A') ?></div>
                        </div>
                    </div>

                    <div class="admin-panel">
                        <div class="admin-panel-title">Cập nhật trạng thái</div>
                        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            
                            <div style="margin-bottom: 4px;">
                                <?php
                                $status_classes = [
                                    'pending' => 'neutral',
                                    'confirmed' => 'success',
                                    'preparing' => 'warning',
                                    'shipping' => 'warning',
                                    'delivered' => 'success',
                                    'completed' => 'success',
                                    'canceled' => 'error'
                                ];
                                $status_labels = [
                                    'pending' => 'Mới',
                                    'confirmed' => 'Đã xác nhận',
                                    'preparing' => 'Đang chuẩn bị',
                                    'shipping' => 'Đang giao',
                                    'delivered' => 'Giao thành công',
                                    'completed' => 'Hoàn thành',
                                    'canceled' => 'Đã huỷ'
                                ];
                                $cls = $status_classes[$order['status']] ?? 'neutral';
                                $lbl = $status_labels[$order['status']] ?? 'Khác';
                                ?>
                                <span class="admin-badge <?= $cls ?>"><?= mb_strtoupper($lbl, 'UTF-8') ?></span>
                            </div>
                            
                            <div class="admin-field" style="margin-bottom: 0;">
                                <select name="status">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Mới</option>
                                    <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                                    <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Đang chuẩn bị</option>
                                    <option value="shipping" <?= $order['status'] === 'shipping' ? 'selected' : '' ?>>Đang giao</option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Giao thành công</option>
                                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                    <option value="canceled" <?= $order['status'] === 'canceled' ? 'selected' : '' ?>>Đã huỷ</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="admin-btn primary" style="width: 100%;">Lưu thay đổi</button>
                        </form>
                    </div>
                </div>
            </div>

        <?php elseif ($page === 'users'): ?>
            <div class="admin-title" style="margin-bottom: 2rem;">
                <div>
                    <h1>Quản lý khách hàng</h1>
                    <p>Danh sách tài khoản người dùng và quản trị viên.</p>
                </div>
                <a href="<?= BASE_URL ?>admin/users/create" class="admin-btn primary">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM20 8h-4M18 6v4"></path></svg>
                    Thêm quản trị viên
                </a>
            </div>

            <?php if ($admin_success): ?>
                <div class="admin-flash success">
                    ✅ <?= htmlspecialchars($admin_success) ?>
                </div>
            <?php endif; ?>

            <h2 style="font-size: 1.25rem; font-family: var(--font-heading); margin-bottom: 1rem; color: #111;">Tài khoản Quản trị (Admin)</h2>
            <div class="admin-table-wrapper" style="margin-bottom: 3rem;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Quản trị viên</th>
                            <th>Email</th>
                            <th>Ngày tham gia</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hasAdmin = false;
                        foreach ($admin_users as $u): 
                            if ($u['role'] !== 'admin') continue;
                            $hasAdmin = true;
                        ?>
                        <tr>
                            <td style="color: #6b7280; font-family: monospace;">#<?= $u['id'] ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="admin-thumb" style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden;">
                                        <img src="<?= !empty($u['avatar']) ? BASE_URL . $u['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($u['full_name']).'&background=111&color=fff&size=40' ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <strong style="color: #111;"><?= htmlspecialchars($u['full_name']) ?></strong>
                                </div>
                            </td>
                            <td style="color: #444;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="color: #6b7280; font-size: 0.9rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div class="admin-actions" style="margin: 0;">
                                    <a href="?page=user_detail&id=<?= $u['id'] ?>" class="admin-btn-sm admin-btn light">Chi tiết</a>
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <form action="" method="POST" onsubmit="return confirm('Xác nhận xóa tài khoản này?')" style="margin: 0; display: inline-block;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="admin-btn-sm admin-btn danger">Xóa</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$hasAdmin): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem 1rem; color: #6b7280;">Chưa có quản trị viên nào.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 style="font-size: 1.25rem; font-family: var(--font-heading); margin-bottom: 1rem; color: #111;">Khách hàng (User)</h2>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>Email</th>
                            <th>Ngày tham gia</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hasUser = false;
                        foreach ($admin_users as $u): 
                            if ($u['role'] === 'admin') continue;
                            $hasUser = true;
                        ?>
                        <tr>
                            <td style="color: #6b7280; font-family: monospace;">#<?= $u['id'] ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="admin-thumb" style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden;">
                                        <img src="<?= !empty($u['avatar']) ? BASE_URL . $u['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($u['full_name']).'&background=111&color=fff&size=40' ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <strong style="color: #111;"><?= htmlspecialchars($u['full_name']) ?></strong>
                                </div>
                            </td>
                            <td style="color: #444;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="color: #6b7280; font-size: 0.9rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div class="admin-actions" style="margin: 0;">
                                    <a href="?page=user_detail&id=<?= $u['id'] ?>" class="admin-btn-sm admin-btn light">Chi tiết</a>
                                    <form action="" method="POST" onsubmit="return confirm('Xác nhận xóa tài khoản này?')" style="margin: 0; display: inline-block;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="admin-btn-sm admin-btn danger">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$hasUser): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem 1rem; color: #6b7280;">Chưa có khách hàng nào.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page === 'user_detail'): ?>
            <div class="admin-title" style="margin-bottom: 2rem;">
                <div>
                    <h1>Chi tiết khách hàng</h1>
                    <p>Hồ sơ khách hàng, địa chỉ và lịch sử mua hàng.</p>
                </div>
                <a href="?page=users" class="admin-btn light">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Quay lại
                </a>
            </div>

            <!-- Profile and Stats Grid -->
            <div class="admin-form-layout" style="margin-bottom: 2rem; grid-template-columns: 2fr 1fr;">
                <!-- Profile Card -->
                <div class="admin-panel" style="display: flex; align-items: flex-start; gap: 2rem;">
                    <div class="admin-thumb" style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex-shrink: 0;">
                        <img src="<?= !empty($customer['avatar']) ? BASE_URL . $customer['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($customer['full_name']).'&background=111&color=fff&size=100' ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div>
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #111;"><?= htmlspecialchars($customer['full_name']) ?></h3>
                        <div style="color: #555; font-size: 0.95rem; line-height: 1.6;">
                            <div><strong>Email:</strong> <?= htmlspecialchars($customer['email']) ?></div>
                            <div><strong>Vai trò:</strong> <span style="text-transform: capitalize;"><?= htmlspecialchars($customer['role'] ?? 'user') ?></span></div>
                            <div><strong>Ngày tham gia:</strong> <?= date('d/m/Y', strtotime($customer['created_at'])) ?></div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <span class="admin-badge success">Hoạt động</span>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div class="admin-panel" style="padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div class="admin-muted" style="margin-bottom: 4px;">Tổng đơn hàng</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #111;"><?= count($customer_orders) ?></div>
                        </div>
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: #E3F2FD; color: #2196F3; display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"></path></svg>
                        </div>
                    </div>
                    <div class="admin-panel" style="padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div class="admin-muted" style="margin-bottom: 4px;">Tổng chi tiêu</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #111;"><?= number_format($total_spent, 0, ',', '.') ?> ₫</div>
                        </div>
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: #E8F5E9; color: #4CAF50; display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Addresses Grid -->
            <div class="admin-grid" style="margin-bottom: 2rem;">
                <div class="admin-panel">
                    <div class="admin-panel-title">Sổ địa chỉ</div>
                    <?php if (empty($customer_addresses)): ?>
                        <p class="admin-muted" style="font-style: italic;">Chưa có địa chỉ nào được lưu.</p>
                    <?php else: ?>
                        <?php foreach ($customer_addresses as $addr): ?>
                        <div style="margin-bottom: 1.2rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--admin-border); line-height: 1.6;">
                            <strong style="color: #111; font-size: 1rem;"><?= htmlspecialchars($customer['full_name']) ?> 
                                <?php if (isset($addr['is_default']) && $addr['is_default']): ?>
                                <span class="admin-badge neutral" style="font-size: 0.7rem; margin-left: 8px;">Mặc định</span>
                                <?php endif; ?>
                            </strong><br>
                            <div style="color: #555;">
                                <div>SĐT: <?= htmlspecialchars($addr['phone'] ?? 'N/A') ?></div>
                                <div><?= htmlspecialchars($addr['address_line1']) ?></div>
                                <div><?= htmlspecialchars($addr['city']) ?> <?= htmlspecialchars($addr['postal_code']) ?></div>
                                <div><?= htmlspecialchars($addr['country']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="admin-panel">
                    <div class="admin-panel-title">Ghi chú nội bộ</div>
                    <p style="color: #444; line-height: 1.6;">Khách hàng đăng ký qua hệ thống cửa hàng.</p>
                    <p class="admin-muted" style="font-size: 0.85rem; margin-top: 1rem;">Bởi Admin • <?= date('d/m/Y H:i', strtotime($customer['created_at'])) ?></p>
                </div>
            </div>

            <!-- Order History Table -->
            <div class="admin-panel">
                <div class="admin-panel-title">Lịch sử đơn hàng</div>
                <div class="admin-table-scroll">
                    <table class="admin-table" style="min-width: 100%;">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái ĐH</th>
                                <th>Ngày đặt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customer_orders)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 3rem; color: #888;">Chưa có đơn hàng nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($customer_orders as $o): ?>
                                <tr>
                                    <td>
                                        <a href="?page=order_detail&id=<?= $o['id'] ?>" style="color: #2196F3; font-family: monospace; font-weight: 600;">#ORD-<?= str_pad($o['id'], 3, '0', STR_PAD_LEFT) ?></a>
                                    </td>
                                    <td style="font-weight: 700; color: #111;"><?= number_format($o['total_amount'], 0, ',', '.') ?> ₫</td>
                                    <td style="text-transform: uppercase; font-size: 0.85rem; color: #555;"><?= htmlspecialchars($o['payment_method'] ?? 'COD') ?></td>
                                    <td>
                                        <?php
                                        $status_classes = [
                                            'pending' => 'neutral',
                                            'confirmed' => 'success',
                                            'preparing' => 'warning',
                                            'shipping' => 'warning',
                                            'delivered' => 'success',
                                            'completed' => 'success',
                                            'canceled' => 'error'
                                        ];
                                        $status_labels = [
                                            'pending' => 'Mới',
                                            'confirmed' => 'Đã xác nhận',
                                            'preparing' => 'Đang chuẩn bị',
                                            'shipping' => 'Đang giao',
                                            'delivered' => 'Giao thành công',
                                            'completed' => 'Hoàn thành',
                                            'canceled' => 'Đã huỷ'
                                        ];
                                        $cls = $status_classes[$o['status']] ?? 'neutral';
                                        $lbl = $status_labels[$o['status']] ?? 'Khác';
                                        ?>
                                        <span class="admin-badge <?= $cls ?>"><?= mb_strtoupper($lbl, 'UTF-8') ?></span>
                                    </td>
                                    <td style="color: #666; font-size: 0.9rem;"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page === 'settings'): ?>
            <div class="admin-title" style="margin-bottom: 2rem;">
                <div>
                    <h1>Cài đặt hệ thống</h1>
                    <p>Cấu hình thông tin chung của website.</p>
                </div>
            </div>
            
            <div class="admin-panel" style="max-width: 600px;">
                <div class="admin-field">
                    <label>Tên website</label>
                    <input type="text" value="PaceUp">
                </div>
                <div class="admin-field">
                    <label>Email liên hệ</label>
                    <input type="email" value="cskh@paceup.vn">
                </div>
                <div class="admin-field">
                    <label>Phí vận chuyển mặc định (VNĐ)</label>
                    <input type="text" value="30.000">
                </div>
                <button class="admin-btn primary">Lưu cài đặt</button>
            </div>

        <?php elseif ($page === 'categories'): ?>
            <div class="admin-title" style="margin-bottom: 2rem;">
                <div>
                    <h1>Quản lý danh mục</h1>
                    <p>Mục này đã được chuyển sang controller mới.</p>
                </div>
                <a href="<?= BASE_URL ?>admin/categories" class="admin-btn primary">Đến trang quản lý danh mục mới</a>
            </div>

        <?php elseif ($page === 'inventory'): ?>
            <div class="admin-title" style="margin-bottom: 2rem;">
                <div>
                    <h1>Quản lý kho hàng</h1>
                    <p>Kiểm soát số lượng sản phẩm nhập xuất.</p>
                </div>
                <button class="admin-btn primary" onclick="openModal('inventoryModal')">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path d="M12 5v14M5 12h14"></path></svg>
                    Nhập kho
                </button>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Mã SP (SKU)</th>
                            <th>Tồn kho</th>
                            <th>Trạng thái kho</th>
                            <th>Lần nhập cuối</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="admin-product-cell">
                                <div class="admin-thumb" style="width: 44px; height: 44px;">
                                    <img src="<?= BASE_URL ?>assets/images/AIR+ZOOM+PEGASUS+42+WIDE.avif" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                                <strong style="color: #111;">Nike Air Zoom Pegasus 42</strong>
                            </td>
                            <td><span style="color: #666; font-family: monospace;">NK-PEG42-BLK</span></td>
                            <td><strong style="font-size: 1.1rem;">124</strong></td>
                            <td><span class="admin-badge success">Đủ hàng</span></td>
                            <td style="color: #666; font-size: 0.9rem;">20/06/2026</td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('inventoryModal')" class="admin-btn-sm admin-btn light">Cập nhật</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="admin-product-cell">
                                <div class="admin-thumb" style="width: 44px; height: 44px;">
                                    <img src="<?= BASE_URL ?>assets/images/NIKE+SB+DUNK+LOW+PRO.avif" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                                <strong style="color: #111;">Nike SB Dunk Low Pro</strong>
                            </td>
                            <td><span style="color: #666; font-family: monospace;">NK-DUNK-LOW</span></td>
                            <td><strong style="font-size: 1.1rem; color: #f59e0b;">5</strong></td>
                            <td><span class="admin-badge warning">Sắp hết</span></td>
                            <td style="color: #666; font-size: 0.9rem;">15/05/2026</td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('inventoryModal')" class="admin-btn-sm admin-btn light">Cập nhật</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="admin-product-cell">
                                <div class="admin-thumb" style="width: 44px; height: 44px; background: #eee;"></div>
                                <strong style="color: #111;">Adidas Ultraboost Light</strong>
                            </td>
                            <td><span style="color: #666; font-family: monospace;">AD-UB-LGT</span></td>
                            <td><strong style="font-size: 1.1rem; color: #ef4444;">0</strong></td>
                            <td><span class="admin-badge error">Hết hàng</span></td>
                            <td style="color: #666; font-size: 0.9rem;">10/04/2026</td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('inventoryModal')" class="admin-btn-sm admin-btn light">Cập nhật</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal Nhập Kho -->
            <div id="inventoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: #fff; width: 100%; max-width: 500px; border-radius: 12px; padding: 2rem; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <button onclick="closeModal('inventoryModal')" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
                    <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.5rem; text-transform: uppercase;">Cập nhật kho hàng</h3>
                    
                    <form action="?page=inventory" method="POST">
                        <input type="hidden" name="action" value="add_inventory">
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Chọn sản phẩm *</label>
                            <select name="variant_id" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                                <option value="1">Nike Air Zoom Pegasus 42</option>
                                <option value="2">Nike SB Dunk Low Pro</option>
                                <option value="3">Adidas Ultraboost Light</option>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Loại giao dịch *</label>
                                <select name="transaction_type" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                                    <option value="in">Nhập thêm (+)</option>
                                    <option value="out">Xuất kho (-)</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Số lượng *</label>
                                <input type="number" name="quantity" required min="1" placeholder="Nhập số lượng" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                            </div>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Ghi chú</label>
                            <textarea name="reason" rows="2" placeholder="Ví dụ: Nhập hàng đợt 2 tháng 6" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui); resize: vertical;"></textarea>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="button" onclick="closeModal('inventoryModal')" style="padding: 0.8rem 1.5rem; background: #f5f5f5; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; color: #333;">Hủy</button>
                            <button type="submit" class="btn btn-dark" style="padding: 0.8rem 1.5rem; border-radius: 6px;">Cập nhật</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($page === 'coupons'): ?>
            <div class="admin-title" style="margin-bottom: 2rem;">
                <div>
                    <h1>Mã giảm giá</h1>
                    <p>Quản lý các chương trình khuyến mãi.</p>
                </div>
                <button class="admin-btn primary" onclick="if(typeof resetCouponForm === 'function') resetCouponForm(); openModal('couponModal')">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path d="M12 5v14M5 12h14"></path></svg>
                    Tạo mã mới
                </button>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã Code</th>
                            <th>Mức giảm</th>
                            <th>Điều kiện</th>
                            <th>Đã dùng / Tổng</th>
                            <th>Hạn sử dụng</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admin_coupons as $coupon): 
                            $now = date('Y-m-d H:i:s');
                            $isValid = true;
                            $statusLabel = 'Khả dụng';
                            $statusClass = 'success';

                            if (!empty($coupon['expiry_date']) && $now > $coupon['expiry_date']) {
                                $isValid = false;
                                $statusLabel = 'Đã hết hạn';
                                $statusClass = 'error';
                            } elseif (!empty($coupon['usage_limit']) && $coupon['used_count'] >= $coupon['usage_limit']) {
                                $isValid = false;
                                $statusLabel = 'Hết lượt';
                                $statusClass = 'error';
                            } elseif (!empty($coupon['start_date']) && $now < $coupon['start_date']) {
                                $isValid = false;
                                $statusLabel = 'Chưa đến hạn';
                                $statusClass = 'warning';
                            }
                            
                            $discountText = $coupon['discount_percent'] ? '-'.$coupon['discount_percent'].'%' : '-'.number_format($coupon['max_discount'], 0, ',', '.').'₫';
                            $conditionText = $coupon['min_order_amount'] > 0 ? 'Đơn tối thiểu '.number_format($coupon['min_order_amount'], 0, ',', '.').'đ' : 'Không điều kiện';
                        ?>
                        <tr>
                            <td><strong style="font-family: monospace; font-size: 1.1rem; color: #111; letter-spacing: 1px;"><?= htmlspecialchars($coupon['code']) ?></strong></td>
                            <td><strong style="color: #ef4444;"><?= $discountText ?></strong></td>
                            <td style="color: #555;"><?= htmlspecialchars($conditionText) ?></td>
                            <td style="font-weight: 600;"><?= $coupon['used_count'] ?> / <span style="color: #888;"><?= $coupon['usage_limit'] ?: '∞' ?></span></td>
                            <td>
                                <?php if ($isValid): ?>
                                    <span style="color: #666;"><?= date('d/m/Y', strtotime($coupon['expiry_date'])) ?></span>
                                <?php else: ?>
                                    <span style="color: #ef4444; font-weight: bold;"><?= date('d/m/Y', strtotime($coupon['expiry_date'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="admin-badge <?= $statusClass ?>"><?= mb_strtoupper($statusLabel, 'UTF-8') ?></span></td>
                            <td>
                                <?php
                                $dType = $coupon['discount_percent'] ? 'percent' : 'fixed';
                                $dVal = $coupon['discount_percent'] ?: $coupon['max_discount'];
                                $cJson = htmlspecialchars(json_encode([
                                    'id' => $coupon['id'],
                                    'code' => $coupon['code'],
                                    'usage_limit' => $coupon['usage_limit'],
                                    'discount_type' => $dType,
                                    'discount_value' => $dVal,
                                    'start_date' => date('Y-m-d', strtotime($coupon['start_date'])),
                                    'expiry_date' => date('Y-m-d', strtotime($coupon['expiry_date'])),
                                    'min_order_amount' => $coupon['min_order_amount']
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <div class="admin-actions">
                                    <a href="javascript:void(0)" onclick="editCoupon(<?= $cJson ?>)" class="admin-btn-sm admin-btn light">Sửa</a>
                                    <a href="javascript:void(0)" onclick="deleteCoupon(<?= $coupon['id'] ?>, '<?= htmlspecialchars($coupon['code'], ENT_QUOTES) ?>')" class="admin-btn-sm admin-btn danger">Xóa</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($admin_coupons)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 3rem 1rem; color: #888;">Chưa có mã giảm giá nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Form ẩn để xóa -->
            <form id="deleteCouponForm" action="?page=coupons" method="POST" style="display: none;">
                <input type="hidden" name="action" value="delete_coupon">
                <input type="hidden" name="id" id="delete_coupon_id">
            </form>

            <script>
            function editCoupon(coupon) {
                document.getElementById('coupon_id').value = coupon.id;
                document.getElementById('coupon_action').value = 'edit_coupon';
                document.querySelector('input[name="code"]').value = coupon.code;
                document.querySelector('input[name="usage_limit"]').value = coupon.usage_limit;
                document.querySelector('select[name="discount_type"]').value = coupon.discount_type;
                document.querySelector('input[name="discount_value"]').value = coupon.discount_value;
                document.querySelector('input[name="start_date"]').value = coupon.start_date;
                document.querySelector('input[name="expiry_date"]').value = coupon.expiry_date;
                document.querySelector('input[name="min_order_amount"]').value = coupon.min_order_amount;
                
                document.getElementById('couponModalTitle').innerText = 'Sửa mã giảm giá';
                openModal('couponModal');
            }

            function resetCouponForm() {
                document.getElementById('coupon_id').value = '';
                document.getElementById('coupon_action').value = 'add_coupon';
                document.getElementById('couponModalForm').reset();
                document.getElementById('couponModalTitle').innerText = 'Thông tin mã giảm giá';
            }

            function deleteCoupon(id, code) {
                if (confirm('Bạn có chắc chắn muốn xóa mã ' + code + ' không?')) {
                    document.getElementById('delete_coupon_id').value = id;
                    document.getElementById('deleteCouponForm').submit();
                }
            }

            function generateCode() {
                const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                let result = '';
                for (let i = 0; i < 8; i++) {
                    result += characters.charAt(Math.floor(Math.random() * characters.length));
                }
                document.querySelector('input[name="code"]').value = result;
            }
            </script>

            <!-- Modal Tạo Mã Mới -->
            <div id="couponModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: #fff; width: 100%; max-width: 550px; border-radius: 12px; padding: 2rem; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <button onclick="closeModal('couponModal'); if(typeof resetCouponForm === 'function') resetCouponForm();" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
                    <h3 id="couponModalTitle" style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.5rem; text-transform: uppercase;">Thông tin mã giảm giá</h3>
                    
                    <form id="couponModalForm" action="?page=coupons" method="POST">
                        <input type="hidden" name="action" id="coupon_action" value="add_coupon">
                        <input type="hidden" name="id" id="coupon_id" value="">
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Mã Code (Tự nhập hoặc tạo ngẫu nhiên) *</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" name="code" required placeholder="VD: SUMMER26" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui); text-transform: uppercase;">
                                    <button type="button" style="padding: 0 1rem; background: #eee; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: bold;" onclick="generateCode()">Tạo</button>
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Số lượng</label>
                                <input type="number" name="usage_limit" value="100" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Loại giảm giá</label>
                                <select name="discount_type" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                                    <option value="percent">Phần trăm (%)</option>
                                    <option value="fixed">Số tiền cố định (VNĐ)</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Mức giảm *</label>
                                <input type="number" name="discount_value" required placeholder="Ví dụ: 10" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Ngày bắt đầu</label>
                                <input type="date" name="start_date" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Hạn sử dụng *</label>
                                <input type="date" name="expiry_date" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                            </div>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Đơn tối thiểu (VNĐ)</label>
                            <input type="number" name="min_order_amount" placeholder="0" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="button" onclick="closeModal('couponModal')" style="padding: 0.8rem 1.5rem; background: #f5f5f5; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; color: #333;">Hủy</button>
                            <button type="submit" class="btn btn-dark" style="padding: 0.8rem 1.5rem; border-radius: 6px;">Lưu mã giảm giá</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php else: // products ?>
            <div class="admin-title" style="margin-bottom: 2rem;">
                <div>
                    <h1>Danh mục sản phẩm</h1>
                    <p>Mục này đã được chuyển sang controller mới.</p>
                </div>
                <a href="<?= BASE_URL ?>admin/products" class="admin-btn primary">Đến trang quản lý sản phẩm mới</a>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sản phẩm</th>
                            <th>Phân loại</th>
                            <th>Giá</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td class="admin-product-cell">
                                <div class="admin-thumb" style="width: 44px; height: 44px;">
                                    <img src="<?= BASE_URL ?>assets/images/AIR+ZOOM+PEGASUS+42+WIDE.avif" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                                <strong style="color: #111;">Nike Air Zoom Pegasus 42</strong>
                            </td>
                            <td>Giày Chạy Bộ Nam</td>
                            <td style="font-weight: 600;">3.800.000 ₫</td>
                            <td>
                                <div class="admin-actions">
                                    <a href="javascript:void(0)" onclick="openModal('productModal')" class="admin-btn-sm admin-btn light">Sửa</a>
                                    <a href="javascript:void(0)" onclick="confirmDelete(1)" class="admin-btn-sm admin-btn danger">Xóa</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td class="admin-product-cell">
                                <div class="admin-thumb" style="width: 44px; height: 44px;">
                                    <img src="<?= BASE_URL ?>assets/images/NIKE+SB+DUNK+LOW+PRO.avif" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                                <strong style="color: #111;">Nike SB Dunk Low Pro</strong>
                            </td>
                            <td>Giày Skate Nam</td>
                            <td style="font-weight: 600;">4.200.000 ₫</td>
                            <td>
                                <div class="admin-actions">
                                    <a href="javascript:void(0)" onclick="openModal('productModal')" class="admin-btn-sm admin-btn light">Sửa</a>
                                    <a href="javascript:void(0)" onclick="confirmDelete(2)" class="admin-btn-sm admin-btn danger">Xóa</a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal Thêm/Sửa Sản Phẩm -->
            <div id="productModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: #fff; width: 100%; max-width: 600px; border-radius: 12px; padding: 2rem; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <button onclick="closeModal('productModal')" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
                    <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.5rem; text-transform: uppercase;">Thông tin sản phẩm</h3>
                    
                    <form action="#" method="POST" enctype="multipart/form-data">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Tên sản phẩm *</label>
                                <input type="text" required placeholder="Nhập tên sản phẩm" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Giá bán (VNĐ) *</label>
                                <input type="number" required placeholder="Ví dụ: 3800000" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Phân loại *</label>
                                <select style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                                    <option value="men_running">Giày chạy bộ nam</option>
                                    <option value="women_running">Giày chạy bộ nữ</option>
                                    <option value="men_lifestyle">Giày thời trang nam</option>
                                    <option value="women_lifestyle">Giày thời trang nữ</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Hình ảnh *</label>
                                <input type="file" accept="image/*" style="width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui); background: #fafafa;">
                            </div>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Mô tả sản phẩm</label>
                            <textarea rows="4" placeholder="Nhập mô tả chi tiết..." style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui); resize: vertical;"></textarea>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="button" onclick="closeModal('productModal')" style="padding: 0.8rem 1.5rem; background: #f5f5f5; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; color: #333;">Hủy</button>
                            <button type="submit" class="btn btn-dark" style="padding: 0.8rem 1.5rem; border-radius: 6px;">Lưu sản phẩm</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>

<!-- Modal Xác Nhận Xóa -->
<div id="deleteConfirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 100%; max-width: 400px; border-radius: 12px; padding: 2rem; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2); text-align: center;">
        <div style="width: 60px; height: 60px; background: #FFEBEE; color: #D32F2F; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: bold; margin: 0 auto 1.5rem;">
            !
        </div>
        <h3 style="margin-bottom: 1rem; font-family: var(--font-heading); font-size: 1.5rem;">Xác nhận xóa</h3>
        <p style="color: #666; font-family: var(--font-ui); margin-bottom: 2rem;">Bạn có chắc chắn muốn xóa mục này? Hành động này không thể hoàn tác.</p>
        
        <form id="deleteForm" action="" method="POST">
            <input type="hidden" name="action" id="deleteActionName" value="">
            <input type="hidden" name="delete_id" id="deleteIdInput" value="">
            
            <div style="display: flex; gap: 1rem;">
                <button type="button" onclick="closeModal('deleteConfirmModal')" style="flex: 1; padding: 0.8rem; background: #f5f5f5; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; color: #333; font-family: var(--font-ui);">Hủy</button>
                <button type="submit" style="flex: 1; padding: 0.8rem; background: #F44336; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-family: var(--font-ui); transition: 0.3s;">Xóa ngay</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    function confirmDelete(id, actionName = 'delete_item') {
        document.getElementById('deleteIdInput').value = id;
        document.getElementById('deleteActionName').value = actionName;
        openModal('deleteConfirmModal');
    }
</script>

<?php adminEnd(); ?>
