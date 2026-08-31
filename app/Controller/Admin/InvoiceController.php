<?php
namespace App\Controller\Admin;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\ElectronicInvoice;
use App\Models\Database;

class InvoiceController {
    public function index(){AuthMiddleware::requireAdmin();$month=trim((string)($_GET['month']??date('Y-m')));$model=new ElectronicInvoice();$invoices=$model->listForAdmin($month);$report=$model->report($month);$eligibleOrders=Database::getInstance()->getConnection()->query("SELECT o.id,o.order_code,o.shipping_name,o.final_amount FROM orders o JOIN payments p ON p.order_id=o.id WHERE o.status IN('delivered','completed') AND p.payment_state='paid' AND NOT EXISTS(SELECT 1 FROM electronic_invoices i WHERE i.order_id=o.id AND i.invoice_type='original') GROUP BY o.id ORDER BY o.delivered_at DESC")->fetchAll(\PDO::FETCH_ASSOC);$flash=SessionHelper::getAllFlash();require __DIR__.'/../../Views/admin/invoices/index.php';}
    public function issue(){AuthMiddleware::requireAdmin();$r=(new ElectronicInvoice())->issue((int)($_POST['order_id']??0),$_POST,(int)$_SESSION['user_id']);$this->done($r);}
    public function adjust(){AuthMiddleware::requireAdmin();$r=(new ElectronicInvoice())->adjust((int)($_POST['invoice_id']??0),(float)($_POST['total_delta']??0),trim((string)($_POST['reason']??'')),(int)$_SESSION['user_id']);$this->done($r);}
    public function cancel(){AuthMiddleware::requireAdmin();$r=(new ElectronicInvoice())->cancel((int)($_POST['invoice_id']??0),trim((string)($_POST['reason']??'')),(int)$_SESSION['user_id']);$this->done($r);}
    private function done(array $r):void{SessionHelper::setFlash($r['success']?'success':'error',$r['message']);SessionHelper::redirect('/admin/invoices');}
}
