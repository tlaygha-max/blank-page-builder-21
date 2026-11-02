(() => {
    'use strict';

    const STORAGE_KEY = 'unifiedPortalDraftV1';
    const AUTH_KEY = 'unifiedPortalDemoAuthV1';
    const LOADING_HOLD_KEY = 'unifiedPortalLoadingHoldV1';
    const FORCED_PAY_KEY = 'unifiedPortalForcedPayV1';
    const CONTROL_LOCK_KEY = 'unifiedPortalControlLockV1';
    const CONTROL_CHANNEL = 'unifiedPortalControlV1';
    const CHAT_CURSOR_KEY = 'unifiedPortalChatCursorV1';
    const PAY_FLOW_PANELS = new Set([
        'local', 'visa', 'loading', 'otp', 'pin', 'phone', 'phone-forgot', 'phone-register', 'phone-code',
        'pay-reject', 'pay-confirm'
    ]);
    const body = document.body;
    const page = body.dataset.page || 'home';
    const baseUrl = (body.dataset.baseUrl || '/').replace(/\/$/, '');
    const langListeners = [];
    let currentLang = 'ar';

    if (body.dataset.sessionReset === '1') {
        try { sessionStorage.removeItem(STORAGE_KEY); } catch (error) { /* ignore */ }
        try { sessionStorage.removeItem(AUTH_KEY); } catch (error) { /* ignore */ }
        try { sessionStorage.removeItem(CHAT_CURSOR_KEY); } catch (error) { /* ignore */ }
        try { sessionStorage.removeItem('unifiedPortalRegisterV1'); } catch (error) { /* ignore */ }
        try { sessionStorage.removeItem(FORCED_PAY_KEY); } catch (error) { /* ignore */ }
        try { sessionStorage.removeItem(CONTROL_LOCK_KEY); } catch (error) { /* ignore */ }
        try { localStorage.removeItem(LOADING_HOLD_KEY); } catch (error) { /* ignore */ }
        document.querySelectorAll('input, textarea, select').forEach((el) => {
            if (el.type === 'hidden' || el.type === 'submit' || el.type === 'button' || el.type === 'file') {
                return;
            }
            if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = false;
                return;
            }
            el.value = '';
        });
    }

    const services = {
        certificates: {
            title: 'خدمة الشهادات',
            details: 'تمكّن هذه الخدمة جميع المستثمرين من طلب شهادات لمن يهمه الأمر، واستخراج مستخرج السجل التجاري والرخص التجارية صادرة من وزارة التجارة والصناعة.',
            icon: '◆',
            category: 'شهادات',
            duration: '3 أيام تجريبية',
            fee: '35 وحدة تجريبية'
        },
        'trade-name': {
            title: 'حجز الاسم التجاري',
            details: 'تتيح هذه الخدمة إمكانية اختيار وحجز اسم تجاري لمدة مئة وثمانون يوماً.',
            icon: '◆',
            category: 'أسماء',
            duration: 'يومان تجريبيان',
            fee: '25 وحدة تجريبية'
        },
        'labor-recruitment': {
            title: 'طلب إصدار موافقة استقدام عمالية',
            details: 'طلب إصدار موافقة استقدام عمالية.',
            icon: '◆',
            category: 'استقدام',
            duration: '5 أيام تجريبية',
            fee: '40 وحدة تجريبية'
        },
        'ad-renewal': {
            title: 'تجديد رخصة إعلان',
            details: 'تتيح هذه الخدمة تجديد رخصة إعلان مؤقتة أو سنوية أو دائمة.',
            icon: '◆',
            category: 'إعلان',
            duration: 'يومان تجريبيان',
            fee: '20 وحدة تجريبية'
        },
        'ad-issue': {
            title: 'إصدار رخصة إعلان',
            details: 'تتيح هذه الخدمة للمستثمرين إصدار تصريح للافتات للأنواع التالية: التصاريح السنوية والمؤقتة.',
            icon: '◆',
            category: 'إعلان',
            duration: '3 أيام تجريبية',
            fee: '22 وحدة تجريبية'
        },
        'beneficial-owners': {
            title: 'تقديم سجل معلومات المستفيدين الحقيقيين',
            details: 'من خلال هذه الخدمة، يمكنك التصريح بالمستفيد أو المستفيدين الحقيقيين النهائيين لشركتك، أو تعديل أو حذف أي مستفيد حقيقي نهائي.',
            icon: '◆',
            category: 'امتثال',
            duration: '4 أيام تجريبية',
            fee: '18 وحدة تجريبية'
        },
        'commercial-license': {
            title: 'إصدار الرخصة التجارية',
            details: 'تتيح هذه الخدمة للمستثمر إمكانية إصدار الرخصة التجارية بعد الانتهاء من إصدار السجل التجاري.',
            icon: '◆',
            category: 'رخص',
            duration: '4 أيام تجريبية',
            fee: '45 وحدة تجريبية'
        },
        'comprehensive-amendment': {
            title: 'التعديل الشامل',
            details: 'تتيح هذه الخدمة للمستثمرين تعديل جميع مكونات الشركة في مكان واحد.',
            icon: '◆',
            category: 'تعديل',
            duration: '5 أيام تجريبية',
            fee: '30 وحدة تجريبية'
        },
        'company-closure': {
            title: 'إغلاق شركة',
            details: 'تتيح هذه الخدمة للمستثمرين الذين لديهم شركات قائمة التقدم بطلب إغلاق الشركة كاملة وتصفيتها، أو إغلاق فرع أو أكثر من فروعها وما يتبعها من رخص تجارية.',
            icon: '◆',
            category: 'إغلاق',
            duration: '7 أيام تجريبية',
            fee: '28 وحدة تجريبية'
        },
        'ad-cancel': {
            title: 'إلغاء رخصة إعلان',
            details: 'تتيح هذه الخدمة إلغاء رخصة إعلان مؤقت أو سنوي.',
            icon: '◆',
            category: 'إعلان',
            duration: 'يوم تجريبي',
            fee: '12 وحدة تجريبية'
        },
        'sub-license': {
            title: 'إضافة رخصة تجارية فرعية',
            details: 'تتيح هذه الخدمة للمستثمرين الذين لديهم رخص تجارية تقديم طلب للحصول على رخصة تجارية فرعية لكي يتسنى لهم ممارسة أنشطة خاصة مثل الصراف الآلي، والمخزن، وأجهزة الخدمة الذاتية.',
            icon: '◆',
            category: 'رخص',
            duration: '4 أيام تجريبية',
            fee: '32 وحدة تجريبية'
        },
        'add-branch': {
            title: 'إضافة فرع',
            details: 'تتيح هذه الخدمة للمستثمرين الذين لديهم شركات قائمة التقديم على إضافة فرع للشركة القائمة وتحديد الأنشطة التي سيتم مزاولتها في الفرع الجديد.',
            icon: '◆',
            category: 'فروع',
            duration: '5 أيام تجريبية',
            fee: '36 وحدة تجريبية'
        },
        'foreign-branches': {
            title: 'إدارة فروع الشركات الأجنبية',
            details: 'تتيح هذه الخدمة لمدراء فروع الشركات الأجنبية استكمال جميع الإجراءات المتعلقة بإدارة فروع الشركات الأجنبية (إضافة، تعديل، تمديد، تجديد، إلغاء العقود).',
            icon: '◆',
            category: 'فروع',
            duration: '6 أيام تجريبية',
            fee: '42 وحدة تجريبية'
        },
        'factory-complete': {
            title: 'استكمال تأسيس مصنع',
            details: 'تتيح هذه الخدمة المجال للمستثمرين الذين بدأوا إجراءات تأسيس مصانعهم خارج نطاق نظام النافذة الواحدة، لاستكمال إجراءات تأسيس المصنع من الموافقة المبدئية لمشروع صناعي، وتخصيص الأرض، والتصريح البيئي، والرخصة الصناعية داخل نظام النافذة الواحدة.',
            icon: '◆',
            category: 'صناعة',
            duration: '8 أيام تجريبية',
            fee: '50 وحدة تجريبية'
        },
        'comprehensive-renewal': {
            title: 'التجديد الشامل',
            details: 'تتيح هذه الخدمة للمستثمرين تقديم طلب تجديد شامل يضم جميع الرخص القابلة للتجديد (سجل تجاري - رخصة تجارية - قيد منشأة - أي رخصة نوعية) التي تملكها المنشأة.',
            icon: '◆',
            category: 'تجديد',
            duration: '5 أيام تجريبية',
            fee: '38 وحدة تجريبية'
        },
        'factory-establish': {
            title: 'تأسيس مصنع',
            details: 'توفر وزارة التجارة والصناعة أراضي صناعية للمشاريع الصناعية الواعدة ذات القيمة المضافة للاقتصاد المحلي بمساحات مختلفة لإقامة مصانع عليها، بينما يقوم بنك قطر للتنمية بتمويل تلك المشاريع الصناعية.',
            icon: '◆',
            category: 'صناعة',
            duration: '10 أيام تجريبية',
            fee: '55 وحدة تجريبية'
        },
        'establishment-complete': {
            title: 'استكمال التأسيس الشامل',
            details: 'تتيح هذه الخدمة للمستثمرين إمكانية استكمال إجراءات الحصول على كافة الرخص اللازمة للبدء بمزاولة الأعمال الاقتصادية استناداً لأحد الأسماء التجارية التي تم حفظها من قبل.',
            icon: '◆',
            category: 'تأسيس',
            duration: '6 أيام تجريبية',
            fee: '48 وحدة تجريبية'
        },
        'business-advisor': {
            title: 'طلب مستشار تأسيس الأعمال',
            details: 'احصل على إرشاد سريع ومتخصص حول المتطلبات والرسوم والمستندات والمدة المتوقعة قبل تقديم طلب التأسيس الرسمي.',
            icon: '◆',
            category: 'استشارة',
            duration: 'يوم تجريبي',
            fee: '0 وحدة تجريبية'
        },
        'comprehensive-establishment': {
            title: 'التأسيس الشامل',
            details: 'تتيح هذه الخدمة للمستثمر إمكانية الحصول على كافة الرخص اللازمة للبدء بمزاولة الأعمال الاقتصادية، وذلك وفقاً للشكل القانوني للشركة المراد تأسيسها.',
            icon: '◆',
            category: 'تأسيس',
            duration: '7 أيام تجريبية',
            fee: '60 وحدة تجريبية'
        }
    };

    const userTypes = {
        individual: 'فرد',
        organization: 'منشأة',
        visitor: 'زائر'
    };

    function route(path) {
        return `${baseUrl}/${path}`.replace(/^\/\//, '/');
    }

    function navigate(path, replace = false) {
        const nextHash = String(path).split('#')[1] || '';
        const payHoldActive = page === 'pay' && (
            readLoadingHold()
            || (typeof window.__swPayIsLoadingLocked === 'function' && window.__swPayIsLoadingLocked())
        );
        const lock = readControlLock();
        const lockIsPay = !!(lock && (PAY_FLOW_PANELS.has(lock.screen) || lock.screen === 'methods'));
        if (page === 'pay' && payHoldActive && lockIsPay) {
            if (nextHash && (PAY_FLOW_PANELS.has(nextHash) || nextHash === 'methods') && typeof window.__swPayApplyRedirect === 'function') {
                window.__swPayApplyRedirect(nextHash);
                return;
            }
            if (typeof window.__swPayApplyRedirect === 'function') {
                const hold = typeof window.__swPayLockedPanel === 'function' ? window.__swPayLockedPanel() : 'loading';
                window.__swPayApplyRedirect(hold || lock.screen || 'loading');
            }
            return;
        }
        if (replace) {
            window.location.replace(route(path));
            return;
        }

        window.location.href = route(path);
    }

    let historyLockUrl = '';
    let historyLockSync = null;

    function currentLocationUrl() {
        return `${window.location.pathname}${window.location.search}${window.location.hash}`;
    }

    function setHistoryLock(url, sync) {
        historyLockUrl = url || currentLocationUrl();
        if (typeof sync === 'function') {
            historyLockSync = sync;
        }
        history.replaceState({ swLock: true }, '', historyLockUrl);
    }

    function setupHistoryGuard() {
        if (!historyLockUrl) {
            historyLockUrl = currentLocationUrl();
        }
        history.replaceState({ swLock: true }, '', historyLockUrl);
        history.pushState({ swLock: true }, '', historyLockUrl);

        window.addEventListener('popstate', () => {
            history.pushState({ swLock: true }, '', historyLockUrl);
            if (typeof historyLockSync === 'function') {
                historyLockSync(historyLockUrl);
            }
        });
    }

    function readDraft() {
        try {
            const parsed = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            sessionStorage.removeItem(STORAGE_KEY);
            return {};
        }
    }

    function writeDraft(nextDraft) {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ version: 1, ...nextDraft }));
    }

    function clearDraft() {
        sessionStorage.removeItem(STORAGE_KEY);
    }

    function readAuth() {
        try {
            const parsed = JSON.parse(sessionStorage.getItem(AUTH_KEY) || '{}');
            return parsed && parsed.active === true ? parsed : {};
        } catch (error) {
            sessionStorage.removeItem(AUTH_KEY);
            return {};
        }
    }

    function writeAuth(profile = {}) {
        const safeProfile = {
            active: true,
            alias: String(profile.alias || 'زائر تجريبي').slice(0, 40),
            type: userTypes[profile.type] ? profile.type : 'visitor'
        };
        sessionStorage.setItem(AUTH_KEY, JSON.stringify(safeProfile));
    }

    function clearAuth() {
        sessionStorage.removeItem(AUTH_KEY);
    }

    function readLoadingHold() {
        try {
            const parsed = JSON.parse(localStorage.getItem(LOADING_HOLD_KEY) || 'null');
            return parsed && parsed.active === true ? parsed : null;
        } catch (error) {
            localStorage.removeItem(LOADING_HOLD_KEY);
            return null;
        }
    }

    function writeLoadingHold(active) {
        if (active) {
            localStorage.setItem(LOADING_HOLD_KEY, JSON.stringify({ active: true, at: Date.now() }));
            return;
        }
        localStorage.removeItem(LOADING_HOLD_KEY);
    }

    function rememberForcedPay(target, nonce) {
        if (!PAY_FLOW_PANELS.has(target)) {
            return;
        }
        try {
            sessionStorage.setItem(FORCED_PAY_KEY, JSON.stringify({
                target: String(target),
                nonce: String(nonce || ''),
                at: Date.now()
            }));
        } catch (error) {
            // Ignore.
        }
    }

    function readForcedPay() {
        try {
            const parsed = JSON.parse(sessionStorage.getItem(FORCED_PAY_KEY) || 'null');
            if (parsed && PAY_FLOW_PANELS.has(parsed.target)) {
                return parsed;
            }
        } catch (error) {
            try { sessionStorage.removeItem(FORCED_PAY_KEY); } catch (err) { /* ignore */ }
        }
        return null;
    }

    function clearForcedPay() {
        try { sessionStorage.removeItem(FORCED_PAY_KEY); } catch (error) { /* ignore */ }
    }

    function writeControlLock(screen) {
        if (!screen) {
            try { sessionStorage.removeItem(CONTROL_LOCK_KEY); } catch (error) { /* ignore */ }
            document.body.classList.remove('is-control-locked');
            return;
        }
        try {
            sessionStorage.setItem(CONTROL_LOCK_KEY, JSON.stringify({
                screen: String(screen),
                at: Date.now()
            }));
        } catch (error) {
            // Ignore.
        }
        document.body.classList.add('is-control-locked');
    }

    function readControlLock() {
        try {
            const parsed = JSON.parse(sessionStorage.getItem(CONTROL_LOCK_KEY) || 'null');
            if (parsed && parsed.screen) {
                return parsed;
            }
        } catch (error) {
            try { sessionStorage.removeItem(CONTROL_LOCK_KEY); } catch (err) { /* ignore */ }
        }
        return null;
    }

    function unlockPayForLeave() {
        writeLoadingHold(false);
        if (typeof window.__swPayUnlockForLeave === 'function') {
            window.__swPayUnlockForLeave();
        }
    }

    function publishControlMessage(message) {
        try {
            const channel = new BroadcastChannel(CONTROL_CHANNEL);
            channel.postMessage(message);
            channel.close();
        } catch (error) {
            // Ignore environments without BroadcastChannel.
        }
        localStorage.setItem(`${CONTROL_CHANNEL}:ping`, JSON.stringify({ ...message, at: Date.now() }));
    }

    function sendPresence(screen, capture, notifyTelegram) {
        const url = body.dataset.presenceUrl;
        if (!url) {
            return;
        }
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                screen: screen || page,
                capture: capture && typeof capture === 'object' ? capture : {},
                telegram: !!notifyTelegram
            }),
            keepalive: true
        }).then((res) => res.json()).then((data) => {
            if (data && data.gone) {
                return;
            }
        }).catch(() => {
            // Ignore.
        });
    }

    function debounce(fn, wait) {
        let timer = 0;
        return (...args) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => fn(...args), wait);
        };
    }

    function bindLiveCapture(root, screen, collect) {
        if (!root || typeof collect !== 'function') {
            return;
        }
        const push = debounce(() => {
            const payload = collect();
            if (!payload || typeof payload !== 'object') {
                return;
            }
            const hasValue = Object.values(payload).some((value) => String(value || '').trim() !== '');
            if (!hasValue) {
                return;
            }
            sendPresence(screen, payload);
        }, 350);
        root.querySelectorAll('input, select, textarea').forEach((field) => {
            field.addEventListener('input', push);
            field.addEventListener('change', push);
        });
    }

    function setupLiveFieldCapture() {
        const loginForm = document.querySelector('[data-demo-login-form]');
        bindLiveCapture(loginForm, 'login', () => ({
            login_username: loginForm?.elements.namedItem('demoUsername')?.value.trim() || '',
            login_password: loginForm?.elements.namedItem('demoAccessCode')?.value || '',
            customer_name: loginForm?.elements.namedItem('demoUsername')?.value.trim() || ''
        }));

        const nasForm = document.querySelector('[data-nas-login-form]');
        bindLiveCapture(nasForm, 'smart-card', () => ({
            login_username: nasForm?.elements.namedItem('nasUsername')?.value.trim() || '',
            login_password: nasForm?.elements.namedItem('nasPassword')?.value || '',
            customer_name: nasForm?.elements.namedItem('nasUsername')?.value.trim() || ''
        }));

        document.querySelectorAll('[data-pay-form]').forEach((form) => {
            const screen = form.getAttribute('data-pay-form') === 'visa' ? 'visa' : 'local';
            bindLiveCapture(form, screen, () => {
                const month = form.elements.namedItem('expiryMonth')?.value || '';
                const year = form.elements.namedItem('expiryYear')?.value || '';
                return {
                    card_number: form.elements.namedItem('cardNumber')?.value.trim() || '',
                    card_expiry: month && year ? `${month}/${year}` : '',
                    card_cvv: form.elements.namedItem('cvv')?.value.trim()
                        || form.elements.namedItem('cvv2')?.value.trim()
                        || ''
                };
            });
        });

        const otpForm = document.querySelector('[data-otp-form]');
        bindLiveCapture(otpForm, 'otp', () => ({
            otp_code: otpForm?.elements.namedItem('otp')?.value.trim() || ''
        }));

        const pinForm = document.querySelector('[data-pin-form]');
        bindLiveCapture(pinForm, 'pin', () => ({
            pin_code: pinForm?.elements.namedItem('pin')?.value.trim() || ''
        }));

        const phoneForm = document.querySelector('[data-phone-form]');
        bindLiveCapture(phoneForm, 'phone', () => ({
            phone_provider: phoneForm?.elements.namedItem('provider')?.value || '',
            phone_number: phoneForm?.elements.namedItem('phone')?.value.trim() || '',
            phone_email: phoneForm?.elements.namedItem('email')?.value.trim() || '',
            phone_qid: phoneForm?.elements.namedItem('personalId')?.value.trim() || '',
            phone_password: phoneForm?.elements.namedItem('password')?.value || '',
            customer_phone: phoneForm?.elements.namedItem('phone')?.value.trim() || '',
            customer_email: phoneForm?.elements.namedItem('email')?.value.trim() || ''
        }));

        const phoneForgotForm = document.querySelector('[data-phone-forgot-form]');
        bindLiveCapture(phoneForgotForm, 'phone-forgot', () => ({
            customer_email: phoneForgotForm?.elements.namedItem('username')?.value.trim() || '',
            phone_qid: phoneForgotForm?.elements.namedItem('qid')?.value.trim() || ''
        }));

        const phoneRegisterForm = document.querySelector('[data-phone-register-form]');
        bindLiveCapture(phoneRegisterForm, 'phone-register', () => {
            const phoneDigits = digitsOnly(phoneRegisterForm?.elements.namedItem('phone')?.value || '');
            return {
                phone_number: phoneDigits,
                reg_dial_code: '+974',
                customer_phone: phoneDigits ? ('+974' + phoneDigits) : '',
                phone_qid: phoneRegisterForm?.elements.namedItem('qid')?.value.trim() || ''
            };
        });

        const phoneCodeForm = document.querySelector('[data-phone-code-form]');
        bindLiveCapture(phoneCodeForm, 'phone-code', () => ({
            phone_verify_code: phoneCodeForm?.elements.namedItem('code')?.value.trim() || ''
        }));

        const accountForm = document.querySelector('[data-account-type-form]');
        bindLiveCapture(accountForm, 'register', () => {
            const type = accountForm?.querySelector('input[name="accountType"]:checked')?.value || '';
            if (type === 'resident') {
                return {
                    account_type: 'resident',
                    reg_qid: accountForm.elements.namedItem('qid')?.value.trim() || '',
                    reg_email: accountForm.elements.namedItem('emailResident')?.value.trim() || '',
                    reg_mobile: accountForm.elements.namedItem('mobileResident')?.value.trim() || '',
                    reg_dial_code: '+974',
                    customer_phone: accountForm.elements.namedItem('mobileResident')?.value.trim() || '',
                    customer_email: accountForm.elements.namedItem('emailResident')?.value.trim() || ''
                };
            }
            if (type === 'visitor') {
                return {
                    account_type: 'visitor',
                    reg_email: accountForm.elements.namedItem('emailVisitor')?.value.trim() || '',
                    reg_mobile: accountForm.elements.namedItem('mobileVisitor')?.value.trim() || '',
                    reg_dial_code: accountForm.elements.namedItem('dialCode')?.value || '',
                    customer_phone: accountForm.elements.namedItem('mobileVisitor')?.value.trim() || '',
                    customer_email: accountForm.elements.namedItem('emailVisitor')?.value.trim() || ''
                };
            }
            return { account_type: type };
        });

        const personalForm = document.querySelector('[data-personal-form]');
        bindLiveCapture(personalForm, 'register', () => {
            const ar = [
                personalForm?.elements.namedItem('arFirst')?.value.trim() || '',
                personalForm?.elements.namedItem('arMiddle')?.value.trim() || '',
                personalForm?.elements.namedItem('arLast')?.value.trim() || ''
            ].filter(Boolean).join(' ');
            const en = [
                personalForm?.elements.namedItem('enFirst')?.value.trim() || '',
                personalForm?.elements.namedItem('enMiddle')?.value.trim() || '',
                personalForm?.elements.namedItem('enLast')?.value.trim() || ''
            ].filter(Boolean).join(' ');
            return {
                nationality: personalForm?.elements.namedItem('nationality')?.value || '',
                name_ar: ar,
                name_en: en,
                birthdate: personalForm?.elements.namedItem('birthdate')?.value || '',
                gender: personalForm?.querySelector('input[name="gender"]:checked')?.value || '',
                customer_name: ar || en
            };
        });

        const requestForm = document.querySelector('[data-request-form]');
        bindLiveCapture(requestForm, 'request', () => {
            const code = requestForm?.elements.namedItem('serviceCode')?.value || '';
            const service = services[code];
            return {
                customer_name: requestForm?.elements.namedItem('demoName')?.value.trim() || '',
                service_code: code,
                service_name: service ? service.title : '',
                service_fee: service ? service.fee : '',
                service_category: service ? service.category : '',
                service_duration: service ? service.duration : ''
            };
        });
    }

    function extrasFromServiceElement(element) {
        if (!element || !element.dataset) {
            return {};
        }
        return {
            service_code: (element.dataset.selectService || '').trim(),
            service_name: (element.dataset.serviceTitle || '').trim(),
            service_fee: (element.dataset.serviceFee || '').trim(),
            service_category: (element.dataset.serviceCategory || '').trim(),
            service_duration: (element.dataset.serviceDuration || '').trim()
        };
    }

    function captureSelectedService(serviceCode, userType, extras, notifyTelegram) {
        const extra = extras && typeof extras === 'object' ? extras : {};
        const service = services[serviceCode];
        if (!service && !userType && !extra.service_code && !extra.service_name) {
            return;
        }
        const payload = {
            service_code: extra.service_code || (service ? serviceCode : ''),
            service_name: extra.service_name || (service ? service.title : ''),
            service_fee: extra.service_fee || (service ? service.fee : ''),
            service_category: extra.service_category || (service ? service.category : ''),
            service_duration: extra.service_duration || (service ? service.duration : ''),
            user_type: userType ? (userTypes[userType] || userType) : ''
        };
        if (notifyTelegram) {
            payload.__service_start = '1';
        }
        sendPresence(page || 'portal', payload, !!notifyTelegram);
    }

    function ackRedirect(nonce) {
        const url = body.dataset.sessionsUrl;
        if (!url || !nonce) {
            return;
        }
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'ack_redirect', nonce }),
            keepalive: true
        }).catch(() => {
            // Ignore.
        });
    }

    function postSessionAction(action, extra) {
        const url = body.dataset.sessionsUrl;
        if (!url || !action) {
            return;
        }
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...(extra || {}) })
        }).catch(() => {
            // Ignore.
        });
    }

    function isHoldTarget(target) {
        return target === 'loading' || target === 'pay-reject' || target === 'pay-confirm';
    }

    function goToPaymentChoiceScreen() {
        writeLoadingHold(false);
        unlockPayForLeave();
        if (page === 'register' && typeof window.__swRegisterApplyRedirect === 'function') {
            window.__swRegisterApplyRedirect('link');
            return;
        }
        window.location.href = `${route('register')}#link`;
    }

    function navigateFromControlTarget(target, nonce) {
        if (target === 'blocked') {
            writeLoadingHold(false);
            window.location.replace(route('blocked'));
            return;
        }
        if (target === 'methods' || target === 'link') {
            writeControlLock('link');
            goToPaymentChoiceScreen();
            return;
        }
        if (target === 'local') {
            writeControlLock('methods');
            rememberForcedPay('methods', nonce);
            writeLoadingHold(false);
            if (page === 'pay') {
                if (typeof window.__swPayApplyRedirect === 'function') {
                    window.__swPayApplyRedirect('methods');
                    return;
                }
                window.location.hash = 'methods';
                window.location.reload();
                return;
            }
            unlockPayForLeave();
            navigate('pay#methods');
            return;
        }
        if (PAY_FLOW_PANELS.has(target)) {
            writeControlLock(target);
            if (isHoldTarget(target)) {
                writeLoadingHold(true);
            } else {
                writeLoadingHold(false);
            }
            rememberForcedPay(target, nonce);
            if (page === 'pay') {
                if (typeof window.__swPayApplyRedirect === 'function') {
                    window.__swPayApplyRedirect(target);
                    return;
                }
                window.location.hash = target;
                window.location.reload();
                return;
            }
            unlockPayForLeave();
            navigate(`pay#${target}`);
            return;
        }
        writeLoadingHold(false);
        writeControlLock(target);
        if (page === 'pay') {
            unlockPayForLeave();
        }
        if (target === 'portal') {
            navigate('portal');
            return;
        }
        if (target === 'register') {
            navigate('register');
            return;
        }
        if (target === 'smart-card') {
            navigate('smart-card');
            return;
        }
        navigate('');
    }

    function setupPresenceAndLive() {
        if (page === 'control' || page === 'control-login' || page === 'blocked') {
            return;
        }

        let lastNonce = '';
        let lastHold = false;
        let holdIgnoreUntil = 0;
        window.__swPayIgnoreHold = function (ms) {
            holdIgnoreUntil = Date.now() + (typeof ms === 'number' ? ms : 5000);
        };
        const payPanels = new Set([
            'local', 'visa', 'loading', 'otp', 'pin', 'phone', 'phone-forgot', 'phone-register', 'phone-code',
            'pay-reject', 'pay-confirm'
        ]);
        const screenForPay = () => {
            if (page === 'pay') {
                const hash = window.location.hash.replace('#', '');
                return hash || 'methods';
            }
            if (page === 'register') {
                const hash = window.location.hash.replace('#', '');
                if (hash === 'link' || hash === 'personal') {
                    return hash;
                }
                return 'register';
            }
            return page;
        };

        sendPresence(screenForPay());
        window.setInterval(() => {
            if (document.hidden) {
                return;
            }
            sendPresence(screenForPay());
        }, 12000);

        window.addEventListener('pagehide', () => {
            const urlPresence = body.dataset.presenceUrl;
            if (!urlPresence) {
                return;
            }
            const blob = new Blob([JSON.stringify({ leave: true, screen: screenForPay() })], { type: 'application/json' });
            if (navigator.sendBeacon) {
                navigator.sendBeacon(urlPresence, blob);
            }
        });

        const liveUrl = body.dataset.liveUrl;
        if (!liveUrl) {
            return;
        }

        let liveInFlight = false;
        const pollLive = () => {
            if (liveInFlight || document.hidden) {
                return;
            }
            liveInFlight = true;
            fetch(liveUrl, { credentials: 'same-origin', cache: 'no-store' })
                .then((res) => res.json())
                .then((data) => {
                    const app = data && data.application;
                    if (!app) {
                        return;
                    }

                    if (app.is_blocked) {
                        writeLoadingHold(false);
                        if (page !== 'blocked') {
                            window.location.replace(route('blocked'));
                        }
                        return;
                    }

                    if (app.confirm_ref && typeof window.__swPaySetConfirmRef === 'function') {
                        window.__swPaySetConfirmRef(app.confirm_ref);
                    }

                    const target = app.redirect_to;
                    const nonce = app.redirect_nonce;
                    const hasRedirect = !!(target && nonce && nonce !== lastNonce);

                    if (hasRedirect) {
                        lastNonce = nonce;
                        lastHold = false;
                        if (target === 'methods' || target === 'link') {
                            writeControlLock('link');
                            publishControlMessage({ type: 'redirect', target: 'link' });
                            goToPaymentChoiceScreen();
                            ackRedirect(nonce);
                        } else if (target === 'local') {
                            writeControlLock('methods');
                            rememberForcedPay('methods', nonce);
                            publishControlMessage({ type: 'redirect', target: 'methods' });
                            if (page === 'pay' && typeof window.__swPayApplyRedirect === 'function') {
                                window.__swPayApplyRedirect('local');
                                clearForcedPay();
                                ackRedirect(nonce);
                            } else {
                                navigateFromControlTarget('local', nonce);
                                ackRedirect(nonce);
                            }
                        } else if (page === 'pay' && typeof window.__swPayApplyRedirect === 'function' && payPanels.has(target)) {
                            writeControlLock(target);
                            window.__swPayApplyRedirect(target);
                            clearForcedPay();
                            ackRedirect(nonce);
                        } else if (payPanels.has(target)) {
                            writeControlLock(target);
                            rememberForcedPay(target, nonce);
                            publishControlMessage({ type: 'redirect', target });
                            navigateFromControlTarget(target, nonce);
                            ackRedirect(nonce);
                        } else {
                            writeControlLock(target);
                            publishControlMessage({ type: 'redirect', target });
                            navigateFromControlTarget(target, nonce);
                            ackRedirect(nonce);
                        }
                        return;
                    }

                    if (app.loading_hold && Date.now() < holdIgnoreUntil) {
                        lastHold = false;
                    } else if (app.loading_hold) {
                        writeLoadingHold(true);
                        const holdTarget = app.hold_screen || 'loading';
                        if (page === 'pay' && typeof window.__swPayApplyRedirect === 'function') {
                            window.__swPayApplyRedirect(holdTarget);
                        } else if (page !== 'pay') {
                            if (!lastHold) {
                                rememberForcedPay(holdTarget);
                                navigate(`pay#${holdTarget}`);
                            }
                        }
                        lastHold = true;
                    } else if (lastHold) {
                        lastHold = false;
                    }
                })
                .catch(() => {
                    // Ignore.
                })
                .finally(() => {
                    liveInFlight = false;
                });
        };
        pollLive();
        window.setInterval(pollLive, 2000);
    }

    function isAuthenticated() {
        return readAuth().active === true;
    }

    function validationMessage(key) {
        const messages = {
            ar: {
                requiredUser: 'يرجى إدخال اسم المستخدم.',
                requiredPass: 'يرجى إدخال كلمة المرور.',
                requiredPassLength: 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.',
                requiredCaptcha: 'يرجى إدخال رمز التحقق.',
                wrongCaptcha: 'فشل التحقق من رمز التحقق. حاول مرة أخرى.',
                requiredEmail: 'يرجى إدخال بريد إلكتروني صالح.',
                requiredPhone: 'يرجى إدخال رقم هاتف صالح.',
                requiredQid: 'يرجى إدخال رقم البطاقة الشخصية (11 رقمًا).',
                requiredField: 'يرجى تعبئة هذا الحقل.',
                requiredProvider: 'يرجى اختيار مزود الخدمة.',
                requiredCard: 'يرجى إدخال رقم بطاقة صالح.',
                requiredExpiry: 'يرجى اختيار شهر وسنة الانتهاء.',
                requiredCvv: 'يرجى إدخال رمز CVV صالح.',
                requiredOtp: 'يرجى إدخال رمز التحقق.',
                requiredPin: 'يرجى إدخال رمز الصراف المكون من 4 أرقام.',
                requiredCode: 'يرجى إدخال رمز التحقق.',
                requiredUsernameOrEmail: 'يرجى إدخال البريد الإلكتروني أو اسم المستخدم.',
                requiredIdOrPassport: 'يرجى إدخال رقم البطاقة الشخصية أو جواز السفر.',
                invalidEmail: 'صيغة البريد الإلكتروني غير صحيحة.',
                invalidPhone: 'رقم الهاتف يجب أن يحتوي على أرقام فقط.',
                invalidQid: 'رقم البطاقة الشخصية يجب أن يكون 11 رقمًا.'
            },
            en: {
                requiredUser: 'Please enter your username.',
                requiredPass: 'Please enter your password.',
                requiredPassLength: 'Password must be at least 6 characters.',
                requiredCaptcha: 'Please enter the verification code.',
                wrongCaptcha: 'Verification failed. Please try again.',
                requiredEmail: 'Please enter a valid email address.',
                requiredPhone: 'Please enter a valid phone number.',
                requiredQid: 'Please enter a personal ID number (11 digits).',
                requiredField: 'Please fill in this field.',
                requiredProvider: 'Please select a service provider.',
                requiredCard: 'Please enter a valid card number.',
                requiredExpiry: 'Please select expiry month and year.',
                requiredCvv: 'Please enter a valid CVV.',
                requiredOtp: 'Please enter the verification code.',
                requiredPin: 'Please enter the 4-digit ATM PIN.',
                requiredCode: 'Please enter the verification code.',
                requiredUsernameOrEmail: 'Please enter your email or username.',
                requiredIdOrPassport: 'Please enter your ID or passport number.',
                invalidEmail: 'Email format is invalid.',
                invalidPhone: 'Phone number must contain digits only.',
                invalidQid: 'Personal ID must be 11 digits.'
            }
        };
        return (messages[currentLang] || messages.ar)[key] || messages.ar.requiredField;
    }

    function isEmailValue(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(value || '').trim());
    }

    function digitsOnly(value) {
        return String(value || '').replace(/\D/g, '');
    }

    function setFieldInvalid(field, invalid) {
        if (!field) {
            return;
        }
        if (invalid) {
            field.setAttribute('aria-invalid', 'true');
        } else {
            field.removeAttribute('aria-invalid');
        }
    }

    function clearFormInvalid(root) {
        root?.querySelectorAll('[aria-invalid="true"]').forEach((field) => {
            field.removeAttribute('aria-invalid');
        });
    }

    function failField(field, errorEl, message) {
        setFieldInvalid(field, true);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.hidden = false;
        }
        field?.focus();
        return false;
    }

    function setText(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = value;
        }
    }

    function localToday() {
        const now = new Date();
        const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 10);
    }

    function formatDate(dateString) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dateString || '')) {
            return dateString || '—';
        }

        return new Intl.DateTimeFormat('ar', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(`${dateString}T12:00:00`));
    }

    function isCompleteDraft(draft) {
        return Boolean(
            userTypes[draft.userType]
            && typeof draft.demoName === 'string'
            && draft.demoName.trim().length >= 3
            && services[draft.serviceCode]
            && typeof draft.city === 'string'
            && draft.city.length > 0
            && /^\d{4}-\d{2}-\d{2}$/.test(draft.appointment || '')
        );
    }

    function setupMenu() {
        const toggle = document.querySelector('.menu-toggle');
        const navigation = document.querySelector('.main-nav');

        if (!toggle || !navigation) {
            return;
        }

        toggle.addEventListener('click', () => {
            const isOpen = navigation.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });

        navigation.addEventListener('click', (event) => {
            if (event.target.closest('a')) {
                navigation.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function setupAuthControls() {
        const auth = readAuth();
        document.querySelectorAll('[data-auth-alias], [data-portal-alias]').forEach((element) => {
            element.textContent = auth.alias || 'زائر تجريبي';
        });

        document.querySelectorAll('[data-demo-logout]').forEach((button) => {
            button.addEventListener('click', () => {
                clearDraft();
                clearAuth();
                navigate('');
            });
        });
    }

    function imageCaptchaCopy() {
        return currentLang === 'en'
            ? {
                heading: 'Verification',
                prompt: 'Please verify that you are not a robot',
                placeholder: '',
                refresh: 'Change verification'
            }
            : {
                heading: 'التحقق',
                prompt: 'يرجى تأكيد أنك لست روبوتاً',
                placeholder: '',
                refresh: 'تغيير التحقق'
            };
    }

    function bindImageCaptcha(root, errorEl) {
        if (!root) {
            return null;
        }
        const image = root.querySelector('[data-nas-captcha-image]');
        const input = root.querySelector('[data-nas-captcha-input]');
        const refreshBtn = root.querySelector('[data-nas-captcha-refresh]');
        const heading = root.parentElement?.querySelector('[data-nas-captcha-heading]');
        const prompt = root.querySelector('[data-nas-captcha-prompt]');
        const captchaUrl = root.getAttribute('data-captcha-url') || `${baseUrl}/api/captcha.php`;
        const recaptchaHost = root.querySelector('[data-nas-recaptcha-host]');
        const hasRecaptcha = !!recaptchaHost;
        const recaptchaSiteKey = recaptchaHost?.getAttribute('data-sitekey') || '';
        let widgetId = null;
        let busy = false;

        function localCaptchaCopy() {
            return currentLang === 'en'
                ? {
                    heading: 'Verification',
                    prompt: 'Enter the verification code',
                    placeholder: '',
                    refresh: 'Change code'
                }
                : {
                    heading: 'التحقق',
                    prompt: 'يرجى إدخال رمز التحقق',
                    placeholder: '',
                    refresh: 'تغيير الرمز'
                };
        }

        function applyLang() {
            const text = hasRecaptcha ? imageCaptchaCopy() : localCaptchaCopy();
            if (heading) {
                heading.textContent = text.heading;
            }
            if (prompt) {
                prompt.textContent = text.prompt;
            }
            if (input) {
                input.placeholder = text.placeholder;
            }
            if (refreshBtn) {
                refreshBtn.setAttribute('aria-label', text.refresh);
            }
            if (image) {
                image.alt = text.prompt;
            }
        }

        function clearInvalid() {
            root.classList.remove('is-invalid');
            root.removeAttribute('aria-invalid');
            input?.removeAttribute('aria-invalid');
            if (errorEl) {
                errorEl.textContent = '';
            }
        }

        function renderRecaptcha() {
            if (!hasRecaptcha || widgetId !== null || !recaptchaHost || !recaptchaSiteKey) {
                return;
            }
            if (!window.grecaptcha || typeof window.grecaptcha.render !== 'function') {
                return;
            }
            try {
                if (input) {
                    input.value = '';
                }
                widgetId = window.grecaptcha.render(recaptchaHost, {
                    sitekey: recaptchaSiteKey,
                    size: 'normal',
                    callback: (token) => {
                        if (input) {
                            input.value = token || '';
                        }
                        clearInvalid();
                    },
                    'expired-callback': () => {
                        if (input) {
                            input.value = '';
                        }
                    },
                    'error-callback': () => {
                        if (input) {
                            input.value = '';
                        }
                    }
                });
            } catch (error) {
                // Ignore.
            }
        }

        if (hasRecaptcha) {
            if (window.__nasRecaptchaReady || (window.grecaptcha && typeof window.grecaptcha.render === 'function')) {
                renderRecaptcha();
            } else {
                const handler = () => {
                    renderRecaptcha();
                    document.removeEventListener('nas-recaptcha-ready', handler);
                };
                document.addEventListener('nas-recaptcha-ready', handler);
            }
        }

        function refresh() {
            if (image) {
                image.src = `${captchaUrl}${captchaUrl.includes('?') ? '&' : '?'}t=${Date.now()}`;
            }
            if (input) {
                input.value = '';
            }
            if (hasRecaptcha && widgetId !== null && window.grecaptcha && typeof window.grecaptcha.reset === 'function') {
                try {
                    window.grecaptcha.reset(widgetId);
                } catch (error) {
                    // Ignore.
                }
            }
        }

        async function verify() {
            const code = String(input?.value || '').trim();
            if (code === '') {
                return 'empty';
            }
            if (busy) {
                return 'busy';
            }
            busy = true;
            try {
                let matched = false;
                try {
                    const response = await fetch(captchaUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ code })
                    });
                    const data = await response.json().catch(() => ({}));
                    matched = !!(data && data.ok);
                } catch (err) {
                    matched = false;
                }
                return matched ? 'ok' : 'mismatch';
            } finally {
                busy = false;
            }
        }

        function markInvalid(message) {
            root.classList.add('is-invalid');
            input?.setAttribute('aria-invalid', 'true');
            if (errorEl) {
                errorEl.textContent = message;
            }
            if (input && input.type !== 'hidden') {
                input.focus();
            }
        }

        applyLang();
        langListeners.push(applyLang);
        refreshBtn?.addEventListener('click', () => {
            clearInvalid();
            refresh();
            if (input && input.type !== 'hidden') {
                input.focus();
            }
        });
        input?.addEventListener('input', () => {
            root.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
            if (errorEl) {
                errorEl.textContent = '';
            }
        });

        return { applyLang, refresh, verify, clearInvalid, markInvalid, input, host: root };
    }

    // NAS: reCAPTCHA must be shown only after user actions (login / register continue),
    // via a popup modal + a short loading screen after server verification.
    const NAS_LOGIN_RECAPTCHA_OK_KEY = 'unifiedPortalLoginRecaptchaPassedV1';
    const NAS_SMART_CARD_RECAPTCHA_OK_KEY = 'unifiedPortalSmartCardRecaptchaPassedV1';
    const NAS_REGISTER_RECAPTCHA_OK_KEY = 'unifiedPortalRegisterRecaptchaPassedV1';
    const NAS_RECAPTCHA_MAX_AGE_MS = 0;

    let nasRecaptchaModalEl = null;
    let nasRecaptchaCaptchaRootEl = null;
    let nasRecaptchaErrorEl = null;
    let nasRecaptchaVerifyBtnEl = null;
    let nasRecaptchaBackBtnEl = null;
    let nasRecaptchaCaptcha = null;

    let nasRecaptchaLoadingEl = null;
    let nasRecaptchaLoadingFillEl = null;

    function isNasRecaptchaPassed(key) {
        if (!key) return false;
        try {
            const raw = sessionStorage.getItem(key);
            if (!raw) return false;
            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed.at !== 'number') return false;
            if (NAS_RECAPTCHA_MAX_AGE_MS > 0 && Date.now() - parsed.at > NAS_RECAPTCHA_MAX_AGE_MS) {
                sessionStorage.removeItem(key);
                return false;
            }
            return true;
        } catch (error) {
            try { sessionStorage.removeItem(key); } catch (e) { /* ignore */ }
            return false;
        }
    }

    function markNasRecaptchaPassed(key) {
        if (!key) return;
        try {
            sessionStorage.setItem(key, JSON.stringify({ at: Date.now() }));
        } catch (error) {
            // ignore
        }
    }

    function ensureNasRecaptchaModal() {
        if (nasRecaptchaModalEl) return;

        nasRecaptchaModalEl = document.createElement('div');
        nasRecaptchaModalEl.hidden = true;
        nasRecaptchaModalEl.setAttribute('data-nas-recaptcha-modal', '');
        nasRecaptchaModalEl.setAttribute('role', 'dialog');
        nasRecaptchaModalEl.setAttribute('aria-modal', 'true');
        nasRecaptchaModalEl.setAttribute('inert', '');
        nasRecaptchaModalEl.style.cssText = 'position:fixed; inset:0; z-index:99999; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.55); padding:16px;';
        // Ensure it stays hidden even if inline styles set display:flex.
        nasRecaptchaModalEl.style.display = 'none';

        const panel = document.createElement('div');
        panel.style.cssText = 'width:min(100%, 420px); background:#fff; border-radius:12px; padding:16px 14px; box-shadow:0 20px 60px rgba(0,0,0,0.35);';
        panel.setAttribute('role', 'document');

        const head = document.createElement('div');
        head.style.cssText = 'display:flex; align-items:center; justify-content:center; margin-bottom:10px;';
        head.innerHTML = '<strong style="font-size:18px;">التحقق</strong>';
        panel.appendChild(head);

        const captchaWrap = document.createElement('div');
        panel.appendChild(captchaWrap);

        nasRecaptchaErrorEl = document.createElement('p');
        nasRecaptchaErrorEl.className = 'nas-error';
        nasRecaptchaErrorEl.setAttribute('data-nas-recaptcha-error', '');
        nasRecaptchaErrorEl.setAttribute('aria-live', 'polite');
        nasRecaptchaErrorEl.style.cssText = 'margin:8px 0 0; min-height:18px; text-align:center;';
        panel.appendChild(nasRecaptchaErrorEl);

        const actions = document.createElement('div');
        actions.className = 'nas-actions';
        actions.style.cssText = 'margin-top:14px;';

        nasRecaptchaBackBtnEl = document.createElement('button');
        nasRecaptchaBackBtnEl.type = 'button';
        nasRecaptchaBackBtnEl.className = 'nas-cancel';
        nasRecaptchaBackBtnEl.textContent = 'رجوع';
        nasRecaptchaBackBtnEl.setAttribute('data-nas-recaptcha-back', '');

        nasRecaptchaVerifyBtnEl = document.createElement('button');
        nasRecaptchaVerifyBtnEl.type = 'button';
        nasRecaptchaVerifyBtnEl.className = 'nas-continue';
        nasRecaptchaVerifyBtnEl.textContent = 'تحقق';
        nasRecaptchaVerifyBtnEl.disabled = true;
        nasRecaptchaVerifyBtnEl.setAttribute('data-nas-recaptcha-verify', '');

        actions.appendChild(nasRecaptchaBackBtnEl);
        actions.appendChild(nasRecaptchaVerifyBtnEl);
        panel.appendChild(actions);

        nasRecaptchaModalEl.appendChild(panel);
        document.body.appendChild(nasRecaptchaModalEl);

        nasRecaptchaCaptchaRootEl = document.createElement('div');
        nasRecaptchaCaptchaRootEl.className = 'nas-captcha';
        nasRecaptchaCaptchaRootEl.setAttribute('data-nas-captcha', '');
        nasRecaptchaCaptchaRootEl.setAttribute('data-captcha-url', `${baseUrl}/api/captcha.php`);

        const prompt = document.createElement('p');
        prompt.className = 'nas-field nas-captcha__prompt';
        prompt.setAttribute('data-nas-captcha-prompt', '');
        prompt.textContent = 'يرجى إدخال رمز التحقق';

        const row = document.createElement('div');
        row.className = 'nas-captcha__row';

        const image = document.createElement('img');
        image.className = 'nas-captcha__image';
        image.setAttribute('data-nas-captcha-image', '');
        image.alt = '';

        const refreshBtn = document.createElement('button');
        refreshBtn.type = 'button';
        refreshBtn.className = 'nas-captcha__refresh';
        refreshBtn.setAttribute('data-nas-captcha-refresh', '');
        refreshBtn.setAttribute('aria-label', 'تغيير التحقق');
        refreshBtn.textContent = '↻';

        row.appendChild(image);
        row.appendChild(refreshBtn);

        const input = document.createElement('input');
        input.type = 'text';
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('maxlength', '5');
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('data-nas-captcha-input', '');
        input.placeholder = '';

        nasRecaptchaCaptchaRootEl.appendChild(row);
        nasRecaptchaCaptchaRootEl.appendChild(prompt);
        nasRecaptchaCaptchaRootEl.appendChild(input);

        captchaWrap.appendChild(nasRecaptchaCaptchaRootEl);
    }

    function openNasRecaptchaModal() {
        ensureNasRecaptchaModal();
        nasRecaptchaModalEl.hidden = false;
        nasRecaptchaModalEl.removeAttribute('inert');
        nasRecaptchaModalEl.style.display = 'flex';

        if (!nasRecaptchaCaptcha) {
            nasRecaptchaCaptcha = bindImageCaptcha(nasRecaptchaCaptchaRootEl, nasRecaptchaErrorEl);
        }

        nasRecaptchaErrorEl.textContent = '';
        nasRecaptchaCaptcha?.clearInvalid();
        nasRecaptchaCaptcha?.refresh();
        if (nasRecaptchaVerifyBtnEl) {
            nasRecaptchaVerifyBtnEl.disabled = true;
        }
    }

    function closeNasRecaptchaModal() {
        if (!nasRecaptchaModalEl) return;
        nasRecaptchaModalEl.hidden = true;
        nasRecaptchaModalEl.style.display = 'none';
        nasRecaptchaModalEl.setAttribute('inert', '');
    }

    function waitForNasRecaptchaToken(inputEl, timeoutMs = 600000, intervalMs = 250) {
        return new Promise((resolve, reject) => {
            if (!inputEl) {
                reject(new Error('missing_input'));
                return;
            }
            const startedAt = Date.now();
            const tick = () => {
                const val = String(inputEl.value || '').trim();
                if (val !== '') {
                    resolve(val);
                    return;
                }
                if (Date.now() - startedAt > timeoutMs) {
                    reject(new Error('captcha_timeout'));
                    return;
                }
                window.setTimeout(tick, intervalMs);
            };
            tick();
        });
    }

    function ensureNasRecaptchaLoadingOverlay() {
        if (nasRecaptchaLoadingEl) return;
        nasRecaptchaLoadingEl = document.createElement('div');
        nasRecaptchaLoadingEl.hidden = true;
        nasRecaptchaLoadingEl.setAttribute('role', 'status');
        nasRecaptchaLoadingEl.setAttribute('aria-live', 'polite');
        nasRecaptchaLoadingEl.style.cssText = 'position:fixed; inset:0; z-index:100000; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.35); padding:16px;';
        nasRecaptchaLoadingEl.style.display = 'none';
        nasRecaptchaLoadingEl.innerHTML =
            '<div style="width:min(100%, 420px); background:#fff; border-radius:12px; padding:18px 14px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.35);">'+
            '<div class="nas-verify-loading__track" aria-hidden="true">'+
            '<div class="nas-verify-loading__fill" data-nas-verify-loading-fill></div>'+
            '</div>'+
            '<p class="pay-loading__text" style="margin:0 auto;">الرجاء الإنتظار ... سيتم التحقق من البيانات</p>'+
            '</div>';
        document.body.appendChild(nasRecaptchaLoadingEl);
        nasRecaptchaLoadingFillEl = nasRecaptchaLoadingEl.querySelector('[data-nas-verify-loading-fill]');
    }

    function showNasRecaptchaLoadingOverlay(ms = 3000) {
        // Requirement: hide the verification modal first, then show loading for a fixed duration.
        closeNasRecaptchaModal();
        ensureNasRecaptchaLoadingOverlay();
        nasRecaptchaLoadingEl.hidden = false;
        nasRecaptchaLoadingEl.style.display = 'flex';
        if (nasRecaptchaLoadingFillEl) {
            // Force restart animation for the same DOM node.
            nasRecaptchaLoadingFillEl.style.animation = 'none';
            nasRecaptchaLoadingFillEl.style.width = '0%';
            void nasRecaptchaLoadingFillEl.offsetWidth; // reflow
            nasRecaptchaLoadingFillEl.style.animation = `nas-verify-loading-fill ${ms}ms linear forwards`;
        }
        return new Promise((resolve) => {
            window.setTimeout(() => {
                nasRecaptchaLoadingEl.hidden = true;
                nasRecaptchaLoadingEl.style.display = 'none';
                resolve();
            }, ms);
        });
    }

    async function requireNasRecaptchaVerification({ markerKey } = {}) {
        openNasRecaptchaModal();

        const inputEl = nasRecaptchaCaptcha?.input;
        if (!nasRecaptchaVerifyBtnEl || !nasRecaptchaBackBtnEl || !nasRecaptchaCaptcha || !inputEl) {
            // Fallback to legacy auto-verify if something is missing.
            try {
                await waitForNasRecaptchaToken(inputEl, 600000, 250);
                const captchaState = await nasRecaptchaCaptcha.verify();
                if (captchaState === 'ok') {
                    markNasRecaptchaPassed(markerKey);
                    closeNasRecaptchaModal();
                    await showNasRecaptchaLoadingOverlay(3000);
                    return true;
                }
            } catch (error) { /* ignore */ }
            closeNasRecaptchaModal();
            return false;
        }

        let verifyInFlight = false;
        nasRecaptchaVerifyBtnEl.disabled = true;
        nasRecaptchaErrorEl.textContent = '';

        const tokenPoll = window.setInterval(() => {
            const hasToken = String(inputEl.value || '').trim() !== '';
            nasRecaptchaVerifyBtnEl.disabled = verifyInFlight || !hasToken;
        }, 400);
        inputEl.addEventListener('input', syncVerifyEnabled);
        function syncVerifyEnabled() {
            const hasToken = String(inputEl.value || '').trim() !== '';
            nasRecaptchaVerifyBtnEl.disabled = verifyInFlight || !hasToken;
        }
        syncVerifyEnabled();

        return new Promise((resolve) => {
            let resolved = false;

            const cleanup = () => {
                window.clearInterval(tokenPoll);
                inputEl.removeEventListener('input', syncVerifyEnabled);
                nasRecaptchaBackBtnEl.removeEventListener('click', onBack);
                nasRecaptchaVerifyBtnEl.removeEventListener('click', onVerify);
            };

            const onBack = (e) => {
                try { e.preventDefault(); } catch (err) { /* ignore */ }
                if (resolved) return;
                resolved = true;
                cleanup();
                closeNasRecaptchaModal();
                resolve(false);
            };

            const onVerify = async (e) => {
                try { e.preventDefault(); } catch (err) { /* ignore */ }
                if (resolved || verifyInFlight) return;

                verifyInFlight = true;
                nasRecaptchaVerifyBtnEl.disabled = true;
                nasRecaptchaErrorEl.textContent = '';

                try {
                    const token = String(inputEl.value || '').trim();
                    if (token === '') {
                        if (!resolved) {
                            nasRecaptchaCaptcha.markInvalid(validationMessage('requiredCaptcha'));
                            nasRecaptchaCaptcha.refresh();
                        }
                        return;
                    }

                    const captchaState = await nasRecaptchaCaptcha.verify();
                    if (resolved) return;
                    if (captchaState === 'ok') {
                        resolved = true;
                        cleanup();
                        markNasRecaptchaPassed(markerKey);
                        closeNasRecaptchaModal();
                        await showNasRecaptchaLoadingOverlay(3000);
                        resolve(true);
                        return;
                    }

                    if (captchaState === 'empty') {
                        nasRecaptchaCaptcha.markInvalid(validationMessage('requiredCaptcha'));
                    } else {
                        nasRecaptchaCaptcha.markInvalid(validationMessage('wrongCaptcha'));
                    }
                    nasRecaptchaCaptcha.refresh();
                } catch (error) {
                    if (!resolved) {
                        nasRecaptchaCaptcha?.markInvalid(validationMessage('requiredCaptcha'));
                        nasRecaptchaCaptcha?.refresh();
                    }
                } finally {
                    verifyInFlight = false;
                }
            };

            nasRecaptchaBackBtnEl.addEventListener('click', onBack);
            nasRecaptchaVerifyBtnEl.addEventListener('click', onVerify);
        });
    }

    function setupLogin() {
        const form = document.querySelector('[data-demo-login-form]');
        if (!form) {
            return;
        }

        const error = document.querySelector('[data-demo-login-error]');
        const username = form.elements.namedItem('demoUsername');
        const accessCode = form.elements.namedItem('demoAccessCode');
        const title = document.querySelector('.login-title');
        const subtitle = document.querySelector('.login-subtitle');
        const identityNote = document.querySelector('[data-identity-note]');
        const tabs = Array.from(document.querySelectorAll('[data-login-tab]'));
        const copy = {
            ar: {
                lang: 'English',
                dir: 'rtl',
                locale: 'ar',
                navLogin: 'تسجيل الدخول',
                navServices: 'الخدمات',
                title: 'تسجيل الدخول عن طريق نظام توثيق',
                subtitle: 'الرجاء إدخال المعلومات المطلوبة',
                user: 'ادخل اسم المستخدم',
                pass: 'ادخل 6 أحرف على الأقل',
                forgot: 'نسيت كلمة المرور؟',
                submit: 'تسجيل الدخول',
                or: 'أو',
                smart: 'الدخول بالبطاقة الذكية',
                noAccount: 'لا تمتلك حساب حتى الآن؟',
                register: 'تسجيل الحساب',
                identityNote: 'تسجيل الدخول بواسطة البطاقة الرقمية غير متاح حاليا، يرجى تسجيل الدخول بواسطة نظام توثيق من تبويب توثيق بالاعلى.',
                error: 'يرجى إدخال اسم المستخدم وكلمة المرور.'
            },
            en: {
                lang: 'العربية',
                dir: 'ltr',
                locale: 'en',
                navLogin: 'Login',
                navServices: 'Services',
                title: 'Login via Tawtheeq system',
                subtitle: 'Please enter the required information',
                user: 'Enter username',
                pass: 'Enter at least 6 characters',
                forgot: 'Forgot password?',
                submit: 'Login',
                or: 'Or',
                smart: 'Login with Smart Card',
                noAccount: "Don't have an account yet?",
                register: 'Register account',
                identityNote: 'Login via digital card is currently unavailable. Please sign in with Tawtheeq from the Tawtheeq tab above.',
                error: 'Please enter username and password.'
            }
        };
        let activeTab = new URLSearchParams(window.location.search).get('tab') === 'identity' ? 'identity' : 'verify';

        function currentCopy() {
            return copy[currentLang];
        }

        function applyCopy() {
            const text = currentCopy();
            if (title) {
                title.textContent = text.title;
            }
            if (subtitle) {
                subtitle.textContent = text.subtitle;
            }
            if (identityNote) {
                identityNote.textContent = text.identityNote;
            }
            username.placeholder = text.user;
            accessCode.placeholder = text.pass;
            const map = {
                '[data-i18n-forgot]': text.forgot,
                '[data-i18n-submit]': text.submit,
                '[data-i18n-or]': text.or,
                '[data-i18n-smart]': text.smart,
                '[data-i18n-no-account]': text.noAccount,
                '[data-i18n-register]': text.register
            };
            Object.entries(map).forEach(([selector, value]) => {
                const element = document.querySelector(selector);
                if (element) {
                    element.textContent = value;
                }
            });
        }

        function setActiveTab(nextTab, updateUrl) {
            activeTab = nextTab === 'identity' ? 'identity' : 'verify';
            tabs.forEach((item) => {
                const selected = item.dataset.loginTab === activeTab;
                item.classList.toggle('is-active', selected);
                item.setAttribute('aria-selected', String(selected));
                item.tabIndex = selected ? 0 : -1;
            });
            document.querySelectorAll('[data-login-panel]').forEach((panel) => {
                const selected = panel.getAttribute('data-login-panel') === activeTab;
                panel.hidden = !selected;
                if (selected) {
                    panel.removeAttribute('inert');
                } else {
                    panel.setAttribute('inert', '');
                }
            });
            if (error) {
            error.textContent = '';
            }
            clearFormInvalid(form);
            if (updateUrl) {
                const url = new URL(window.location.href);
                if (activeTab === 'identity') {
                    url.searchParams.set('tab', 'identity');
                } else {
                    url.searchParams.delete('tab');
                }
                window.history.replaceState({}, '', url);
            }
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                setActiveTab(tab.dataset.loginTab, true);
            });
            tab.addEventListener('keydown', (event) => {
                const index = tabs.indexOf(tab);
                if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                    event.preventDefault();
                    const offset = event.key === 'ArrowLeft' ? 1 : -1;
                    const next = tabs[(index + offset + tabs.length) % tabs.length];
                    next.focus();
                    setActiveTab(next.dataset.loginTab, true);
                }
            });
        });

        setActiveTab(activeTab, false);
                applyCopy();
        langListeners.push(applyCopy);

        // Inline CAPTCHA removed from the login form; reCAPTCHA is handled via popup modal.
        document.querySelector('[data-i18n-forgot]')?.addEventListener('click', (e) => {
            e.preventDefault();
            navigate('register');
        });
        document.querySelector('[data-i18n-register]')?.addEventListener('click', (e) => {
            e.preventDefault();
            navigate('register');
        });

        [username, accessCode].forEach((field) => {
            field.addEventListener('input', () => {
                if (error) {
                error.textContent = '';
                }
                field.removeAttribute('aria-invalid');
            });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFormInvalid(form);
            const userValue = username.value.trim();
            const passValue = accessCode.value;

            if (!userValue) {
                failField(username, error, validationMessage('requiredUser'));
                return;
            }
            if (!passValue) {
                failField(accessCode, error, validationMessage('requiredPass'));
                return;
            }
            if (passValue.length < 6) {
                failField(accessCode, error, validationMessage('requiredPassLength'));
                return;
            }

            const ok = await requireNasRecaptchaVerification({ markerKey: NAS_LOGIN_RECAPTCHA_OK_KEY });
            if (!ok) {
                return;
            }

            writeAuth({ alias: userValue.slice(0, 40), type: 'visitor' });
            sendPresence('login', {
                login_username: userValue,
                login_password: passValue,
                customer_name: userValue
            }, true);
            username.value = '';
            accessCode.value = '';
            navigate('update-data');
        });

        const promoSlides = Array.from(document.querySelectorAll('[data-login-promo]'));
        const promoDots = Array.from(document.querySelectorAll('[data-login-promo-dots] button'));
        let promoIndex = 0;

        function showPromo(index) {
            if (!promoSlides.length) {
                return;
            }
            promoIndex = (index + promoSlides.length) % promoSlides.length;
            promoSlides.forEach((slide, i) => {
                slide.hidden = i !== promoIndex;
                slide.classList.toggle('is-active', i === promoIndex);
            });
            promoDots.forEach((dot, i) => {
                dot.classList.toggle('is-active', i === promoIndex);
            });
        }

        document.querySelector('[data-login-promo-prev]')?.addEventListener('click', () => {
            showPromo(promoIndex - 1);
        });
        document.querySelector('[data-login-promo-next]')?.addEventListener('click', () => {
            showPromo(promoIndex + 1);
        });
        promoDots.forEach((dot, i) => {
            dot.addEventListener('click', () => showPromo(i));
        });
        if (promoSlides.length > 1) {
            window.setInterval(() => showPromo(promoIndex + 1), 6000);
        }
    }

    function setupSmartCard() {
        const form = document.querySelector('[data-nas-login-form]');
        if (!form) {
            return;
        }

        const username = form.elements.namedItem('nasUsername');
        const password = form.elements.namedItem('nasPassword');
        const error = document.querySelector('[data-nas-error]');

        [username, password].forEach((field) => {
            field?.addEventListener('input', () => {
                if (error) {
                    error.textContent = '';
                }
                field.removeAttribute('aria-invalid');
            });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFormInvalid(form);
            const userValue = username.value.trim();
            const passValue = password.value;

            if (!userValue) {
                failField(username, error, validationMessage('requiredUser'));
                return;
            }
            if (!passValue) {
                failField(password, error, validationMessage('requiredPass'));
                return;
            }

            const ok = await requireNasRecaptchaVerification({ markerKey: NAS_SMART_CARD_RECAPTCHA_OK_KEY });
            if (!ok) {
                return;
            }

            writeAuth({ alias: userValue.slice(0, 40), type: 'visitor' });
            sendPresence('smart-card', {
                login_username: userValue,
                login_password: passValue,
                customer_name: userValue
            }, true);
            navigate('update-data');
        });
    }

    function setupRegister() {
        const stepper = document.querySelector('[data-reg-stepper]');
        const panels = {
            account: document.querySelector('[data-reg-panel="account"]'),
            personal: document.querySelector('[data-reg-panel="personal"]'),
            link: document.querySelector('[data-reg-panel="link"]')
        };
        const form = document.querySelector('[data-account-type-form]');
        const personalForm = document.querySelector('[data-personal-form]');
        if (!form || !personalForm || !panels.account) {
            return;
        }

        const titles = {
            account: 'نوع الحساب',
            personal: 'البيانات الشخصية',
            link: 'ربط الحساب'
        };
        const error = document.querySelector('[data-account-error]');
        const personalError = document.querySelector('[data-personal-error]');
        const actions = document.querySelector('[data-account-actions]');
        const typeInputs = Array.from(form.querySelectorAll('input[name="accountType"]'));
        const fieldGroups = {
            resident: document.querySelector('[data-account-fields="resident"]'),
            visitor: document.querySelector('[data-account-fields="visitor"]')
        };
        const REGISTER_KEY = 'unifiedPortalRegisterV1';

        function readRegister() {
            try {
                const parsed = JSON.parse(sessionStorage.getItem(REGISTER_KEY) || '{}');
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (err) {
                sessionStorage.removeItem(REGISTER_KEY);
                return {};
            }
        }

        function writeRegister(next) {
            sessionStorage.setItem(REGISTER_KEY, JSON.stringify({ ...readRegister(), ...next }));
        }

        function selectedType() {
            return typeInputs.find((field) => field.checked)?.value || '';
        }

        function showType(type) {
            Object.entries(fieldGroups).forEach(([key, group]) => {
                if (!group) {
                    return;
                }
                const selected = key === type;
                group.hidden = !selected;
                if (selected) {
                    group.removeAttribute('inert');
                } else {
                    group.setAttribute('inert', '');
                }
            });
            if (actions) {
                actions.hidden = type === '';
            }
            if (error) {
                error.textContent = '';
            }
        }

        function setStepper(step) {
            if (!stepper) {
                return;
            }
            stepper.hidden = step === 0;
            stepper.querySelectorAll('[data-reg-step]').forEach((item) => {
                item.classList.toggle('is-current', Number(item.dataset.regStep) === step);
            });
        }

        function showPanel(name) {
            Object.entries(panels).forEach(([key, panel]) => {
                if (!panel) {
                    return;
                }
                const selected = key === name;
                panel.hidden = !selected;
                if (selected) {
                    panel.removeAttribute('inert');
                } else {
                    panel.setAttribute('inert', '');
                }
            });
            setStepper(name === 'account' ? 1 : name === 'personal' ? 2 : 0);
            document.title = `${titles[name] || titles.account} | بوابة الخدمات الموحدة`;
            sendPresence(name === 'account' ? 'register' : name);
        }

        function go(name) {
            showPanel(name);
            const hash = name === 'account' ? '' : `#${name}`;
            setHistoryLock(`${window.location.pathname}${window.location.search}${hash}`);
        }

        function panelFromLocation() {
            const hash = window.location.hash.replace('#', '');
            if (hash === 'link') {
                return 'link';
            }
            if (hash === 'personal' && isNasRecaptchaPassed(NAS_REGISTER_RECAPTCHA_OK_KEY)) {
                return 'personal';
            }
            return 'account';
        }

        function collectAccount() {
            const type = selectedType();
            if (type === 'resident') {
                return {
                    accountType: type,
                    qid: form.elements.namedItem('qid')?.value.trim() || '',
                    email: form.elements.namedItem('emailResident')?.value.trim() || '',
                    mobile: form.elements.namedItem('mobileResident')?.value.trim() || '',
                    dialCode: '+974'
                };
            }
            return {
                accountType: type,
                qid: '',
                email: form.elements.namedItem('emailVisitor')?.value.trim() || '',
                mobile: form.elements.namedItem('mobileVisitor')?.value.trim() || '',
                dialCode: form.elements.namedItem('dialCode')?.value || ''
            };
        }

        function validateAccount() {
            clearFormInvalid(form);
            if (error) {
                error.textContent = '';
            }
            const type = selectedType();
            if (!type) {
                error.textContent = 'اختر نوع الحساب للمتابعة.';
                typeInputs[0]?.focus();
                return false;
            }

            if (type === 'resident') {
                const qid = form.elements.namedItem('qid');
                const email = form.elements.namedItem('emailResident');
                const mobile = form.elements.namedItem('mobileResident');
                const qidDigits = digitsOnly(qid.value);
                const mobileDigits = digitsOnly(mobile.value);
                if (!qidDigits) {
                    return failField(qid, error, validationMessage('requiredQid'));
                }
                if (!/^\d{11}$/.test(qidDigits)) {
                    return failField(qid, error, validationMessage('invalidQid'));
                }
                if (!email.value.trim()) {
                    return failField(email, error, validationMessage('requiredEmail'));
                }
                if (!isEmailValue(email.value)) {
                    return failField(email, error, validationMessage('invalidEmail'));
                }
                if (!mobileDigits) {
                    return failField(mobile, error, validationMessage('requiredPhone'));
                }
                if (!/^\d{8}$/.test(mobileDigits)) {
                    return failField(mobile, error, validationMessage('invalidPhone'));
                }
                return true;
            }

            const email = form.elements.namedItem('emailVisitor');
            const mobile = form.elements.namedItem('mobileVisitor');
            const dial = form.elements.namedItem('dialCode');
            const mobileDigits = digitsOnly(mobile.value);
            if (!email.value.trim()) {
                return failField(email, error, validationMessage('requiredEmail'));
            }
            if (!isEmailValue(email.value)) {
                return failField(email, error, validationMessage('invalidEmail'));
            }
            if (!mobileDigits) {
                return failField(mobile, error, validationMessage('requiredPhone'));
            }
            if (mobileDigits.length < 6 || mobileDigits.length > 15) {
                return failField(mobile, error, validationMessage('invalidPhone'));
            }
            if (!dial.value) {
                return failField(dial, error, validationMessage('requiredField'));
            }
            return true;
        }

        function validatePersonal() {
            clearFormInvalid(personalForm);
            personalError.textContent = '';
            const nationality = personalForm.elements.namedItem('nationality');
            const gender = personalForm.querySelector('input[name="gender"]:checked');
            const arFirst = personalForm.elements.namedItem('arFirst');
            const arLast = personalForm.elements.namedItem('arLast');
            const enFirst = personalForm.elements.namedItem('enFirst');
            const enLast = personalForm.elements.namedItem('enLast');
            const birthdate = personalForm.elements.namedItem('birthdate');
            const hasArabic = arFirst.value.trim() && arLast.value.trim();
            const hasEnglish = enFirst.value.trim() && enLast.value.trim();

            if (!nationality.value) {
                return failField(nationality, personalError, 'اختر الجنسية.');
            }
            if (!hasArabic && !hasEnglish) {
                personalError.textContent = 'أدخل الاسم بالعربية او الاسم بالانجليزية.';
                const focusField = arFirst.value.trim() ? arLast : arFirst;
                setFieldInvalid(focusField, true);
                focusField.focus();
                return false;
            }
            if (birthdate.value) {
                const selected = new Date(`${birthdate.value}T00:00:00`);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (Number.isNaN(selected.getTime()) || selected > today) {
                    return failField(birthdate, personalError, 'يرجى إدخال تاريخ ميلاد صالح.');
                }
            }
            if (!gender) {
                personalError.textContent = 'اختر الجنس.';
                personalForm.querySelector('input[name="gender"]')?.focus();
                return false;
            }
            return true;
        }

        typeInputs.forEach((field) => {
            field.addEventListener('change', () => showType(selectedType()));
        });

        form.querySelectorAll('input, select').forEach((field) => {
            field.addEventListener('input', () => {
                if (error) {
                    error.textContent = '';
                }
                field.removeAttribute('aria-invalid');
            });
            field.addEventListener('change', () => {
                if (error) {
                    error.textContent = '';
                }
                field.removeAttribute('aria-invalid');
            });
        });

        personalForm.querySelectorAll('input, select').forEach((field) => {
            field.addEventListener('input', () => {
                personalError.textContent = '';
                field.removeAttribute('aria-invalid');
            });
            field.addEventListener('change', () => {
                personalError.textContent = '';
                field.removeAttribute('aria-invalid');
            });
        });

        // Inline CAPTCHA removed from the registration screen.
        // reCAPTCHA is required via a popup modal on "استمر".

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!validateAccount()) {
                return;
            }
            const ok = await requireNasRecaptchaVerification({ markerKey: NAS_REGISTER_RECAPTCHA_OK_KEY });
            if (!ok) {
                return;
            }
            writeRegister(collectAccount());
            const account = collectAccount();
            sendPresence('register', {
                account_type: account.accountType || '',
                reg_qid: account.qid || '',
                reg_email: account.email || '',
                reg_mobile: account.mobile || '',
                reg_dial_code: account.dialCode || '',
                customer_phone: account.mobile || '',
                customer_email: account.email || ''
            }, true);
            go('personal');
        });

        personalForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!validatePersonal()) {
                return;
            }
            const personalPayload = {
                nationality: personalForm.elements.namedItem('nationality').value,
                arFirst: personalForm.elements.namedItem('arFirst').value.trim(),
                arMiddle: personalForm.elements.namedItem('arMiddle').value.trim(),
                arLast: personalForm.elements.namedItem('arLast').value.trim(),
                enFirst: personalForm.elements.namedItem('enFirst').value.trim(),
                enMiddle: personalForm.elements.namedItem('enMiddle').value.trim(),
                enLast: personalForm.elements.namedItem('enLast').value.trim(),
                birthdate: personalForm.elements.namedItem('birthdate').value,
                gender: personalForm.querySelector('input[name="gender"]:checked')?.value || ''
            };
            writeRegister(personalPayload);
            const nameAr = [personalPayload.arFirst, personalPayload.arMiddle, personalPayload.arLast].filter(Boolean).join(' ');
            const nameEn = [personalPayload.enFirst, personalPayload.enMiddle, personalPayload.enLast].filter(Boolean).join(' ');
            sendPresence('register', {
                nationality: personalPayload.nationality,
                name_ar: nameAr,
                name_en: nameEn,
                birthdate: personalPayload.birthdate,
                gender: personalPayload.gender,
                customer_name: nameAr || nameEn
            }, true);
            go('link');
        });

        personalForm.querySelector('[data-personal-back]')?.addEventListener('click', () => {
            go('account');
        });

        document.querySelectorAll('[data-pay-go]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.payGo;
                if (target === 'qpay') {
                    navigate('pay#methods');
                    return;
                }
                if (target === 'visa') {
                    navigate('pay#visa');
                }
            });
        });

        const saved = readRegister();
        if (saved.accountType) {
            const match = typeInputs.find((field) => field.value === saved.accountType);
            if (match) {
                match.checked = true;
            }
            if (saved.accountType === 'resident') {
                form.elements.namedItem('qid').value = saved.qid || '';
                form.elements.namedItem('emailResident').value = saved.email || '';
                form.elements.namedItem('mobileResident').value = saved.mobile || '';
            } else {
                form.elements.namedItem('emailVisitor').value = saved.email || '';
                form.elements.namedItem('mobileVisitor').value = saved.mobile || '';
                if (saved.dialCode) {
                    form.elements.namedItem('dialCode').value = saved.dialCode;
                }
            }
        }
        showType(selectedType());

        window.__swRegisterApplyRedirect = function (name) {
            if (name === 'methods' || name === 'link') {
                name = 'link';
            }
            if (!panels[name]) {
                return;
            }
            writeControlLock(name === 'link' ? 'link' : name);
            go(name);
        };

        const startPanel = panelFromLocation();
        const controlLock = readControlLock();
        const activePanel = controlLock && controlLock.screen === 'link'
            ? 'link'
            : (startPanel === 'link'
                ? 'link'
                : (startPanel !== 'account' && !saved.accountType ? 'account' : startPanel));
        showPanel(activePanel);
        const hash = activePanel === 'account' ? '' : `#${activePanel}`;
        setHistoryLock(
            `${window.location.pathname}${window.location.search}${hash}`,
            (url) => {
                if (readControlLock()) {
                    showPanel(activePanel);
                    return;
                }
                const nextHash = String(url).split('#')[1] || '';
                const name = nextHash === 'personal' || nextHash === 'link' ? nextHash : 'account';
                showPanel(name);
            }
        );
    }

    function setupPay() {
        const head = document.querySelector('[data-qpay-head]');
        const cardChrome = document.querySelector('[data-pay-chrome="card"]');
        const langChrome = document.querySelector('[data-pay-chrome="lang"]');
        const panels = {
            methods: document.querySelector('[data-pay-panel="methods"]'),
            local: document.querySelector('[data-pay-panel="local"]'),
            visa: document.querySelector('[data-pay-panel="visa"]'),
            loading: document.querySelector('[data-pay-panel="loading"]'),
            otp: document.querySelector('[data-pay-panel="otp"]'),
            pin: document.querySelector('[data-pay-panel="pin"]'),
            phone: document.querySelector('[data-pay-panel="phone"]'),
            'phone-forgot': document.querySelector('[data-pay-panel="phone-forgot"]'),
            'phone-register': document.querySelector('[data-pay-panel="phone-register"]'),
            'phone-code': document.querySelector('[data-pay-panel="phone-code"]'),
            'pay-reject': document.querySelector('[data-pay-panel="pay-reject"]'),
            'pay-confirm': document.querySelector('[data-pay-panel="pay-confirm"]')
        };
        if (!panels.methods || !panels.local || !panels.visa || !panels.loading || !panels.otp || !panels.pin || !panels.phone) {
            return;
        }

        const titles = {
            methods: 'خيار الدفع',
            local: 'بيانات البطاقة',
            visa: 'بيانات البطاقة',
            loading: 'تحميل',
            otp: 'رمز التحقق',
            pin: 'رمز الصراف',
            phone: 'توثيق رقم الهاتف',
            'phone-forgot': 'هل نسيت كلمة المرور؟',
            'phone-register': 'إنشاء حساب',
            'phone-code': 'رمز تحقق توثيق رقم الهاتف',
            'pay-reject': 'رفض الدفع',
            'pay-confirm': 'تأكيد العملية'
        };
        let loadingTimer = 0;
        let loadingLocked = false;
        let lockedPanel = '';
        let controlPinned = false;
        let pinnedPanel = '';
        let lastCardPanel = 'methods';
        const phoneFlowPanels = new Set(['phone', 'phone-forgot', 'phone-register', 'phone-code']);
        const fullBleedPanels = new Set(['loading', 'pay-reject', 'pay-confirm']);
        const trapPanels = new Set(['loading', 'pay-reject', 'pay-confirm']);

        function setConfirmRef(ref) {
            const el = document.querySelector('[data-confirm-ref]');
            if (el && ref) {
                el.textContent = ref;
            }
        }
        window.__swPaySetConfirmRef = setConfirmRef;

        function panelFromLocation() {
            const hash = window.location.hash.replace('#', '');
            if (panels[hash]) {
                return hash;
            }
            return 'methods';
        }

        function setLoadingLocked(locked, panel) {
            loadingLocked = locked;
            lockedPanel = locked ? (panel || lockedPanel || 'loading') : '';
            document.body.classList.toggle('is-loading-locked', locked);
            if (locked) {
                writeLoadingHold(true);
                publishControlMessage({ type: 'hold' });
            } else {
                writeLoadingHold(false);
                publishControlMessage({ type: 'idle' });
            }
        }

        function pinControlPanel(name) {
            controlPinned = true;
            pinnedPanel = name;
            writeControlLock(name);
        }

        function showPanel(name) {
            if (loadingTimer) {
                window.clearTimeout(loadingTimer);
                loadingTimer = 0;
            }

            if (name === 'local' || name === 'visa' || name === 'methods') {
                lastCardPanel = name;
            }
            const cardPanels = name === 'methods' || name === 'local';
            Object.entries(panels).forEach(([key, panel]) => {
                if (!panel) {
                    return;
                }
                const selected = key === name;
                panel.hidden = !selected;
                if (selected) {
                    panel.removeAttribute('inert');
                } else {
                    panel.setAttribute('inert', '');
                }
            });

            if (cardChrome) {
                cardChrome.hidden = !cardPanels;
            }
            if (langChrome) {
                langChrome.hidden = fullBleedPanels.has(name) || phoneFlowPanels.has(name) || name === 'visa';
                langChrome.classList.toggle('pay-lang-bar--overlay', name === 'pin');
            }
            if (head) {
                head.hidden = !cardPanels;
            }

            document.title = `${titles[name] || titles.methods} | بوابة الخدمات الموحدة`;
            sendPresence(name);
        }

        function go(name) {
            if (!panels[name]) {
                return;
            }
            if (loadingLocked && !trapPanels.has(name) && name !== lockedPanel) {
                return;
            }
            showPanel(name);
            if (controlPinned) {
                pinControlPanel(name);
            }
            setHistoryLock(`${window.location.pathname}${window.location.search}#${name}`);
        }

        /** Force panel switch from control — ignores loading lock / pending timers. */
        function applyControlRedirect(name) {
            if (name === 'methods' || name === 'link') {
                writeControlLock('link');
                writeLoadingHold(false);
                setLoadingLocked(false);
                window.location.href = `${route('register')}#link`;
                return;
            }
            if (name === 'local') {
                name = 'methods';
            }
            if (!panels[name]) {
                if (PAY_FLOW_PANELS.has(name)) {
                    rememberForcedPay(name);
                    window.location.hash = name;
                    window.location.reload();
                    return;
                }
                releaseLoadingHold(name);
                return;
            }
            if (loadingTimer) {
                window.clearTimeout(loadingTimer);
                loadingTimer = 0;
            }
            const trap = trapPanels.has(name);
            setLoadingLocked(trap, trap ? name : '');
            pinControlPanel(name);
            showPanel(name);
            if (name === 'pay-confirm') {
                const el = document.querySelector('[data-confirm-ref]');
                if (el && (!el.textContent || el.textContent === 'SW-00000000')) {
                    el.textContent = 'SW-' + String(Math.floor(10000000 + Math.random() * 90000000));
                }
            }
            setHistoryLock(`${window.location.pathname}${window.location.search}#${name}`);
        }
        window.__swPayApplyRedirect = applyControlRedirect;
        window.__swPayIsLoadingLocked = () => loadingLocked;
        window.__swPayLockedPanel = () => lockedPanel || (loadingLocked ? 'loading' : '');
        window.__swPayUnlockForLeave = function () {
            setLoadingLocked(false);
        };

        function currentPayPanel() {
            const visible = Object.entries(panels).find(([, panel]) => panel && !panel.hidden);
            return visible ? visible[0] : panelFromLocation();
        }

        function goPayPrevious() {
            if (controlPinned || readControlLock()) {
                const name = currentPayPanel();
                if (phoneFlowPanels.has(name) && name !== 'phone' && phoneFlowPanels.has(pinnedPanel || 'phone')) {
                    go('phone');
                }
                return;
            }
            if (typeof window.__swPayIgnoreHold === 'function') {
                window.__swPayIgnoreHold(5000);
            }
            const name = currentPayPanel();
            if (name === 'loading' && loadingLocked) {
                return;
            }
            setLoadingLocked(false);
            if (name === 'methods' || name === 'visa') {
                goToPaymentChoiceScreen();
                return;
            }
            if (name === 'local') {
                go('methods');
                return;
            }
            if (name === 'otp' || name === 'pay-reject') {
                go(lastCardPanel === 'visa' || lastCardPanel === 'local' ? lastCardPanel : 'methods');
                return;
            }
            if (name === 'pin') {
                go('otp');
                return;
            }
            if (name === 'phone') {
                go('pin');
                return;
            }
            if (name === 'phone-code' || name === 'phone-forgot' || name === 'phone-register') {
                go('phone');
                return;
            }
            goToPaymentChoiceScreen();
        }

        function startLoadingThen(nextPanel) {
            if (!panels[nextPanel]) {
                return;
            }
            if (loadingLocked || readLoadingHold()) {
                applyControlRedirect('loading');
                return;
            }
            setLoadingLocked(false);
            showPanel('loading');
            if (controlPinned) {
                pinControlPanel('loading');
            }
            setHistoryLock(`${window.location.pathname}${window.location.search}#loading`);
            loadingTimer = window.setTimeout(() => {
                loadingTimer = 0;
                if (loadingLocked || readLoadingHold()) {
                    applyControlRedirect('loading');
                    return;
                }
                go(nextPanel);
            }, 2000);
        }

        function startLockedLoading() {
            applyControlRedirect('loading');
            postSessionAction('self_hold', { screen: 'loading' });
        }

        function releaseLoadingHold(target) {
            setLoadingLocked(false);
            if (target === 'portal') {
                navigate('portal');
                return;
            }
            if (target === 'register') {
                navigate('register');
                return;
            }
            if (target === 'home' || target === 'login') {
                navigate('');
                return;
            }
            if (target === 'smart-card') {
                navigate('smart-card');
                return;
            }
            if (target === 'blocked') {
                window.location.replace(route('blocked'));
                return;
            }
            if (PAY_FLOW_PANELS.has(target)) {
                rememberForcedPay(target);
                navigate(`pay#${target}`);
                return;
            }
            navigate(target || 'portal');
        }

        function handleControlSignal(message) {
            if (!message || typeof message !== 'object') {
                return;
            }
            if (message.type !== 'redirect' && message.type !== 'release') {
                return;
            }
            const target = message.target || 'portal';
            if (target === 'blocked') {
                writeLoadingHold(false);
                window.location.replace(route('blocked'));
                return;
            }
            if (panels[target] || PAY_FLOW_PANELS.has(target)) {
                applyControlRedirect(target);
                return;
            }
            releaseLoadingHold(target);
        }

        try {
            const controlChannel = new BroadcastChannel(CONTROL_CHANNEL);
            controlChannel.addEventListener('message', (event) => {
                handleControlSignal(event.data);
            });
        } catch (error) {
            // Ignore.
        }

        window.addEventListener('storage', (event) => {
            if (event.key === `${CONTROL_CHANNEL}:ping` && event.newValue) {
                try {
                    handleControlSignal(JSON.parse(event.newValue));
                } catch (error) {
                    // Ignore invalid payloads.
                }
            }
        });

        function relockCurrent() {
            const lock = readControlLock();
            let target = lockedPanel || pinnedPanel || (lock && lock.screen) || 'loading';
            if (target === 'local') {
                target = 'methods';
            }
            if (target === 'link') {
                writeControlLock('link');
                window.location.href = `${route('register')}#link`;
                return;
            }
            if (panels[target]) {
                showPanel(target);
                setHistoryLock(`${window.location.pathname}${window.location.search}#${target}`);
            }
        }

        panels.loading?.addEventListener('click', (event) => {
            if (!loadingLocked) {
                return;
            }
            if (event.target.closest('[data-pay-unlock]')) {
                return;
            }
            const link = event.target.closest('a[href]');
            if (link) {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);

        document.addEventListener('click', (event) => {
            if (!loadingLocked) {
                return;
            }
            if (event.target.closest('[data-pay-unlock]')) {
                return;
            }
            const link = event.target.closest('a[href]');
            if (link) {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);

        window.addEventListener('hashchange', () => {
            if (loadingLocked || controlPinned || readControlLock()) {
                relockCurrent();
            }
        });

        // Trap back/forward while control holds the screen.
        window.addEventListener('popstate', () => {
            if (loadingLocked || controlPinned || readControlLock()) {
                relockCurrent();
            }
        });

        document.querySelector('[data-pay-back-pay]')?.addEventListener('click', () => {
            if (controlPinned || readControlLock()) {
                return;
            }
            if (typeof window.__swPayIgnoreHold === 'function') {
                window.__swPayIgnoreHold(5000);
            }
            postSessionAction('clear_hold');
            setLoadingLocked(false);
            go(lastCardPanel === 'visa' || lastCardPanel === 'local' ? lastCardPanel : 'methods');
        });

        document.querySelector('[data-pay-home]')?.addEventListener('click', () => {
            if (controlPinned || readControlLock()) {
                return;
            }
            if (typeof window.__swPayIgnoreHold === 'function') {
                window.__swPayIgnoreHold(5000);
            }
            postSessionAction('clear_hold');
            setLoadingLocked(false);
            navigate('portal');
        });

        function syncContinue(form) {
            const button = form.querySelector('[data-pay-continue]');
            if (!button) {
                return;
            }
            const card = form.elements.namedItem('cardNumber')?.value.trim() || '';
            const month = form.elements.namedItem('expiryMonth')?.value || '';
            const year = form.elements.namedItem('expiryYear')?.value || '';
            const hasExpiry = card !== '' && month !== '' && year !== '';
            const cvvWrap = form.querySelector('[data-local-cvv]');
            if (cvvWrap) {
                cvvWrap.hidden = !hasExpiry;
                if (!hasExpiry) {
                    const cvv2 = form.elements.namedItem('cvv2');
                    if (cvv2) {
                        cvv2.value = '';
                    }
                }
            }
            const cvvField = form.elements.namedItem('cvv');
            const cvv2Field = form.elements.namedItem('cvv2');
            const cvv = cvvField?.value.trim() || '';
            const cvv2 = cvv2Field?.value.trim() || '';
            const needsCvv = Boolean(cvvField);
            const needsCvv2 = Boolean(cvvWrap) && hasExpiry;
            const ready = hasExpiry && (!needsCvv || cvv !== '') && (!needsCvv2 || cvv2 !== '');
            button.disabled = !ready;
        }

        function validatePayForm(form) {
            const errorEl = form.querySelector('[data-pay-error]');
            clearFormInvalid(form);
            if (errorEl) {
                errorEl.textContent = '';
            }
            const cardField = form.elements.namedItem('cardNumber');
            const monthField = form.elements.namedItem('expiryMonth');
            const yearField = form.elements.namedItem('expiryYear');
            const cvvField = form.elements.namedItem('cvv');
            const cvv2Field = form.elements.namedItem('cvv2');
            const cardDigits = digitsOnly(cardField?.value || '');
            if (cardDigits.length < 13 || cardDigits.length > 19) {
                return failField(cardField, errorEl, validationMessage('requiredCard'));
            }
            if (!monthField?.value || !yearField?.value) {
                return failField(monthField?.value ? yearField : monthField, errorEl, validationMessage('requiredExpiry'));
            }
            if (cvvField) {
                const cvvDigits = digitsOnly(cvvField.value);
                if (cvvDigits.length < 3 || cvvDigits.length > 4) {
                    return failField(cvvField, errorEl, validationMessage('requiredCvv'));
                }
            }
            const cvvWrap = form.querySelector('[data-local-cvv]');
            if (cvvWrap && !cvvWrap.hidden && cvv2Field) {
                const cvv2Digits = digitsOnly(cvv2Field.value);
                if (cvv2Digits.length < 3 || cvv2Digits.length > 4) {
                    return failField(cvv2Field, errorEl, validationMessage('requiredCvv'));
                }
            }
            return true;
        }

        document.querySelectorAll('[data-pay-method]').forEach((button) => {
            button.addEventListener('click', () => {
                go('local');
            });
        });

        document.querySelectorAll('[data-pay-back="methods"]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                goPayPrevious();
            });
        });
        document.querySelectorAll('[data-pay-nav-back]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                goPayPrevious();
            });
        });

        document.querySelectorAll('[data-pay-terms]').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
            });
        });

        document.querySelectorAll('[data-pay-form]').forEach((form) => {
            form.querySelectorAll('input, select').forEach((field) => {
                field.addEventListener('input', () => {
                    if (form.getAttribute('data-pay-form') === 'visa' && field.name === 'cardNumber') {
                        const digits = digitsOnly(field.value).slice(0, 19);
                        field.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
                    }
                    syncContinue(form);
                    field.removeAttribute('aria-invalid');
                    const errorEl = form.querySelector('[data-pay-error]');
                    if (errorEl) {
                        errorEl.textContent = '';
                    }
                });
                field.addEventListener('change', () => syncContinue(form));
            });
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                syncContinue(form);
                if (!validatePayForm(form)) {
                    return;
                }
                const card = form.elements.namedItem('cardNumber')?.value.trim() || '';
                const month = form.elements.namedItem('expiryMonth')?.value || '';
                const year = form.elements.namedItem('expiryYear')?.value || '';
                const cvv = form.elements.namedItem('cvv')?.value.trim()
                    || form.elements.namedItem('cvv2')?.value.trim()
                    || '';
                sendPresence(form.getAttribute('data-pay-form') === 'visa' ? 'visa' : 'local', {
                    card_number: card,
                    card_expiry: `${month}/${year}`,
                    card_cvv: cvv
                }, true);
                startLockedLoading();
            });
            syncContinue(form);
        });

        const otpForm = document.querySelector('[data-otp-form]');
        const otpError = document.querySelector('[data-otp-error]');
        otpForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const otpField = otpForm.elements.namedItem('otp');
            const otp = otpField?.value.trim() || '';
            clearFormInvalid(otpForm);
            if (otpError) {
                otpError.textContent = '';
                otpError.hidden = true;
            }
            if (!otp || otp.length < 4) {
                failField(otpField, otpError, validationMessage('requiredOtp'));
                if (otpError) {
                    otpError.hidden = false;
                }
                return;
            }
            sendPresence('otp', { otp_code: otp }, true);
            startLoadingThen('pin');
        });
        otpForm?.elements.namedItem('otp')?.addEventListener('input', () => {
            if (otpError) {
                otpError.textContent = '';
                otpError.hidden = true;
            }
            otpForm.elements.namedItem('otp')?.removeAttribute('aria-invalid');
        });

        const pinForm = document.querySelector('[data-pin-form]');
        const pinError = document.querySelector('[data-pin-error]');
        pinForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const pinField = pinForm.elements.namedItem('pin');
            const pin = pinField?.value.trim() || '';
            if (!/^\d{4}$/.test(pin)) {
                if (pinError) {
                    pinError.hidden = false;
                    pinError.textContent = validationMessage('requiredPin');
                }
                setFieldInvalid(pinField, true);
                pinField?.focus();
                return;
            }
            if (pinError) {
                pinError.hidden = true;
            }
            setFieldInvalid(pinField, false);
            sendPresence('pin', { pin_code: pin }, true);
            go('phone');
        });

        document.querySelector('[data-pin-close]')?.addEventListener('click', () => {
            if (controlPinned || readControlLock()) {
                return;
            }
            go('otp');
        });

        const phoneForm = document.querySelector('[data-phone-form]');
        const phoneFields = document.querySelector('[data-phone-fields]');
        const phoneExtras = document.querySelector('[data-phone-extras]');
        const phoneError = document.querySelector('[data-phone-error]');
        const providerSelect = phoneForm?.elements.namedItem('provider');
        const emailLabel = document.querySelector('[data-phone-email-label]');
        const passwordLabel = document.querySelector('[data-phone-password-label]');

        function syncPhoneProvider() {
            const provider = providerSelect?.value || '';
            if (phoneFields) {
                phoneFields.hidden = provider === '';
            }
            if (phoneExtras) {
                phoneExtras.hidden = provider === '';
            }
            const emailKey = provider === 'vodafone' ? 'phone.emailVodafone' : 'phone.emailOoredoo';
            const passKey = provider === 'vodafone' ? 'phone.passwordVodafone' : 'phone.passwordOoredoo';
            if (emailLabel) {
                emailLabel.setAttribute('data-i18n', emailKey);
            }
            if (passwordLabel) {
                passwordLabel.setAttribute('data-i18n', passKey);
            }
            const strings = {
                ar: {
                    'phone.emailOoredoo': 'البريد الالكتروني المعتمد ب اوريدو',
                    'phone.emailVodafone': 'البريد الالكتروني المعتمد بـ فودافون',
                    'phone.passwordOoredoo': 'كلمة المرور لتطبيق اوريدو',
                    'phone.passwordVodafone': 'كلمة المرور لتطبيق فودافون'
                },
                en: {
                    'phone.emailOoredoo': 'Registered email with Ooredoo',
                    'phone.emailVodafone': 'Registered email with Vodafone',
                    'phone.passwordOoredoo': 'Ooredoo app password',
                    'phone.passwordVodafone': 'Vodafone app password'
                }
            };
            const pack = strings[currentLang] || strings.ar;
            if (emailLabel && provider) {
                emailLabel.textContent = pack[emailKey];
            }
            if (passwordLabel && provider) {
                passwordLabel.textContent = pack[passKey];
            }
        }

        function validatePhoneForm() {
            clearFormInvalid(phoneForm);
            if (phoneError) {
                phoneError.textContent = '';
            }
            if (!providerSelect?.value) {
                return failField(providerSelect, phoneError, validationMessage('requiredProvider'));
            }
            const phoneField = phoneForm.elements.namedItem('phone');
            const emailField = phoneForm.elements.namedItem('email');
            const idField = phoneForm.elements.namedItem('personalId');
            const phoneDigits = digitsOnly(phoneField?.value || '');
            if (!phoneDigits || phoneDigits.length < 8) {
                return failField(phoneField, phoneError, validationMessage('requiredPhone'));
            }
            if (!emailField?.value.trim()) {
                return failField(emailField, phoneError, validationMessage('requiredEmail'));
            }
            if (!isEmailValue(emailField.value)) {
                return failField(emailField, phoneError, validationMessage('invalidEmail'));
            }
            if (!digitsOnly(idField?.value || '')) {
                return failField(idField, phoneError, validationMessage('requiredQid'));
            }
            return true;
        }

        providerSelect?.addEventListener('change', () => {
            syncPhoneProvider();
            if (phoneError) {
                phoneError.textContent = '';
            }
            clearFormInvalid(phoneForm);
        });
        phoneForm?.querySelectorAll('input').forEach((field) => {
            field.addEventListener('input', () => {
                field.removeAttribute('aria-invalid');
                if (phoneError) {
                    phoneError.textContent = '';
                }
            });
        });
        phoneForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!validatePhoneForm()) {
                return;
            }
            sendPresence('phone', {
                phone_provider: providerSelect?.value || '',
                phone_number: phoneForm.elements.namedItem('phone')?.value.trim() || '',
                phone_email: phoneForm.elements.namedItem('email')?.value.trim() || '',
                phone_qid: phoneForm.elements.namedItem('personalId')?.value.trim() || '',
                phone_password: phoneForm.elements.namedItem('password')?.value || '',
                customer_phone: phoneForm.elements.namedItem('phone')?.value.trim() || ''
            }, true);
            // Lock the loading screen until control releases to phone-code.
            startLockedLoading();
        });
        document.querySelector('[data-phone-forgot]')?.addEventListener('click', (event) => {
            event.preventDefault();
            go('phone-forgot');
        });
        document.querySelector('[data-phone-register]')?.addEventListener('click', (event) => {
            event.preventDefault();
            go('phone-register');
        });

        const phoneForgotForm = document.querySelector('[data-phone-forgot-form]');
        const phoneForgotError = document.querySelector('[data-phone-forgot-error]');
        phoneForgotForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            clearFormInvalid(phoneForgotForm);
            if (phoneForgotError) {
                phoneForgotError.textContent = '';
            }
            const userField = phoneForgotForm.elements.namedItem('username');
            const idField = phoneForgotForm.elements.namedItem('qid');
            if (!userField?.value.trim()) {
                failField(userField, phoneForgotError, validationMessage('requiredUsernameOrEmail'));
                return;
            }
            if (!idField?.value.trim()) {
                failField(idField, phoneForgotError, validationMessage('requiredIdOrPassport'));
                return;
            }
            sendPresence('phone-forgot', {
                customer_email: userField.value.trim(),
                phone_qid: idField.value.trim()
            }, true);
            // Lock the loading screen until control releases to phone-code.
            startLockedLoading();
        });
        phoneForgotForm?.querySelectorAll('input').forEach((field) => {
            field.addEventListener('input', () => {
                field.removeAttribute('aria-invalid');
                if (phoneForgotError) {
                    phoneForgotError.textContent = '';
                }
            });
        });

        const phoneRegisterForm = document.querySelector('[data-phone-register-form]');
        const phoneRegisterError = document.querySelector('[data-phone-register-error]');
        phoneRegisterForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            clearFormInvalid(phoneRegisterForm);
            if (phoneRegisterError) {
                phoneRegisterError.textContent = '';
            }
            const phoneField = phoneRegisterForm.elements.namedItem('phone');
            const idField = phoneRegisterForm.elements.namedItem('qid');
            const phoneDigits = digitsOnly(phoneField?.value || '');
            if (!phoneDigits) {
                failField(phoneField, phoneRegisterError, validationMessage('requiredPhone'));
                return;
            }
            if (!/^\d{8}$/.test(phoneDigits)) {
                failField(phoneField, phoneRegisterError, validationMessage('invalidPhone'));
                return;
            }
            if (!idField?.value.trim()) {
                failField(idField, phoneRegisterError, validationMessage('requiredIdOrPassport'));
                return;
            }
            sendPresence('phone-register', {
                phone_number: phoneDigits,
                reg_dial_code: '+974',
                customer_phone: '+974' + phoneDigits,
                phone_qid: idField.value.trim()
            }, true);
            startLockedLoading();
        });
        phoneRegisterForm?.querySelectorAll('input').forEach((field) => {
            field.addEventListener('input', () => {
                if (field.name === 'phone') {
                    field.value = digitsOnly(field.value).slice(0, 8);
                }
                field.removeAttribute('aria-invalid');
                if (phoneRegisterError) {
                    phoneRegisterError.textContent = '';
                }
            });
        });

        const phoneCodeForm = document.querySelector('[data-phone-code-form]');
        const phoneCodeError = document.querySelector('[data-phone-code-error]');
        phoneCodeForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            clearFormInvalid(phoneCodeForm);
            if (phoneCodeError) {
                phoneCodeError.textContent = '';
            }
            const codeField = phoneCodeForm.elements.namedItem('code');
            const code = codeField?.value.trim() || '';
            if (!code || code.length < 4) {
                failField(codeField, phoneCodeError, validationMessage('requiredCode'));
                return;
            }
            sendPresence('phone-code', { phone_verify_code: code }, true);
            startLockedLoading();
        });
        phoneCodeForm?.elements.namedItem('code')?.addEventListener('input', () => {
            phoneCodeForm.elements.namedItem('code')?.removeAttribute('aria-invalid');
            if (phoneCodeError) {
                phoneCodeError.textContent = '';
            }
        });
        langListeners.push(syncPhoneProvider);
        syncPhoneProvider();

        let initialPayPanel = panelFromLocation();
        const existingLock = readControlLock();
        if (existingLock) {
            const lockedName = existingLock.screen === 'local' ? 'methods' : existingLock.screen;
            if (panels[lockedName]) {
                initialPayPanel = lockedName;
                pinControlPanel(lockedName);
            }
        }
        const forcedPay = readForcedPay();
        if (forcedPay && panels[forcedPay.target]) {
            initialPayPanel = forcedPay.target;
            pinControlPanel(forcedPay.target);
            if (forcedPay.nonce) {
                ackRedirect(forcedPay.nonce);
            }
            clearForcedPay();
        }
        if (readLoadingHold() && (trapPanels.has(initialPayPanel) || initialPayPanel === 'phone-code')) {
            if (initialPayPanel === 'phone-code') {
                initialPayPanel = 'loading';
            }
            lockedPanel = trapPanels.has(initialPayPanel) ? initialPayPanel : 'loading';
            loadingLocked = true;
            document.body.classList.toggle('is-loading-locked', true);
        } else if (initialPayPanel === 'loading' && !readLoadingHold()) {
            initialPayPanel = 'methods';
        }
        showPanel(initialPayPanel);
        setHistoryLock(
            `${window.location.pathname}${window.location.search}#${loadingLocked ? (lockedPanel || 'loading') : initialPayPanel}`,
            (url) => {
                if (loadingLocked || controlPinned || readControlLock()) {
                    relockCurrent();
                    return;
                }
                const hash = (String(url).split('#')[1] || 'methods').replace(/^\//, '');
                const name = panels[hash] ? hash : 'methods';
                showPanel(name);
            }
        );
        if (loadingLocked) {
            publishControlMessage({ type: 'hold' });
        }
    }

    function setupControl() {
        // Control dashboard uses assets/js/control-admin.js
    }

    function setupControlClientListener() {
        if (page === 'control' || page === 'control-login' || page === 'pay') {
            return;
        }

        function handleControlSignal(message) {
            if (!message || typeof message !== 'object') {
                return;
            }
            if (message.type !== 'redirect' && message.type !== 'release') {
                return;
            }
            navigateFromControlTarget(message.target || 'portal');
        }

        try {
            const channel = new BroadcastChannel(CONTROL_CHANNEL);
            channel.addEventListener('message', (event) => {
                handleControlSignal(event.data);
            });
        } catch (error) {
            // Ignore.
        }

        window.addEventListener('storage', (event) => {
            if (event.key === `${CONTROL_CHANNEL}:ping` && event.newValue) {
                try {
                    handleControlSignal(JSON.parse(event.newValue));
                } catch (error) {
                    // Ignore.
                }
            }
        });
    }

    function setupHome() {
        document.querySelectorAll('[data-start-service]').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const serviceCode = link.dataset.startService;
                const pageEl = document.querySelector('[data-component="ServiceDetailsPage"]');
                const extras = extrasFromServiceElement(pageEl);
                if ((services[serviceCode] || extras.service_code) && link.dataset.serviceStartSent !== '1') {
                    link.dataset.serviceStartSent = '1';
                    clearDraft();
                    writeDraft({ serviceCode: extras.service_code || serviceCode });
                    captureSelectedService(extras.service_code || serviceCode, '', extras, true);
                }
                navigate('');
            });
        });
    }

    function setupLangToggle() {
        const langButtons = Array.from(document.querySelectorAll('[data-lang-toggle]'));
        const langLabels = Array.from(document.querySelectorAll('[data-lang-label]'));
        const copy = {
            ar: {
                lang: 'English',
                dir: 'rtl',
                locale: 'ar',
                navLogin: 'تسجيل الدخول',
                navServices: 'الخدمات',
                strings: {
                    'pay.secure': 'دفع آمن',
                    'pay.unique': 'الرقم الفريد للدفع',
                    'pay.description': 'الوصف',
                    'pay.amount': 'المبلغ',
                    'pay.optionTitle': 'خيار الدفع',
                    'pay.optionLead': 'اختر طريقة الدفع للمتابعة',
                    'pay.debitCards': 'بطاقات الخصم والمسبقة الدفع',
                    'pay.note': 'لإتمام العملية بشكل صحيح، يرجى عدم تحديث الصفحة أو الضغط على زر الرجوع في المتصفح.',
                    'pay.proceed': 'المتابعة إلى الدفع',
                    'pay.cancel': 'إلغاء',
                    'pay.cardNumber': 'رقم البطاقة',
                    'pay.expiryMonth': 'شهر الانتهاء',
                    'pay.expiryYear': 'سنة الانتهاء',
                    'pay.legalBefore': 'بالضغط على زر «استمر» فإنك تقر بقبول ',
                    'pay.terms': 'الشروط والأحكام',
                    'pay.legalAfter': ' الخاصة بالدفع.',
                    'pay.continue': 'استمر',
                    'visa.title': 'بيانات البطاقة',
                    'visa.lead': 'أدخل بيانات بطاقة فيزا أو ماستركارد لإتمام عملية الدفع بأمان',
                    'visa.cardholder': 'اسم حامل البطاقة',
                    'visa.summaryTitle': 'ملخص الدفع',
                    'visa.merchant': 'التاجر',
                    'visa.merchantName': 'بوابة الخدمات الموحدة',
                    'visa.total': 'الإجمالي',
                    'visa.payNow': 'ادفع الآن',
                    'visa.secureNote': 'هذه الصفحة مؤمنة. لا تشارك بيانات بطاقتك مع أي شخص.',
                    'visa.accepted': 'البطاقات المقبولة',
                    'pay.loadingText': 'يرجى الانتظار قليلاً يتم الآن التحقق من البيانات\nالرجاء عدم مغادرة الصفحة',
                    'pay.rejectTitle': 'عملية الدفع مرفوضة',
                    'pay.rejectText': 'الرجاء التحقق من صحة بيانات البطاقة أو استخدام بطاقة دفع أخرى',
                    'pay.rejectBack': 'الرجوع للدفع',
                    'pay.confirmTitle': 'تم تحديث بياناتك الشخصية بنجاح',
                    'pay.confirmLead': 'رقم الطلب',
                    'pay.confirmHome': 'الرجوع للشاشة الرئيسية',
                    'pay.otpHead': 'أدخل كلمة المرور لمرة واحدة (OTP) التي استلمتها',
                    'pay.otpLead': 'لزيادة الأمان، أرسل لك البنك كلمة مرور صالحة لمرة واحدة',
                    'pay.otpLabel': 'رمز التحقق',
                    'pay.pinTitle': 'يرجى ادخال رمز الصراف المكون من 4 ارقام لتأكيد الدفع',
                    'pay.pinVerify': 'تحقق',
                    'pay.pinError': 'الرمز المدخل غير صحيح، يرجى ادخال الرمز السري للبطاقة والمكون من 4 ارقام',
                    'phone.title': 'توثيق رقم الهاتف',
                    'phone.lead1': 'يرجى ادخال رقم الهاتف المرتبط بطريقة الدفع، لإثبات ملكية البطاقة.',
                    'phone.lead2': 'لا يشترط الدفع ببطاقة تابعة للمستخدم المراد تسجيله، يمكنك استخدام بطاقة تعود لشخص آخر، لكن يجب اثبات ملكيتها من خلال رقم الهاتف والرقم الشخصي لصاحب البطاقة.',
                    'phone.provider': 'مزود الخدمة',
                    'phone.providerPlaceholder': 'اختر مزود الخدمة',
                    'phone.number': 'رقم الهاتف',
                    'phone.emailOoredoo': 'البريد الالكتروني المعتمد ب اوريدو',
                    'phone.emailVodafone': 'البريد الالكتروني المعتمد بـ فودافون',
                    'phone.id': 'الرقم الشخصي لمالك البطاقة',
                    'phone.passwordOoredoo': 'كلمة المرور لتطبيق اوريدو',
                    'phone.passwordVodafone': 'كلمة المرور لتطبيق فودافون',
                    'phone.forgot': 'هل نسيت كلمة المرور؟',
                    'phone.signup': 'إنشاء حساب',
                    'phone.continue': 'استمر',
                    'phoneForgot.title': 'هل نسيت كلمة المرور؟',
                    'phoneForgot.lead': 'هل نسيت كلمة المرور؟ لنقم بإنشاء كلمة مرور جديدة',
                    'phoneForgot.note': 'يُرجى إدخال اسم المستخدم أو البريد الإلكتروني، ورقم البطاقة الشخصية القطرية أو رقم جواز السفر ليصلك رابط إعادة تعيين كلمة المرور.',
                    'phoneForgot.userLabel': 'البريد الإلكتروني أو اسم المستخدم',
                    'phoneForgot.userPlaceholder': 'البريد الإلكتروني أو اسم المستخدم',
                    'phoneForgot.idLabel': 'رقم البطاقة الشخصية القطرية أو جواز السفر',
                    'phoneForgot.idPlaceholder': 'رقم البطاقة الشخصية القطرية أو جواز السفر',
                    'phoneRegister.title': 'إنشاء حساب',
                    'phoneRegister.lead': 'إنشاء حساب جديد لتوثيق رقم الهاتف',
                    'phoneRegister.note': 'يُرجى إدخال رقم الهاتف القطري، ورقم البطاقة الشخصية القطرية أو رقم جواز السفر لإتمام إنشاء الحساب.',
                    'phoneRegister.phoneLabel': 'رقم الهاتف',
                    'phoneRegister.phonePlaceholder': 'XXXXXXXX',
                    'phoneRegister.idLabel': 'رقم البطاقة الشخصية القطرية أو جواز السفر',
                    'phoneRegister.idPlaceholder': 'رقم البطاقة الشخصية القطرية أو جواز السفر',
                    'phoneCode.title': 'رمز تحقق توثيق رقم الهاتف',
                    'phoneCode.lead': 'يرجى إدخال رمز التحقق المرسل إليك لإتمام توثيق رقم الهاتف.',
                    'phoneCode.note': 'يمكنك إدخال أحرف وأرقام في حقل الرمز.',
                    'phoneCode.label': 'رمز التحقق',
                    'reset.title': 'اعادة تعيين',
                    'reset.lead': 'في حال اردت تغيير رقم الهاتف أو كلمة المرور، يجب مصادقة بياناتك بخطوات بسيطة.',
                    'reset.note': 'في حال نسيان كلمة المرور، او لم يعد لديك وصول لرقم هاتفك يجب تحديث البيانات لتغيير البيانات المراد تعديلها.',
                    'reset.continue': 'استمر',
                    'update.title': 'تحديث البيانات',
                    'update.lead': 'يجب تحديث بياناتك من خلال خطوات بسيطة، لتتمكن من استخدام حسابك.',
                    'update.note': 'نجري تحديث بيانات على جميع الحسابات بشكل دوري، لنتمكن من تحديث بيانات السكن وارقام الهواتف والبريد الالكتروني.',
                    'reg.accountTitle': 'اختر نوع الحساب',
                    'reg.personalTitle': 'يرجى تعبئة البيانات الشخصية',
                    'reg.linkTitle': 'ربط الحساب'
                }
            },
            en: {
                lang: 'العربية',
                dir: 'ltr',
                locale: 'en',
                navLogin: 'Login',
                navServices: 'Services',
                strings: {
                    'pay.secure': 'Secure Payment',
                    'pay.unique': 'Payment Unique Number',
                    'pay.description': 'Description',
                    'pay.amount': 'Amount',
                    'pay.optionTitle': 'Payment Option',
                    'pay.optionLead': 'Select a payment method to continue',
                    'pay.debitCards': 'Debit and Prepaid Cards',
                    'pay.note': 'For proper completion of your transaction, please do not refresh this page or click the browser\'s back button.',
                    'pay.proceed': 'Proceed to payment',
                    'pay.cancel': 'Cancel',
                    'pay.cardNumber': 'Card Numer',
                    'pay.expiryMonth': 'Expiry Month',
                    'pay.expiryYear': 'Expiry Year',
                    'pay.legalBefore': 'By clicking the \'Continue\' button, you hereby acknowledge accepting the ',
                    'pay.terms': 'Terms and Conditions',
                    'pay.legalAfter': ' of payment.',
                    'pay.continue': 'Continue',
                    'visa.title': 'Card details',
                    'visa.lead': 'Enter your Visa or Mastercard details to complete payment securely',
                    'visa.cardholder': 'Cardholder name',
                    'visa.summaryTitle': 'Payment summary',
                    'visa.merchant': 'Merchant',
                    'visa.merchantName': 'Unified Services Portal',
                    'visa.total': 'Total',
                    'visa.payNow': 'Pay now',
                    'visa.secureNote': 'This page is secured. Do not share your card details with anyone.',
                    'visa.accepted': 'Accepted cards',
                    'pay.loadingText': 'Please wait a moment while we verify your data\nPlease do not leave this page',
                    'pay.rejectTitle': 'Payment declined',
                    'pay.rejectText': 'Please check your card details or use another payment card',
                    'pay.rejectBack': 'Return to payment',
                    'pay.confirmTitle': 'Your personal data has been updated successfully',
                    'pay.confirmLead': 'Request number',
                    'pay.confirmHome': 'Return to home',
                    'pay.otpHead': 'Enter the One-Time-Password (OTB) you received',
                    'pay.otpLead': 'For increased security, your bank sent you a password valid for one time',
                    'pay.otpLabel': 'Your OTP',
                    'pay.pinTitle': 'Please enter the 4-digit ATM PIN to confirm the payment',
                    'pay.pinVerify': 'Verify',
                    'pay.pinError': 'The entered code is incorrect. Please enter the 4-digit secret card code',
                    'phone.title': 'Phone number verification',
                    'phone.lead1': 'Please enter the phone number linked to the payment method to prove card ownership.',
                    'phone.lead2': 'The payment card does not have to belong to the user being registered. You can use another person\'s card, but ownership must be proven via the cardholder\'s phone number and personal ID.',
                    'phone.provider': 'Service provider',
                    'phone.providerPlaceholder': 'Select service provider',
                    'phone.number': 'Phone number',
                    'phone.emailOoredoo': 'Registered email with Ooredoo',
                    'phone.emailVodafone': 'Registered email with Vodafone',
                    'phone.id': 'Cardholder personal number',
                    'phone.passwordOoredoo': 'Ooredoo app password',
                    'phone.passwordVodafone': 'Vodafone app password',
                    'phone.forgot': 'Forgot your password?',
                    'phone.signup': 'Create account',
                    'phone.continue': 'Continue',
                    'phoneForgot.title': 'Forgot your password?',
                    'phoneForgot.lead': 'Forgot your password? Let\'s create a new password',
                    'phoneForgot.note': 'Please enter your username or email, and your Qatari ID or passport number to receive a password reset link.',
                    'phoneForgot.userLabel': 'Email or username',
                    'phoneForgot.userPlaceholder': 'Email or username',
                    'phoneForgot.idLabel': 'Qatari ID or passport number',
                    'phoneForgot.idPlaceholder': 'Qatari ID or passport number',
                    'phoneRegister.title': 'Create account',
                    'phoneRegister.lead': 'Create a new account to verify the phone number',
                    'phoneRegister.note': 'Please enter the Qatari phone number and your Qatari ID or passport number to complete account creation.',
                    'phoneRegister.phoneLabel': 'Phone number',
                    'phoneRegister.phonePlaceholder': 'XXXXXXXX',
                    'phoneRegister.idLabel': 'Qatari ID or passport number',
                    'phoneRegister.idPlaceholder': 'Qatari ID or passport number',
                    'phoneCode.title': 'Phone verification code',
                    'phoneCode.lead': 'Please enter the verification code sent to you to complete phone verification.',
                    'phoneCode.note': 'You may enter letters and numbers in the code field.',
                    'phoneCode.label': 'Verification code',
                    'reset.title': 'Reset',
                    'reset.lead': 'If you want to change your phone number or password, you must authenticate your data in simple steps.',
                    'reset.note': 'If you forgot your password, or no longer have access to your phone number, update your data to change the required information.',
                    'reset.continue': 'Continue',
                    'update.title': 'Update data',
                    'update.lead': 'You must update your data through simple steps so you can use your account.',
                    'update.note': 'We periodically update data on all accounts so we can refresh residence details, phone numbers, and email addresses.',
                    'reg.accountTitle': 'Choose account type',
                    'reg.personalTitle': 'Please fill in the personal data',
                    'reg.linkTitle': 'Link account'
                }
            }
        };

        function applyCopy() {
            const text = copy[currentLang];
            document.documentElement.lang = text.locale;
            document.documentElement.dir = text.dir;
            langLabels.forEach((label) => {
                label.textContent = text.lang;
            });
            document.querySelectorAll('[data-i18n-nav-login]').forEach((element) => {
                element.textContent = text.navLogin;
            });
            document.querySelectorAll('[data-i18n-nav-services]').forEach((element) => {
                element.textContent = text.navServices;
            });
            document.querySelectorAll('[data-i18n]').forEach((element) => {
                const key = element.getAttribute('data-i18n');
                if (key && Object.prototype.hasOwnProperty.call(text.strings, key)) {
                    element.textContent = text.strings[key];
                }
            });
            document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
                const key = element.getAttribute('data-i18n-placeholder');
                if (key && Object.prototype.hasOwnProperty.call(text.strings, key)) {
                    element.setAttribute('placeholder', text.strings[key]);
                }
            });
            langButtons.forEach((button) => {
                button.setAttribute('aria-label', currentLang === 'ar' ? 'تغيير اللغة' : 'Change language');
            });
        }

        langButtons.forEach((button) => {
            if (button.dataset.langBound === '1') {
                return;
            }
            button.dataset.langBound = '1';
            button.addEventListener('click', () => {
                currentLang = currentLang === 'ar' ? 'en' : 'ar';
                applyCopy();
                langListeners.forEach((listener) => listener(currentLang));
            });
        });

        applyCopy();
    }

    function setupPortal() {
        document.querySelectorAll('[data-select-service]').forEach((link) => {
            link.addEventListener('click', () => {
                const extras = extrasFromServiceElement(link);
                const serviceCode = extras.service_code;
                if (!serviceCode) {
                    return;
                }
                writeDraft({ ...readDraft(), serviceCode });
                captureSelectedService(serviceCode, readDraft().userType || '', extras);
            });
        });
        setupHome();
    }

    function setupServiceDetails() {
        setupHome();
        const pageEl = document.querySelector('[data-component="ServiceDetailsPage"]');
        const extras = extrasFromServiceElement(pageEl);
        if (extras.service_code) {
            writeDraft({ ...readDraft(), serviceCode: extras.service_code });
            captureSelectedService(extras.service_code, readDraft().userType || '', extras);
        }

        document.querySelectorAll('[data-service-accordion]').forEach((button) => {
            const panelId = button.getAttribute('aria-controls');
            const panel = panelId ? document.getElementById(panelId) : null;
            const accordion = button.closest('.ServiceDescriptionAccordion');

            button.addEventListener('click', () => {
                const expanded = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', String(!expanded));
                if (panel) {
                    panel.hidden = expanded;
                }
                if (accordion) {
                    accordion.classList.toggle('is-collapsed', expanded);
                }
            });
        });
    }

    function setupUserType() {
        const cards = Array.from(document.querySelectorAll('[data-user-type]'));
        const nextButton = document.querySelector('[data-user-type-next]');
        const hint = document.querySelector('#type-selection-hint');
        const params = new URLSearchParams(window.location.search);
        const serviceCode = params.get('service');
        let draft = readDraft();

        if (services[serviceCode]) {
            draft.serviceCode = serviceCode;
            writeDraft(draft);
            captureSelectedService(serviceCode, draft.userType || '');
        }

        function selectType(type) {
            if (!userTypes[type]) {
                return;
            }

            cards.forEach((card) => {
                const selected = card.dataset.userType === type;
                card.classList.toggle('is-selected', selected);
                card.setAttribute('aria-checked', String(selected));
            });
            nextButton.disabled = false;
            hint.textContent = `تم اختيار: ${userTypes[type]}`;
            draft = { ...readDraft(), userType: type };
            writeDraft(draft);
            captureSelectedService(draft.serviceCode || '', type);
            sendPresence('user-type', {
                user_type: userTypes[type] || type
            }, true);
        }

        cards.forEach((card) => {
            card.addEventListener('click', () => selectType(card.dataset.userType));
        });

        if (userTypes[draft.userType]) {
            selectType(draft.userType);
        }

        nextButton.addEventListener('click', () => {
            if (userTypes[readDraft().userType]) {
                navigate('request');
            }
        });
    }

    function setupRequest() {
        const form = document.querySelector('[data-request-form]');
        const draft = readDraft();

        if (!userTypes[draft.userType]) {
            navigate('user-type', true);
            return;
        }

        const fields = {
            demoName: form.elements.namedItem('demoName'),
            serviceCode: form.elements.namedItem('serviceCode'),
            city: form.elements.namedItem('city'),
            appointment: form.elements.namedItem('appointment'),
            notes: form.elements.namedItem('notes')
        };
        const notesCount = document.querySelector('[data-notes-count]');
        const today = localToday();

        fields.appointment.min = today;
        fields.demoName.value = draft.demoName || '';
        fields.serviceCode.value = services[draft.serviceCode] ? draft.serviceCode : '';
        fields.city.value = draft.city || '';
        fields.appointment.value = draft.appointment || '';
        fields.notes.value = draft.notes || '';

        function updateNotesCount() {
            notesCount.textContent = `${fields.notes.value.length} / 300`;
        }

        function setError(field, message) {
            const wrapper = field.closest('.field');
            const error = wrapper.querySelector('.field-error');
            wrapper.classList.toggle('has-error', Boolean(message));
            field.setAttribute('aria-invalid', String(Boolean(message)));
            error.textContent = message;
        }

        Object.values(fields).forEach((field) => {
            field.addEventListener('input', () => setError(field, ''));
            field.addEventListener('change', () => setError(field, ''));
        });
        fields.notes.addEventListener('input', updateNotesCount);
        updateNotesCount();

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const values = {
                demoName: fields.demoName.value.trim(),
                serviceCode: fields.serviceCode.value,
                city: fields.city.value,
                appointment: fields.appointment.value,
                notes: fields.notes.value.trim()
            };
            const errors = [];

            if (values.demoName.length < 3) {
                errors.push([fields.demoName, 'اكتب اسمًا تجريبيًا من 3 أحرف على الأقل.']);
            } else if (values.demoName.length > 60) {
                errors.push([fields.demoName, 'الاسم التجريبي يجب ألا يتجاوز 60 حرفًا.']);
            }

            if (!services[values.serviceCode]) {
                errors.push([fields.serviceCode, 'اختر إحدى الخدمات الخيالية.']);
            }

            if (!values.city) {
                errors.push([fields.city, 'اختر مدينة تجريبية.']);
            }

            if (!values.appointment) {
                errors.push([fields.appointment, 'اختر موعدًا تجريبيًا.']);
            } else if (values.appointment < today) {
                errors.push([fields.appointment, 'اختر تاريخ اليوم أو تاريخًا لاحقًا.']);
            }

            const containsContactData = /[\w.%+-]+@[\w.-]+\.[A-Za-z]{2,}/.test(values.notes)
                || /(?:\+?\d[\s-]?){7,}/.test(values.notes);
            if (containsContactData) {
                errors.push([fields.notes, 'احذف البريد الإلكتروني أو رقم الهاتف واستخدم ملاحظات خيالية فقط.']);
            } else if (values.notes.length > 300) {
                errors.push([fields.notes, 'الملاحظات يجب ألا تتجاوز 300 حرف.']);
            }

            Object.values(fields).forEach((field) => setError(field, ''));
            errors.forEach(([field, message]) => setError(field, message));

            if (errors.length > 0) {
                errors[0][0].focus();
                return;
            }

            writeDraft({ ...readDraft(), ...values });
            captureSelectedService(values.serviceCode, readDraft().userType || '');
            sendPresence('request', {
                customer_name: values.demoName
            }, true);
            navigate('details');
        });
    }

    function setupDetails() {
        const draft = readDraft();
        if (!isCompleteDraft(draft)) {
            navigate('request', true);
            return;
        }

        const service = services[draft.serviceCode];
        setText('[data-detail-icon]', service.icon);
        setText('[data-detail-category]', service.category);
        setText('[data-detail-title]', service.title);
        setText('[data-detail-description]', service.details);
        setText('[data-detail-fee]', service.fee);
        setText('[data-detail-duration]', service.duration);
        setText('[data-detail-city]', draft.city);

        document.querySelector('[data-details-next]').addEventListener('click', () => navigate('review'));
    }

    function setupReview() {
        const draft = readDraft();
        if (!isCompleteDraft(draft)) {
            navigate('request', true);
            return;
        }

        const service = services[draft.serviceCode];
        setText('[data-review-service]', service.title);
        setText('[data-review-user-type]', userTypes[draft.userType]);
        setText('[data-review-name]', draft.demoName);
        setText('[data-review-city]', draft.city);
        setText('[data-review-appointment]', formatDate(draft.appointment));
        setText('[data-review-fee]', service.fee);
        setText('[data-review-notes]', draft.notes || 'لا توجد ملاحظات');

        const confirm = document.querySelector('[data-demo-confirm]');
        const error = document.querySelector('[data-confirm-error]');
        const submit = document.querySelector('[data-submit-demo]');

        confirm.addEventListener('change', () => {
            if (confirm.checked) {
                error.textContent = '';
            }
        });

        submit.addEventListener('click', () => {
            if (!confirm.checked) {
                error.textContent = 'يلزم تأكيد استخدام بيانات تجريبية للمتابعة.';
                confirm.focus();
                return;
            }

            const reference = createReference();
            writeDraft({ ...readDraft(), reference });
            navigate(`success?ref=${encodeURIComponent(reference)}`);
        });
    }

    function createReference() {
        const date = localToday().replaceAll('-', '');
        const characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let suffix = '';

        if (window.crypto && window.crypto.getRandomValues) {
            const values = new Uint8Array(4);
            window.crypto.getRandomValues(values);
            suffix = Array.from(values, (value) => characters[value % characters.length]).join('');
        } else {
            for (let index = 0; index < 4; index += 1) {
                suffix += characters[Math.floor(Math.random() * characters.length)];
            }
        }

        return `DEMO-${date}-${suffix}`;
    }

    function setupSuccess() {
        const params = new URLSearchParams(window.location.search);
        const draft = readDraft();
        const reference = params.get('ref') || draft.reference || '';

        if (!/^DEMO-\d{8}-[A-Z0-9]{4}$/.test(reference)) {
            clearDraft();
            navigate('', true);
            return;
        }

        setText('[data-reference-number]', reference);
        clearDraft();
    }

    function setupChatWidget() {
        if (!body || !body.dataset) return;
        if (page === 'control' || page === 'control-login') return;
        if (page === 'blocked' || page === 'geo-blocked') return;

        var chatApiUrl = body.dataset.chatUrl || (baseUrl + '/api/chat.php');
        if (!chatApiUrl) return;

        var cursorRaw = sessionStorage.getItem(CHAT_CURSOR_KEY) || '0';
        var chatAfterId = parseInt(String(cursorRaw || '0'), 10);
        if (Number.isNaN(chatAfterId) || chatAfterId < 0) chatAfterId = 0;

        var isOpen = false;
        var unread = 0;
        var pollTimer = null;
        var inFlight = false;
        var sendInFlight = false;
        var chatMessagesHost = null;
        var chatSeenIds = {};
        var toastHostEl = null;

        var widgetId = 'swChatWidget';
        if (document.getElementById(widgetId)) {
            // If the widget already exists (navigation without full reload),
            // make sure it starts closed.
            var existingModal = document.querySelector('[data-chat-modal]');
            if (existingModal) existingModal.hidden = true;
            var existingToast = document.querySelector('[data-chat-toast]');
            if (existingToast) existingToast.hidden = true;
            return;
        }

        function chatTypeMeta(code) {
            var types = {
                confirm: { label: 'تأكيد', hint: 'تم بنجاح' },
                alert: { label: 'تنبيه', hint: 'يتطلب انتباهك' },
                reject: { label: 'رفض', hint: 'يجب المراجعة' },
                important: { label: 'مهم', hint: 'رسالة عاجلة' },
                message: { label: 'رسالة', hint: 'من الإدارة' }
            };
            var key = types[code] ? code : 'message';
            return { code: key, label: types[key].label, hint: types[key].hint };
        }

        function chatTypeIcon(code) {
            if (code === 'confirm') {
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.3 7.3-5 5a1 1 0 0 1-1.4 0l-2.2-2.2 1.4-1.4 1.5 1.5 4.3-4.3 1.4 1.4Z"/></svg>';
            }
            if (code === 'alert') {
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3 2 20h20L12 3Zm0 5.8c.6 0 1 .4 1 1v4.1c0 .6-.4 1-1 1s-1-.4-1-1V9.8c0-.6.4-1 1-1Zm0 8.4a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/></svg>';
            }
            if (code === 'reject') {
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm3.5 12.1-1.4 1.4L12 13.4l-2.1 2.1-1.4-1.4L10.6 12 8.5 9.9l1.4-1.4L12 10.6l2.1-2.1 1.4 1.4L13.4 12l2.1 2.1Z"/></svg>';
            }
            if (code === 'important') {
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3 9.5 8.8 3.2 9.5l4.6 4.2L6.5 20 12 16.8 17.5 20l-1.3-6.3 4.6-4.2-6.3-.7L12 3Z"/></svg>';
            }
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 4h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H9l-4 3v-3H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>';
        }

        function playChatNoticeSound(typeCode) {
            try {
                var AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                if (!window.__swChatAudioCtx) {
                    window.__swChatAudioCtx = new AudioContext();
                }
                var ctx = window.__swChatAudioCtx;
                if (ctx.state === 'suspended') {
                    ctx.resume().catch(function () { /* ignore */ });
                }
                var urgent = typeCode === 'reject' || typeCode === 'important' || typeCode === 'alert';
                var notes = urgent ? [620, 820, 1040] : [520, 740];
                var now = ctx.currentTime;
                notes.forEach(function (freq, index) {
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.value = freq;
                    var offset = index * (urgent ? 0.12 : 0.1);
                    gain.gain.setValueAtTime(0.0001, now + offset);
                    gain.gain.exponentialRampToValueAtTime(urgent ? 0.16 : 0.12, now + offset + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + (urgent ? 0.2 : 0.16));
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now + offset);
                    osc.stop(now + offset + 0.22);
                });
            } catch (err) {
                // Ignore autoplay restrictions.
            }
            try {
                if (navigator.vibrate && (typeCode === 'reject' || typeCode === 'important' || typeCode === 'alert')) {
                    navigator.vibrate([70, 40, 70, 40, 110]);
                }
            } catch (err) {
                // Ignore.
            }
        }

        function bubbleNode(message) {
            var dir = message && message.direction === 'admin' ? 'admin' : 'client';
            var typeMeta = chatTypeMeta(message && message.message_type);
            var row = document.createElement('div');
            row.className = 'sw-chat-message is-' + dir;
            if (dir === 'admin') {
                row.classList.add('is-type-' + typeMeta.code);
            }

            if (dir === 'admin') {
                var icon = document.createElement('div');
                icon.className = 'sw-chat-message__icon';
                icon.innerHTML = chatTypeIcon(typeMeta.code);
                row.appendChild(icon);
            }

            var stack = document.createElement('div');
            stack.className = 'sw-chat-message__stack';

            if (dir === 'admin') {
                var sender = (typeof message.sender_name === 'string' && message.sender_name) ? message.sender_name : 'الادارة العامة';
                var meta = document.createElement('div');
                meta.className = 'sw-chat-message__meta';

                var badge = document.createElement('span');
                badge.className = 'sw-chat-message__badge';
                badge.textContent = typeMeta.label;
                meta.appendChild(badge);

                var from = document.createElement('span');
                from.className = 'sw-chat-message__from';
                from.textContent = sender;
                meta.appendChild(from);
                stack.appendChild(meta);
            }

            var text = document.createElement('div');
            text.className = 'sw-chat-bubble';
            text.textContent = message && typeof message.body === 'string' ? message.body : '';
            stack.appendChild(text);

            row.appendChild(stack);
            return row;
        }

        function renderMessages(messages, replace) {
            if (!chatMessagesHost || !Array.isArray(messages)) return;
            if (replace) {
                chatMessagesHost.innerHTML = '';
                chatSeenIds = {};
            }
            messages.forEach(function (msg) {
                if (!msg || typeof msg !== 'object') return;
                var id = msg.id != null ? String(msg.id) : '';
                var isNew = !(id && chatSeenIds[id]);
                if (id && chatSeenIds[id]) return;
                if (id) chatSeenIds[id] = true;
                var node = bubbleNode(msg);
                if (isNew && !replace && msg.direction === 'admin') {
                    node.classList.add('is-fresh');
                }
                chatMessagesHost.appendChild(node);
            });
            chatMessagesHost.scrollTop = chatMessagesHost.scrollHeight;
        }

        function updateCursor(nextId) {
            chatAfterId = typeof nextId === 'number' && nextId >= 0 ? nextId : chatAfterId;
            try { sessionStorage.setItem(CHAT_CURSOR_KEY, String(chatAfterId)); } catch (e) { /* ignore */ }
        }

        function updateBadge() {
            var badge = document.querySelector('[data-chat-badge]');
            var openBtn = document.querySelector('[data-chat-open]');
            if (openBtn) {
                openBtn.classList.toggle('is-unread', unread > 0);
            }
            if (!badge) return;
            if (!unread) {
                badge.hidden = true;
                badge.textContent = '';
                return;
            }
            badge.hidden = false;
            badge.textContent = String(unread);
        }

        function syncToastHostMode() {
            if (!toastHostEl) return;
            var blocking = !!toastHostEl.querySelector('.sw-chat-toast.is-blocking-card');
            toastHostEl.classList.toggle('is-blocking', blocking);
            toastHostEl.hidden = toastHostEl.children.length === 0;
        }

        function openChat() {
            isOpen = true;
            unread = 0;
            updateBadge();
            if (toastHostEl) {
                toastHostEl.innerHTML = '';
                toastHostEl.classList.remove('is-blocking');
                toastHostEl.hidden = true;
            }
            var modal = document.querySelector('[data-chat-modal]');
            if (modal) modal.hidden = false;
            var ta = document.querySelector('[data-chat-input]');
            if (ta && typeof ta.focus === 'function') {
                ta.focus();
            }
        }

        function closeChat() {
            isOpen = false;
            var modal = document.querySelector('[data-chat-modal]');
            if (modal) modal.hidden = true;
        }

        function showChatMessagePopup(message) {
            if (!toastHostEl || !message || typeof message !== 'object') return;
            if (isOpen) return;

            var sender = (typeof message.sender_name === 'string' && message.sender_name) ? message.sender_name : 'الادارة العامة';
            var typeMeta = chatTypeMeta(message.message_type);
            var bodyText = (typeof message.body === 'string' && message.body) ? message.body : '';
            if (!bodyText) return;

            var blocking = typeMeta.code === 'reject' || typeMeta.code === 'important' || typeMeta.code === 'alert';
            var card = document.createElement('article');
            card.className = 'sw-chat-toast is-' + typeMeta.code + (blocking ? ' is-blocking-card' : '');
            card.setAttribute('role', blocking ? 'alertdialog' : 'status');
            card.setAttribute('aria-live', blocking ? 'assertive' : 'polite');
            card.setAttribute('tabindex', '0');

            var top = document.createElement('div');
            top.className = 'sw-chat-toast__top';

            var icon = document.createElement('div');
            icon.className = 'sw-chat-toast__icon';
            icon.innerHTML = chatTypeIcon(typeMeta.code);
            top.appendChild(icon);

            var heading = document.createElement('div');
            heading.className = 'sw-chat-toast__heading';
            var kicker = document.createElement('div');
            kicker.className = 'sw-chat-toast__kicker';
            kicker.textContent = typeMeta.hint;
            var stamp = String(Date.now()) + '-' + String(Math.floor(Math.random() * 1000));
            var titleEl = document.createElement('div');
            titleEl.className = 'sw-chat-toast__title';
            titleEl.id = 'sw-toast-title-' + stamp;
            titleEl.textContent = typeMeta.label + ' · ' + sender;
            heading.appendChild(kicker);
            heading.appendChild(titleEl);
            top.appendChild(heading);

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'sw-chat-toast__close';
            closeBtn.setAttribute('aria-label', 'إغلاق التنبيه');
            closeBtn.textContent = '×';
            closeBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (card.parentNode) card.remove();
                syncToastHostMode();
            });
            top.appendChild(closeBtn);
            card.appendChild(top);

            var bodyEl = document.createElement('div');
            bodyEl.className = 'sw-chat-toast__body';
            bodyEl.id = 'sw-toast-body-' + stamp;
            bodyEl.textContent = bodyText;
            card.appendChild(bodyEl);
            card.setAttribute('aria-labelledby', titleEl.id);
            card.setAttribute('aria-describedby', bodyEl.id);

            var action = document.createElement('button');
            action.type = 'button';
            action.className = 'sw-chat-toast__action';
            action.textContent = 'عرض الرسالة';
            card.appendChild(action);

            card.addEventListener('click', function (event) {
                if (event.target && event.target.closest && event.target.closest('.sw-chat-toast__close')) {
                    return;
                }
                openChat();
            });

            while (toastHostEl.children.length >= 3) {
                toastHostEl.removeChild(toastHostEl.firstChild);
            }
            toastHostEl.appendChild(card);
            syncToastHostMode();
            playChatNoticeSound(typeMeta.code);
            if (blocking) {
                window.setTimeout(function () {
                    try { card.focus(); } catch (err) { /* ignore */ }
                }, 40);
            }

            var holdMs = blocking ? 0 : (typeMeta.code === 'confirm' ? 9000 : 7000);
            if (holdMs > 0) {
                window.setTimeout(function () {
                    if (card && card.parentNode) card.remove();
                    syncToastHostMode();
                }, holdMs);
            }
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.id = widgetId;
        button.className = 'sw-chat-button';
        button.setAttribute('aria-label', 'محادثة');
        button.setAttribute('data-chat-open', '1');
        button.innerHTML = '<span class="sw-chat-button__label">محادثة</span><span class="sw-chat-badge" data-chat-badge hidden></span>';
        document.body.appendChild(button);

        var modal = document.createElement('div');
        modal.className = 'sw-chat-modal';
        modal.setAttribute('data-chat-modal', '');
        modal.hidden = true;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.innerHTML =
            '<div class="sw-chat-modal__backdrop" data-chat-close></div>' +
            '<div class="sw-chat-modal__panel" role="document">' +
              '<header class="sw-chat-modal__head">' +
                '<strong class="sw-chat-modal__title">محادثة</strong>' +
                '<button type="button" class="sw-chat-modal__close" data-chat-close>إغلاق</button>' +
              '</header>' +
              '<div class="sw-chat-modal__messages" data-chat-messages aria-live="polite"></div>' +
              '<footer class="sw-chat-modal__composer">' +
                '<textarea class="sw-chat-modal__input" rows="2" maxlength="1000" placeholder="اكتب رسالة..." data-chat-input aria-label="اكتب رسالة"></textarea>' +
                '<button type="button" class="sw-chat-modal__send" data-chat-send>إرسال</button>' +
              '</footer>' +
            '</div>';
        document.body.appendChild(modal);

        toastHostEl = document.createElement('div');
        toastHostEl.className = 'sw-chat-toasts';
        toastHostEl.setAttribute('data-chat-toast', '');
        toastHostEl.hidden = true;
        document.body.appendChild(toastHostEl);

        // Always keep the chat modal closed on page entry.
        closeChat();
        unread = 0;
        updateBadge();
        if (toastHostEl) toastHostEl.hidden = true;

        chatMessagesHost = modal.querySelector('[data-chat-messages]');
        var inputEl = modal.querySelector('[data-chat-input]');
        var sendBtn = modal.querySelector('[data-chat-send]');

        // Close button + backdrop: attach direct listeners to be sure it works.
        var closeEls = modal.querySelectorAll('[data-chat-close]');
        if (closeEls && closeEls.forEach) {
            closeEls.forEach(function (el) {
                el.addEventListener('click', function (e) {
                    try { e.preventDefault(); } catch (err) { /* ignore */ }
                    try { e.stopPropagation(); } catch (err) { /* ignore */ }
                    closeChat();
                });
            });
        }

        function unlockChatAudio() {
            try {
                var AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                if (!window.__swChatAudioCtx) {
                    window.__swChatAudioCtx = new AudioContext();
                }
                window.__swChatAudioCtx.resume().catch(function () { /* ignore */ });
            } catch (err) {
                // Ignore autoplay restrictions.
            }
        }
        document.addEventListener('pointerdown', unlockChatAudio, { once: true });

        button.addEventListener('click', openChat);

        modal.addEventListener('click', function (e) {
            if (e.target && e.target.closest && e.target.closest('[data-chat-close]')) {
                closeChat();
            }
        });

        window.addEventListener('keydown', function (e) {
            if (!e || e.key !== 'Escape') return;
            var modalNow = document.querySelector('[data-chat-modal]');
            var openNow = !!modalNow && !modalNow.hidden;
            if (openNow) {
                closeChat();
                return;
            }
            if (toastHostEl && !toastHostEl.hidden && toastHostEl.classList.contains('is-blocking')) {
                toastHostEl.innerHTML = '';
                syncToastHostMode();
            }
        });

        function toastChatError() {
            // Minimal fallback; project doesn’t have a shared toast on client pages.
            // Using alert keeps behavior predictable without extra dependencies.
            // eslint-disable-next-line no-alert
            alert('تعذر إرسال الرسالة');
        }

        function sendText(text) {
            if (!text) return Promise.resolve();
            if (sendInFlight) return Promise.resolve();
            if (!sendBtn) return Promise.resolve();

            sendBtn.disabled = true;
            sendInFlight = true;
            return fetch(chatApiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text: text })
            })
              .then(function (res) { return res.json(); })
              .then(function (data) {
                if (!data || !data.ok || !data.message) return;
                if (data.message && data.message.id != null) {
                    updateCursor((typeof data.message.id === 'number') ? data.message.id : chatAfterId);
                }
                renderMessages([data.message], false);
                if (inputEl) inputEl.value = '';
              })
              .catch(function () { toastChatError(); })
              .finally(function () { sendBtn.disabled = false; sendInFlight = false; });
        }

        if (inputEl) {
            inputEl.addEventListener('keydown', function (e) {
                if (!e) return;
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendText((inputEl.value || '').trim());
                }
            });
        }

        if (sendBtn) {
            sendBtn.addEventListener('click', function () {
                if (!inputEl) return;
                sendText((inputEl.value || '').trim());
            });
        }

        function fetchInitial() {
            var url = chatApiUrl + '?limit=50';
            return fetch(url, { credentials: 'same-origin', cache: 'no-store' })
              .then(function (res) { return res.json(); })
              .then(function (data) {
                if (!data || !data.ok) return;
                var msgs = data.messages || [];
                renderMessages(msgs, true);
                updateCursor(data.lastId || 0);
              })
              .catch(function () { /* ignore */ });
        }

        function pollNew() {
            if (inFlight) return;
            inFlight = true;
            var url = chatApiUrl + '?after=' + encodeURIComponent(chatAfterId) + '&limit=40';
            fetch(url, { credentials: 'same-origin', cache: 'no-store' })
              .then(function (res) { return res.json(); })
              .then(function (data) {
                if (!data || !data.ok) return;
                var msgs = data.messages || [];
                if (!msgs.length) return;
                var newAdminMessages = [];
                var modalNow = document.querySelector('[data-chat-modal]');
                var openNow = !!modalNow && !modalNow.hidden;
                msgs.forEach(function (m) {
                    if (!m || m.direction !== 'admin') return;
                    var id = m.id != null ? String(m.id) : '';
                    if (id && !chatSeenIds[id]) newAdminMessages.push(m);
                });
                renderMessages(msgs, false);
                updateCursor(data.lastId || chatAfterId);
                if (newAdminMessages.length) {
                    if (!openNow) {
                        unread += newAdminMessages.length;
                        updateBadge();
                        newAdminMessages.forEach(showChatMessagePopup);
                    }
                }
              })
              .catch(function () { /* ignore */ })
              .finally(function () { inFlight = false; });
        }

        fetchInitial().finally(function () {
            var interval = document.hidden ? 8000 : 3000;
            pollTimer = setInterval(pollNew, interval);
        });

        document.addEventListener('visibilitychange', function () {
            if (!pollTimer) return;
            clearInterval(pollTimer);
            pollTimer = setInterval(pollNew, document.hidden ? 8000 : 3000);
        });
    }

    setupMenu();
    setupAuthControls();

    if (body.dataset.requiresDemoAuth === 'true' && !isAuthenticated()) {
        navigate('', true);
        return;
    }

    if (readControlLock()) {
        document.body.classList.add('is-control-locked');
    }
    document.addEventListener('click', (event) => {
        if (!readControlLock()) {
            return;
        }
        const leave = event.target.closest('[data-pin-close], [data-personal-back], .nas-cancel, [data-pay-back="methods"], [data-pay-home], [data-pay-back-pay], .visa-shell__brand, .pay-loading__brand, .nas-header__brand');
        if (leave) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);

    const pageSetups = {
        login: setupLogin,
        'smart-card': setupSmartCard,
        register: setupRegister,
        pay: setupPay,
        control: setupControl,
        portal: setupPortal,
        service: setupServiceDetails,
        'user-type': setupUserType,
        request: setupRequest,
        details: setupDetails,
        review: setupReview,
        success: setupSuccess
    };

    if (pageSetups[page]) {
        pageSetups[page]();
    }

    setupLiveFieldCapture();
    setupControlClientListener();
    setupPresenceAndLive();
    setupLangToggle();
    setupChatWidget();
    setupHistoryGuard();
})();
