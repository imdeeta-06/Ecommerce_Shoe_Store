<?php
$e = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => number_format((float)$value, 0, ',', '.') . ' đ';
$quantity = static fn($value) => number_format((float)$value, 0, '', '');
$series = (string)$invoice['invoice_series'];
$formNumber = substr($series, 0, 1);
$invoiceSymbol = substr($series, 1);
$invoiceTitle = $invoice['invoice_type'] === 'adjustment'
    ? 'HÓA ĐƠN GIÁ TRỊ GIA TĂNG ĐIỀU CHỈNH'
    : 'HÓA ĐƠN GIÁ TRỊ GIA TĂNG';

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
    <style nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
        @page { size: A4 landscape; margin: 10mm; }
        :root { --ink:#17211f; --muted:#65716e; --brand:#215f57; --line:#cbd8d5; --soft:#f3f8f7; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #edf1f0; color: var(--ink); font: 13px/1.45 Arial, 'Helvetica Neue', sans-serif; }
        .actions { display:flex; justify-content:center; gap:10px; padding:18px; }
        .actions button { border:0; border-radius:7px; background:var(--brand); color:#fff; padding:11px 24px; cursor:pointer; font-size:14px; font-weight:700; box-shadow:0 4px 12px rgba(33,95,87,.2); }
        .sheet { width:1120px; max-width:calc(100% - 32px); margin:0 auto 28px; padding:30px 34px 24px; background:#fff; border:1px solid #dfe7e5; box-shadow:0 10px 35px rgba(27,55,50,.09); }
        .seller { display:grid; grid-template-columns:minmax(250px,.8fr) minmax(520px,1.7fr); gap:30px; align-items:center; padding-bottom:16px; border-bottom:2px solid var(--brand); }
        .brand { color:var(--brand); font-size:27px; line-height:1; font-weight:900; letter-spacing:1.5px; }
        .legal-name { margin-top:7px; font-size:12px; font-weight:700; }
        .seller-meta { display:grid; grid-template-columns:1fr; gap:3px; text-align:right; color:#34413e; font-size:12px; }
        .seller-meta strong { color:var(--ink); }
        .document-heading { text-align:center; padding:20px 0 15px; }
        h1 { margin:0; font-size:23px; line-height:1.2; letter-spacing:.8px; }
        .invoice-meta { display:flex; justify-content:center; gap:16px; flex-wrap:wrap; margin-top:9px; color:#485653; font-size:11.5px; }
        .invoice-meta span { padding:3px 9px; border:1px solid #d8e2e0; border-radius:999px; background:#fbfdfd; }
        .invoice-date { margin-top:8px; color:var(--muted); font-size:11.5px; font-style:italic; }
        .buyer { display:grid; grid-template-columns:1.25fr .75fr; gap:8px 30px; margin-bottom:16px; padding:14px 17px; border:1px solid #dce5e3; border-radius:8px; background:#fafcfb; }
        .buyer-row { display:grid; grid-template-columns:105px 1fr; gap:8px; min-width:0; }
        .buyer-row.full { grid-column:1/-1; }
        .buyer-label { color:var(--muted); font-size:11.5px; font-weight:700; }
        .buyer-value { min-width:0; font-weight:600; overflow-wrap:anywhere; }
        .table-wrap { width:100%; overflow:hidden; border:1px solid var(--line); border-radius:7px; }
        .invoice-table { width:100%; margin:0; border-collapse:collapse; table-layout:fixed; font-size:11.5px; }
        .invoice-table th,.invoice-table td { padding:8px 7px; border-right:1px solid var(--line); border-bottom:1px solid var(--line); vertical-align:middle; }
        .invoice-table th:last-child,.invoice-table td:last-child { border-right:0; }
        .invoice-table tbody tr:last-child td { border-bottom:0; }
        .invoice-table th { background:var(--soft); color:#263431; font-size:10.5px; line-height:1.25; font-weight:800; text-align:center; }
        .invoice-table tbody tr:nth-child(even) { background:#fcfdfd; }
        .invoice-table .center { text-align:center; }
        .invoice-table .money { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .product-name { font-weight:700; line-height:1.35; overflow-wrap:anywhere; }
        .variant { display:block; margin-top:3px; color:var(--muted); font-size:10.5px; font-weight:400; }
        .settlement { display:grid; grid-template-columns:1fr 440px; gap:28px; align-items:start; margin-top:16px; }
        .document-state { padding:12px 14px; border-left:3px solid var(--brand); background:#f7faf9; color:#45514f; }
        .status-badge { display:inline-block; margin-left:5px; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:800; }
        .status-badge.issued { background:#dcfce7; color:#166534; }
        .status-badge.canceled { background:#fee2e2; color:#991b1b; }
        .summary { border:1px solid var(--line); border-radius:8px; overflow:hidden; }
        .summary-row { display:grid; grid-template-columns:1fr 155px; border-bottom:1px solid #dbe4e2; }
        .summary-row:last-child { border-bottom:0; }
        .summary-row span,.summary-row strong { padding:9px 12px; }
        .summary-row span { text-align:right; color:#485653; }
        .summary-row strong { border-left:1px solid #dbe4e2; text-align:right; white-space:nowrap; font-size:13px; font-variant-numeric:tabular-nums; }
        .summary-row.grand { background:var(--brand); color:#fff; }
        .summary-row.grand span,.summary-row.grand strong { color:#fff; font-size:14px; font-weight:800; }
        .summary-row.grand strong { border-left-color:rgba(255,255,255,.25); }
        .total-words { margin-top:11px; padding:10px 12px; border:1px dashed #b8c8c4; border-radius:7px; background:#fbfdfc; font-size:11.5px; }
        .total-words strong { color:var(--brand); }
        .signatures { display:grid; grid-template-columns:1fr 1fr; gap:140px; margin-top:24px; padding:0 95px; text-align:center; min-height:86px; }
        .sig-box strong { font-size:12.5px; }
        .sig-box span { color:var(--muted); font-size:10.5px; font-style:italic; }
        .warn { margin-top:15px; padding:9px 12px; border:1px solid #efd28e; border-radius:6px; background:#fff9e9; color:#795b16; font-size:10.5px; }
        @media (max-width:800px) { .sheet{min-width:1040px;margin:12px 16px;max-width:none}.actions{justify-content:flex-start}.buyer{grid-template-columns:1fr}.buyer-row.full{grid-column:auto}.settlement{grid-template-columns:1fr 420px} }
        @media print {
            body { background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .actions { display:none!important; }
            .sheet { width:auto; max-width:none; margin:0; padding:0; border:0; box-shadow:none; }
            .seller { grid-template-columns:.8fr 1.7fr; }
            .invoice-table { font-size:10px; }
            .invoice-table th { font-size:9.5px; }
            .invoice-table th,.invoice-table td { padding:6px 5px; }
            .buyer { padding:10px 13px; margin-bottom:12px; }
            .document-heading { padding:14px 0 11px; }
            .settlement { margin-top:12px; }
            .signatures { margin-top:18px; min-height:70px; }
            tr,.summary,.signatures { break-inside:avoid; page-break-inside:avoid; }
        }
    </style>
</head>
<body>
<div class="actions"><button type="button" onclick="window.print()">In / Lưu PDF</button></div>
<main class="sheet">
    <header class="seller">
        <div><div class="brand">LIÊN HOA</div><div class="legal-name"><?= $e($store['legal_name']) ?></div></div>
        <div class="seller-meta">
            <div><strong>Mã số thuế:</strong> <?= $e($store['tax_code']) ?></div>
            <div><strong>Địa chỉ:</strong> <?= $e($store['address']) ?></div>
            <div><strong>Hotline:</strong> <?= $e($store['phone']) ?> &nbsp;•&nbsp; <strong>Email:</strong> <?= $e($store['email']) ?></div>
        </div>
    </header>

    <div class="document-heading">
        <h1><?= $e($invoiceTitle) ?></h1>
        <div class="invoice-meta">
            <span>Mẫu số: <strong><?= $e($formNumber) ?></strong></span>
            <span>Ký hiệu: <strong><?= $e($invoiceSymbol) ?></strong></span>
            <span>Số: <strong><?= $e(str_pad((string)$invoice['invoice_number'], 7, '0', STR_PAD_LEFT)) ?></strong></span>
        </div>
        <div class="invoice-date">Ngày <?= date('d', strtotime($invoice['issued_at'])) ?> tháng <?= date('m', strtotime($invoice['issued_at'])) ?> năm <?= date('Y', strtotime($invoice['issued_at'])) ?></div>
    </div>

    <section class="buyer">
        <div class="buyer-row"><span class="buyer-label">Người mua hàng</span><span class="buyer-value"><?= $e($invoice['buyer_name']) ?></span></div>
        <div class="buyer-row"><span class="buyer-label">Mã đơn hàng</span><span class="buyer-value"><?= $e($invoice['order_code']) ?></span></div>
        <div class="buyer-row full"><span class="buyer-label">Địa chỉ</span><span class="buyer-value"><?= $e($invoice['buyer_address']) ?></span></div>
        <div class="buyer-row"><span class="buyer-label">Mã số thuế</span><span class="buyer-value"><?= $e($invoice['buyer_tax_code'] ?: 'Không cung cấp') ?></span></div>
        <div class="buyer-row"><span class="buyer-label">Thanh toán</span><span class="buyer-value"><?= $e($pmText) ?></span></div>
    </section>

    <div class="table-wrap"><table class="invoice-table">
        <colgroup><col style="width:4%"><col style="width:24%"><col style="width:5%"><col style="width:5%"><col style="width:11%"><col style="width:9%"><col style="width:11%"><col style="width:7%"><col style="width:10%"><col style="width:14%"></colgroup>
        <thead><tr><th>STT</th><th>Hàng hóa / Dịch vụ</th><th>ĐVT</th><th>SL</th><th>Đơn giá<br>đã thuế</th><th>Giảm giá</th><th>Tiền trước<br>thuế</th><th>Thuế<br>suất</th><th>Tiền thuế</th><th>Thành tiền</th></tr></thead>
        <tbody>
        <?php foreach (($invoice['items'] ?? []) as $index => $item): ?>
            <tr>
                <td class="center"><?= $index + 1 ?></td>
                <td><div class="product-name"><?= $e($item['item_name']) ?></div><?php if (!empty($item['variant_description'])): ?><span class="variant"><?= $e($item['variant_description']) ?></span><?php endif; ?></td>
                <td class="center"><?= $e($item['unit_name']) ?></td>
                <td class="center"><?= $e($quantity($item['quantity'])) ?></td>
                <td class="money"><?= $money($item['unit_price']) ?></td>
                <td class="money"><?= $money($item['discount_amount']) ?></td>
                <td class="money"><?= $money($item['taxable_amount'] ?? 0) ?></td>
                <td class="money"><?php
                    $taxCategory = $item['tax_category'] ?? '';
                    echo $taxCategory === 'not_subject'
                        ? 'KCT'
                        : ($taxCategory === 'mixed_adjustment'
                            ? 'Hỗn hợp'
                            : $e(number_format((float)($item['tax_rate'] ?? 0), 2, ',', '.') . '%'));
                ?></td>
                <td class="money"><?= $money($item['tax_amount'] ?? 0) ?></td>
                <td class="money"><strong><?= $money($item['total_amount']) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>

    <section class="settlement">
        <div>
            <div class="document-state">Trạng thái chứng từ:<span class="status-badge <?= $invoice['status'] === 'canceled' ? 'canceled' : 'issued' ?>"><?= $e($invoice['status'] === 'canceled' ? 'ĐÃ HỦY' : 'ĐÃ PHÁT HÀNH') ?></span><?php if (!empty($invoice['adjustment_reason'])): ?><div style="margin-top:7px"><strong>Lý do điều chỉnh:</strong> <?= $e($invoice['adjustment_reason']) ?></div><?php endif; ?></div>
            <div class="total-words"><strong>Số tiền viết bằng chữ:</strong><br><?= $e($readNumber($invoice['total_amount'])) ?></div>
        </div>
        <div class="summary">
            <div class="summary-row"><span>Cộng tiền trước thuế</span><strong><?= $money($invoice['taxable_amount'] ?? 0) ?></strong></div>
            <?php if ((float)($invoice['non_taxable_amount'] ?? 0) > 0): ?><div class="summary-row"><span>Tiền không chịu thuế</span><strong><?= $money($invoice['non_taxable_amount']) ?></strong></div><?php endif; ?>
            <div class="summary-row"><span>Tổng tiền thuế VAT</span><strong><?= $money($invoice['tax_amount'] ?? 0) ?></strong></div>
            <div class="summary-row grand"><span>TỔNG TIỀN KHÁCH PHẢI TRẢ</span><strong><?= $money($invoice['total_amount']) ?></strong></div>
        </div>
    </section>
    
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
    
    <div class="warn"><strong>LƯU Ý ĐỒ ÁN:</strong> Chứng từ mô phỏng này thể hiện việc hệ thống ghi nhận doanh thu và nghĩa vụ VAT; không có chữ ký số, mã cơ quan thuế và không thay thế hóa đơn điện tử/GTGT hợp pháp.</div>
</main>
</body>
</html>
