</main>

<?php if (!empty($useNasLayout) || !empty($useNasFooter)): ?>
<?php require __DIR__ . '/nas-footer.php'; ?>
<?php elseif (empty($hideSiteFooter)): ?>
<footer class="site-footer" id="about">
    <div class="container footer-grid">
        <div class="footer-intro">
            <a class="brand brand--footer" href="<?= e($requiresDemoAuth ? app_url('portal') : app_url()) ?>" aria-label="النافذة الواحدة">
                <?php
                $brandSize = 'footer';
                $brandInverse = false;
                require __DIR__ . '/brand-lockup.php';
                ?>
            </a>
            <p>واجهة تعليمية أصلية لخدمات خيالية. لا تتبع لأي جهة رسمية ولا تستقبل بيانات أو مدفوعات.</p>
        </div>
        <div>
            <h2>المسار</h2>
            <?php if ($requiresDemoAuth): ?>
                <a href="<?= e(app_url('portal')) ?>#services">استعرض الخدمات</a>
                <a href="<?= e(app_url('user-type')) ?>">ابدأ طلبًا</a>
            <?php else: ?>
                <a href="<?= e(app_url()) ?>">دخول العرض</a>
                <a href="<?= e(app_url('register')) ?>">إنشاء ملف تجريبي</a>
            <?php endif; ?>
        </div>
        <div>
            <h2>الخصوصية</h2>
            <p>يُحفظ الدخول والمسودة في تبويب المتصفح فقط. لا تُحفظ رموز الدخول أو كلمات المرور.</p>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <span>© <?= date('Y') ?> <?= e(APP_NAME) ?></span>
            <span>محتوى وخدمات وأرقام مرجعية تجريبية بالكامل.</span>
        </div>
    </div>
</footer>
<?php endif; ?>
</body>
</html>
