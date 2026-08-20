<footer class="footer">
        <!-- Đăng ký nhận tin Pháp -->
        <div class="newsletter-section">
            <div class="newsletter-content">
                <h2>Đăng ký nhận tin Pháp</h2>
                <p>Nhận thông báo sản phẩm mới, khuyến mãi và bài viết Phật học</p>
            </div>
            <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Cảm ơn quý Phật tử đã đăng ký nhận tin Pháp!'); this.reset();">
                <input type="email" placeholder="Địa chỉ email của bạn" required>
                <button type="submit">Đăng ký</button>
            </form>
        </div>

        <div class="footer-content">
            <div class="footer-brand-col">
                <div class="footer-brand-logo">
                    <span class="logo-icon">🌸</span>
                    <div>
                        <span class="brand-main">Liên Hoa</span>
                        <span class="brand-sub">ĐỒ LAM PHẬT GIÁO</span>
                    </div>
                </div>
                <p class="brand-desc">Chuyên cung cấp pháp phục, đồ lam, vật phẩm tâm linh Phật giáo chất lượng cao. Phục vụ quý Phật tử bằng tâm từ bi và sự tận tụy.</p>
                <div class="footer-socials">
                    <a href="#" aria-label="Facebook"><svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg></a>
                    <a href="#" aria-label="YouTube"><svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 4-8 4z"/></svg></a>
                    <a href="#" aria-label="TikTok"><svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.02 1.73 4.1 1.12 1.1 2.66 1.71 4.23 1.79v3.9c-1.85-.02-3.66-.63-5.12-1.78-.17-.13-.33-.27-.49-.42v6.62c.04 4.38-3.23 8.08-7.61 8.39-4.34.33-8.24-2.73-8.73-7.05-.56-4.9 3.23-9.35 8.16-9.17 1.43.04 2.8.54 3.92 1.45V0c0 .02.01.02.01.02z"/></svg></a>
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
                    <li><a href="<?= BASE_URL ?>careers">Blog & Kiến thức</a></li>
                    <li><a href="<?= BASE_URL ?>support">Liên hệ</a></li>
                    <li><a href="<?= BASE_URL ?>faqs">Câu hỏi thường gặp</a></li>
                </ul>
            </div>
            <div>
                <h3>HỖ TRỢ</h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>privacy">Chính sách giao hàng</a></li>
                    <li><a href="<?= BASE_URL ?>terms">Chính sách đổi trả</a></li>
                    <li><a href="<?= BASE_URL ?>privacy">Điều khoản sử dụng</a></li>
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
                <span>VISA</span>
                <span>MC</span>
                <span>MOMO</span>
                <span>PAYPAL</span>
                <span>COD</span>
            </div>
        </div>
    </footer>
</body>
</html>
