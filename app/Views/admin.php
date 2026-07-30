<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_success = $_SESSION['admin_success'] ?? null;
unset($_SESSION['admin_success']);
$admin_error = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_error']);

require_once 'config/db.php';

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
        if ($o['status'] !== 'canceled') {
            $total_revenue += $o['final_amount'];
        }
        if ($o['status'] === 'pending') {
            $pending_count++;
        }
    }
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

include __DIR__ . '/partials/header.php';
?>

<style>
    .admin-container,
    .admin-container *,
    .admin-header,
    .admin-header * {
        font-family: "Segoe UI", Arial, sans-serif !important;
        letter-spacing: 0 !important;
    }
    .admin-header h1,
    .admin-header h2,
    .admin-header h3,
    .admin-container h1,
    .admin-container h2,
    .admin-container h3,
    .admin-container [style*="font-heading"] {
        font-family: "Segoe UI", Arial, sans-serif !important;
        line-height: 1.25 !important;
        font-weight: 800 !important;
        text-transform: none !important;
    }
    .admin-header button,
    .admin-container button,
    .admin-container .btn {
        font-family: "Segoe UI", Arial, sans-serif !important;
        line-height: 1.2 !important;
        font-weight: 700 !important;
        text-transform: none !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .admin-container button, 
    .admin-container .btn,
    .admin-header button {
        transition: all 0.25s ease;
        cursor: pointer;
    }
    .admin-container button:hover, 
    .admin-container .btn:hover,
    .admin-header button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        opacity: 0.9;
    }
    .admin-container button:active, 
    .admin-container .btn:active,
    .admin-header button:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
</style>

<div class="admin-container">
    <aside class="admin-sidebar">
        <ul>
            <li><a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>admin/products">Sản phẩm</a></li>
            <li><a href="<?= BASE_URL ?>admin/categories">Danh mục</a></li>
            <li><a href="?page=orders" class="<?= $page === 'orders' ? 'active' : '' ?>">Đơn hàng</a></li>
            <li><a href="<?= BASE_URL ?>admin/inventory">Kho hàng</a></li>
            <li><a href="?page=coupons" class="<?= $page === 'coupons' ? 'active' : '' ?>">Mã giảm giá</a></li>
            <li><a href="?page=users" class="<?= $page === 'users' ? 'active' : '' ?>">Người dùng</a></li>
            <li><a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>">Cài đặt</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <?php if ($page === 'dashboard'): ?>
            <div class="admin-header" style="border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 2rem; background: transparent; box-shadow: none;">
                <div>
                    <h2 style="font-size: 2rem; letter-spacing: 0; line-height: 1.25; font-family: var(--font-ui); font-weight: 800;">Dashboard</h2>
                    <p style="color: #666; font-family: var(--font-ui); font-size: 0.95rem;">Xin chào Admin, đây là tổng quan hoạt động kinh doanh hôm nay.</p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
                <!-- Card 1: Doanh thu -->
                <div style="background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem; border: 1px solid #f0f0f0;">
                    <div style="background: #E8F5E9; color: #4CAF50; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div>
                        <div style="color: #888; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; font-family: var(--font-ui); margin-bottom: 0.2rem;">Tổng doanh thu</div>
                        <div style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-ui); color: #111;">24.500.000 ₫</div>
                        <div style="color: #4CAF50; font-size: 0.8rem; font-weight: 500; margin-top: 0.2rem;">+12% so với tháng trước</div>
                    </div>
                </div>

                <!-- Card 2: Đơn hàng -->
                <div style="background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem; border: 1px solid #f0f0f0;">
                    <div style="background: #E3F2FD; color: #2196F3; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"></path></svg>
                    </div>
                    <div>
                        <div style="color: #888; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; font-family: var(--font-ui); margin-bottom: 0.2rem;">Đơn hàng mới</div>
                        <div style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-ui); color: #111;">48</div>
                        <div style="color: #2196F3; font-size: 0.8rem; font-weight: 500; margin-top: 0.2rem;">+5% so với tháng trước</div>
                    </div>
                </div>

                <!-- Card 3: Khách hàng -->
                <div style="background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem; border: 1px solid #f0f0f0;">
                    <div style="background: #F3E5F5; color: #9C27B0; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div>
                        <div style="color: #888; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; font-family: var(--font-ui); margin-bottom: 0.2rem;">Khách hàng</div>
                        <div style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-ui); color: #111;">1,024</div>
                        <div style="color: #9C27B0; font-size: 0.8rem; font-weight: 500; margin-top: 0.2rem;">+28 thành viên mới</div>
                    </div>
                </div>

                <!-- Card 4: Sản phẩm -->
                <div style="background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 1rem; border: 1px solid #f0f0f0;">
                    <div style="background: #FFF3E0; color: #FF9800; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </div>
                    <div>
                        <div style="color: #888; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; font-family: var(--font-ui); margin-bottom: 0.2rem;">Sản phẩm</div>
                        <div style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-ui); color: #111;">156</div>
                        <div style="color: #FF9800; font-size: 0.8rem; font-weight: 500; margin-top: 0.2rem;">12 sản phẩm sắp hết</div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2.5rem;">
                <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                    <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">Biểu đồ doanh thu (7 ngày)</h3>
                    <canvas id="revenueChart" height="120"></canvas>
                </div>
                <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; display: flex; flex-direction: column;">
                    <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">Trạng thái đơn hàng</h3>
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="table-wrapper" style="border-radius: 12px; border: 1px solid #f0f0f0; padding: 2rem;">
                <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">Đơn hàng mới nhất</h3>
                <table class="table">
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
                            <td>Nguyễn Văn A</td>
                            <td>25/06/2026</td>
                            <td>3.800.000 ₫</td>
                            <td><span style="background: #E3F2FD; color: #1976D2; padding: 6px 12px; border-radius: 100px; font-size: 0.8rem; font-weight: 700; font-family: var(--font-ui);">MỚI</span></td>
                        </tr>
                        <tr>
                            <td>#ORD-002</td>
                            <td>Trần Thị B</td>
                            <td>24/06/2026</td>
                            <td>4.200.000 ₫</td>
                            <td><span style="background: #FFF3E0; color: #F57C00; padding: 6px 12px; border-radius: 100px; font-size: 0.8rem; font-weight: 700; font-family: var(--font-ui);">ĐANG GIAO</span></td>
                        </tr>
                        <tr>
                            <td>#ORD-003</td>
                            <td>Lê Văn C</td>
                            <td>23/06/2026</td>
                            <td>2.100.000 ₫</td>
                            <td><span style="background: #E8F5E9; color: #388E3C; padding: 6px 12px; border-radius: 100px; font-size: 0.8rem; font-weight: 700; font-family: var(--font-ui);">HOÀN THÀNH</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
                new Chart(ctxRevenue, {
                    type: 'bar',
                    data: {
                        labels: ['19/06', '20/06', '21/06', '22/06', '23/06', '24/06', '25/06'],
                        datasets: [{
                            label: 'Doanh thu (VNĐ)',
                            data: [3500000, 5200000, 4800000, 2100000, 7500000, 4200000, 8900000],
                            backgroundColor: '#111',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { 
                            y: { 
                                beginAtZero: true,
                                grid: { color: '#f0f0f0' },
                                border: { dash: [4, 4] }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });

                const ctxStatus = document.getElementById('statusChart').getContext('2d');
                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: ['Mới', 'Đang giao', 'Hoàn thành', 'Đã huỷ'],
                        datasets: [{
                            data: [15, 8, 22, 3],
                            backgroundColor: ['#1976D2', '#F57C00', '#388E3C', '#D32F2F'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { 
                                position: 'bottom',
                                labels: { font: { family: "'Poppins', sans-serif", size: 12 }, padding: 20 }
                            } 
                        },
                        cutout: '75%'
                    }
                });
            });
            </script>

        <?php elseif ($page === 'orders'): ?>
            <div class="admin-header">
                <h2>Quản lý đơn hàng</h2>
                <div style="display: flex; gap: 1rem;">
                    <select style="padding: 0.5rem 1rem; border-radius: 4px; border: 1px solid #ddd; font-family: var(--font-ui);">
                        <option>Tất cả trạng thái</option>
                        <option>Mới</option>
                        <option>Đang xử lý</option>
                        <option>Đang giao</option>
                        <option>Hoàn thành</option>
                        <option>Đã huỷ</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                    <div style="color: #666; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem; font-family: var(--font-ui);">Total Orders</div>
                    <div style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-ui); color: #111;"><?= $total_orders_count ?></div>
                </div>
                <div style="background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                    <div style="color: #666; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem; font-family: var(--font-ui);">Revenue</div>
                    <div style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-ui); color: #111;"><?= number_format($total_revenue, 0, ',', '.') ?> ₫</div>
                </div>
                <div style="background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                    <div style="color: #666; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem; font-family: var(--font-ui);">Pending</div>
                    <div style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-ui); color: #111;"><?= $pending_count ?></div>
                </div>
                <div style="background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                    <div style="color: #666; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem; font-family: var(--font-ui);">Customers</div>
                    <div style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-ui); color: #111;"><?= $total_customers ?></div>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table">
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
                            <td>#ORD-<?= str_pad($o['id'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($o['user_name'] ?? $o['shipping_name'] ?? 'Khách lẻ') ?></td>
                            <td><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                            <td><?= htmlspecialchars($o['payment_method'] ?? 'COD') ?></td>
                            <td><?= number_format($o['final_amount'], 0, ',', '.') ?> ₫</td>
                            <td>
                                <?php
                                $status_colors = [
                                    'pending' => ['#E3F2FD', '#1976D2', 'Mới'],
                                    'confirmed' => ['#E8EAF6', '#3F51B5', 'Đã xác nhận'],
                                    'shipping' => ['#FFF3E0', '#F57C00', 'Đang giao'],
                                    'delivered' => ['#E8F5E9', '#388E3C', 'Hoàn thành'],
                                    'canceled' => ['#FFEBEE', '#D32F2F', 'Đã huỷ']
                                ];
                                $s = $status_colors[$o['status']] ?? ['#f5f5f5', '#333', 'Khác'];
                                ?>
                                <span style="background: <?= $s[0] ?>; color: <?= $s[1] ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;"><?= $s[2] ?></span>
                            </td>
                            <td>
                                <a href="?page=order_detail&id=<?= $o['id'] ?>" style="color: #2196F3; margin-right: 10px; font-weight: bold; font-size: 0.9rem;">Chi tiết</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($admin_orders)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">Chưa có đơn hàng nào.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page === 'order_detail'): ?>
            <div class="admin-header" style="border-bottom: none; margin-bottom: 1rem;">
                <div>
                    <h2>Order Details</h2>
                    <p style="color: #666; font-family: var(--font-ui); font-size: 0.95rem;">Sales / Orders / #ORD-<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></p>
                </div>
                <a href="?page=orders" style="font-weight: 600; font-family: var(--font-ui); color: #111; padding: 8px 16px; border: 1px solid #ddd; border-radius: 6px; background: #fff;">&larr; Back</a>
            </div>

            <?php if ($admin_success): ?>
                <div style="margin-bottom: 1rem; padding: 0.9rem 1rem; background: #e7f6ec; color: #176b35; border-radius: 6px; font-family: var(--font-ui); font-weight: 600;">
                    <?= htmlspecialchars($admin_success) ?>
                </div>
            <?php endif; ?>

            <?php if ($admin_error): ?>
                <div style="margin-bottom: 1rem; padding: 0.9rem 1rem; background: #ffebee; color: #b71c1c; border-radius: 6px; font-family: var(--font-ui); font-weight: 600;">
                    <?= htmlspecialchars($admin_error) ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2.5rem;">
                <!-- Left Column -->
                <div>
                    <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                        <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.2rem;">Product Details</h3>
                        <table style="width: 100%; border-collapse: collapse; font-family: var(--font-ui); font-size: 0.9rem;">
                            <thead>
                                <tr style="color: #888; font-size: 0.8rem; text-transform: uppercase; border-bottom: 1px solid #eee;">
                                    <th style="padding-bottom: 1rem; text-align: left;">Product</th>
                                    <th style="padding-bottom: 1rem; text-align: left;">Unit Price</th>
                                    <th style="padding-bottom: 1rem; text-align: left;">Qty</th>
                                    <th style="padding-bottom: 1rem; text-align: right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr style="border-bottom: 1px solid #f5f5f5;">
                                    <td style="padding: 1rem 0; display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 50px; height: 50px; border-radius: 6px; overflow: hidden; background: #eee;">
                                            <img src="<?= BASE_URL . htmlspecialchars(adminOrderImagePath($item['product_image'] ?? '')) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div>
                                            <span style="font-weight: 600; color: #111; display: block;"><?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm') ?></span>
                                            <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                                                <span style="font-size: 0.8rem; color: #777;"><?= htmlspecialchars(trim(($item['size'] ?? '') . ' ' . ($item['color'] ?? ''))) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="color: #666;"><?= number_format($item['price_at_time'], 0, ',', '.') ?> ₫</td>
                                    <td style="color: #111; font-weight: 600;"><?= $item['quantity'] ?></td>
                                    <td style="text-align: right; font-weight: 700; color: #111;"><?= number_format($item['price_at_time'] * $item['quantity'], 0, ',', '.') ?> ₫</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1.5rem; font-family: var(--font-ui);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #666;">
                                <span>Subtotal</span>
                                <span style="font-weight: 600; color: #111;"><?= number_format($order['total_amount'], 0, ',', '.') ?> ₫</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #666;">
                                <span>Shipping</span>
                                <span style="font-weight: 600; color: #4CAF50;">FREE</span>
                            </div>
                            <?php 
                            $discount = $order['total_amount'] - $order['final_amount']; 
                            if ($discount > 0): 
                            ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: #666;">
                                <span>Discount</span>
                                <span style="font-weight: 600; color: #D32F2F;">- <?= number_format($discount, 0, ',', '.') ?> ₫</span>
                            </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between; margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;">
                                <span style="font-weight: 700; font-size: 1.2rem; color: #111;">Total</span>
                                <span style="font-weight: 800; font-size: 1.5rem; color: #111;"><?= number_format($order['final_amount'], 0, ',', '.') ?> ₫</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Transaction Info -->
                    <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; font-family: var(--font-ui);">
                        <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.1rem;">Transaction Info</h3>
                        <div style="margin-bottom: 1rem;">
                            <div style="color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.2rem;">Order Code</div>
                            <div style="color: #111; font-weight: 600;">#ORD-<?= str_pad($order['id'], 3, '0', STR_PAD_LEFT) ?></div>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <div style="color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.2rem;">Order Date</div>
                            <div style="color: #111; font-weight: 600;"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                        </div>
                        <div>
                            <div style="color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.2rem;">Payment Gateway</div>
                            <div style="color: #111; font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($order['payment_method'] ?? 'COD') ?></div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; font-family: var(--font-ui);">
                        <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.1rem;">Customer Information</h3>
                        <div style="margin-bottom: 1rem;">
                            <div style="color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.2rem;">Full Name & Email</div>
                            <div style="color: #111; font-weight: 600;"><?= htmlspecialchars($order['shipping_name'] ?? $customer['full_name'] ?? '') ?></div>
                            <div style="color: #666; font-size: 0.9rem;"><?= htmlspecialchars($order['shipping_email'] ?? $customer['email'] ?? 'N/A') ?></div>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <div style="color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.2rem;">Phone</div>
                            <div style="color: #111; font-weight: 600;"><?= htmlspecialchars($order['shipping_phone'] ?? 'N/A') ?></div>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <div style="color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.2rem;">Shipping Address</div>
                            <div style="color: #111; font-weight: 600; font-size: 0.9rem; line-height: 1.5;"><?= htmlspecialchars($order['shipping_address'] ?? 'N/A') ?></div>
                        </div>
                        <div style="background: #FFF9C4; padding: 1rem; border-radius: 6px; border-left: 4px solid #FBC02D;">
                            <div style="color: #F57F17; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; margin-bottom: 0.2rem;">Customer Note</div>
                            <div style="color: #E65100; font-size: 0.9rem; font-style: italic;">No specific note for this order.</div>
                        </div>
                    </div>

                    <!-- Order Status -->
                    <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                        <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.1rem;">Order Status</h3>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <?php
                            $status_colors = [
                                'pending' => ['#FFF3E0', '#F57C00', 'Pending'],
                                'confirmed' => ['#E8EAF6', '#3F51B5', 'Confirmed'],
                                'shipping' => ['#E3F2FD', '#1976D2', 'Shipping'],
                                'delivered' => ['#E8F5E9', '#388E3C', 'Delivered'],
                                'canceled' => ['#FFEBEE', '#D32F2F', 'Canceled']
                            ];
                            $s = $status_colors[$order['status']] ?? ['#f5f5f5', '#333', 'Unknown'];
                            ?>
                            <div style="margin-bottom: 1rem;">
                                <span style="background: <?= $s[0] ?>; color: <?= $s[1] ?>; padding: 6px 16px; border-radius: 100px; font-size: 0.85rem; font-weight: 700; font-family: var(--font-ui);"><?= $s[2] ?></span>
                            </div>
                            <select name="status" style="width: 100%; padding: 0.8rem; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui); font-weight: 600; outline: none;">
                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="shipping" <?= $order['status'] === 'shipping' ? 'selected' : '' ?>>Shipping</option>
                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="canceled" <?= $order['status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                            </select>
                            <button type="submit" style="width: 100%; padding: 0.8rem; background: #111; color: #fff; border: none; border-radius: 6px; font-weight: 600; font-family: var(--font-ui); cursor: pointer; transition: 0.3s;">Update</button>
                        </form>
                    </div>

                    <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; font-family: var(--font-ui);">
                        <h3 style="margin-bottom: 1rem; font-family: var(--font-heading); font-size: 1.1rem;">Status History</h3>
                        <?php if (empty($order_status_logs)): ?>
                            <div style="color: #888; font-size: 0.9rem;">Chưa có lịch sử cập nhật.</div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <?php foreach ($order_status_logs as $log): ?>
                                    <div style="border-left: 3px solid #111; padding-left: 0.75rem;">
                                        <div style="font-weight: 700; color: #111; text-transform: capitalize;"><?= htmlspecialchars($log['status']) ?></div>
                                        <?php if (!empty($log['note'])): ?>
                                            <div style="color: #666; font-size: 0.85rem; margin-top: 0.15rem;"><?= htmlspecialchars($log['note']) ?></div>
                                        <?php endif; ?>
                                        <div style="color: #999; font-size: 0.8rem; margin-top: 0.15rem;"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        <?php elseif ($page === 'users'): ?>
            <div class="admin-header">
                <h2>Quản lý người dùng</h2>
                <a href="<?= BASE_URL ?>admin/users/create" class="btn btn-dark">Thêm quản trị viên</a>
            </div>

            <?php if ($admin_success): ?>
                <div style="margin-bottom: 1rem; padding: 0.9rem 1rem; background: #e7f6ec; color: #176b35; border-radius: 6px; font-family: var(--font-ui); font-weight: 600;">
                    <?= htmlspecialchars($admin_success) ?>
                </div>
            <?php endif; ?>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Ngày tham gia</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admin_users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: #eee;">
                                        <img src="<?= !empty($u['avatar']) ? BASE_URL . $u['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($u['full_name']).'&background=2A9D8F&color=fff&size=40' ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <span><?= htmlspecialchars($u['full_name']) ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span style="background: <?= $u['role'] === 'admin' ? '#E0E0E0' : '#E3F2FD' ?>; color: <?= $u['role'] === 'admin' ? '#333' : '#1976D2' ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;"><?= ucfirst($u['role']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <a href="?page=user_detail&id=<?= $u['id'] ?>" style="color: #2196F3; margin-right: 15px; font-weight: bold;">Chi tiết</a>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <a href="javascript:void(0)" onclick="confirmDelete(<?= $u['id'] ?>, 'delete_user')" style="color: #F44336; font-weight: bold;">Xóa</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($admin_users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Chưa có người dùng nào.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page === 'user_detail'): ?>
            <div class="admin-header" style="border-bottom: none; margin-bottom: 1rem;">
                <div>
                    <h2>Customer Detail</h2>
                    <p style="color: #666; font-family: var(--font-ui); font-size: 0.95rem;">View customer profile, addresses, notes and order history.</p>
                </div>
                <a href="?page=users" style="font-weight: 600; font-family: var(--font-ui); color: #111;">&larr; Back to Customers</a>
            </div>

            <!-- Profile and Stats Grid -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Profile Card -->
                <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; display: flex; align-items: flex-start; gap: 2rem;">
                    <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; background: #eee; flex-shrink: 0; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <img src="<?= !empty($customer['avatar']) ? BASE_URL . $customer['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($customer['full_name']).'&background=2A9D8F&color=fff&size=100' ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div>
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #111; font-family: var(--font-heading);"><?= htmlspecialchars($customer['full_name']) ?></h3>
                        <div style="color: #666; font-family: var(--font-ui); font-size: 0.9rem; line-height: 1.6;">
                            <div>Email: <?= htmlspecialchars($customer['email']) ?></div>
                            <!--<div>Phone: 0913000010</div>-->
                            <!--<div>Gender: female</div>-->
                            <div>Date of birth: N/A</div>
                            <div>Joined: <?= date('d/m/Y', strtotime($customer['created_at'])) ?></div>
                            <div>Last login: N/A</div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <span style="background: #E8F5E9; color: #388E3C; padding: 4px 12px; border-radius: 100px; font-size: 0.8rem; font-weight: 700; font-family: var(--font-ui);">Active</span>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="background: #fafafa; padding: 1.5rem; border-radius: 12px; border: 1px solid #f0f0f0;">
                        <div style="font-size: 1.5rem; font-weight: 800; font-family: var(--font-ui); color: #111; margin-bottom: 0.2rem;"><?= count($customer_orders) ?></div>
                        <div style="color: #888; font-size: 0.85rem; font-family: var(--font-ui);">Total Orders</div>
                    </div>
                    <div style="background: #fafafa; padding: 1.5rem; border-radius: 12px; border: 1px solid #f0f0f0;">
                        <div style="font-size: 1.5rem; font-weight: 800; font-family: var(--font-ui); color: #111; margin-bottom: 0.2rem;">$<?= number_format($total_spent, 2) ?></div>
                        <div style="color: #888; font-size: 0.85rem; font-family: var(--font-ui);">Total Spent</div>
                    </div>
                </div>
            </div>

            <!-- Addresses and Notes Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                    <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.2rem;">Addresses</h3>
                    <?php if (empty($customer_addresses)): ?>
                        <p style="color: #888; font-style: italic;">No addresses found.</p>
                    <?php else: ?>
                        <?php foreach ($customer_addresses as $addr): ?>
                        <div style="margin-bottom: 1rem; color: #666; font-family: var(--font-ui); font-size: 0.9rem; line-height: 1.5;">
                            <strong style="color: #111; font-size: 1rem;"><?= htmlspecialchars($customer['full_name']) ?> <span style="background: #E8EAF6; color: #3F51B5; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">Default</span></strong><br>
                            <?= htmlspecialchars($addr['phone'] ?? 'N/A') ?><br>
                            <?= htmlspecialchars($addr['address_line1']) ?><br>
                            <?= htmlspecialchars($addr['city']) ?> <?= htmlspecialchars($addr['postal_code']) ?><br>
                            <?= htmlspecialchars($addr['country']) ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                    <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.2rem;">Customer Notes</h3>
                    <p style="color: #333; font-family: var(--font-ui); font-size: 0.95rem;">Demo customer generated for dashboard and report testing.</p>
                    <p style="color: #888; font-family: var(--font-ui); font-size: 0.85rem; margin-top: 0.5rem;">By PaceUp Admin • <?= date('d/m/Y H:i', strtotime($customer['created_at'])) ?></p>
                </div>
            </div>

            <!-- Order History Table -->
            <div style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;">
                <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.2rem;">Order History</h3>
                <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-family: var(--font-ui); font-size: 0.9rem;">
                    <thead>
                        <tr style="color: #666; border-bottom: 1px solid #eee;">
                            <th style="padding: 1rem 0;">Order Code</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customer_orders)): ?>
                        <tr><td colspan="6" style="padding: 1rem 0; color: #888;">No orders found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($customer_orders as $o): ?>
                            <tr style="border-bottom: 1px solid #fafafa;">
                                <td style="padding: 1rem 0; color: #111;">#ORD-<?= str_pad($o['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td style="color: #111;">$<?= number_format($o['total_amount'], 2) ?></td>
                                <td style="color: #666;"><?= htmlspecialchars($o['payment_method'] ?? 'COD') ?></td>
                                <td><span style="background: #f5f5f5; color: #333; padding: 4px 10px; border-radius: 100px; font-size: 0.8rem; font-weight: 600;">paid</span></td>
                                <td><span style="background: #f5f5f5; color: #333; padding: 4px 10px; border-radius: 100px; font-size: 0.8rem; font-weight: 600;"><?= htmlspecialchars($o['status']) ?></span></td>
                                <td style="color: #666;"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page === 'settings'): ?>
            <div class="admin-header">
                <h2>Cài đặt hệ thống</h2>
            </div>
            <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); max-width: 600px;">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui);">Tên website</label>
                    <input type="text" value="PaceUp" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui);">Email liên hệ</label>
                    <input type="email" value="cskh@paceup.vn" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui);">Phí vận chuyển mặc định</label>
                    <input type="text" value="30.000" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <button class="btn btn-dark">Lưu cài đặt</button>
            </div>

        <?php elseif ($page === 'categories'): ?>
            <div class="admin-header">
                <h2>Quản lý danh mục</h2>
                <button class="btn btn-dark" onclick="openModal('categoryModal')">Thêm danh mục</button>
            </div>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên danh mục</th>
                            <th>Đường dẫn (Slug)</th>
                            <th>Sản phẩm</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><strong style="font-family: var(--font-ui);">Giày Chạy Bộ</strong></td>
                            <td><span style="color: #666; font-size: 0.9rem;">giay-chay-bo</span></td>
                            <td>45</td>
                            <td><span style="background: #E8F5E9; color: #388E3C; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">Hoạt động</span></td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('categoryModal')" style="color: #2196F3; margin-right: 15px; font-weight: bold;">Sửa</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(1)" style="color: #F44336; font-weight: bold;">Xóa</a>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><strong style="font-family: var(--font-ui);">Giày Thời Trang</strong></td>
                            <td><span style="color: #666; font-size: 0.9rem;">giay-thoi-trang</span></td>
                            <td>82</td>
                            <td><span style="background: #E8F5E9; color: #388E3C; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">Hoạt động</span></td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('categoryModal')" style="color: #2196F3; margin-right: 15px; font-weight: bold;">Sửa</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(2)" style="color: #F44336; font-weight: bold;">Xóa</a>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td><strong style="font-family: var(--font-ui);">Phụ Kiện</strong></td>
                            <td><span style="color: #666; font-size: 0.9rem;">phu-kien</span></td>
                            <td>0</td>
                            <td><span style="background: #FFEBEE; color: #D32F2F; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">Ẩn</span></td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('categoryModal')" style="color: #2196F3; margin-right: 15px; font-weight: bold;">Sửa</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(3)" style="color: #F44336; font-weight: bold;">Xóa</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal Thêm/Sửa Danh Mục -->
            <div id="categoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: #fff; width: 100%; max-width: 500px; border-radius: 12px; padding: 2rem; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <button onclick="closeModal('categoryModal')" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
                    <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.5rem; text-transform: uppercase;">Thông tin danh mục</h3>
                    
                    <form action="?page=categories" method="POST">
                        <input type="hidden" name="action" value="add_category">
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Tên danh mục *</label>
                            <input type="text" name="name" required placeholder="Nhập tên danh mục" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Đường dẫn (Slug)</label>
                            <input type="text" name="slug" placeholder="Để trống để tự tạo từ tên danh mục" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                        </div>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-family: var(--font-ui); font-size: 0.9rem;">Trạng thái</label>
                            <select name="status" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                                <option value="1">Hoạt động</option>
                                <option value="0">Ẩn</option>
                            </select>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                            <button type="button" onclick="closeModal('categoryModal')" style="padding: 0.8rem 1.5rem; background: #f5f5f5; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; color: #333;">Hủy</button>
                            <button type="submit" class="btn btn-dark" style="padding: 0.8rem 1.5rem; border-radius: 6px;">Lưu danh mục</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($page === 'inventory'): ?>
            <div class="admin-header">
                <h2>Quản lý kho hàng</h2>
                <button class="btn btn-dark" onclick="openModal('inventoryModal')">Nhập kho</button>
            </div>

            <div class="table-wrapper">
                <table class="table">
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
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?= BASE_URL ?>assets/images/AIR+ZOOM+PEGASUS+42+WIDE.avif" alt="Shoe" width="40" height="40" style="object-fit: contain; background: #f5f5f5; border-radius: 4px;">
                                    <strong style="font-family: var(--font-ui);">Nike Air Zoom Pegasus 42</strong>
                                </div>
                            </td>
                            <td><span style="color: #666; font-size: 0.9rem;">NK-PEG42-BLK</span></td>
                            <td><strong style="font-size: 1.1rem;">124</strong></td>
                            <td><span style="background: #E8F5E9; color: #388E3C; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">Đủ hàng</span></td>
                            <td>20/06/2026</td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('inventoryModal')" style="color: #2196F3; font-weight: bold;">Cập nhật</a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?= BASE_URL ?>assets/images/NIKE+SB+DUNK+LOW+PRO.avif" alt="Shoe" width="40" height="40" style="object-fit: contain; background: #f5f5f5; border-radius: 4px;">
                                    <strong style="font-family: var(--font-ui);">Nike SB Dunk Low Pro</strong>
                                </div>
                            </td>
                            <td><span style="color: #666; font-size: 0.9rem;">NK-DUNK-LOW</span></td>
                            <td><strong style="font-size: 1.1rem; color: #F57C00;">5</strong></td>
                            <td><span style="background: #FFF3E0; color: #F57C00; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">Sắp hết</span></td>
                            <td>15/05/2026</td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('inventoryModal')" style="color: #2196F3; font-weight: bold;">Cập nhật</a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 40px; height: 40px; background: #eee; border-radius: 4px;"></div>
                                    <strong style="font-family: var(--font-ui);">Adidas Ultraboost Light</strong>
                                </div>
                            </td>
                            <td><span style="color: #666; font-size: 0.9rem;">AD-UB-LGT</span></td>
                            <td><strong style="font-size: 1.1rem; color: #D32F2F;">0</strong></td>
                            <td><span style="background: #FFEBEE; color: #D32F2F; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">Hết hàng</span></td>
                            <td>10/04/2026</td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('inventoryModal')" style="color: #2196F3; font-weight: bold;">Cập nhật</a>
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
            <div class="admin-header">
                <h2>Mã giảm giá</h2>
                <button class="btn btn-dark" onclick="if(typeof resetCouponForm === 'function') resetCouponForm(); openModal('couponModal')">Tạo mã mới</button>
            </div>

            <div class="table-wrapper">
                <table class="table">
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
                            $statusColor = ['#E8F5E9', '#388E3C'];

                            if (!empty($coupon['expiry_date']) && $now > $coupon['expiry_date']) {
                                $isValid = false;
                                $statusLabel = 'Đã hết hạn';
                                $statusColor = ['#FFEBEE', '#D32F2F'];
                            } elseif (!empty($coupon['usage_limit']) && $coupon['used_count'] >= $coupon['usage_limit']) {
                                $isValid = false;
                                $statusLabel = 'Hết lượt';
                                $statusColor = ['#FFEBEE', '#D32F2F'];
                            } elseif (!empty($coupon['start_date']) && $now < $coupon['start_date']) {
                                $isValid = false;
                                $statusLabel = 'Chưa đến hạn';
                                $statusColor = ['#FFF3E0', '#F57C00'];
                            }
                            
                            $discountText = $coupon['discount_percent'] ? '-'.$coupon['discount_percent'].'%' : '-'.number_format($coupon['max_discount'], 0, ',', '.').'₫';
                            $conditionText = $coupon['min_order_amount'] > 0 ? 'Đơn tối thiểu '.number_format($coupon['min_order_amount'], 0, ',', '.').'đ' : 'Không điều kiện';
                        ?>
                        <tr>
                            <td><strong style="font-family: var(--font-ui); background: #f5f5f5; padding: 4px 8px; border-radius: 4px; border: 1px dashed #ccc; letter-spacing: 1px;"><?= htmlspecialchars($coupon['code']) ?></strong></td>
                            <td><strong style="color: #D32F2F;"><?= $discountText ?></strong></td>
                            <td><?= htmlspecialchars($conditionText) ?></td>
                            <td><?= $coupon['used_count'] ?> / <?= $coupon['usage_limit'] ?: '∞' ?></td>
                            <td>
                                <?php if ($isValid): ?>
                                    <span style="color: #666;"><?= date('d/m/Y', strtotime($coupon['expiry_date'])) ?></span>
                                <?php else: ?>
                                    <span style="color: #D32F2F; font-weight: bold;"><?= date('d/m/Y', strtotime($coupon['expiry_date'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span style="background: <?= $statusColor[0] ?>; color: <?= $statusColor[1] ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;"><?= $statusLabel ?></span></td>
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
                                <a href="javascript:void(0)" onclick="editCoupon(<?= $cJson ?>)" style="color: #2196F3; margin-right: 10px; font-weight: bold;">Sửa</a>
                                <a href="javascript:void(0)" onclick="deleteCoupon(<?= $coupon['id'] ?>, '<?= htmlspecialchars($coupon['code'], ENT_QUOTES) ?>')" style="color: #F44336; font-weight: bold;">Xóa</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($admin_coupons)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 2rem;">Chưa có mã giảm giá nào.</td></tr>
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
            <div class="admin-header">
                <h2>Danh mục sản phẩm</h2>
                <button class="btn btn-dark" onclick="openModal('productModal')">Thêm mới</button>
            </div>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Phân loại</th>
                            <th>Giá</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><img src="<?= BASE_URL ?>assets/images/AIR+ZOOM+PEGASUS+42+WIDE.avif" alt="Shoe" width="60" height="60" style="object-fit: contain; background: #f5f5f5; border-radius: 4px;"></td>
                            <td>Nike Air Zoom Pegasus 42</td>
                            <td>Giày Chạy Bộ Nam</td>
                            <td>3.800.000 ₫</td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('productModal')" style="color: #2196F3; margin-right: 15px; font-weight: bold;">Sửa</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(1)" style="color: #F44336; font-weight: bold;">Xóa</a>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><img src="<?= BASE_URL ?>assets/images/NIKE+SB+DUNK+LOW+PRO.avif" alt="Shoe" width="60" height="60" style="object-fit: contain; background: #f5f5f5; border-radius: 4px;"></td>
                            <td>Nike SB Dunk Low Pro</td>
                            <td>Giày Skate Nam</td>
                            <td>4.200.000 ₫</td>
                            <td>
                                <a href="javascript:void(0)" onclick="openModal('productModal')" style="color: #2196F3; margin-right: 15px; font-weight: bold;">Sửa</a>
                                <a href="javascript:void(0)" onclick="confirmDelete(2)" style="color: #F44336; font-weight: bold;">Xóa</a>
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
    </main>
</div>

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

<?php include __DIR__ . '/partials/footer.php'; ?>
