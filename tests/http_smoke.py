"""Run against a disposable local database/server with SMTP disabled.
TEST_BASE_URL=http://127.0.0.1:18766 python3 tests/http_smoke.py
Creates a test customer and one COD order. Never run against production.
"""
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request
import uuid

BASE = os.environ.get('TEST_BASE_URL', 'http://127.0.0.1:18766').rstrip('/')
if urllib.parse.urlparse(BASE).hostname not in ('127.0.0.1', 'localhost', '::1'):
    raise SystemExit('Only a disposable localhost server is allowed.')
checks = 0

def check(condition, label):
    global checks
    if not condition:
        raise AssertionError(label)
    checks += 1
    print('PASS', label)

class Client:
    def __init__(self):
        self.http = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.token = ''

    def request(self, path, data=None, json_body=False, csrf=True):
        headers = {}
        payload = None
        if data is not None:
            headers['X-Requested-With'] = 'XMLHttpRequest'
            if csrf:
                headers['X-CSRF-Token'] = self.token
            if json_body:
                headers['Content-Type'] = 'application/json'
                payload = json.dumps(data).encode()
            else:
                payload = urllib.parse.urlencode(data).encode()
        try:
            result = self.http.open(urllib.request.Request(BASE + path, data=payload, headers=headers))
        except urllib.error.HTTPError as error:
            result = error
        body = result.read().decode()
        match = re.search(r'name="csrf-token" content="([^"]+)', body) or re.search(r'name="csrf_token" value="([^"]+)', body)
        if match:
            self.token = match.group(1)
        check(not re.search(r'(Fatal error|<b>Warning</b>|<b>Deprecated</b>|500 Server Error)', body), 'clean response ' + path)
        return result.code, body, result.url

    def login(self, email, password):
        self.request('/login')
        return self.request('/login', {'email': email, 'password': password})

c = Client()
public = ['/', '/shop', '/shop?category=not-found', '/about', '/careers', '/franchise', '/faqs', '/privacy', '/terms', '/tracking', '/feedback', '/support', '/contact', '/wishlist', '/login', '/register', '/forgot-password']
for path in public:
    check(c.request(path)[0] == 200, 'public route ' + path)
check(c.request('/product?id=999999')[0] == 404, 'missing product 404')
check(c.request('/tests/commerce.php')[0] == 404, 'test scripts blocked from web')
check(c.request('/admin')[2].endswith('/login'), 'guest cannot enter admin')
check(c.request('/cart/add', {'variant_id': 1}, csrf=False)[0] == 419, 'CSRF rejected')
check(c.request('/cart/add')[0] == 405, 'GET cannot mutate cart')
shop = c.request('/shop')[1]
product_id = int(re.search(r'/product\?id=(\d+)', shop).group(1))
product = c.request('/product?id=' + str(product_id))[1]
variant = int(re.search(r'data-variant-id="(\d+)"', product).group(1))
check(json.loads(c.request('/cart/add', {'variant_id': variant, 'qty': 1})[1])['success'], 'guest adds selected variant')
email = 'http-test-' + uuid.uuid4().hex[:10] + '@example.invalid'
password = 'TestCustomer@12345'
c.request('/register')
c.request('/register', {'full_name': 'HTTP Test', 'email': email, 'password': password, 'confirm_password': password, 'phone': '0900000000'})
check(c.login(email, password)[2].rstrip('/') == BASE, 'register and login')
check(c.request('/admin/products')[2].rstrip('/') == BASE, 'customer cannot enter admin')
# Registration may merge or rotate the guest session; add explicitly if needed.
items = json.loads(c.request('/cart/get')[1])['items']
if not items:
    check(json.loads(c.request('/cart/add', {'variant_id': variant, 'qty': 1})[1])['success'], 'customer adds variant')
for path in ['/account', '/checkout', '/wishlist', '/cart']:
    check(c.request(path)[0] == 200, 'customer route ' + path)
check(json.loads(c.request('/wishlist/add', {'product_id': product_id})[1])['success'], 'wishlist add')
check(json.loads(c.request('/wishlist/remove', {'product_id': product_id})[1])['success'], 'wishlist remove')
check(not json.loads(c.request('/product/review', {'product_id': product_id, 'rating': 5, 'comment': 'test'})[1])['success'], 'unbought product review rejected as JSON')
order_data = {'shipping_name': 'HTTP Test', 'shipping_phone': '0900000000', 'shipping_address': 'Test address', 'shipping_province': 'Hồ Chí Minh', 'shipping_carrier_code': 'standard', 'payment_method': 'cod', 'terms_accepted': False}
check(c.request('/checkout/place-order', order_data, True)[0] == 400, 'checkout requires agreement')
order_data['terms_accepted'] = True
placed = json.loads(c.request('/checkout/place-order', order_data, True)[1])
check(placed['success'], 'HTTP COD checkout')
order_id = placed['order_id']
check(not json.loads(c.request('/cart/get')[1])['items'], 'checkout clears cart')
check(c.request('/order/receipt?order_id=' + str(order_id))[0] == 200, 'customer receipt')
other = Client()
other.login('customer@lienhoa.local', 'Customer@12345')
check(other.request('/order/receipt?order_id=' + str(order_id))[0] == 404, 'receipt ownership')
a = Client()
check(a.login('admin@lienhoa.local', 'Admin@12345')[2].endswith('/admin'), 'admin login')
for path in ['/admin', '/admin/products', '/admin/products/create', '/admin/products/edit?id=' + str(product_id), '/admin/categories', '/admin/inventory', '/admin/coupons', '/admin/coupons/create', '/admin/orders', '/admin/after-sales', '/admin/marketing', '/admin/invoices', '/admin/tax-report', '/admin/procurement', '/admin/support', '/admin/users/create', '/admin/users', '/admin/settings']:
    check(a.request(path)[0] == 200, 'admin route ' + path)
view = '/admin/orders/view?id=' + str(order_id)
for state in ['confirmed', 'preparing']:
    a.request('/admin/orders/status', {'order_id': order_id, 'status': state})
a.request('/admin/orders/shipping', {'order_id': order_id, 'shipping_carrier': 'Test carrier', 'tracking_code': 'HTTP-TEST'})
a.request('/admin/orders/status', {'order_id': order_id, 'status': 'shipping'})
check('name="cod_collected"' in a.request(view)[1], 'cash confirmation shown')
failed_delivery = a.request('/admin/orders/status', {'order_id': order_id, 'status': 'delivered'})[1]
check('Vui lòng xác nhận người nhận' in failed_delivery, 'HTTP delivery without cash rejected')
a.request('/admin/orders/status', {'order_id': order_id, 'status': 'delivered', 'cod_collected': '1'})
check('name="cod_collected"' not in a.request(view)[1], 'cash confirmation consumed after delivery')
review = json.loads(c.request('/product/review', {'product_id': product_id, 'rating': 5, 'comment': 'Test delivered purchase'})[1])
check(review['success'], 'HTTP delivered product review succeeds')
check(not json.loads(c.request('/product/review', {'product_id': product_id, 'rating': 5, 'comment': 'duplicate'})[1])['success'], 'duplicate review rejected')
check(a.request('/admin/inventory/variants/create', {'product_id': 999999, 'size': 'M', 'color': 'Test'})[0] == 200, 'invalid inventory variant returns validation instead of 500')
check(a.request('/admin?page=users')[2].endswith('/admin/users'), 'legacy users URL redirected')
check(a.request('/admin?page=settings')[2].endswith('/admin/settings'), 'legacy settings URL redirected')
users_page = a.request('/admin/users?keyword=' + urllib.parse.quote(email))[1]
customer_id = int(re.search(r'name="user_id" value="(\d+)"', users_page).group(1))
a.request('/admin/users/status', {'user_id': customer_id, 'status': '0'})
check(c.request('/account')[2].endswith('/login'), 'locked customer session revoked immediately')
a.request('/admin/users/status', {'user_id': customer_id, 'status': '1'})
check(c.login(email, password)[2].rstrip('/') == BASE, 'unlocked customer can log in')
print('SUCCESS:', checks, 'HTTP checks')
