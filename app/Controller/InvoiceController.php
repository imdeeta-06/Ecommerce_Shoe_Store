<?php
namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Models\ElectronicInvoice;

class InvoiceController {
    public function view(){AuthMiddleware::requireLogin();$invoice=(new ElectronicInvoice())->getForViewer((int)($_GET['id']??0),(int)$_SESSION['user_id'],($_SESSION['user_role']??'')==='admin');if(!$invoice){http_response_code(404);echo 'Không tìm thấy hóa đơn.';return;}$store=require __DIR__.'/../../config/store.php';require __DIR__.'/../Views/electronic-invoice.php';}
}
