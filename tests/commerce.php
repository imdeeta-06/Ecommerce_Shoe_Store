<?php
// Creates and drops ONLY a randomly named test database. Never loads project .env.
// TEST_MYSQL_DSN='mysql:unix_socket=/path/mysql.sock' TEST_MYSQL_USER=root TEST_MYSQL_PASSWORD=... php tests/commerce.php
require __DIR__ . '/../app/Core/App.php';
App\Core\App::router();
use App\Models\{Database, Order, Report, TaxReport, Cart, Review, AfterSale, Procurement, ElectronicInvoice};
$dsn = getenv('TEST_MYSQL_DSN');
if (!$dsn || str_contains($dsn, 'dbname=')) throw new RuntimeException('Supply TEST_MYSQL_DSN without dbname; requires CREATE DATABASE permission.');
$db = new PDO($dsn, getenv('TEST_MYSQL_USER') ?: 'root', getenv('TEST_MYSQL_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = 'lienhoa_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->exec("USE `$name`");
putenv('SMTP_ENABLED=false');
putenv('APP_ENV=local');
putenv('APP_PUBLIC_URL=http://127.0.0.1:18766');
$count = 0;
function check(bool $condition, string $label): void {
    global $count;
    if (!$condition) throw new RuntimeException("FAIL: $label");
    $count++;
    echo "PASS $label\n";
}
function ok(array $result, string $label): array {
    check(!empty($result['success']), $label . ' ' . ($result['message'] ?? ''));
    return $result;
}
function equalMoney($actual, $expected, string $label): void { check(abs((float)$actual - (float)$expected) < .01, "$label ($actual = $expected)"); }
try {
    $db->exec(file_get_contents(__DIR__ . '/../Database/paceup_db.sql'));
    $db->exec("SET time_zone = '+07:00'");
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $instance = (new ReflectionClass(Database::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(Database::class, 'connection'))->setValue($instance, $db);
    (new ReflectionProperty(Database::class, 'instance'))->setValue(null, $instance);
    $orders = new Order(); $report = new Report(); $tax = new TaxReport(); $cart = new Cart();
    $variant = $db->query('SELECT * FROM product_variants WHERE status=1 AND stock_quantity>=10 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $variantId = (int)$variant['id'];
    $user = (int)$db->query("SELECT id FROM user WHERE role='user' LIMIT 1")->fetchColumn();
    $admin = (int)$db->query("SELECT id FROM user WHERE role='admin' LIMIT 1")->fetchColumn();
    $place = function(string $method = 'cod', int $quantity = 1) use ($orders, $variantId, $user) {
        return ok($orders->placeOrder([
            'user_id'=>$user, 'shipping_name'=>'Test receiver', 'shipping_phone'=>'0900000000',
            'shipping_address'=>'Test address', 'shipping_province'=>'Hồ Chí Minh',
            'shipping_carrier_code'=>'standard', 'payment_method'=>$method, 'terms_accepted'=>true
        ], [['variant_id'=>$variantId,'quantity'=>$quantity,'price'=>1]]), "place $method")['order_id'];
    };
    $advance = function(int $id) use ($orders, $admin) {
        ok($orders->updateStatus($id,'confirmed','test',$admin,false), 'confirm');
        ok($orders->updateStatus($id,'preparing','test',$admin,false), 'prepare');
        ok($orders->updateShipping($id,'Test carrier','TEST-'.$id,'packing'), 'shipping metadata');
        ok($orders->updateStatus($id,'shipping','test',$admin,false), 'ship');
    };
    $id = $place('cod',2);
    $order = $orders->getOrder($id); $total=(float)$order['final_amount'];
    check($total > 2, 'server ignores client price');
    equalMoney($report->calculateTotalRevenue(),0,'new COD revenue zero');
    check((int)$db->query("SELECT reserved_quantity FROM product_variants WHERE id=$variantId")->fetchColumn()===2,'pending reserves inventory');
    check(!$orders->updateStatus($id,'delivered','',$admin,false,true)['success'],'cannot skip shipping');
    $advance($id);
    equalMoney($report->calculateTotalRevenue(),0,'shipping revenue zero');
    check(!$orders->updateStatus($id,'delivered','',$admin,false)['success'],'COD delivery requires cash confirmation');
    check($orders->getOrder($id)['status']==='shipping','failed delivery rolls back state');
    ok($orders->updateStatus($id,'delivered','',$admin,false,true),'COD delivered and collected');
    equalMoney($report->calculateTotalRevenue(),$total,'delivered paid revenue');
    $sold=(int)$db->query('SELECT sold_count FROM product WHERE id='.(int)$variant['product_id'])->fetchColumn();
    ok($orders->updateStatus($id,'delivered','',$admin,false,true),'retry delivery');
    check((int)$db->query('SELECT sold_count FROM product WHERE id='.(int)$variant['product_id'])->fetchColumn()===$sold,'delivery retry does not duplicate sold count');
    ok($orders->updateStatus($id,'completed','',$admin,false),'complete');
    equalMoney($report->calculateTotalRevenue(),$total,'completion does not double revenue');
    equalMoney(array_sum($report->getDashboardData()['revenue_by_day']),$total,'chart equals total');
    equalMoney($tax->build('month',date('Y-m'),(int)date('Y'),1)['summary']['net_revenue'],$total,'tax equals total');

    $bank=$place('bank');
    check(!$orders->updateStatus($bank,'confirmed','',$admin,false)['success'],'unpaid bank cannot confirm');
    ok($orders->confirmBankTransfer($bank,'TEST-BANK-'.$bank,$admin),'bank collected');
    equalMoney($report->calculateTotalRevenue(),$total,'prepayment is not revenue');
    $advance($bank);
    ok($orders->updateStatus($bank,'delivered','',$admin,false),'paid bank delivery');
    $bankTotal=(float)$orders->getOrder($bank)['final_amount'];
    equalMoney($report->calculateTotalRevenue(),$total+$bankTotal,'bank revenue after delivery');

    $missing=$place(); $advance($missing);
    $db->exec("DELETE FROM payments WHERE order_id=$missing");
    check(!$orders->updateStatus($missing,'delivered','',$admin,false,true)['success'],'missing payment blocks delivery');
    $unpaid=$place('bank');
    $db->exec("UPDATE orders SET status='shipping' WHERE id=$unpaid");
    check(!$orders->updateStatus($unpaid,'delivered','',$admin,false,true)['success'],'legacy unpaid bank delivery blocked');
    $db->exec("UPDATE orders SET status='delivered',delivered_at=NOW() WHERE id=$unpaid");
    equalMoney($report->calculateTotalRevenue(),$total+$bankTotal,'legacy delivered unpaid excluded');

    $cancel=$place();
    $reservedBefore=(int)$db->query("SELECT reserved_quantity FROM product_variants WHERE id=$variantId")->fetchColumn();
    ok($orders->cancelOrder($cancel,$user),'cancel pending');
    check((int)$db->query("SELECT reserved_quantity FROM product_variants WHERE id=$variantId")->fetchColumn()===$reservedBefore-1,'cancel releases reservation');
    equalMoney($report->calculateTotalRevenue(),$total+$bankTotal,'cancel never revenue');
    $failed=$place(); $advance($failed);
    $stockBefore=(int)$db->query("SELECT stock_quantity FROM product_variants WHERE id=$variantId")->fetchColumn();
    check(!$orders->updateStatus($failed,'canceled','',$admin,false)['success'],'failed delivery needs reason');
    ok($orders->updateStatus($failed,'canceled','Customer refused',$admin,false),'failed delivery returns stock');
    check((int)$db->query("SELECT stock_quantity FROM product_variants WHERE id=$variantId")->fetchColumn()===$stockBefore+1,'shipping cancellation restores inventory');
    check(!$orders->updateStatus($failed,'delivered','',$admin,false,true)['success'],'canceled order cannot deliver');

    $itemId=(int)$order['items'][0]['id'];
    $review=new Review();
    check(!$review->createVerifiedReview($admin,$itemId,5,'test')['success'],'review ownership');
    ok($review->createDirectReview($user,(int)$variant['product_id'],5,'Test review'),'product review uses delivered purchase');
    $after=new AfterSale();
    $request=ok($after->createRequest($user,$itemId,'return','Test return',1),'request partial return');
    $requestId=(int)$request['request_id'];
    ok($after->updateStatus($requestId,'approved','test',1),'approve return');
    equalMoney($report->calculateTotalRevenue(),$total+$bankTotal,'refund pending keeps revenue');
    equalMoney($tax->build('month',date('Y-m'),(int)date('Y'),1)['summary']['net_revenue'],$total+$bankTotal,'tax keeps pending refund revenue');
    ok($after->updateStatus($requestId,'received','test',1),'receive return');
    ok($after->updateStatus($requestId,'refunded','test',1,true,'TEST-REFUND-'.$requestId),'refund partial');
    $refund=(float)$orders->getPayment($id)['refunded_amount'];
    check($refund>0,'refund amount recorded');
    equalMoney($report->calculateTotalRevenue(),$total+$bankTotal-$refund,'actual refund reduces total');
    equalMoney(array_sum($report->getDashboardData()['revenue_by_day']),$total+$bankTotal-$refund,'refund chart equals total');
    equalMoney($tax->build('month',date('Y-m'),(int)date('Y'),1)['summary']['net_revenue'],$total+$bankTotal-$refund,'refund tax equals total');

    $secondReturn=ok($after->createRequest($user,$itemId,'return','Second unit',1),'request remaining unit');
    check(!$after->createRequest($user,$itemId,'return','Too many',1)['success'],'reject excessive return requests');
    check(!$db->inTransaction(),'invalid return request releases lock');
    $secondRequestId=(int)$secondReturn['request_id'];
    ok($after->updateStatus($secondRequestId,'approved','test',1),'approve second return');
    ok($after->updateStatus($secondRequestId,'received','test',1),'receive second return');
    check(!$after->updateStatus($secondRequestId,'refunded','test',1,true,'TEST-REFUND-'.$requestId)['success'],'duplicate refund receipt must fail instead of false success');
    equalMoney($report->calculateTotalRevenue(),$total+$bankTotal-$refund,'duplicate receipt leaves revenue unchanged');

    // Duplicate payment attempts must not multiply order revenue.
    $db->exec("INSERT INTO payments(order_id,payment_method,payment_state) VALUES($bank,'bank_transfer','pending')");
    equalMoney($report->calculateTotalRevenue(),$total+$bankTotal-$refund,'duplicate payment attempt counted once');
    $db->exec("UPDATE orders SET created_at='2025-01-01', delivered_at='2025-02-28 12:00:00',completed_at='2025-03-01' WHERE id=$id");
    $db->exec("UPDATE payments SET paid_at='2025-02-28 12:00:00' WHERE order_id=$id");
    equalMoney($report->calculateTotalRevenue('2025-02-01','2025-02-28'),$total-$refund,'uses delivery month not order or completion month');
    equalMoney($tax->build('month','2025-02',2025,1)['summary']['net_revenue'],$total-$refund,'tax uses same delivery month');
    $db->exec("UPDATE payments SET paid_at='2025-03-02' WHERE order_id=$id");
    equalMoney($report->calculateTotalRevenue('2025-02-01','2025-02-28'),0,'late payment not backdated before collection');
    equalMoney($report->calculateTotalRevenue('2025-03-01','2025-03-31'),$total-$refund,'late collection recognition date');
    $db->exec("UPDATE payments SET refunded_amount=$total,payment_state='refunded',payment_status=2 WHERE order_id=$id");
    equalMoney($report->calculateTotalRevenue(),$bankTotal,'full refund leaves zero for refunded order');



    $coupons=new \App\Models\Coupons();
    $couponId=(int)$coupons->createCoupon(['code'=>'TEST10','discount_percent'=>10,'usage_limit'=>1,'usage_limit_per_user'=>1,'expiry_date'=>date('Y-m-d H:i:s',time()+86400)]);
    $couponData=['user_id'=>$user,'coupon_id'=>$couponId,'terms_accepted'=>true,'shipping_name'=>'Test','shipping_phone'=>'0900000000','shipping_address'=>'Test','shipping_province'=>'Hồ Chí Minh','payment_method'=>'cod'];
    $couponOrder=ok($orders->placeOrder($couponData,[['variant_id'=>$variantId,'quantity'=>1]]),'coupon order')['order_id'];
    check((int)$coupons->getCoupon($couponId)['used_count']===1,'coupon reserved');
    check(!$orders->placeOrder($couponData,[['variant_id'=>$variantId,'quantity'=>1]])['success'],'coupon cannot exceed usage limit');
    ok($orders->cancelOrder($couponOrder,$user),'cancel coupon order');
    check((int)$coupons->getCoupon($couponId)['used_count']===0,'coupon usage restored');
    $couponRetry=ok($orders->placeOrder($couponData,[['variant_id'=>$variantId,'quantity'=>1]]),'reuse canceled coupon')['order_id'];
    ok($orders->cancelOrder($couponRetry,$user),'cancel reused coupon');

    $paypal=$place('paypal');
    $baseline=$report->calculateTotalRevenue();
    ok($orders->attachPayPalOrder($paypal,'TESTPAYPAL123456','USD',20,27000),'attach simulated PayPal order');
    check(!$orders->completePayPalPayment($paypal,'WRONGORDER123456','TESTCAPTURE12345','USD',20)['success'],'reject wrong PayPal order');
    check(!$orders->completePayPalPayment($paypal,'TESTPAYPAL123456','TESTCAPTURE12345','USD',19)['success'],'reject wrong PayPal amount');
    ok($orders->completePayPalPayment($paypal,'TESTPAYPAL123456','TESTCAPTURE12345','USD',20),'simulated PayPal capture');
    ok($orders->completePayPalPayment($paypal,'TESTPAYPAL123456','TESTCAPTURE12345','USD',20),'capture idempotency');
    equalMoney($report->calculateTotalRevenue(),$baseline,'PayPal paid pending no revenue');
    $advance($paypal);
    equalMoney($report->calculateTotalRevenue(),$baseline,'PayPal shipping no revenue');
    ok($orders->updateStatus($paypal,'delivered','',$admin,false),'PayPal delivered');
    equalMoney($report->calculateTotalRevenue(),$baseline+(float)$orders->getOrder($paypal)['final_amount'],'PayPal revenue only at delivery');

    $invoiceOrder=$place(); $advance($invoiceOrder);
    $invoices=new ElectronicInvoice();
    check(!$invoices->issue($invoiceOrder,[],$admin)['success'],'invoice blocked before delivery');
    ok($orders->updateStatus($invoiceOrder,'delivered','',$admin,false,true),'invoice order delivery');
    $invoice=ok($invoices->issue($invoiceOrder,[],$admin),'issue invoice');
    check(!$invoices->issue($invoiceOrder,[],$admin)['success'],'invoice not issued twice');
    ok($invoices->adjust((int)$invoice['id'],-1000,'Test correction',$admin),'invoice adjustment');
    ok($invoices->cancel((int)$invoice['id'],'Test cancellation',$admin),'invoice cancel');
    check((int)$db->query('SELECT COUNT(*) FROM electronic_invoices WHERE order_id='.$invoiceOrder." AND status<>'canceled'")->fetchColumn()===0,'cancel includes adjustment');

    $exchangeItem=(int)$orders->getOrder($invoiceOrder)['items'][0]['id'];
    $exchange=ok($after->createRequest($user,$exchangeItem,'exchange','Wrong size',1),'request exchange');
    $exchangeId=(int)$exchange['request_id'];
    ok($after->updateStatus($exchangeId,'approved','test',1),'approve exchange');
    check(!$after->updateStatus($exchangeId,'completed','test')['success'],'exchange cannot complete before replacement');
    ok($after->updateStatus($exchangeId,'received','test',1),'receive exchange');
    $beforeExchangeRevenue=$report->calculateTotalRevenue();
    $db->exec("UPDATE product_variants SET status=0 WHERE id=$variantId");
    check(!$after->updateStatus($exchangeId,'replacement_shipped','test',1,true,'',$variantId,1,'Test','EXCHANGE')['success'],'hidden replacement variant blocked');
    $db->exec("UPDATE product_variants SET status=1 WHERE id=$variantId");
    ok($after->updateStatus($exchangeId,'replacement_shipped','test',1,true,'',$variantId,1,'Test','EXCHANGE'),'ship valid replacement');
    ok($after->updateStatus($exchangeId,'completed','test'),'complete exchange');
    equalMoney($report->calculateTotalRevenue(),$beforeExchangeRevenue,'exchange does not duplicate or refund revenue');

    $expired=$place();
    $db->exec("UPDATE orders SET reservation_expires_at=DATE_SUB(NOW(),INTERVAL 1 HOUR) WHERE id=$expired");
    $orders->expirePendingOrders(100);
    check($orders->getOrder($expired)['status']==='canceled','expiry releases unpaid order');
    $paidPending=$place('bank');
    ok($orders->confirmBankTransfer($paidPending,'TEST-PAID-EXPIRY',$admin),'collect pending bank');
    $db->exec("UPDATE orders SET reservation_expires_at=DATE_SUB(NOW(),INTERVAL 1 HOUR) WHERE id=$paidPending");
    $orders->expirePendingOrders(100);
    check($orders->getOrder($paidPending)['status']==='pending','paid pending order never expires');

    $procurement=new Procurement();
    ok($procurement->createSupplier(['supplier_code'=>'TEST-SUPPLIER','name'=>'Test supplier']),'create supplier');
    $supplier=(int)$db->query("SELECT id FROM suppliers WHERE supplier_code='TEST-SUPPLIER'")->fetchColumn();
    ok($procurement->createOrder(['supplier_id'=>$supplier,'variant_id'=>[$variantId],'quantity'=>[3],'unit_cost'=>[100000],'tax_rate'=>[8]],$admin),'create purchase order');
    $po=(int)$db->query('SELECT MAX(id) FROM purchase_orders')->fetchColumn();
    $poItem=(int)$db->query("SELECT id FROM purchase_order_items WHERE purchase_order_id=$po")->fetchColumn();
    $stock=(int)$db->query("SELECT stock_quantity FROM product_variants WHERE id=$variantId")->fetchColumn();
    ok($procurement->receive($po,$admin,[$poItem=>1]),'receive partial purchase');
    check((int)$db->query("SELECT stock_quantity FROM product_variants WHERE id=$variantId")->fetchColumn()===$stock+1,'partial receipt inventory');
    $payable=(int)$db->query("SELECT id FROM supplier_payables WHERE purchase_order_id=$po")->fetchColumn();
    equalMoney($db->query("SELECT amount_due FROM supplier_payables WHERE id=$payable")->fetchColumn(),108000,'payable only received quantity');
    check(!$procurement->receive($po,$admin,[$poItem=>3])['success'],'reject over receipt');
    ok($procurement->pay($payable,50000,'TEST-PAY-1',$admin),'supplier partial payment');
    check(!$procurement->pay($payable,100000,'TEST-OVERPAY',$admin)['success'],'reject payable overpayment');
    ok($procurement->receive($po,$admin,[$poItem=>2]),'finish receipt');
    check(!$procurement->receive($po,$admin)['success'],'receipt cannot repeat');
    ok($procurement->pay($payable,274000,'TEST-PAY-2',$admin),'supplier final payment');
    check($db->query("SELECT status FROM supplier_payables WHERE id=$payable")->fetchColumn()==='paid','supplier payable closed');

    $users=new \App\Models\UserModel();
    $addressData=['recipient_name'=>'Test','recipient_phone'=>'0900000000','address_line'=>'Test','ward_district_city'=>'HCM','is_default'=>1];
    $addr1=$users->addAddress($user,$addressData); $addr2=$users->addAddress($user,$addressData);
    check((int)$db->query("SELECT COUNT(*) FROM user_addresses WHERE user_id=$user AND is_default=1")->fetchColumn()===1,'single default address');
    check(!$users->deleteAddress($addr1,$admin),'cannot delete another user address');
    check($users->deleteAddress($addr2,$user),'delete default address');
    check((int)$db->query("SELECT is_default FROM user_addresses WHERE id=$addr1")->fetchColumn()===1,'default promoted after deletion');
    $newsletter=new \App\Models\Newsletter();
    ok($newsletter->subscribe('test@example.invalid','127.0.0.1','test-v1'),'newsletter subscription');
    $subscription=$db->query("SELECT * FROM newsletter_subscriptions WHERE email='test@example.invalid'")->fetch(PDO::FETCH_ASSOC);
    check($subscription['status']==='pending','newsletter requires confirmation');
    check($newsletter->confirm($subscription['confirmation_token']),'newsletter confirms token');
    check(!$newsletter->confirm($subscription['confirmation_token']),'newsletter token single use');
    check($newsletter->unsubscribe($subscription['unsubscribe_token']),'newsletter unsubscribe');
    $support=new \App\Models\SupportTicket();
    $ticket=$support->createTicket(['user_id'=>$user,'name'=>'Test','email'=>'test@example.invalid','subject'=>'Test','message'=>'Test']);
    check($support->updateStatus($ticket,'resolved'),'resolve support ticket');
    check(!$support->updateStatus($ticket,'invalid'),'reject invalid ticket status');
    $product=new \App\Models\Product();
    check(!$product->getProductWithImages(999999),'unknown product does not create invalid variant');
    check((bool)$product->getProductWithImages((int)$variant['product_id']),'product detail valid');

    $cartId=(int)$cart->createCartItem(['user_id'=>$user,'variant_id'=>$variantId,'quantity'=>1]);
    check(!$cart->updateCartQuantity($cartId,1,$admin),'cart ownership enforced');
    $db->exec("UPDATE product_variants SET reserved_quantity=stock_quantity WHERE id=$variantId");
    check(!$cart->updateCartQuantity($cartId,2,$user),'cannot add reserved inventory to cart');
    check(!$orders->placeOrder(['user_id'=>$user,'shipping_province'=>'Hồ Chí Minh'],[['variant_id'=>$variantId,'quantity'=>1]])['success'],'checkout rejects exhausted available stock');
    echo "SUCCESS: $count checks\n";
} finally {
    if ($db->inTransaction()) $db->rollBack();
    $db->exec("DROP DATABASE `$name`");
}
