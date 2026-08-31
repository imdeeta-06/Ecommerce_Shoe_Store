<?php
namespace App\Controller\Admin;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\Procurement;

class ProcurementController {
    public function index(){AuthMiddleware::requireAdmin();$data=(new Procurement())->dashboard();extract($data);$flash=SessionHelper::getAllFlash();require __DIR__.'/../../Views/admin/procurement/index.php';}
    public function supplier(){AuthMiddleware::requireAdmin();$this->done((new Procurement())->createSupplier($_POST));}
    public function order(){AuthMiddleware::requireAdmin();$this->done((new Procurement())->createOrder($_POST,(int)$_SESSION['user_id']));}
    public function receive(){AuthMiddleware::requireAdmin();$this->done((new Procurement())->receive((int)($_POST['id']??0),(int)$_SESSION['user_id'],is_array($_POST['receive_qty']??null)?$_POST['receive_qty']:[]));}
    public function pay(){AuthMiddleware::requireAdmin();$this->done((new Procurement())->pay((int)($_POST['id']??0),(float)($_POST['amount']??0),trim((string)($_POST['reference']??'')),(int)$_SESSION['user_id']));}
    private function done(array $r):void{SessionHelper::setFlash($r['success']?'success':'error',$r['message']);SessionHelper::redirect('/admin/procurement');}
}
