<?php

namespace App\Models;

use PDO;
use Throwable;

class Procurement extends BaseModel {
    public function dashboard(): array {
        $orderItems=$this->db->query("SELECT poi.*,p.name product_name,pv.sku,pv.size,pv.color
            FROM purchase_order_items poi JOIN product_variants pv ON pv.id=poi.variant_id JOIN product p ON p.id=pv.product_id
            ORDER BY poi.purchase_order_id DESC,poi.id")->fetchAll(PDO::FETCH_ASSOC);
        $itemsByOrder=[];
        foreach($orderItems as $item){$itemsByOrder[(int)$item['purchase_order_id']][]=$item;}
        return [
            'suppliers'=>$this->db->query('SELECT * FROM suppliers ORDER BY status DESC,name')->fetchAll(PDO::FETCH_ASSOC),
            'orders'=>$this->db->query("SELECT po.*,s.name supplier_name,COALESCE(sp.amount_paid,0) amount_paid,sp.status payable_status,sp.due_date
                FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id LEFT JOIN supplier_payables sp ON sp.purchase_order_id=po.id
                ORDER BY po.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC),
            'variants'=>$this->db->query("SELECT pv.id,pv.sku,pv.size,pv.color,pv.cost_price,p.name product_name
                FROM product_variants pv JOIN product p ON p.id=pv.product_id WHERE pv.status=1 ORDER BY p.name,pv.size,pv.color LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC),
            'payables'=>$this->db->query("SELECT sp.*,s.name supplier_name,po.po_code FROM supplier_payables sp JOIN suppliers s ON s.id=sp.supplier_id JOIN purchase_orders po ON po.id=sp.purchase_order_id ORDER BY sp.status,sp.due_date")->fetchAll(PDO::FETCH_ASSOC),
            'items_by_order'=>$itemsByOrder,
            'supplier_payments'=>$this->db->query("SELECT py.*,sp.purchase_order_id,po.po_code,s.name supplier_name
                FROM supplier_payments py JOIN supplier_payables sp ON sp.id=py.payable_id
                JOIN purchase_orders po ON po.id=sp.purchase_order_id JOIN suppliers s ON s.id=sp.supplier_id
                ORDER BY py.paid_at DESC,py.id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC),
            'gross_profit'=>$this->grossProfit()
        ];
    }

    public function createSupplier(array $data): array {
        $code=strtoupper(trim((string)($data['supplier_code']??'')));
        $name=trim((string)($data['name']??''));
        if ($code==='' || $name==='') return ['success'=>false,'message'=>'Mã và tên nhà cung cấp là bắt buộc.'];
        try {
            $stmt=$this->db->prepare('INSERT INTO suppliers(supplier_code,name,tax_code,contact_name,phone,email,address,payment_terms_days,status) VALUES(?,?,?,?,?,?,?,?,1)');
            $stmt->execute([$code,$name,trim((string)($data['tax_code']??''))?:null,trim((string)($data['contact_name']??''))?:null,
                trim((string)($data['phone']??''))?:null,trim((string)($data['email']??''))?:null,trim((string)($data['address']??''))?:null,max(0,(int)($data['payment_terms_days']??0))]);
            return ['success'=>true,'message'=>'Đã thêm nhà cung cấp.'];
        } catch (Throwable $e) { return ['success'=>false,'message'=>'Không thể thêm nhà cung cấp: '.$e->getMessage()]; }
    }

    public function createOrder(array $data,int $adminId): array {
        $supplierId=(int)($data['supplier_id']??0);
        $variantIds=is_array($data['variant_id']??null)?$data['variant_id']:[$data['variant_id']??0];
        $quantities=is_array($data['quantity']??null)?$data['quantity']:[$data['quantity']??0];
        $unitCosts=is_array($data['unit_cost']??null)?$data['unit_cost']:[$data['unit_cost']??0];
        $taxRates=is_array($data['tax_rate']??null)?$data['tax_rate']:[$data['tax_rate']??0];
        $lines=[];$seen=[];
        foreach($variantIds as $index=>$variantValue){
            $variantId=(int)$variantValue;$qty=(int)($quantities[$index]??0);$unitCost=(float)($unitCosts[$index]??0);$taxRate=(float)($taxRates[$index]??0);
            if($variantId<=0&&$qty<=0&&$unitCost<=0)continue;
            if($variantId<=0||$qty<=0||$unitCost<=0||$taxRate<0||$taxRate>100)return ['success'=>false,'message'=>'Mỗi dòng hàng phải có biến thể, số lượng, giá vốn và VAT hợp lệ.'];
            if(isset($seen[$variantId]))return ['success'=>false,'message'=>'Một biến thể chỉ được xuất hiện một lần trong đơn nhập.'];
            $seen[$variantId]=true;$lines[]=['variant_id'=>$variantId,'quantity'=>$qty,'unit_cost'=>$unitCost,'tax_rate'=>$taxRate];
        }
        if($supplierId<=0||!$lines)return ['success'=>false,'message'=>'Nhà cung cấp và ít nhất một dòng hàng là bắt buộc.'];
        try {
            $this->db->beginTransaction();
            $poCode='PO-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,6));
            $subtotal=0.0;$tax=0.0;
            foreach($lines as $line){$lineSubtotal=$line['quantity']*$line['unit_cost'];$subtotal+=$lineSubtotal;$tax+=round($lineSubtotal*$line['tax_rate']/100,2);}
            $total=$subtotal+$tax;
            $stmt=$this->db->prepare("INSERT INTO purchase_orders(po_code,supplier_id,status,ordered_at,expected_at,note,subtotal,tax_amount,total_amount,created_by)
                VALUES(?,?,'ordered',NOW(),?,?,?,?,?,?)");
            $stmt->execute([$poCode,$supplierId,trim((string)($data['expected_at']??''))?:null,trim((string)($data['note']??''))?:null,$subtotal,$tax,$total,$adminId]);
            $poId=(int)$this->db->lastInsertId();
            $insert=$this->db->prepare('INSERT INTO purchase_order_items(purchase_order_id,variant_id,quantity_ordered,unit_cost,tax_rate) VALUES(?,?,?,?,?)');
            foreach($lines as $line){$insert->execute([$poId,$line['variant_id'],$line['quantity'],$line['unit_cost'],$line['tax_rate']]);}
            $this->db->commit();
            return ['success'=>true,'message'=>'Đã tạo đơn nhập '.$poCode.'.'];
        } catch(Throwable $e){ if($this->db->inTransaction())$this->db->rollBack(); return ['success'=>false,'message'=>$e->getMessage()]; }
    }

    public function receive(int $poId,int $adminId,array $requestedQuantities=[]): array {
        try {
            $this->db->beginTransaction();
            $stmt=$this->db->prepare("SELECT po.*,s.payment_terms_days FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id WHERE po.id=? FOR UPDATE");
            $stmt->execute([$poId]); $po=$stmt->fetch(PDO::FETCH_ASSOC);
            if(!$po||!in_array($po['status'],['ordered','partially_received'],true))throw new \RuntimeException('Đơn nhập không ở trạng thái có thể nhận hàng.');
            $items=$this->db->prepare('SELECT poi.*,pv.stock_quantity,pv.cost_price FROM purchase_order_items poi JOIN product_variants pv ON pv.id=poi.variant_id WHERE poi.purchase_order_id=? FOR UPDATE');
            $items->execute([$poId]); $rows=$items->fetchAll(PDO::FETCH_ASSOC);
            if(!$rows)throw new \RuntimeException('Đơn nhập không có dòng hàng.');
            $hasTrigger=(int)$this->db->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME='trg_after_insert_inventory_log'")->fetchColumn()>0;
            $receivedAny=false;
            foreach($rows as $row){
                $remaining=max(0,(int)$row['quantity_ordered']-(int)$row['quantity_received']);
                $receive=$requestedQuantities?max(0,(int)($requestedQuantities[(int)$row['id']]??0)):$remaining;
                if($receive>$remaining)throw new \RuntimeException('Số lượng nhận vượt quá số còn lại của một dòng hàng.');
                if($receive===0)continue;$receivedAny=true;
                $oldQty=(int)$row['stock_quantity']; $newQty=$oldQty+$receive;
                // Hộ kinh doanh tính thuế trực tiếp không được khấu trừ thuế đầu vào.
                // Vì vậy giá vốn thực tế phải gồm cả phần thuế của hàng mua vào.
                $landedUnitCost=round((float)$row['unit_cost']*(1+(float)$row['tax_rate']/100),2);
                $weighted=$newQty>0?round(($oldQty*(float)$row['cost_price']+$receive*$landedUnitCost)/$newQty,2):$landedUnitCost;
                $this->db->prepare('INSERT INTO inventory_logs(variant_id,quantity_changed,reason) VALUES(?,?,?)')->execute([(int)$row['variant_id'],$receive,'Nhận hàng đơn nhập '.$po['po_code']]);
                if($hasTrigger)$this->db->prepare('UPDATE product_variants SET cost_price=? WHERE id=?')->execute([$weighted,(int)$row['variant_id']]);
                else $this->db->prepare('UPDATE product_variants SET stock_quantity=?,cost_price=? WHERE id=?')->execute([$newQty,$weighted,(int)$row['variant_id']]);
                $this->db->prepare('UPDATE purchase_order_items SET quantity_received=quantity_received+? WHERE id=?')->execute([$receive,(int)$row['id']]);
            }
            if(!$receivedAny)throw new \RuntimeException('Vui lòng nhập số lượng thực nhận cho ít nhất một dòng hàng.');
            $remainingStmt=$this->db->prepare('SELECT COALESCE(SUM(quantity_ordered-quantity_received),0) FROM purchase_order_items WHERE purchase_order_id=?');
            $remainingStmt->execute([$poId]);$hasRemaining=(int)$remainingStmt->fetchColumn()>0;
            $newStatus=$hasRemaining?'partially_received':'received';
            $this->db->prepare("UPDATE purchase_orders SET status=?,received_at=IF(?='received',NOW(),received_at) WHERE id=?")
                ->execute([$newStatus,$newStatus,$poId]);
            $dueStmt=$this->db->prepare('SELECT COALESCE(SUM(quantity_received*unit_cost*(1+tax_rate/100)),0) FROM purchase_order_items WHERE purchase_order_id=?');
            $dueStmt->execute([$poId]);$receivedValue=round((float)$dueStmt->fetchColumn(),2);
            $dueDate=date('Y-m-d',strtotime('+'.(int)$po['payment_terms_days'].' days'));
            $this->db->prepare("INSERT INTO supplier_payables(supplier_id,purchase_order_id,amount_due,due_date,status) VALUES(?,?,?,?,'unpaid')
                ON DUPLICATE KEY UPDATE amount_due=VALUES(amount_due),due_date=COALESCE(due_date,VALUES(due_date)),
                    status=CASE WHEN amount_paid>=VALUES(amount_due)-0.01 THEN 'paid' WHEN amount_paid>0 THEN 'partial' ELSE 'unpaid' END")
                ->execute([(int)$po['supplier_id'],$poId,$receivedValue,$dueDate]);
            $this->db->commit(); return ['success'=>true,'message'=>'Đã nhận một phần hàng, tăng kho, cập nhật giá vốn và công nợ theo số thực nhận.'];
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();return ['success'=>false,'message'=>$e->getMessage()];}
    }

    public function pay(int $payableId,float $amount,string $reference,?int $adminId=null): array {
        if($amount<=0||trim($reference)==='')return ['success'=>false,'message'=>'Số tiền và mã tham chiếu thanh toán là bắt buộc.'];
        try{
            $this->db->beginTransaction();
            $stmt=$this->db->prepare('SELECT * FROM supplier_payables WHERE id=? FOR UPDATE');$stmt->execute([$payableId]);$p=$stmt->fetch(PDO::FETCH_ASSOC);
            if(!$p||in_array($p['status'],['paid','void'],true))throw new \RuntimeException('Công nợ không còn cho phép thanh toán.');
            $remaining=(float)$p['amount_due']-(float)$p['amount_paid'];
            if($amount>$remaining+0.01)throw new \RuntimeException('Số tiền thanh toán vượt công nợ còn lại.');
            $paid=$amount;$new=(float)$p['amount_paid']+$paid;$status=$new>=(float)$p['amount_due']-0.01?'paid':'partial';
            $this->db->prepare('INSERT INTO supplier_payments(payable_id,amount,reference,created_by) VALUES(?,?,?,?)')
                ->execute([$payableId,$paid,trim($reference),$adminId]);
            $this->db->prepare("UPDATE supplier_payables SET amount_paid=?,status=?,last_payment_reference=?,paid_at=IF(?='paid',NOW(),paid_at) WHERE id=?")
                ->execute([$new,$status,trim($reference),$status,$payableId]);
            $this->db->commit();return ['success'=>true,'message'=>'Đã ghi nhận thanh toán công nợ '.number_format($paid,0,',','.').' ₫.'];
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();return ['success'=>false,'message'=>$e->getMessage()];}
    }

    public function grossProfit(): array {
        $stmt=$this->db->query("SELECT
            COALESCE(SUM(GREATEST(0, oi.price_at_time*oi.quantity-COALESCE(oi.discount_amount,0)-COALESCE(r.refund_amount,0))),0) net_sales,
            COALESCE(SUM(oi.unit_cost_snapshot*GREATEST(0,oi.quantity-COALESCE(r.reversed_quantity,0))),0) cogs
            FROM order_items oi JOIN orders o ON o.id=oi.order_id
            LEFT JOIN (
                SELECT order_item_id, SUM(refund_amount) refund_amount, SUM(sales_reversed_quantity) reversed_quantity
                FROM after_sale_requests WHERE status IN('refunded','completed') GROUP BY order_item_id
            ) r ON r.order_item_id=oi.id
            WHERE o.status IN('delivered','completed')");
        $row=$stmt->fetch(PDO::FETCH_ASSOC)?:['net_sales'=>0,'cogs'=>0];
        $row['gross_profit']=(float)$row['net_sales']-(float)$row['cogs'];
        $row['gross_margin_percent']=(float)$row['net_sales']>0?$row['gross_profit']*100/(float)$row['net_sales']:0;
        return $row;
    }
}
