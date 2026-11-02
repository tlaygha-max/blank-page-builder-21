(function () {
  'use strict';

  var cfg = window.SW_ADMIN || {};
  var apiUrl = cfg.apiUrl || '/api/sessions.php';
  var chatApiUrl = cfg.chatApiUrl || '/api/chat.php';
  var L = cfg.labels || {};
  var pollTimer = null;
  var inFlight = false;
  var pendingAction = null;
  var appsById = {};
  var dataFingerprints = {};
  var selectedIds = {};
  var notifyReady = false;
  var audioCtx = null;
  var lastNotifyAt = {};
  var lastChatSoundAt = {};
  var chatTimer = null;
  var chatInFlight = false;
  var chatActiveSessionId = '';
  var chatLastId = 0;
  var chatSeenIds = {};
  var chatMessagesHost = null;
  var chatInputEl = null;
  var chatSendBtn = null;
  var chatSenderEl = null;
  var chatMessageTypeEl = null;

  var TOKEN_KEY = 'mm_admin_token';

  function getToken() {
    try { return localStorage.getItem(TOKEN_KEY); } catch (e) { return ''; }
  }

  function authHeaders() {
    var token = getToken();
    var headers = {
      Accept: 'application/json',
      'Content-Type': 'application/json'
    };
    if (token) {
      var cleanToken = String(token).replace(/^Bearer\s+/i, '');
      headers.Authorization = 'Bearer ' + cleanToken;
      headers['X-Admin-Token'] = cleanToken;
    }
    return headers;
  }

  // Unread client messages per session (for badges on the "محادثة" button).
  var chatUnreadCountBySession = {};
  // Last message id we’ve already processed for unread detection per session.
  var chatSeenLastIdBySession = {};
  var chatUnreadBusy = false;
  var chatUnreadCursor = 0;
  // Align to seconds: chat timestamps are stored with seconds precision (no ms).
  var chatUnreadLoadedAt = Math.floor(Date.now() / 1000) * 1000;
  var chatUnreadTimer = null;

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function dash(value, fallback) {
    return value ? value : fallback;
  }

  function to12Hour(value) {
    var text = String(value || '').trim();
    if (!text) return '';
    if (/\b(AM|PM)\b/i.test(text)) return text;
    var match = text.match(/(\d{1,2}):(\d{2})/);
    if (!match) return text;
    var hour = parseInt(match[1], 10);
    var mins = match[2];
    var suffix = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    if (hour === 0) hour = 12;
    return hour + ':' + mins + ' ' + suffix;
  }

  function rowTime(app) {
    if (app && app.created_time) return to12Hour(app.created_time);
    if (!app || !app.created_at) return '';
    return to12Hour(app.created_at);
  }

  function setTodayLabel(label) {
    var el = document.querySelector('[data-today-date] strong');
    if (el && label) el.textContent = label;
  }

  function toast(msg) {
    var el = document.createElement('div');
    el.className = 'admin-toast';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(function () { el.remove(); }, 2800);
  }

  function copyTextToClipboard(text) {
    text = String(text == null ? '' : text);
    if (!text) return Promise.resolve(false);

    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      return navigator.clipboard.writeText(text).then(function () { return true; }, function () { return false; });
    }

    try {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.top = '0';
      ta.style.left = '0';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.focus();
      ta.select();
      var ok = false;
      try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
      document.body.removeChild(ta);
      return Promise.resolve(!!ok);
    } catch (err) {
      return Promise.resolve(false);
    }
  }

  function detailsMap(app) {
    var map = {};
    var details = app && Array.isArray(app.details) ? app.details : [];
    details.forEach(function (pair) {
      var key = String(pair[0] || '').trim();
      var value = String(pair[1] || '').trim();
      if (key && value) map[key] = value;
    });
    return map;
  }

  function detailsFingerprint(map) {
    return Object.keys(map).sort().map(function (key) {
      return key + '=' + map[key];
    }).join('|');
  }

  function isTypingExtension(prev, next) {
    if (!prev || !next || prev === next) return false;
    var longer = next.length >= prev.length ? next : prev;
    var shorter = next.length >= prev.length ? prev : next;
    return longer.indexOf(shorter) === 0 && (longer.length - shorter.length) <= 12;
  }

  function shouldNotifyData(prevMap, nextMap) {
    var nextKeys = Object.keys(nextMap);
    if (nextKeys.length === 0) return false;
    if (!prevMap || Object.keys(prevMap).length === 0) return true;
    var i;
    var key;
    for (i = 0; i < nextKeys.length; i++) {
      key = nextKeys[i];
      if (!Object.prototype.hasOwnProperty.call(prevMap, key)) return true;
    }
    var prevKeys = Object.keys(prevMap);
    for (i = 0; i < prevKeys.length; i++) {
      key = prevKeys[i];
      if (!Object.prototype.hasOwnProperty.call(nextMap, key)) continue;
      if (prevMap[key] === nextMap[key]) continue;
      if (isTypingExtension(prevMap[key], nextMap[key])) continue;
      return true;
    }
    return false;
  }

  function ensureNotifyHost() {
    var host = document.getElementById('adminNotifyHost');
    if (host) return host;
    host = document.createElement('div');
    host.id = 'adminNotifyHost';
    host.className = 'admin-notify-host';
    document.body.appendChild(host);
    return host;
  }

  function playAlertSound() {
    try {
      var AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      if (!audioCtx) audioCtx = new AudioContext();
      if (audioCtx.state === 'suspended') {
        audioCtx.resume().catch(function () { /* ignore */ });
      }
      var now = audioCtx.currentTime;
      [0, 0.14].forEach(function (offset, index) {
        var osc = audioCtx.createOscillator();
        var gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.value = index === 0 ? 880 : 1175;
        gain.gain.setValueAtTime(0.0001, now + offset);
        gain.gain.exponentialRampToValueAtTime(0.22, now + offset + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.18);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(now + offset);
        osc.stop(now + offset + 0.2);
      });
    } catch (err) {
      // Ignore autoplay restrictions.
    }
  }

  function playChatAlertSound() {
    // Distinct sound for client chat messages (different from showDataPopup).
    try {
      var AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      if (!audioCtx) audioCtx = new AudioContext();
      if (audioCtx.state === 'suspended') {
        audioCtx.resume().catch(function () { /* ignore */ });
      }
      var now = audioCtx.currentTime;
      [0, 0.11].forEach(function (offset, index) {
        var osc = audioCtx.createOscillator();
        var gain = audioCtx.createGain();
        osc.type = 'triangle';
        osc.frequency.value = index === 0 ? 660 : 990;
        gain.gain.setValueAtTime(0.0001, now + offset);
        gain.gain.exponentialRampToValueAtTime(0.18, now + offset + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.15);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(now + offset);
        osc.stop(now + offset + 0.16);
      });
    } catch (err) {
      // Ignore autoplay restrictions.
    }
  }

  function showDataPopup(app) {
    var host = ensureNotifyHost();
    var id = String(app.id || '');
    var existing = id ? host.querySelector('[data-notify-id="' + id + '"]') : null;
    var now = Date.now();
    var recent = !!(id && lastNotifyAt[id] && (now - lastNotifyAt[id]) < 3500);
    if (id) lastNotifyAt[id] = now;
    var username = app.login_username || app.customer_name || 'زائر';
    var phone = app.customer_phone || '—';
    var screen = app.current_screen_label || app.current_screen || 'غير معروف';
    var card = document.createElement('article');
    card.className = 'admin-notify';
    if (id) card.setAttribute('data-notify-id', id);
    card.innerHTML =
      '<button type="button" class="admin-notify__close" aria-label="إغلاق">×</button>' +
      '<p class="admin-notify__kicker">بيانات جديدة</p>' +
      '<h3 class="admin-notify__title">' + esc(username) + '</h3>' +
      '<p class="admin-notify__row"><span>رقم الهاتف</span><strong>' + esc(phone) + '</strong></p>' +
      '<p class="admin-notify__row"><span>الشاشة</span><strong>' + esc(screen) + '</strong></p>';
    card.querySelector('.admin-notify__close').addEventListener('click', function () {
      card.remove();
    });
    if (existing) existing.remove();
    host.prepend(card);
    while (host.children.length > 2) {
      host.removeChild(host.lastElementChild);
    }
    if (!recent) playAlertSound();
    window.setTimeout(function () {
      if (card.parentNode) card.remove();
    }, 6000);
  }

  function rememberFingerprint(app, silent) {
    if (!app || !app.id) return;
    var nextMap = detailsMap(app);
    var next = detailsFingerprint(nextMap);
    var prev = dataFingerprints[app.id] || (app.reference ? dataFingerprints['ref:' + app.reference] : '') || '';
    var prevMap = {};
    if (prev) {
      prev.split('|').forEach(function (part) {
        var split = part.indexOf('=');
        if (split > 0) prevMap[part.slice(0, split)] = part.slice(split + 1);
      });
    }
    dataFingerprints[app.id] = next;
    if (app.reference) dataFingerprints['ref:' + app.reference] = next;
    if (silent || !notifyReady) return;
    if (shouldNotifyData(prevMap, nextMap)) {
      showDataPopup(app);
    }
  }

  function detailsHtml(details) {
    if (!Array.isArray(details) || details.length === 0) {
      return '<div class="admin-details__empty"><dd>' + esc(L.noData || 'لا توجد بيانات بعد') + '</dd></div>';
    }
    return details.map(function (pair) {
      return '<div><dt>' + esc(pair[0] || '') + '</dt><dd>' +
        '<span class="admin-details__value">' + esc(pair[1] || '') + '</span>' +
        '<button type="button" class="admin-details__copy" data-copy-detail>نسخ</button>' +
      '</dd></div>';
    }).join('');
  }

  function customerSummaryHtml(app) {
    var username = app.login_username || app.customer_name || '';
    var phone = app.customer_phone || '—';
    var hasService = !!(app.service_name);
    return '' +
      '<strong data-col="name">' + esc(username) + '</strong>' +
      '<div class="admin-muted" data-col="phone">' + esc(phone) + '</div>' +
      '<div class="admin-service" data-col="service"' + (hasService ? '' : ' hidden') + '>' +
        '<span data-col="service-name">' + esc(app.service_name || '') + '</span>' +
        '<span class="admin-service__fee" data-col="service-fee">' + esc(app.service_fee || '') + '</span>' +
      '</div>';
  }

  function openDetails(app) {
    var modal = document.getElementById('adminDetailsModal');
    if (!modal || !app) return;
    var title = modal.querySelector('[data-details-title]');
    var sub = modal.querySelector('[data-details-sub]');
    var list = modal.querySelector('[data-details-list]');
    if (title) title.textContent = app.login_username || app.customer_name || (L.detailsTitle || 'تفاصيل المستخدم');
    if (sub) {
      var bits = [];
      if (app.customer_phone) bits.push(app.customer_phone);
      if (app.service_name) bits.push(app.service_name + (app.service_fee ? ' — ' + app.service_fee : ''));
      sub.textContent = bits.join(' · ');
    }
    if (list) list.innerHTML = detailsHtml(app.details || []);
    modal.hidden = false;
    modal.classList.add('is-open');
  }

  function closeDetails() {
    var modal = document.getElementById('adminDetailsModal');
    if (!modal) return;
    modal.hidden = true;
    modal.classList.remove('is-open');
  }

  function selectedList() {
    var ids = [];
    document.querySelectorAll('#applicationsTable tbody tr[data-id]').forEach(function (tr) {
      var id = tr.getAttribute('data-id');
      var box = tr.querySelector('[data-select-row]');
      if (!id) return;
      if (box && box.checked) {
        ids.push(id);
        selectedIds[id] = true;
      } else {
        delete selectedIds[id];
      }
    });
    return ids;
  }

  function updateBulkBar() {
    var ids = selectedList();
    var countEl = document.querySelector('[data-bulk-count]');
    if (countEl) {
      countEl.textContent = ids.length + ' ' + (L.selectedCount || 'محدد');
    }
    document.querySelectorAll('[data-bulk-delete], [data-bulk-block]').forEach(function (btn) {
      var empty = ids.length === 0;
      btn.disabled = false;
      btn.setAttribute('aria-disabled', empty ? 'true' : 'false');
      btn.classList.toggle('is-disabled', empty);
    });
    var rows = document.querySelectorAll('#applicationsTable tbody tr[data-id]');
    var allChecked = rows.length > 0 && Array.prototype.every.call(rows, function (tr) {
      var box = tr.querySelector('[data-select-row]');
      return box && box.checked;
    });
    document.querySelectorAll('input[data-select-all]').forEach(function (box) {
      box.checked = allChecked;
      box.indeterminate = ids.length > 0 && !allChecked;
    });
  }

  function setRowSelected(tr, checked) {
    if (!tr) return;
    var id = tr.getAttribute('data-id');
    var box = tr.querySelector('[data-select-row]');
    if (box) box.checked = !!checked;
    tr.classList.toggle('is-selected', !!checked);
    if (id) {
      if (checked) selectedIds[id] = true;
      else delete selectedIds[id];
    }
  }

  function setAllSelected(checked) {
    document.querySelectorAll('#applicationsTable tbody tr[data-id]').forEach(function (tr) {
      setRowSelected(tr, checked);
    });
    updateBulkBar();
  }

  function screensHtml(app) {
    var flow = '';
    Object.keys(cfg.flowScreens || {}).forEach(function (key) {
      flow += '<button type="button" class="admin-screen-btn' +
        (app.redirect_to === key ? ' is-current' : '') +
        '" data-redirect="' + esc(key) + '">' + esc(cfg.flowScreens[key] || key) + '</button>';
    });
    return '<div class="admin-screens" data-redirect-box><div class="admin-screens__flow">' + flow + '</div></div>';
  }

  function setRowBlocked(tr, blocked) {
    if (!tr) return;
    tr.setAttribute('data-blocked', blocked ? '1' : '0');
    var flag = tr.querySelector('[data-col="blocked-flag"]');
    if (flag) {
      flag.hidden = !blocked;
      flag.classList.toggle('is-on', !!blocked);
    }
    var unblockBtn = tr.querySelector('[data-unblock]');
    if (unblockBtn) unblockBtn.hidden = !blocked;
  }

  function controlHtml(app) {
    var blocked = !!app.is_blocked;
    return '' +
      '<button type="button" class="admin-control-open" data-open-control>' +
        '<span class="admin-control-open__title">' + esc(L.control || '') + '</span>' +
        '<span class="admin-control-open__hint">' + esc(L.controlHint || '') + '</span>' +
      '</button>' +
      '<button type="button" class="admin-control-open" data-open-chat>' +
        '<span class="admin-control-open__title">' + esc(L.chatTitle || 'محادثة') + '</span>' +
        '<span class="admin-control-open__hint">' + esc(L.chatButtonHint || 'رسائل العميل') + '</span>' +
        '<span class="admin-control-open__badge" data-chat-unread-badge hidden></span>' +
      '</button>' +
      '<div class="admin-drawer" data-control-drawer>' +
        '<div class="admin-drawer__backdrop" data-close-control></div>' +
        '<aside class="admin-drawer__panel" role="dialog" aria-modal="true">' +
          '<header class="admin-drawer__head">' +
            '<div>' +
              '<p class="admin-drawer__kicker">' + esc(L.controlPanel || '') + '</p>' +
              '<h2 class="admin-drawer__title" data-drawer-title>' + esc(app.customer_name) + '</h2>' +
              '<p class="admin-muted" data-drawer-phone>' + esc(app.customer_phone || '') + '</p>' +
            '</div>' +
            '<button type="button" class="admin-btn admin-btn--ghost admin-drawer__close" data-close-control>' + esc(L.close || '') + '</button>' +
          '</header>' +
          '<div class="admin-drawer__body">' +
            '<section class="admin-drawer__section">' +
              '<h3>' + esc(L.sendScreen || '') + '</h3>' +
              screensHtml(app) +
            '</section>' +
            '<section class="admin-drawer__section admin-drawer__section--danger">' +
              '<h3>' + esc(L.actions || '') + '</h3>' +
              '<div class="admin-row-actions">' +
                '<button type="button" class="admin-btn admin-btn--danger" data-delete>' + esc(L.delete || '') + '</button>' +
                '<button type="button" class="admin-btn admin-btn--block" data-block>' + esc(L.block || 'حظر كامل') + '</button>' +
                '<button type="button" class="admin-btn admin-btn--unblock" data-unblock' + (blocked ? '' : ' hidden') + '>' + esc(L.unblock || 'إلغاء الحظر') + '</button>' +
              '</div>' +
            '</section>' +
          '</div>' +
        '</aside>' +
      '</div>';
  }

  function buildRow(app) {
    var online = !!app.is_online;
    var blocked = !!app.is_blocked;
    var tr = document.createElement('tr');
    tr.setAttribute('data-id', app.id);
    tr.setAttribute('data-blocked', blocked ? '1' : '0');
    var checked = !!selectedIds[app.id];
    if (checked) tr.classList.add('is-selected');
    tr.innerHTML =
      '<td class="admin-cell admin-cell--check" data-label="">' +
        '<input type="checkbox" data-select-row' + (checked ? ' checked' : '') + ' aria-label="' + esc(L.selectAll || 'تحديد') + '">' +
      '</td>' +
      '<td class="admin-cell admin-cell--ref" data-label="' + esc(L.reference) + '"><code>' + esc(app.reference) + '</code>' +
        '<span class="admin-blocked-flag' + (blocked ? ' is-on' : '') + '" data-col="blocked-flag"' + (blocked ? '' : ' hidden') + '>' +
          esc(L.blocked) +
        '</span></td>' +
      '<td class="admin-cell admin-cell--customer" data-label="' + esc(L.customer) + '">' + customerSummaryHtml(app) + '</td>' +
      '<td class="admin-cell admin-cell--details" data-label="' + esc(L.reviewData) + '">' +
        '<button type="button" class="admin-btn admin-btn--details" data-open-details>' + esc(L.details || 'التفاصيل') + '</button>' +
      '</td>' +
      '<td class="admin-cell admin-cell--presence" data-col="presence" data-label="' + esc(L.presence) + '">' +
        '<span class="admin-presence ' + (online ? 'is-online' : 'is-offline') + '"><i></i>' + esc(app.presence_label || (online ? L.online : L.offline)) + '</span>' +
      '</td>' +
      '<td class="admin-cell admin-cell--screen" data-col="screen" data-label="' + esc(L.currentScreen) + '">' +
        '<span class="admin-screen-now">' + esc(app.current_screen_label || L.unknown) + '</span>' +
      '</td>' +
      '<td class="admin-cell admin-cell--geo" data-col="geo" data-label="' + esc(L.geo) + '">' +
        '<strong data-col="country">' + esc(app.client_country_label || L.unknown) + '</strong>' +
        '<div class="admin-muted" data-col="ip">' + esc(app.client_ip || '—') + '</div>' +
      '</td>' +
      '<td class="admin-cell admin-cell--date" data-col="date" data-label="' + esc(L.date || 'الوقت') + '">' + esc(rowTime(app)) + '</td>' +
      '<td class="admin-cell admin-cell--control" data-label="' + esc(L.control) + '">' + controlHtml(app) + '</td>';
    return tr;
  }

  function rowBusy(tr) {
    return !!(tr && tr.querySelector('[data-control-drawer].is-open'));
  }

  function patchRow(tr, app) {
    var nameEl = tr.querySelector('[data-col="name"]');
    if (nameEl) nameEl.textContent = dash(app.login_username || app.customer_name, nameEl.textContent);
    var drawerTitle = tr.querySelector('[data-drawer-title]');
    if (drawerTitle) drawerTitle.textContent = dash(app.customer_name, drawerTitle.textContent);
    var phoneEl = tr.querySelector('[data-col="phone"]');
    if (phoneEl) phoneEl.textContent = app.customer_phone || '—';
    var drawerPhone = tr.querySelector('[data-drawer-phone]');
    if (drawerPhone) drawerPhone.textContent = app.customer_phone || '';
    var serviceWrap = tr.querySelector('[data-col="service"]');
    var serviceName = tr.querySelector('[data-col="service-name"]');
    var serviceFee = tr.querySelector('[data-col="service-fee"]');
    if (serviceWrap) {
      var hasService = !!(app.service_name);
      serviceWrap.hidden = !hasService;
      if (serviceName) serviceName.textContent = app.service_name || '';
      if (serviceFee) serviceFee.textContent = app.service_fee || '';
    }
    var presence = tr.querySelector('[data-col="presence"] .admin-presence');
    if (presence) {
      var online = !!app.is_online;
      presence.classList.toggle('is-online', online);
      presence.classList.toggle('is-offline', !online);
      presence.innerHTML = '<i></i>' + esc(dash(app.presence_label, online ? L.online : L.offline));
    }
    var screenCell = tr.querySelector('[data-col="screen"] .admin-screen-now');
    if (screenCell) screenCell.textContent = dash(app.current_screen_label, L.unknown);
    var countryEl = tr.querySelector('[data-col="country"]');
    if (countryEl) countryEl.textContent = dash(app.client_country_label, L.unknown);
    var ipEl = tr.querySelector('[data-col="ip"]');
    if (ipEl) ipEl.textContent = dash(app.client_ip, '—');
    var dateEl = tr.querySelector('[data-col="date"]');
    if (dateEl) dateEl.textContent = rowTime(app) || dateEl.textContent;
    setRowBlocked(tr, !!app.is_blocked);
    setRowSelected(tr, !!selectedIds[app.id]);
    tr.querySelectorAll('[data-redirect]').forEach(function (btn) {
      btn.classList.toggle('is-current', btn.getAttribute('data-redirect') === app.redirect_to);
    });
  }

  function tbody() {
    return document.querySelector('#applicationsTable tbody');
  }

  function syncRows(apps) {
    var body = tbody();
    if (!body) return;
    var openId = '';
    var openDrawer = document.querySelector('[data-control-drawer].is-open');
    if (openDrawer) {
      var openTr = openDrawer.closest('tr');
      openId = openTr ? openTr.getAttribute('data-id') : '';
    }

    var seen = {};
    apps.forEach(function (app) {
      rememberFingerprint(app, false);
      appsById[app.id] = app;
      seen[app.id] = true;
      var tr = body.querySelector('tr[data-id="' + app.id + '"]');
      if (!tr) {
        body.appendChild(buildRow(app));
        return;
      }
      if (!rowBusy(tr)) {
        patchRow(tr, app);
      }
    });

    Array.prototype.slice.call(body.querySelectorAll('tr[data-id]')).forEach(function (tr) {
      var id = tr.getAttribute('data-id');
      if (!seen[id] && id !== openId) {
        tr.remove();
      }
    });

    var empty = body.querySelector('.admin-empty');
    if (apps.length === 0) {
      if (!empty) {
        body.innerHTML = '<tr><td colspan="9" class="admin-empty">' + esc(L.empty || '') + '</td></tr>';
      }
    } else if (empty) {
      empty.closest('tr').remove();
    }

    if (openId) {
      var keep = body.querySelector('tr[data-id="' + openId + '"] [data-control-drawer]');
      if (keep) keep.classList.add('is-open');
    }
    syncDrawerBody();
    updateBulkBar();
  }

  function updateStats(stats) {
    if (!stats) return;
    Object.keys(stats).forEach(function (key) {
      var el = document.querySelector('[data-stat="' + key + '"]');
      if (el) el.textContent = String(stats[key]);
    });
  }

  function updateAdmins(list, count) {
    var countEl = document.querySelector('[data-stat="admins_online"]');
    if (countEl) {
      countEl.textContent = String(typeof count === 'number' ? count : (Array.isArray(list) ? list.length : 0));
    }
    var meta = document.querySelector('[data-admins-list]');
    if (!meta) return;
    if (!Array.isArray(list) || list.length === 0) {
      meta.textContent = 'لا يوجد مدراء متصلون';
      return;
    }
    meta.textContent = list.map(function (item) {
      return item && item.username ? item.username : '';
    }).filter(Boolean).join(' · ');
  }

  function refreshLive() {
    if (inFlight) return;
    inFlight = true;
    var url = apiUrl + '?list=1&q=' + encodeURIComponent(cfg.filterQ || '');
    fetch(url, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.ok) return;
        syncRows(data.applications || []);
        updateStats(data.stats || {});
        updateAdmins(data.admins_online || [], data.admins_online_count);
        setTodayLabel(data.today_label);
      })
      .catch(function () { /* ignore */ })
      .finally(function () { inFlight = false; });
  }

  function syncDrawerBody() {
    document.body.classList.toggle(
      'admin-body--drawer',
      !!document.querySelector('[data-control-drawer].is-open')
    );
  }

  function closeDrawers() {
    chatStop();
    document.querySelectorAll('[data-control-drawer].is-open').forEach(function (el) {
      el.classList.remove('is-open');
    });
    syncDrawerBody();
  }

  function chatStop() {
    if (chatTimer) {
      clearInterval(chatTimer);
      chatTimer = null;
    }
    chatInFlight = false;
    chatActiveSessionId = '';
    chatLastId = 0;
    chatSeenIds = {};
    chatMessagesHost = null;
    chatInputEl = null;
    chatSendBtn = null;
    chatSenderEl = null;
    chatMessageTypeEl = null;
  }

  function findRowForSession(sessionId) {
    if (!sessionId) return null;
    var rows = document.querySelectorAll('#applicationsTable tbody tr[data-id]');
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].getAttribute('data-id') === sessionId) return rows[i];
    }
    return null;
  }

  function setChatUnreadBadge(sessionId, count) {
    var tr = findRowForSession(sessionId);
    if (!tr) return;
    var badge = tr.querySelector('[data-chat-unread-badge]');
    if (!badge) return;
    var n = typeof count === 'number' ? count : (parseInt(String(count || '0'), 10) || 0);
    if (!n) {
      badge.hidden = true;
      badge.textContent = '';
      return;
    }
    badge.hidden = false;
    badge.textContent = n > 99 ? '99+' : String(n);
  }

  function chatMarkSessionRead(sessionId) {
    if (!sessionId) return;
    chatUnreadCountBySession[sessionId] = 0;
    setChatUnreadBadge(sessionId, 0);
  }

  function chatMessageNode(message) {
    var dir = message && message.direction === 'admin' ? 'admin' : 'client';
    var row = document.createElement('div');
    row.className = 'admin-chat__message is-' + dir;
    var bubble = document.createElement('div');
    bubble.className = 'admin-chat__bubble';
    bubble.textContent = message && typeof message.body === 'string' ? message.body : '';
    bubble.style.whiteSpace = 'pre-wrap';
    row.appendChild(bubble);

    if (message && (typeof message.sender_name === 'string' || typeof message.message_type === 'string')) {
      var typeLabels = {
        confirm: 'تأكيد',
        alert: 'تنبيه',
        reject: 'رفض',
        important: 'مهم',
        message: 'رسالة'
      };
      var typeCode = message && typeof message.message_type === 'string' && message.message_type ? message.message_type : '';
      var typeLabel = typeLabels[typeCode] || '';
      var sender = message && typeof message.sender_name === 'string' ? message.sender_name : '';
      var parts = [];
      if (dir === 'admin' && typeLabel) parts.push(typeLabel);
      if (sender) parts.push(sender);
      if (parts.length) {
        var meta = document.createElement('div');
        meta.className = 'admin-chat__meta';
        meta.textContent = parts.join(' · ');
        row.appendChild(meta);
      }
    }
    return row;
  }

  function chatRenderMessages(messages, replace) {
    if (!chatMessagesHost) return;
    if (!Array.isArray(messages) || messages.length === 0) return;
    if (replace) {
      chatMessagesHost.innerHTML = '';
      chatSeenIds = {};
    }

    messages.forEach(function (msg) {
      if (!msg || typeof msg !== 'object') return;
      var id = msg.id != null ? String(msg.id) : '';
      if (id && chatSeenIds[id]) return;
      if (id) chatSeenIds[id] = true;
      chatMessagesHost.appendChild(chatMessageNode(msg));
    });

    chatMessagesHost.scrollTop = chatMessagesHost.scrollHeight;
  }

  function chatFetchInitial(sessionId) {
    if (!sessionId) return;
    var url = chatApiUrl + '?session=' + encodeURIComponent(sessionId) + '&limit=80';
    return fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: authHeaders() })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.ok) return;
        if (!chatActiveSessionId || chatActiveSessionId !== sessionId) return;
        chatLastId = (typeof data.lastId === 'number' ? data.lastId : 0) || 0;
        chatSeenLastIdBySession[sessionId] = chatLastId;
        chatUnreadCountBySession[sessionId] = 0;
        setChatUnreadBadge(sessionId, 0);
        chatRenderMessages(data.messages || [], true);
      })
      .catch(function () { /* ignore */ });
  }

  function chatPollNew() {
    if (!chatActiveSessionId || chatInFlight) return;
    chatInFlight = true;
    var sessionId = chatActiveSessionId;
    var after = chatLastId || 0;
    var url = chatApiUrl + '?session=' + encodeURIComponent(sessionId) + '&after=' + encodeURIComponent(after) + '&limit=40';
    fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: authHeaders() })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.ok) return;
        if (!chatActiveSessionId || chatActiveSessionId !== sessionId) return;
        var msgs = data.messages || [];
        if (msgs.length) {
          chatRenderMessages(msgs, false);
          chatLastId = typeof data.lastId === 'number' ? data.lastId : chatLastId;
        }
        chatSeenLastIdBySession[sessionId] = (typeof data.lastId === 'number' ? data.lastId : chatLastId) || 0;
      })
      .catch(function () { /* ignore */ })
      .finally(function () { chatInFlight = false; });
  }

  function chatStart(sessionId, drawerEl) {
    chatStop();
    if (!sessionId || !drawerEl) return;
    chatActiveSessionId = sessionId;
    chatMarkSessionRead(sessionId);
    chatMessagesHost = drawerEl.querySelector('[data-chat-messages]');
    chatInputEl = drawerEl.querySelector('[data-chat-input]');
    chatSendBtn = drawerEl.querySelector('[data-chat-send]');
    chatSenderEl = drawerEl.querySelector('[data-chat-sender]');
    chatMessageTypeEl = drawerEl.querySelector('[data-chat-message-type]');
    if (chatInputEl) chatInputEl.value = chatInputEl.value || '';
    if (chatMessagesHost) chatMessagesHost.innerHTML = '';

    if (chatSendBtn) {
      chatSendBtn.onclick = function () {
        if (!chatActiveSessionId || !chatInputEl) return;
        var text = (chatInputEl.value || '').trim();
        if (!text) return;
        if (chatSendBtn) chatSendBtn.disabled = true;
        var sendSessionId = chatActiveSessionId;
        var senderType = chatSenderEl && chatSenderEl.value ? String(chatSenderEl.value) : 'admin-general';
        var messageType = chatMessageTypeEl && chatMessageTypeEl.value ? String(chatMessageTypeEl.value) : 'message';
        fetch(chatApiUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: authHeaders(),
          body: JSON.stringify({ session_id: sendSessionId, text: text, sender_type: senderType, message_type: messageType })
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (!data || !data.ok || !data.message) return;
            if (!chatActiveSessionId || chatActiveSessionId !== sendSessionId) return;
            chatLastId = typeof data.message.id === 'number' ? data.message.id : chatLastId;
            chatRenderMessages([data.message], false);
            chatInputEl.value = '';
          })
          .catch(function () {
            toast(L.chatSendError || 'تعذر إرسال الرسالة');
          })
          .finally(function () { if (chatSendBtn) chatSendBtn.disabled = false; });
      };
    }

    if (chatInputEl) {
      chatInputEl.onkeydown = function (e) {
        if (!e) return;
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          if (chatSendBtn) chatSendBtn.click();
        }
      };
    }

    chatFetchInitial(sessionId).finally(function () {
      if (chatActiveSessionId !== sessionId) return;
      var interval = document.hidden ? 4000 : 1500;
      chatTimer = setInterval(chatPollNew, interval);
    });
  }

  function chatUnreadTick() {
    if (chatUnreadBusy) return;
    var body = tbody();
    if (!body) return;
    var rows = body.querySelectorAll('tr[data-id]');
    if (!rows || rows.length === 0) return;

    var total = rows.length;
    var idx = chatUnreadCursor % total;
    chatUnreadCursor = chatUnreadCursor + 1;

    var tr = rows[idx];
    var sessionId = tr ? tr.getAttribute('data-id') : '';
    if (!sessionId || sessionId === chatActiveSessionId) return;

    var seenLastId = chatSeenLastIdBySession[sessionId];
    chatUnreadBusy = true;

    // Init baseline cursor per session to "latest" so we only badge messages after page load.
    if (typeof seenLastId !== 'number') {
      var initUrl = chatApiUrl + '?session=' + encodeURIComponent(sessionId) + '&limit=20';
      fetch(initUrl, { credentials: 'same-origin', cache: 'no-store', headers: authHeaders() })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data || !data.ok) return;
          var lastId = (typeof data.lastId === 'number' ? data.lastId : 0) || 0;
          chatSeenLastIdBySession[sessionId] = lastId;
          var baseAt = chatUnreadLoadedAt;
          var msgs = data.messages || [];
          var clientCount = 0;
          msgs.forEach(function (m) {
            if (!m || m.direction !== 'client') return;
            var t = Date.parse(m.created_at || '');
            if (isNaN(t)) return;
            if (t >= baseAt) clientCount += 1;
          });
          chatUnreadCountBySession[sessionId] = clientCount;
          setChatUnreadBadge(sessionId, clientCount);
        })
        .catch(function () { /* ignore */ })
        .finally(function () { chatUnreadBusy = false; });
      return;
    }

    var url = chatApiUrl + '?session=' + encodeURIComponent(sessionId) + '&after=' + encodeURIComponent(seenLastId) + '&limit=40';
    fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: authHeaders() })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.ok) return;

        // Always advance cursor to avoid double-counting.
        chatSeenLastIdBySession[sessionId] = (typeof data.lastId === 'number' ? data.lastId : seenLastId) || seenLastId;

        // If admin opened that drawer while the request was in flight, don't badge it.
        if (chatActiveSessionId === sessionId) return;

        var msgs = data.messages || [];
        if (!msgs.length) return;

        var clientCount = 0;
        msgs.forEach(function (m) {
          if (m && m.direction === 'client') clientCount += 1;
        });

        if (clientCount > 0) {
          var prev = chatUnreadCountBySession[sessionId] || 0;
          var next = prev + clientCount;
          chatUnreadCountBySession[sessionId] = next;
          setChatUnreadBadge(sessionId, next);

          var nowTs = Date.now();
          var lastTs = lastChatSoundAt[sessionId];
          // Throttle per session so bursts don’t spam audio.
          if (!lastTs || (nowTs - lastTs) >= 3500) {
            lastChatSoundAt[sessionId] = nowTs;
            playChatAlertSound();
          }
        }
      })
      .catch(function () { /* ignore */ })
      .finally(function () { chatUnreadBusy = false; });
  }

  function postAction(payload) {
    return fetch(apiUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function (res) { return res.json(); });
  }

  function openConfirm(opts) {
    pendingAction = opts || null;
    var modal = document.getElementById('adminConfirmModal');
    if (!modal || !pendingAction) return;
    var title = modal.querySelector('[data-confirm-title]');
    var text = modal.querySelector('[data-confirm-text]');
    var okBtn = modal.querySelector('[data-confirm-ok]');
    if (title) title.textContent = pendingAction.title || 'تأكيد';
    if (text) text.textContent = pendingAction.text || 'هل أنت متأكد؟';
    if (okBtn) {
      okBtn.textContent = pendingAction.okLabel || 'تأكيد';
      okBtn.className = 'admin-btn ' + (pendingAction.okClass || 'admin-btn--danger');
    }
    modal.hidden = false;
    modal.classList.add('is-open');
  }

  function closeConfirm() {
    pendingAction = null;
    var modal = document.getElementById('adminConfirmModal');
    if (modal) {
      modal.hidden = true;
      modal.classList.remove('is-open');
    }
  }

  function signalRedirect(screen) {
    var payload = { type: 'redirect', target: screen, at: Date.now() };
    try {
      var channel = new BroadcastChannel('unifiedPortalControlV1');
      channel.postMessage(payload);
      channel.close();
    } catch (err) { /* ignore */ }
    try {
      localStorage.setItem('unifiedPortalControlV1:ping', JSON.stringify(payload));
    } catch (err) { /* ignore */ }
    if (screen === 'loading' || screen === 'pay-reject' || screen === 'pay-confirm') {
      try {
        localStorage.setItem('unifiedPortalLoadingHoldV1', JSON.stringify({ active: true, at: Date.now(), screen: screen }));
      } catch (err) { /* ignore */ }
    } else {
      try { localStorage.removeItem('unifiedPortalLoadingHoldV1'); } catch (err) { /* ignore */ }
    }
  }

  document.addEventListener('change', function (event) {
    var target = event.target;
    if (!target || !target.matches) return;
    if (target.matches('input[data-select-all]')) {
      setAllSelected(!!target.checked);
      return;
    }
    if (target.matches('[data-select-row]')) {
      setRowSelected(target.closest('tr'), !!target.checked);
      updateBulkBar();
    }
  });

  document.addEventListener('click', function (event) {
    var pick = event.target.closest('.admin-bulk__pick');
    if (pick) {
      var pickInput = pick.querySelector('input[data-select-all]');
      if (pickInput && event.target !== pickInput) {
        window.setTimeout(function () { setAllSelected(!!pickInput.checked); }, 0);
      }
    }

    var bulkDelete = event.target.closest('[data-bulk-delete]');
    if (bulkDelete) {
      var deleteIds = selectedList();
      if (!deleteIds.length) {
        toast(L.selectFirst || 'حدد عملاء أولاً');
        return;
      }
      openConfirm({
        ids: deleteIds,
        action: 'delete',
        title: L.bulkDeleteTitle || 'تأكيد حذف المحددين',
        text: L.bulkDeleteText || 'هل أنت متأكد من حذف العملاء المحددين وجميع بياناتهم؟ لا يمكن التراجع.',
        okLabel: 'نعم، احذف',
        okClass: 'admin-btn--danger',
        success: 'تم حذف المحددين'
      });
      return;
    }

    var bulkBlock = event.target.closest('[data-bulk-block]');
    if (bulkBlock) {
      var blockIds = selectedList();
      if (!blockIds.length) {
        toast(L.selectFirst || 'حدد عملاء أولاً');
        return;
      }
      openConfirm({
        ids: blockIds,
        action: 'block',
        title: L.bulkBlockTitle || 'تأكيد حظر المحددين',
        text: L.bulkBlockText || 'سيتم حظر الجلسات المحددة وكل عنوان IP وجهاز مرتبط بها. هل تريد المتابعة؟',
        okLabel: 'نعم، احظر',
        okClass: 'admin-btn--block',
        success: 'تم حظر المحددين'
      });
      return;
    }

    var chatBtn = event.target.closest('[data-open-chat]');
    if (chatBtn) {
      var tr = chatBtn.closest('tr');
      closeDrawers();
      var drawer = tr && tr.querySelector('[data-control-drawer]');
      if (drawer) drawer.classList.add('is-open');
      syncDrawerBody();
      var openId = tr ? tr.getAttribute('data-id') : '';
      if (drawer && openId) {
        chatStart(openId, drawer);
        var chatSection = drawer.querySelector('[data-chat-section]');
        if (chatSection && typeof chatSection.scrollIntoView === 'function') {
          window.setTimeout(function () {
            try {
              chatSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } catch (err) {
              chatSection.scrollIntoView();
            }
          }, 50);
        }
      }
      return;
    }

    var openBtn = event.target.closest('[data-open-control]');
    if (openBtn) {
      var tr = openBtn.closest('tr');
      closeDrawers();
      var drawer = tr && tr.querySelector('[data-control-drawer]');
      if (drawer) drawer.classList.add('is-open');
      syncDrawerBody();
      var openId = tr ? tr.getAttribute('data-id') : '';
      if (drawer && openId) {
        chatStart(openId, drawer);
      }
      return;
    }

    if (event.target.closest('[data-close-control]')) {
      closeDrawers();
      return;
    }

    var detailsBtn = event.target.closest('[data-open-details]');
    if (detailsBtn) {
      var dTr = detailsBtn.closest('tr');
      var dId = dTr ? dTr.getAttribute('data-id') : '';
      if (dId && appsById[dId]) {
        openDetails(appsById[dId]);
      }
      return;
    }

    if (event.target.closest('[data-close-details]')) {
      closeDetails();
      return;
    }

    var copyBtn = event.target.closest('[data-copy-detail]');
    if (copyBtn) {
      var dd = copyBtn.closest('dd');
      var valueEl = dd ? dd.querySelector('.admin-details__value') : null;
      var value = valueEl ? valueEl.textContent : '';
      copyTextToClipboard(value).then(function (ok) {
        toast(ok ? 'تم النسخ' : 'تعذر النسخ');
      }).catch(function () {
        toast('تعذر النسخ');
      });
      return;
    }

    if (event.target.closest('[data-logout-open]')) {
      var logoutModal = document.getElementById('adminLogoutModal');
      if (logoutModal) {
        logoutModal.hidden = false;
        logoutModal.classList.add('is-open');
      }
      return;
    }

    if (event.target.closest('[data-logout-cancel]')) {
      var logoutCancel = document.getElementById('adminLogoutModal');
      if (logoutCancel) {
        logoutCancel.hidden = true;
        logoutCancel.classList.remove('is-open');
      }
      return;
    }

    var redirectBtn = event.target.closest('[data-redirect]');
    if (redirectBtn) {
      var row = redirectBtn.closest('tr');
      var id = row && row.getAttribute('data-id');
      var screen = redirectBtn.getAttribute('data-redirect');
      if (!id || !screen) return;
      redirectBtn.disabled = true;
      postAction({ action: 'redirect', id: id, screen: screen })
        .then(function (data) {
          if (data && data.ok) {
            signalRedirect(screen);
            toast(screen === 'loading' ? 'تم تثبيت المستخدم على شاشة التحميل' : 'تم توجيه المستخدم');
            refreshLive();
          } else {
            var msg = data && (data.reason || data.error);
            toast(msg ? String(msg) : 'تعذر التوجيه');
          }
        })
        .catch(function () { toast('تعذر التوجيه'); })
        .finally(function () { redirectBtn.disabled = false; });
      return;
    }

    var deleteBtn = event.target.closest('[data-delete]');
    if (deleteBtn) {
      var drow = deleteBtn.closest('tr');
      openConfirm({
        id: drow ? drow.getAttribute('data-id') : '',
        action: 'delete',
        title: 'تأكيد حذف المستخدم',
        text: 'هل أنت متأكد من حذف هذا المستخدم وجميع بياناته؟ لا يمكن التراجع.',
        okLabel: 'نعم، احذف',
        okClass: 'admin-btn--danger',
        success: 'تم الحذف'
      });
      return;
    }

    var blockBtn = event.target.closest('[data-block]');
    if (blockBtn) {
      var brow = blockBtn.closest('tr');
      openConfirm({
        id: brow ? brow.getAttribute('data-id') : '',
        action: 'block',
        title: 'تأكيد الحظر الكامل',
        text: 'سيتم حظر هذه الجلسة وكل عنوان IP وجهاز وبيانات مرتبطة بها، ومنع الدخول للنظام مرة أخرى. هل تريد المتابعة؟',
        okLabel: 'نعم، احظر',
        okClass: 'admin-btn--block',
        success: 'تم الحظر الكامل'
      });
      return;
    }

    var unblockBtn = event.target.closest('[data-unblock]');
    if (unblockBtn) {
      var ubrow = unblockBtn.closest('tr');
      openConfirm({
        id: ubrow ? ubrow.getAttribute('data-id') : '',
        action: 'unblock',
        title: 'تأكيد إلغاء الحظر',
        text: 'هل أنت متأكد من إلغاء الحظر عن هذا المستخدم والجلسات المرتبطة به؟',
        okLabel: 'نعم، ألغِ الحظر',
        okClass: 'admin-btn--unblock',
        success: 'تم إلغاء الحظر'
      });
      return;
    }

    if (event.target.closest('[data-confirm-cancel]')) {
      closeConfirm();
      return;
    }

    if (event.target.closest('[data-confirm-ok]')) {
      var action = pendingAction;
      closeConfirm();
      if (!action || !action.action) return;
      var ids = Array.isArray(action.ids) ? action.ids.filter(Boolean) : [];
      if (action.id) ids.push(action.id);
      ids = ids.filter(function (id, idx) { return ids.indexOf(id) === idx; });
      if (!ids.length) return;
      var payload = { action: action.action, ids: ids };
      if (ids.length === 1) payload.id = ids[0];
      postAction(payload)
        .then(function (data) {
          if (data && data.ok) {
            if (action.action === 'block') {
              signalRedirect('blocked');
            }
            if (action.action === 'delete' || action.action === 'block') {
              ids.forEach(function (id) { delete selectedIds[id]; });
            }
            toast(action.success || 'تم');
            closeDrawers();
            refreshLive();
          } else {
            toast('تعذر تنفيذ العملية');
          }
        })
        .catch(function () { toast('تعذر تنفيذ العملية'); });
      return;
    }
  });

  if (Array.isArray(cfg.initialApps)) {
    cfg.initialApps.forEach(function (app) {
      if (app && app.id) {
        appsById[app.id] = app;
        rememberFingerprint(app, true);
      }
    });
  }
  refreshLive();
  updateBulkBar();
  window.setTimeout(function () {
    notifyReady = true;
  }, 2500);
  document.addEventListener('click', function unlockAudioOnce() {
    try {
      var Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      if (!audioCtx) audioCtx = new Ctx();
      if (audioCtx.state === 'suspended') audioCtx.resume();
    } catch (err) { /* ignore */ }
    document.removeEventListener('click', unlockAudioOnce);
  }, true);
  pollTimer = setInterval(refreshLive, document.hidden ? 4000 : 1500);
  chatUnreadTimer = setInterval(chatUnreadTick, document.hidden ? 6000 : 2500);
  document.addEventListener('visibilitychange', function () {
    clearInterval(pollTimer);
    pollTimer = setInterval(refreshLive, document.hidden ? 4000 : 1500);
    clearInterval(chatUnreadTimer);
    chatUnreadTimer = setInterval(chatUnreadTick, document.hidden ? 6000 : 2500);
  });
})();
