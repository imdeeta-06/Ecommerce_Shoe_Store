<?php
$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => number_format((float)$value, 0, ',', '.') . ' đ';
$quantity = static fn($value) => number_format((float)$value, 0, '', '');
$series = (string)$invoice['invoice_series'];
$formNumber = substr($series, 0, 1);
$invoiceSymbol = substr($series, 1);
$invoiceTitle = $invoice['invoice_type'] === 'adjustment'
    ? 'HÓA ĐƠN BÁN HÀNG ĐIỀU CHỈNH'
    : 'HÓA ĐƠN BÁN HÀNG';

$pm = $invoice['payment_method'] ?? '';
if ($pm === 'cod') $pmText = 'Tiền mặt khi nhận hàng (COD)';
elseif ($pm === 'bank_transfer') $pmText = 'Chuyển khoản ngân hàng';
elseif ($pm === 'paypal') $pmText = 'Thanh toán qua PayPal';
else $pmText = 'Tiền mặt / Chuyển khoản';

$readNumber = function($amount) {
    if ($amount <= 0) return 'Không đồng';
    $words = ["không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín"];
    $units = ["", "nghìn", "triệu", "tỷ", "nghìn tỷ"];
    $amountStr = (string)(int)$amount;
    $len = strlen($amountStr);
    $result = "";
    $unitIdx = 0;
    while ($len > 0) {
        $part = (int)substr($amountStr, max(0, $len - 3), min($len, 3));
        if ($part > 0) {
            $h = (int)($part / 100);
            $t = (int)(($part % 100) / 10);
            $u = $part % 10;
            $s = "";
            if ($h > 0 || ($unitIdx > 0 && $amount > pow(10, $unitIdx*3))) {
                $s .= $words[$h] . " trăm ";
                if ($t == 0 && $u > 0) $s .= "lẻ ";
            }
            if ($t > 1) {
                $s .= $words[$t] . " mươi ";
                if ($u == 1) $s .= "mốt ";
                elseif ($u == 4) $s .= "tư ";
                elseif ($u == 5) $s .= "lăm ";
                elseif ($u > 0) $s .= $words[$u] . " ";
            } elseif ($t == 1) {
                $s .= "mười ";
                if ($u == 5) $s .= "lăm ";
                elseif ($u > 0) $s .= $words[$u] . " ";
            } elseif ($t == 0 && $u > 0) {
                $s .= $words[$u] . " ";
            }
            $result = $s . $units[$unitIdx] . " " . $result;
        }
        $len -= 3;
        $unitIdx++;
    }
    return ucfirst(trim(preg_replace('/\s+/', ' ', $result))) . ' đồng.';
};
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $e($invoiceTitle . ' ' . $series . '-' . $invoice['invoice_number']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f4f6; font: 14px/1.55 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #222; }
        .actions { text-align: center; padding: 20px; }
        .actions button { border: 0; background: #245b55; color: #fff; padding: 10px 24px; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 15px; }
        .sheet { max-width: 980px; margin: 0 auto 30px; background: #fff; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 40px 50px; }
        .seller { display: flex; justify-content: space-between; gap: 30px; border-bottom: 2px solid #245b55; padding-bottom: 20px; }
        .brand { font-size: 28px; font-weight: 800; color: #245b55; letter-spacing: 1px; }
        .seller-meta { text-align: right; line-height: 1.6; font-size: 13.5px; }
        h1 { text-align: center; margin: 35px 0 5px; font-size: 28px; letter-spacing: 1px; }
        .invoice-meta { text-align: center; color: #555; margin-bottom: 5px; font-size: 13px; }
        .invoice-date { text-align: center; color: #333; margin-bottom: 30px; font-style: italic; font-size: 13px; }
        .buyer { padding: 15px 20px; background: #fafafa; border-radius: 6px; border: 1px solid #eee; line-height: 1.8; margin-bottom: 25px; }
        
        table { width: 100%; border-collapse: collapse; margin: 25px 0; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 12px 10px; }
        th { background: #eef6f4; font-weight: 700; color: #111; }
        
        /* Alignments */
        th:nth-child(1), td:nth-child(1) { text-align: center; width: 50px; } /* STT */
        th:nth-child(2), td:nth-child(2) { text-align: left; } /* Hàng hóa */
        th:nth-child(3), td:nth-child(3) { text-align: center; width: 80px; } /* ĐVT */
        th:nth-child(4), td:nth-child(4) { text-align: center; width: 70px; } /* SL */
        th:nth-child(5), td:nth-child(5) { text-align: right; width: 130px; } /* Đơn giá */
        th:nth-child(6), td:nth-child(6) { text-align: right; width: 110px; } /* Giảm giá */
        th:nth-child(7), td:nth-child(7) { text-align: right; width: 140px; font-weight: 500; } /* Thành tiền */
        
        .total { display: flex; justify-content: flex-end; align-items: center; gap: 40px; border-top: 2px solid #245b55; padding: 20px 10px; font-size: 18px; color: #111; }
        .total strong { font-size: 24px; color: #245b55; }
        .total-words { text-align: right; margin-top: -5px; margin-bottom: 20px; font-size: 14px; color: #444; padding-right: 10px; }
        
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; padding: 0 40px; text-align: center; line-height: 1.6; }
        .signatures .sig-box { width: 40%; }
        .signatures span { font-size: 13px; font-style: italic; color: #666; }
        .stamp { margin-top: 30px; font-weight: bold; color: #245b55; border: 2px dashed #245b55; display: inline-block; padding: 8px 15px; transform: rotate(-3deg); opacity: 0.9; }

        .state { margin-top: 10px; font-size: 14px; }
        .warn { background: #fff8e6; border: 1px solid #f9df9f; padding: 15px; margin-top: 30px; color: #8a6400; font-size: 13px; border-radius: 4px; }
        
        @media (max-width: 700px) {
            .sheet { padding: 20px; }
            .seller { display: block; }
            .seller-meta { text-align: left; margin-top: 15px; }
            table { font-size: 13px; }
            th, td { padding: 8px 5px; }
            .total { justify-content: space-between; gap: 20px; font-size: 16px; }
            .total strong { font-size: 20px; }
            .signatures { padding: 0; flex-direction: column; gap: 40px; }
            .signatures .sig-box { width: 100%; }
        }
        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .sheet { border: 0; margin: 0; max-width: none; padding: 0; box-shadow: none; }
            .warn { border: 1px solid #ddd; background: transparent; color: #333; }
        }
    </style>
</head>
<body>
<div class="actions"><button type="button" onclick="window.print()">In / Lưu PDF</button></div>
<main class="sheet">
    <header class="seller">
        <div><div class="brand">LIÊN HOA</div><strong><?= $e($store['legal_name']) ?></strong></div>
        <div class="seller-meta">
            <strong>MST:</strong> <?= $e($store['tax_code']) ?><br>
            <strong>Địa chỉ:</strong> <?= $e($store['address']) ?><br>
            <strong>Hotline:</strong> <?= $e($store['phone']) ?> &nbsp;·&nbsp; <strong>Email:</strong> <?= $e($store['email']) ?>
        </div>
    </header>

    <h1><?= $e($invoiceTitle) ?></h1>
    <div class="invoice-meta">
        Mẫu số <?= $e($formNumber) ?> · Ký hiệu <?= $e($invoiceSymbol) ?> ·
        Số <?= $e(str_pad((string)$invoice['invoice_number'], 7, '0', STR_PAD_LEFT)) ?>
    </div>
    <div class="invoice-date">
        Ngày <?= date('d', strtotime($invoice['issued_at'])) ?> 
        tháng <?= date('m', strtotime($invoice['issued_at'])) ?> 
        năm <?= date('Y', strtotime($invoice['issued_at'])) ?>
    </div>

    <section class="buyer">
        <strong>Người mua:</strong> <?= $e($invoice['buyer_name']) ?><br>
        <?php if (!empty($invoice['buyer_tax_code'])): ?>
            <strong>MST:</strong> <?= $e($invoice['buyer_tax_code']) ?><br>
        <?php endif; ?>
        <strong>Địa chỉ:</strong> <?= $e($invoice['buyer_address']) ?><br>
        <strong>Đơn hàng:</strong> <?= $e($invoice['order_code']) ?><br>
        <strong>Hình thức thanh toán:</strong> <?= $pmText ?>
    </section>

    <table>
        <thead><tr><th>STT</th><th>Hàng hóa/Dịch vụ</th><th>ĐVT</th><th>SL</th><th>Đơn giá</th><th>Giảm giá</th><th>Thành tiền</th></tr></thead>
        <tbody>
        <?php foreach (($invoice['items'] ?? []) as $index => $item): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= $e($item['item_name'] . (!empty($item['variant_description']) ? ' — ' . $item['variant_description'] : '')) ?></td>
                <td><?= $e($item['unit_name']) ?></td>
                <td><?= $e($quantity($item['quantity'])) ?></td>
                <td><?= $money($item['unit_price']) ?></td>
                <td><?= $money($item['discount_amount']) ?></td>
                <td><?= $money($item['total_amount']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total"><span>Tổng cộng tiền thanh toán</span><strong><?= $money($invoice['total_amount']) ?></strong></div>
    <div class="total-words">
        Số tiền viết bằng chữ: <em><?= $e($readNumber($invoice['total_amount'])) ?></em>
    </div>
    
    <p class="state">Trạng thái: <strong style="color: <?= $invoice['status'] === 'canceled' ? '#dc2626' : '#16a34a' ?>;"><?= $e($invoice['status'] === 'canceled' ? 'Đã hủy' : 'Đã phát hành') ?></strong><?php if (!empty($invoice['adjustment_reason'])): ?><br>Lý do: <?= $e($invoice['adjustment_reason']) ?><?php endif; ?></p>
    
    <div class="signatures">
        <div class="sig-box">
            <strong>Người mua hàng</strong><br>
            <span>(Ký, ghi rõ họ tên)</span>
        </div>
        <div class="sig-box">
            <strong>Người bán hàng</strong><br>
            <span>(Ký, ghi rõ họ tên)</span><br>
        </div>
    </div>
    
    <div class="warn"><strong>LƯU Ý:</strong> Dữ liệu trong đồ án không có chữ ký số, mã cơ quan thuế và không thay thế hóa đơn điện tử/GTGT hợp pháp.</div>
</main>
</body>
</html>
