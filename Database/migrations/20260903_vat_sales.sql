-- Bổ sung VAT bán hàng mà không xóa dữ liệu hiện có.
-- Giá bán/giá đơn hàng được hiểu là giá đã gồm thuế.

ALTER TABLE product
  ADD COLUMN IF NOT EXISTS tax_category VARCHAR(30) NOT NULL DEFAULT 'standard_reduced' AFTER unit_name,
  ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER tax_category;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS taxable_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER shipping_fee,
  ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER taxable_amount,
  ADD COLUMN IF NOT EXISTS non_taxable_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tax_amount,
  ADD COLUMN IF NOT EXISTS shipping_tax_category VARCHAR(30) NOT NULL DEFAULT 'standard_reduced' AFTER non_taxable_amount,
  ADD COLUMN IF NOT EXISTS shipping_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER shipping_tax_category,
  ADD COLUMN IF NOT EXISTS shipping_tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER shipping_tax_rate,
  ADD COLUMN IF NOT EXISTS prices_include_tax TINYINT(1) NOT NULL DEFAULT 1 AFTER shipping_tax_amount;

ALTER TABLE order_items
  ADD COLUMN IF NOT EXISTS tax_category_snapshot VARCHAR(30) NOT NULL DEFAULT 'standard_reduced' AFTER unit_name_snapshot,
  ADD COLUMN IF NOT EXISTS tax_rate_snapshot DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER tax_category_snapshot,
  ADD COLUMN IF NOT EXISTS taxable_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tax_rate_snapshot,
  ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER taxable_amount;

ALTER TABLE electronic_invoices
  ADD COLUMN IF NOT EXISTS taxable_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER buyer_address,
  ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER taxable_amount,
  ADD COLUMN IF NOT EXISTS non_taxable_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tax_amount;

ALTER TABLE electronic_invoice_items
  ADD COLUMN IF NOT EXISTS tax_category VARCHAR(30) NOT NULL DEFAULT 'standard_reduced' AFTER unit_name,
  ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER tax_category,
  ADD COLUMN IF NOT EXISTS taxable_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER tax_rate,
  ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER taxable_amount;

-- Đơn cũ được tách ngược từ giá đã gồm thuế; không thay đổi số khách đã trả.
UPDATE order_items oi
LEFT JOIN product p ON p.id=oi.product_id
SET oi.tax_category_snapshot=COALESCE(p.tax_category,'standard_reduced'),
    oi.tax_rate_snapshot=COALESCE(p.tax_rate,8),
    oi.taxable_amount=CASE WHEN COALESCE(p.tax_category,'standard_reduced')='not_subject' THEN 0
      ELSE ROUND(GREATEST(0,oi.price_at_time*oi.quantity-COALESCE(oi.discount_amount,0))/(1+COALESCE(p.tax_rate,8)/100),2) END,
    oi.tax_amount=CASE WHEN COALESCE(p.tax_category,'standard_reduced')='not_subject' THEN 0
      ELSE ROUND(GREATEST(0,oi.price_at_time*oi.quantity-COALESCE(oi.discount_amount,0))-
        ROUND(GREATEST(0,oi.price_at_time*oi.quantity-COALESCE(oi.discount_amount,0))/(1+COALESCE(p.tax_rate,8)/100),2),2) END;

UPDATE orders o
LEFT JOIN (
  SELECT order_id,SUM(taxable_amount) taxable_amount,SUM(tax_amount) tax_amount,
    SUM(CASE WHEN tax_category_snapshot='not_subject' THEN GREATEST(0,price_at_time*quantity-COALESCE(discount_amount,0)) ELSE 0 END) non_taxable_amount
  FROM order_items GROUP BY order_id
) x ON x.order_id=o.id
SET o.shipping_tax_category='standard_reduced',
    o.shipping_tax_rate=8,
    o.shipping_tax_amount=ROUND(o.shipping_fee-ROUND(o.shipping_fee/1.08,2),2),
    o.taxable_amount=COALESCE(x.taxable_amount,0)+ROUND(o.shipping_fee/1.08,2),
    o.tax_amount=COALESCE(x.tax_amount,0)+ROUND(o.shipping_fee-ROUND(o.shipping_fee/1.08,2),2),
    o.non_taxable_amount=COALESCE(x.non_taxable_amount,0),
    o.prices_include_tax=1;

UPDATE electronic_invoices i JOIN orders o ON o.id=i.order_id
SET i.taxable_amount=o.taxable_amount,i.tax_amount=o.tax_amount,i.non_taxable_amount=o.non_taxable_amount
WHERE i.invoice_type='original';

UPDATE electronic_invoice_items ii JOIN order_items oi ON oi.id=ii.order_item_id
SET ii.tax_category=oi.tax_category_snapshot,ii.tax_rate=oi.tax_rate_snapshot,
    ii.taxable_amount=oi.taxable_amount,ii.tax_amount=oi.tax_amount;

UPDATE electronic_invoice_items ii
JOIN electronic_invoices i ON i.id=ii.invoice_id
JOIN orders o ON o.id=i.order_id
SET ii.tax_category=o.shipping_tax_category,ii.tax_rate=o.shipping_tax_rate,
    ii.taxable_amount=GREATEST(0,ii.total_amount-o.shipping_tax_amount),ii.tax_amount=o.shipping_tax_amount
WHERE ii.order_item_id IS NULL AND ii.item_name='Phí giao hàng' AND i.invoice_type='original';

UPDATE electronic_invoices
SET invoice_series=CONCAT('1C',DATE_FORMAT(issued_at,'%y'),'TLI')
WHERE invoice_series REGEXP '^2C[0-9]{2}D';

-- Đồng bộ bộ đếm sau khi đổi ký hiệu để số hóa đơn mới không bị trùng.
INSERT INTO document_sequences(series,current_number)
SELECT invoice_series,MAX(invoice_number) FROM electronic_invoices GROUP BY invoice_series
ON DUPLICATE KEY UPDATE current_number=GREATEST(current_number,VALUES(current_number));

INSERT IGNORE INTO schema_migrations(version) VALUES('vat_sales_v14');
