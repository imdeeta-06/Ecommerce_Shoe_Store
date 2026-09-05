<?php
require_once __DIR__ . '/../_helpers.php';
$summary = array_merge([
    'order_count' => 0, 'gross_revenue' => 0, 'refunded_amount' => 0,
    'net_revenue' => 0, 'taxable_amount' => 0, 'non_taxable_amount' => 0, 'tax_amount' => 0,
], $report['summary'] ?? []);
adminStart('Báo cáo doanh thu & Thuế', 'tax-report');
?>
<style nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
.tax-report-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap}.tax-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:1rem;margin:1.25rem 0}.tax-card{background:#fff;border:1px solid var(--admin-border);border-radius:12px;padding:1.2rem}.tax-card small{display:block;color:var(--admin-text-light);font-weight:700;letter-spacing:.04em;margin-bottom:.5rem}.tax-card strong{font-size:1.45rem;color:#111}.tax-card.highlight{background:#fff7ed;border-color:#fed7aa}.tax-card.highlight strong{color:#c2410c}.tax-note{padding:1rem 1.2rem;border:1px solid #f0cf91;background:#fff9eb;border-radius:10px;line-height:1.65}@media print{.admin-sidebar,.admin-sidebar-backdrop,.admin-topbar,.tax-report-actions{display:none!important}body,.admin-main,.admin-content{display:block!important;background:#fff!important;padding:0!important;margin:0!important}.admin-panel,.tax-card,.admin-table-wrapper{box-shadow:none!important;break-inside:avoid}.admin-title{margin-bottom:1rem}.admin-title h1:after{content:' (mô phỏng)';font-size:.7em}}
</style>

<?php if (!empty($reportError)): ?>
<div class="admin-alert error" style="margin-bottom:1rem;"><strong>Chưa tải được báo cáo:</strong> <?= adminE($reportError) ?></div>
<?php endif; ?>

<div class="tax-report-head">
    <div><h2><?= adminE($report['label']) ?></h2><p style="color:var(--admin-text-light);margin-top:.35rem;">Tổng hợp đơn đã giao/hoàn thành và đã thanh toán; hoàn tiền được khấu trừ theo tỷ lệ.</p></div>
    <div class="admin-actions tax-report-actions"><button class="admin-btn primary" type="button" onclick="window.print()">In / Lưu PDF báo cáo</button></div>
</div>

<section class="admin-panel tax-report-actions" style="margin-top:1.25rem;">
    <form method="get" class="admin-grid" style="align-items:end;">
        <div class="admin-field" style="margin:0"><label>Kiểu kỳ báo cáo</label><select name="period" id="taxPeriod"><option value="month" <?= $report['period']==='month'?'selected':'' ?>>Theo tháng</option><option value="quarter" <?= $report['period']==='quarter'?'selected':'' ?>>Theo quý</option></select></div>
        <div class="admin-field" style="margin:0"><label>Tháng</label><input type="month" name="month" value="<?= adminE($report['month']) ?>"></div>
        <div class="admin-field" style="margin:0"><label>Năm / Quý</label><div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem"><input type="number" name="year" min="2020" max="2100" value="<?= (int)$report['year'] ?>"><select name="quarter"><?php for($q=1;$q<=4;$q++):?><option value="<?= $q ?>" <?= (int)$report['quarter']===$q?'selected':'' ?>>Quý <?= $q ?></option><?php endfor;?></select></div></div>
        <div><button class="admin-btn primary" type="submit">Xem báo cáo</button></div>
    </form>
</section>

<div class="tax-cards">
    <div class="tax-card"><small>ĐƠN THÀNH CÔNG</small><strong><?= (int)$summary['order_count'] ?></strong></div>
    <div class="tax-card"><small>DOANH THU TRƯỚC HOÀN</small><strong><?= adminMoney($summary['gross_revenue']) ?></strong></div>
    <div class="tax-card"><small>HOÀN TIỀN</small><strong><?= adminMoney($summary['refunded_amount']) ?></strong></div>
    <div class="tax-card"><small>DOANH THU SAU HOÀN</small><strong><?= adminMoney($summary['net_revenue']) ?></strong></div>
    <div class="tax-card"><small>TIỀN TRƯỚC THUẾ</small><strong><?= adminMoney($summary['taxable_amount']) ?></strong></div>
    <div class="tax-card"><small>DOANH THU KHÔNG CHỊU THUẾ</small><strong><?= adminMoney($summary['non_taxable_amount']) ?></strong></div>
    <div class="tax-card highlight"><small>VAT DỰ KIẾN PHẢI KÊ KHAI</small><strong><?= adminMoney($summary['tax_amount']) ?></strong></div>
</div>

<div class="tax-note"><strong>Diễn giải nghiệp vụ:</strong> Giá bán là giá đã gồm VAT. Hệ thống tách tiền trước thuế và tiền VAT theo mức thuế đã chụp tại thời điểm đặt hàng. Báo cáo này chứng minh hệ thống có ghi nhận nghĩa vụ thuế trong đồ án; không phải biên lai đã nộp tiền vào ngân sách nhà nước.</div>

<section class="admin-panel" style="margin-top:1.5rem;">
    <h2 class="admin-panel-title">Chi tiết theo thuế suất</h2>
    <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Thuế suất VAT</th><th>Tiền trước thuế</th><th>Tiền thuế VAT</th><th>Tổng sau thuế</th></tr></thead><tbody>
    <?php foreach($report['breakdown'] as $row):?><tr><td><strong><?= adminE($row['tax_label']) ?></strong></td><td><?= adminMoney($row['taxable_amount']) ?></td><td><?= adminMoney($row['tax_amount']) ?></td><td><?= adminMoney($row['gross_amount']) ?></td></tr><?php endforeach;?>
    <?php if(empty($report['breakdown'])):?><tr><td colspan="4" style="text-align:center;color:#777;padding:2rem">Chưa có đơn đủ điều kiện trong kỳ.</td></tr><?php endif;?>
    </tbody></table></div>
</section>

<section class="admin-panel">
    <h2 class="admin-panel-title">Đối chiếu theo tháng</h2>
    <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Tháng</th><th>Đơn thành công</th><th>Doanh thu sau hoàn</th><th>VAT dự kiến</th></tr></thead><tbody>
    <?php foreach($report['trend'] as $row):?><tr><td><?= adminE(date('m/Y',strtotime($row['period_key'].'-01'))) ?></td><td><?= (int)$row['order_count'] ?></td><td><?= adminMoney($row['net_revenue']) ?></td><td><strong><?= adminMoney($row['tax_amount']) ?></strong></td></tr><?php endforeach;?>
    <?php if(empty($report['trend'])):?><tr><td colspan="4" style="text-align:center;color:#777;padding:2rem">Chưa có dữ liệu.</td></tr><?php endif;?>
    </tbody></table></div>
</section>
<?php adminEnd(); ?>
