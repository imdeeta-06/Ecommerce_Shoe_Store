<?php
$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => number_format((float)$value, 0, ',', '.') . ' ₫';
$paymentLabels = ['cod' => 'Thanh toán khi nhận hàng (COD)', 'bank_transfer' => 'Chuyển khoản ngân hàng/VietQR', 'paypal' => 'PayPal'];
$paymentStates = ['pending' => 'Chờ thanh toán', 'paid' => 'Đã thanh toán', 'canceled' => 'Đã hủy', 'partially_refunded' => 'Đã hoàn một phần', 'refunded' => 'Đã hoàn toàn bộ'];
$grossItems = array_reduce($order['items'] ?? [], static fn($sum, $item) => $sum + (float)$item['price_at_time'] * (int)$item['quantity'], 0.0);
$discount = max(0, $grossItems + (float)$order['shipping_fee'] - (float)$order['final_amount']);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Phiếu đơn hàng <?= $e($order['order_code']) ?> - Liên Hoa</title>
    <style>
        *{box-sizing:border-box}body{font:14px/1.55 Arial,sans-serif;color:#222;margin:0;background:#f4f4f4}.sheet{max-width:850px;margin:24px auto;background:#fff;padding:34px;border:1px solid #ddd}.top{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #245b55;padding-bottom:18px}.brand{font-size:28px;color:#245b55;font-weight:700}.meta{text-align:right}h1{text-align:center;font-size:22px;margin:28px 0 4px}.subtitle{text-align:center;color:#666;margin-bottom:25px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin:20px 0}.box{border:1px solid #ddd;padding:14px}.box h2{font-size:14px;text-transform:uppercase;margin:0 0 8px;color:#245b55}table{width:100%;border-collapse:collapse;margin:20px 0}th,td{border:1px solid #ccc;padding:9px;text-align:left}th{background:#eef6f4}.number{text-align:right;white-space:nowrap}.totals{width:430px;margin-left:auto}.totals td{border:0;border-bottom:1px solid #ddd}.total td{font-weight:700;font-size:16px;border-top:2px solid #245b55}.notice{margin-top:24px;padding:14px;border:1px solid #d69e2e;background:#fffaf0}.actions{text-align:center;margin:22px}.actions button{padding:10px 22px;border:0;background:#245b55;color:#fff;cursor:pointer}@media print{body{background:#fff}.sheet{border:0;margin:0;max-width:none;padding:12mm}.actions{display:none}}@media(max-width:650px){.top,.grid{display:block}.meta{text-align:left;margin-top:12px}.totals{width:100%}}
    </style>
</head>
<body>
<div class="actions"><button type="button" onclick="window.print()">In / Lưu PDF</button></div>
<main class="sheet">
    <div class="top">
        <div><div class="brand">LIÊN HOA</div><div><?= $e($store['legal_name']) ?></div></div>
        <div class="meta"><strong>MST:</strong> <?= $e($store['tax_code']) ?><br><strong>ĐKKD:</strong> <?= $e($store['business_registration']) ?><br><?= $e($store['address']) ?><br><?= $e($store['phone']) ?> · <?= $e($store['email']) ?></div>
    </div>
    <h1>PHIẾU XÁC NHẬN ĐƠN HÀNG</h1>
    <div class="subtitle">Mã <?= $e($order['order_code']) ?> · Ngày <?= $e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></div>
    <div class="grid">
        <section class="box"><h2>Người nhận</h2><strong><?= $e($order['shipping_name']) ?></strong><br><?= $e($order['shipping_phone']) ?><br><?= nl2br($e($order['shipping_address'])) ?><?php if (!empty($order['shipping_email'])): ?><br><?= $e($order['shipping_email']) ?><?php endif; ?></section>
        <section class="box"><h2>Thanh toán và giao hàng</h2>Phương thức: <?= $e($paymentLabels[$order['payment']['payment_method'] ?? 'cod'] ?? ($order['payment']['payment_method'] ?? 'COD')) ?><br>Thanh toán: <?= $e($paymentStates[$order['payment']['payment_state'] ?? 'pending'] ?? ($order['payment']['payment_state'] ?? '')) ?><?php if (($order['payment']['payment_method'] ?? '') === 'paypal' && !empty($order['payment']['provider_amount'])): ?><br>PayPal: <?= $e(number_format((float)$order['payment']['provider_amount'], 2, '.', ',') . ' ' . ($order['payment']['provider_currency'] ?? 'USD')) ?><?php endif; ?><?php if (!empty($order['payment']['provider_capture_id'])): ?><br>Mã giao dịch: <?= $e($order['payment']['provider_capture_id']) ?><?php endif; ?><br>Trạng thái đơn: <?= $e($order['status']) ?><br>Vận chuyển: <?= $e($order['shipping_carrier'] ?: 'Chưa phân công') ?></section>
    </div>
    <table>
        <thead><tr><th>STT</th><th>Sản phẩm</th><th>Phân loại</th><th class="number">SL</th><th class="number">Đơn giá</th><th class="number">Thành tiền</th></tr></thead>
        <tbody><?php foreach (($order['items'] ?? []) as $index => $item): ?><tr><td><?= $index + 1 ?></td><td><?= $e($item['product_name_snapshot'] ?? $item['product_name']) ?></td><td><?= $e(trim(($item['size'] ?? '') . ' ' . ($item['color'] ?? ''))) ?></td><td class="number"><?= (int)$item['quantity'] ?></td><td class="number"><?= $money($item['price_at_time']) ?></td><td class="number"><?= $money((float)$item['price_at_time'] * (int)$item['quantity']) ?></td></tr><?php endforeach; ?></tbody>
    </table>
    <table class="totals">
        <tr><td>Tạm tính hàng hóa</td><td class="number"><?= $money($grossItems) ?></td></tr>
        <tr><td>Giảm giá</td><td class="number">-<?= $money($discount) ?></td></tr>
        <tr><td>Phí vận chuyển</td><td class="number"><?= $money($order['shipping_fee']) ?></td></tr>
        <tr class="total"><td>Tổng thanh toán</td><td class="number"><?= $money($order['final_amount']) ?></td></tr>
    </table>
    <div class="notice"><strong>Lưu ý:</strong> Đây là phiếu xác nhận đơn hàng của website đồ án, sử dụng thông tin phục vụ mục đích học tập. Phiếu này <strong>không thay thế hóa đơn điện tử hoặc hóa đơn hợp pháp</strong>.</div>
    <p style="margin-top:20px;color:#666">Điều khoản đã chấp nhận: <?= $e($order['contract_version'] ?? 'Không có dữ liệu') ?> lúc <?= $e($order['terms_accepted_at'] ?? 'Không có dữ liệu') ?>.</p>
</main>
</body>
</html>
