<footer class="footer">
        <!-- Đăng ký nhận tin Pháp -->
        <div class="newsletter-section">
            <div class="newsletter-content">
                <h2>Đăng ký nhận tin Pháp</h2>
                <p>Nhận thông báo sản phẩm mới, khuyến mãi và bài viết Phật học</p>
            </div>
            <form class="newsletter-form" action="<?= BASE_URL ?>newsletter/subscribe" method="post" style="display:flex;flex-wrap:wrap;gap:.5rem;">
                <input type="email" name="email" placeholder="Địa chỉ email của bạn" autocomplete="email" required>
                <button type="submit">Đăng ký</button>
                <label style="flex-basis:100%;font-size:.85rem;line-height:1.5;display:flex;gap:.5rem;align-items:flex-start;margin-top:0.5rem;"><input type="checkbox" name="marketing_consent" value="1" required style="width:auto;margin-top:.25rem;"> <span>Tôi đồng ý nhận email sản phẩm, bài viết và khuyến mãi từ Liên Hoa; có thể hủy bất kỳ lúc nào. Xem <a href="<?= BASE_URL ?>privacy" style="text-decoration:underline;">chính sách dữ liệu</a>.</span></label>
            </form>
        </div>

        <div class="footer-content">
            <div class="footer-brand-col">
                <div class="footer-brand-logo">
                    <img src="<?= BASE_URL ?>assets/images/logo-lotus.jpg" alt="Liên Hoa" class="logo-icon-img">
                    <div>
                        <span class="brand-main">Liên Hoa</span>
                        <span class="brand-sub">ĐỒ LAM PHẬT GIÁO</span>
                    </div>
                </div>
                <p class="brand-desc">Chuyên cung cấp pháp phục, đồ lam, vật phẩm tâm linh Phật giáo chất lượng cao. Phục vụ quý Phật tử bằng tâm từ bi và sự tận tụy.</p>
                <div class="footer-socials">
                    <a href="#" aria-label="Facebook"><svg width="18" height="18" fill="currentColor" viewBox="0 0 320 512"><path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg></a>
                    <a href="#" aria-label="YouTube"><svg width="18" height="18" fill="currentColor" viewBox="0 0 576 512"><path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"/></svg></a>
                    <a href="#" aria-label="TikTok"><svg width="18" height="18" fill="currentColor" viewBox="0 0 448 512"><path d="M448 209.91a210.06 210.06 0 0 1-122.77-39.25V349.38A162.55 162.55 0 1 1 185 188.31V278.2a74.62 74.62 0 1 0 52.23 71.18V0l88 0a121.18 121.18 0 0 0 1.86 22.17h0A122.18 122.18 0 0 0 381 102.39a121.43 121.43 0 0 0 67 20.14Z"/></svg></a>
                </div>
            </div>
            <div>
                <h3>MUA SẮM</h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>shop?category=Đồ+lam+đi+chùa">Đồ lam đi chùa</a></li>
                    <li><a href="<?= BASE_URL ?>shop?category=Quần+áo+Tăng+-+Ni">Pháp phục Tăng - Ni</a></li>
                    <li><a href="<?= BASE_URL ?>shop?category=Túi+đeo+đi+chùa">Túi xách đi chùa</a></li>
                    <li><a href="<?= BASE_URL ?>shop?category=Quần+áo+ngồi+thiền">Quần áo ngồi thiền</a></li>
                    <li><a href="<?= BASE_URL ?>shop?category=Vòng+tay+-+chuỗi+hạt">Tràng hạt &amp; Chuỗi niệm</a></li>
                    <li><a href="<?= BASE_URL ?>shop?category=Phụ+kiện+đi+chùa">Phụ kiện &amp; Pháp cụ</a></li>
                </ul>
            </div>
            <div>
                <h3>VỀ CHÚNG TÔI</h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>about">Giới thiệu</a></li>
                    <li><a href="<?= BASE_URL ?>careers">Tuyển dụng</a></li>
                    <li><a href="<?= BASE_URL ?>support">Liên hệ</a></li>
                    <li><a href="<?= BASE_URL ?>faqs">Câu hỏi thường gặp</a></li>
                    <li><a href="<?= BASE_URL ?>feedback">Gửi phản hồi</a></li>
                </ul>
            </div>
            <div>
                <h3>HỖ TRỢ</h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>terms#delivery">Chính sách giao hàng</a></li>
                    <li><a href="<?= BASE_URL ?>terms#returns">Chính sách đổi trả</a></li>
                    <li><a href="<?= BASE_URL ?>terms">Điều khoản sử dụng</a></li>
                    <li><a href="<?= BASE_URL ?>privacy">Bảo vệ dữ liệu cá nhân</a></li>
                    <li><a href="<?= BASE_URL ?>tracking">Tra cứu đơn hàng</a></li>
                    <li><br></li>
                    <li class="contact-info"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> 1800 2235 (miễn phí)</li>
                    <li class="contact-info"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> lienhoashop.pg@gmail.com</li>
                    <li class="contact-info"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> 7:00 - 21:00 hằng ngày</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Liên Hoa — Đồ Lam Phật Giáo. Kính chúc quý Phật tử an lành.</p>
            <div class="payment-methods">
                <span>COD</span>
                <span>CHUYỂN KHOẢN / VIETQR</span>
                <span>PAYPAL</span>
            </div>
        </div>
    </footer>
    <?php if (isset($_GET['newsletter'])): ?>
        <?php $newsletterFlash = \App\Helpers\SessionHelper::getAllFlash(); ?>
        <?php if (!empty($newsletterFlash)): ?><div role="status" style="position:fixed;right:20px;bottom:20px;z-index:10001;max-width:390px;padding:1rem 1.2rem;background:<?= isset($newsletterFlash['error']) ? '#7f1d1d' : '#245b55' ?>;color:#fff;border-radius:8px;box-shadow:0 8px 30px #0003;"><?= htmlspecialchars(implode(' ', $newsletterFlash), ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php endif; ?>
    <div id="analyticsConsent" style="display:none;position:fixed;left:18px;right:18px;bottom:18px;z-index:10000;max-width:760px;margin:auto;padding:1rem 1.2rem;background:#fff;color:#222;border:1px solid #ccc;border-radius:10px;box-shadow:0 8px 35px #0003;">
        <strong>Đo lường website</strong>
        <p style="margin:.35rem 0 .8rem;">Liên Hoa muốn lưu thống kê truy cập và chuyển đổi để đánh giá đồ án. Bạn có thể từ chối mà vẫn sử dụng đầy đủ chức năng mua hàng. <a href="<?= BASE_URL ?>privacy">Chi tiết</a>.</p>
        <button type="button" data-analytics-choice="accept">Đồng ý đo lường</button>
        <button type="button" data-analytics-choice="reject">Từ chối</button>
    </div>
    <script>
    (() => {
        const key = 'lienhoa_analytics_consent_v1';
        const box = document.getElementById('analyticsConsent');
        const choice = localStorage.getItem(key);
        if (!choice && box) box.style.display = 'block';
        box?.querySelectorAll('[data-analytics-choice]').forEach(button => button.addEventListener('click', () => {
            const value = button.dataset.analyticsChoice === 'accept' ? 'accepted' : 'rejected';
            localStorage.setItem(key, value);
            box.style.display = 'none';
            if (value === 'accepted') recordPageEvent();
        }));

        function recordPageEvent() {
            if (localStorage.getItem(key) !== 'accepted') return;
            const query = new URLSearchParams(location.search);
            const path = location.pathname;
            let eventType = 'page_view';
            if (path.endsWith('/product') || path.endsWith('/product/')) eventType = 'product_view';
            if (path.endsWith('/checkout') || path.endsWith('/checkout/')) eventType = 'checkout_started';
            if (path.endsWith('/checkout-success') || path.endsWith('/checkout-success/')) eventType = 'purchase';
            const dedupe = 'lh_event_' + eventType + '_' + path + '_' + (query.get('id') || query.get('order_id') || '');
            if (sessionStorage.getItem(dedupe)) return;
            sessionStorage.setItem(dedupe, '1');
            fetch(BASE_URL + 'analytics/event', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({
                    consent: true,
                    event_type: eventType,
                    page_path: path,
                    product_id: query.get('id'),
                    order_id: query.get('order_id'),
                    source: query.get('utm_source') || sessionStorage.getItem('lh_utm_source') || '',
                    medium: query.get('utm_medium') || sessionStorage.getItem('lh_utm_medium') || '',
                    campaign: query.get('utm_campaign') || sessionStorage.getItem('lh_utm_campaign') || ''
                })
            }).catch(() => {});
        }
        window.recordAnalyticsEvent = (eventType, extra = {}) => {
            if (localStorage.getItem(key) !== 'accepted') return;
            const params = new URLSearchParams(location.search);
            fetch(BASE_URL + 'analytics/event', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify(Object.assign({
                    consent: true,
                    event_type: eventType,
                    page_path: location.pathname,
                    source: params.get('utm_source') || sessionStorage.getItem('lh_utm_source') || '',
                    medium: params.get('utm_medium') || sessionStorage.getItem('lh_utm_medium') || '',
                    campaign: params.get('utm_campaign') || sessionStorage.getItem('lh_utm_campaign') || ''
                }, extra))
            }).catch(() => {});
        };
        ['source','medium','campaign'].forEach(name => {
            const value = new URLSearchParams(location.search).get('utm_' + name);
            if (value) sessionStorage.setItem('lh_utm_' + name, value.slice(0, 150));
        });
        if (choice === 'accepted') recordPageEvent();
    })();
    </script>
</body>
</html>
