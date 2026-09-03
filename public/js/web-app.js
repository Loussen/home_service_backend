(function () {
    var API = '/api/v1';
    var tokenKey = 'mysancho_web_token';
    var requestKey = 'mysancho_web_request_id';
    var selectedCategoriesKey = 'mysancho_selected_categories';
    var page = document.body ? document.body.getAttribute('data-page') : '';
    var meCache = null;
    var mapsPromise = null;
    var mapsAuthFailed = false;
    var pickerMaps = {};
    var matchMarkers = [];
    var matchMarkerById = {};
    var matchInfoById = {};
    var selectedMatchId = null;

    function el(id) {
        return document.getElementById(id);
    }

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    var MATCH_REASON_AZ = {
        'match.reason.distance': '{km} km',
        'match.reason.schedule_ok': 'Cədvəl uyğun',
        'match.reason.schedule_miss': 'Cədvəl uyğun deyil',
        'match.reason.category': '{name}',
        'match.reason.repeat_client': 'Əvvəl işlədiyiniz provayder',
        'match.reason.bump': 'Önə çıxıb',
    };

    var MATCH_REASON_HINT_AZ = {
        'match.reason.bump':
            'İcraçı profilini ödənişlə müvəqqəti yüksəldib — axtarışda daha görünəndir. Qalan: {hours} saat.',
    };

    function formatMatchReason(reason) {
        if (!reason) return '';
        if (reason.label) return String(reason.label);
        var tpl = MATCH_REASON_AZ[reason.key] || reason.key || '';
        var params = reason.params || {};
        return String(tpl).replace(/\{(\w+)\}/g, function (_, key) {
            return params[key] != null ? String(params[key]) : '';
        });
    }

    function formatMatchReasonHint(reason) {
        if (!reason) return '';
        var tpl = MATCH_REASON_HINT_AZ[reason.key];
        if (!tpl) return formatMatchReason(reason);
        var params = reason.params || {};
        return String(tpl).replace(/\{(\w+)\}/g, function (_, key) {
            return params[key] != null ? String(params[key]) : '';
        });
    }

    function formatMatchReasons(list) {
        return (list || [])
            .map(formatMatchReason)
            .filter(Boolean)
            .join(' · ');
    }

    function renderMatchReasonsHtml(list) {
        var items = list || [];
        if (!items.length) return '';
        return (
            '<div class="match-reasons">' +
            items
                .map(function (reason) {
                    if (!reason) return '';
                    if (reason.key === 'match.reason.bump') {
                        var hint = formatMatchReasonHint(reason);
                        return (
                            '<span class="match-reason-bump" title="' +
                            esc(hint) +
                            '" aria-label="' +
                            esc(hint) +
                            '">' +
                            '<span class="match-reason-bump-icon" aria-hidden="true">↑</span>' +
                            '<span class="match-reason-bump-label">Önə çıxıb</span>' +
                            '</span>'
                        );
                    }
                    var text = formatMatchReason(reason);
                    return text
                        ? '<span class="match-reason-chip">' + esc(text) + '</span>'
                        : '';
                })
                .join('') +
            '</div>'
        );
    }

    function formatConnectHint(me) {
        if (!me || me.active_role !== 'client') return '';
        var q = me.connect_quota || {};
        var daily = q.daily_remaining != null ? q.daily_remaining : 0;
        if (q.in_free_window) {
            var left = q.free_remaining != null
                ? q.free_remaining
                : Math.max(0, (q.free_quota || 5) - (q.free_used || 0));
            var quota = q.free_quota != null ? q.free_quota : 5;
            if (left > 0) {
                return 'Pulsuz CONNECT: ' + left + '/' + quota + ' qalıb · bu gün ' + daily;
            }
            return 'CONNECT pulsuzdur · bu gün ' + daily + ' qalıb';
        }
        var fee = Number(q.fee || 0);
        var feeText = Number.isInteger(fee) ? String(fee) : fee.toFixed(1);
        return 'CONNECT · ' + feeText + ' AZN · bu gün ' + daily + ' qalıb';
    }

    function paintConnectHint(me) {
        var hint = el('connect-hint');
        if (!hint) return;
        var text = formatConnectHint(me || meCache);
        if (!text) {
            hint.hidden = true;
            hint.textContent = '';
            return;
        }
        hint.hidden = false;
        hint.textContent = text;
    }

    function log(msg, data) {
        var box = el('log');
        if (!box) return;
        var line = '[' + new Date().toLocaleTimeString() + '] ' + msg;
        if (data) {
            line += '\n' + JSON.stringify(data, null, 2);
        }
        box.textContent = (line + '\n\n' + box.textContent).slice(0, 12000);
    }

    function toast(type, msg) {
        var stack = el('toast-stack');
        if (!stack) return;
        var item = document.createElement('div');
        item.className = 'toast ' + (type || 'info');
        item.textContent = msg;
        stack.appendChild(item);
        setTimeout(function () {
            item.remove();
        }, 2600);
    }

    /**
     * Branded modal alert (replaces native window.alert).
     * @param {{title?: string, message: string, tone?: string, confirmLabel?: string}} opts
     * @returns {Promise<void>}
     */
    function showAppAlert(opts) {
        opts = opts || {};
        var title = opts.title || 'MySancho';
        var message = opts.message || '';
        var tone = opts.tone || 'info';
        var confirmLabel = opts.confirmLabel || 'Başa düşdüm';

        return new Promise(function (resolve) {
            var existing = document.getElementById('app-alert-modal');
            if (existing) existing.remove();

            var modal = document.createElement('div');
            modal.id = 'app-alert-modal';
            modal.className = 'modal app-alert-modal';
            modal.setAttribute('role', 'alertdialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-labelledby', 'app-alert-title');
            modal.setAttribute('aria-describedby', 'app-alert-message');
            modal.innerHTML =
                '<div class="modal-card app-alert-card tone-' + tone + '">' +
                '<div class="app-alert-icon" aria-hidden="true">' +
                (tone === 'danger'
                    ? '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><circle cx="12" cy="16" r="1.1" fill="currentColor" stroke="none"/></svg>'
                    : '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><circle cx="12" cy="16" r="1.1" fill="currentColor" stroke="none"/></svg>') +
                '</div>' +
                '<h3 id="app-alert-title">' + escapeHtml(title) + '</h3>' +
                '<p id="app-alert-message" class="app-alert-message">' + escapeHtml(message) + '</p>' +
                '<div class="modal-actions app-alert-actions">' +
                '<button type="button" class="btn btn-primary" id="app-alert-ok">' + escapeHtml(confirmLabel) + '</button>' +
                '</div></div>';

            document.body.appendChild(modal);
            document.body.classList.add('app-alert-open');

            function close() {
                document.body.classList.remove('app-alert-open');
                modal.remove();
                resolve();
            }

            modal.querySelector('#app-alert-ok').addEventListener('click', close);
            modal.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' || e.key === 'Enter') {
                    e.preventDefault();
                    close();
                }
            });
            setTimeout(function () {
                var btn = modal.querySelector('#app-alert-ok');
                if (btn) btn.focus();
            }, 30);
        });
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function getToken() {
        return localStorage.getItem(tokenKey);
    }

    function setToken(token) {
        localStorage.setItem(tokenKey, token);
        window.__accountBlockedHandled = false;
    }

    function clearToken() {
        localStorage.removeItem(tokenKey);
    }

    function getRequestId() {
        return localStorage.getItem(requestKey);
    }

    function setRequestId(id) {
        localStorage.setItem(requestKey, String(id));
    }

    function getSelectedCategories() {
        try {
            var raw = JSON.parse(localStorage.getItem(selectedCategoriesKey) || '[]');
            if (!Array.isArray(raw)) return [];
            var seen = {};
            var out = [];
            raw.forEach(function (id) {
                var n = Number(id);
                if (!n || seen[n]) return;
                seen[n] = true;
                out.push(n);
            });
            return out;
        } catch (e) {
            return [];
        }
    }

    function setSelectedCategories(ids) {
        var seen = {};
        var clean = [];
        (ids || []).forEach(function (id) {
            var n = Number(id);
            if (!n || seen[n]) return;
            seen[n] = true;
            clean.push(n);
        });
        localStorage.setItem(selectedCategoriesKey, JSON.stringify(clean.slice(0, 3)));
    }

    function leafCategoryIds(items) {
        var leaves = [];
        flattenCategories(items || [], leaves);
        return leaves.map(function (c) {
            return Number(c.id);
        }).filter(Boolean);
    }

    function pruneSelectedCategories(selected, items) {
        var allowed = {};
        leafCategoryIds(items).forEach(function (id) {
            allowed[id] = true;
        });
        var next = [];
        var seen = {};
        (selected || []).forEach(function (id) {
            var n = Number(id);
            if (!n || !allowed[n] || seen[n]) return;
            seen[n] = true;
            next.push(n);
        });
        selected.length = 0;
        next.forEach(function (id) {
            selected.push(id);
        });
        setSelectedCategories(selected);
        return selected;
    }

    function headers(isMultipart) {
        var h = {
            Accept: 'application/json',
            'X-Client': 'web',
        };
        if (!isMultipart) {
            h['Content-Type'] = 'application/json';
        }
        var token = getToken();
        if (token) {
            h.Authorization = 'Bearer ' + token;
        }
        return h;
    }

    function api(path, options) {
        options = options || {};
        var isMultipart = typeof FormData !== 'undefined' && options.body instanceof FormData;
        return fetch(API + path, {
            method: options.method || 'GET',
            headers: Object.assign(headers(isMultipart), options.headers || {}),
            body: options.body,
        }).then(function (res) {
            return res.json().catch(function () {
                return {};
            }).then(function (json) {
                if (res.status === 403 && json && json.code === 'ACCOUNT_BLOCKED') {
                    handleAccountBlocked(json.message);
                    throw new Error(json.message || 'Hesab bloklanıb');
                }
                if (!res.ok || json.success === false) {
                    throw new Error(json.message || 'HTTP ' + res.status);
                }
                return json.data !== undefined ? json.data : json;
            });
        });
    }

    function handleAccountBlocked(message) {
        if (window.__accountBlockedHandled) return;
        window.__accountBlockedHandled = true;
        var msg = message || 'Sizin profiliniz admin tərəfindən bloklanıb.';
        clearToken();
        meCache = null;
        try {
            localStorage.removeItem('mysancho_web_auth_snap');
            localStorage.removeItem('mysancho_approval_status');
        } catch (e2) {}
        showGuestAuth();
        applyRoleUi();
        showAppAlert({
            title: 'Hesab bloklanıb',
            message: msg,
            tone: 'danger',
            confirmLabel: 'Başa düşdüm',
        }).then(function () {
            go('/login');
        });
    }

    function paintProfileStatus(me) {
        var row = el('menu-profile-status');
        var val = el('menu-profile-status-value');
        if (!row || !val) return;
        if (!me || me.active_role !== 'provider') {
            row.hidden = true;
            return;
        }
        var status = me.profile_status || me.provider_approval_status || 'pending';
        if (me.is_blocked || me.status === 'blocked') {
            status = 'blocked';
        }
        var label = me.profile_status_label || ({
            approved: 'Təsdiqli',
            rejected: 'Rədd edilib',
            blocked: 'Bloklanıb',
            pending: 'Gözləyir',
        })[status] || status;
        val.textContent = label;
        row.classList.remove('is-approved', 'is-rejected', 'is-blocked', 'is-pending');
        row.classList.add('is-' + status);
        row.hidden = false;
    }

    function showPageLoader() {
        var loader = el('page-loader');
        document.body.classList.add('is-navigating');
        if (!loader) return;
        loader.hidden = false;
        loader.setAttribute('aria-busy', 'true');
    }

    function hidePageLoader() {
        var loader = el('page-loader');
        document.body.classList.remove('is-navigating');
        if (!loader) return;
        loader.hidden = true;
        loader.setAttribute('aria-busy', 'false');
    }

    function go(path) {
        showPageLoader();
        window.location.href = path;
    }

    function bindPageTransitions() {
        document.addEventListener('click', function (evt) {
            if (evt.defaultPrevented) return;
            if (evt.metaKey || evt.ctrlKey || evt.shiftKey || evt.altKey) return;
            if (evt.button !== 0) return;
            var a = evt.target.closest && evt.target.closest('a[href]');
            if (!a) return;
            if (a.target && a.target !== '_self') return;
            if (a.hasAttribute('download')) return;
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#') return;
            if (/^(mailto:|tel:|javascript:)/i.test(href)) return;
            var url;
            try {
                url = new URL(href, window.location.href);
            } catch (e) {
                return;
            }
            if (url.origin !== window.location.origin) return;
            if (
                url.pathname === window.location.pathname &&
                url.search === window.location.search
            ) {
                return;
            }
            evt.preventDefault();
            showPageLoader();
            window.location.href = url.pathname + url.search + url.hash;
        });

        window.addEventListener('pageshow', function () {
            hidePageLoader();
        });
    }

    function unwrapMe(me) {
        if (!me) return null;
        if (me.id != null) return me;
        if (me.data && me.data.id != null) return me.data;
        return me;
    }

    function asId(value) {
        if (value == null || value === '') return null;
        var n = Number(value);
        return Number.isFinite(n) ? n : null;
    }

    function messageList(conversation) {
        var raw = conversation && conversation.messages;
        if (Array.isArray(raw)) return raw;
        if (raw && Array.isArray(raw.data)) return raw.data;
        return [];
    }

    function unwrapMsg(m) {
        if (!m) return m;
        if (m.sender_id == null && m.body == null && m.data) return m.data;
        return m;
    }

    function isMineMessage(message, conversation, myId) {
        var m = unwrapMsg(message);
        if (m.is_mine === true || m.is_mine === 1 || m.is_mine === '1') return true;
        if (m.is_mine === false || m.is_mine === 0 || m.is_mine === '0') return false;
        var sender = asId(m.sender_id != null ? m.sender_id : m.senderId);
        var otherId = asId(conversation.other_user && conversation.other_user.id);
        if (sender == null) return false;
        if (otherId != null) return sender !== otherId;
        if (myId == null) return false;
        return sender === myId;
    }

    function roleLabel(role) {
        if (role === 'provider') return 'İcraçı';
        if (role === 'client') return 'Ailə';
        return role || '—';
    }

    function userInitial(me) {
        var raw = (me && (me.name || me.phone) || '?').trim();
        if (!raw) return '?';
        return raw.charAt(0).toUpperCase();
    }

    function showGuestAuth() {
        var guest = el('auth-guest');
        var user = el('auth-user');
        var status = el('auth-status');
        document.documentElement.classList.remove('has-token');
        document.documentElement.removeAttribute('data-auth-role');
        try {
            localStorage.removeItem('mysancho_web_auth_snap');
            localStorage.removeItem('mysancho_approval_status');
        } catch (e) {}
        if (guest) guest.hidden = false;
        if (user) user.hidden = true;
        if (status) status.textContent = 'Qonaq';
    }

    function showUserAuth(me) {
        var guest = el('auth-guest');
        var user = el('auth-user');
        var status = el('auth-status');
        var nameEl = el('auth-name');
        var roleEl = el('auth-role');
        var avatar = el('auth-avatar');
        document.documentElement.classList.add('has-token');
        if (me.active_role === 'client' || me.active_role === 'provider') {
            document.documentElement.setAttribute('data-auth-role', me.active_role);
        }
        if (guest) guest.hidden = true;
        if (user) user.hidden = false;
        var label = (me.name && String(me.name).trim()) || me.phone || 'İstifadəçi';
        var role = roleLabel(me.active_role);
        var initial = userInitial(me);
        if (nameEl) nameEl.textContent = label;
        if (roleEl) roleEl.textContent = role;
        if (avatar) avatar.textContent = initial;
        if (status) status.textContent = label + ' · ' + (me.active_role || '');
        try {
            localStorage.setItem('mysancho_web_auth_snap', JSON.stringify({
                name: label,
                role: role,
                active_role: me.active_role || null,
                initial: initial,
            }));
        } catch (e) {}
        applyRoleUi();
        paintProfileStatus(me);
        paintConnectHint(me);
    }

    function hydrateRoleFromSnap() {
        if (!getToken()) return;
        try {
            var snap = JSON.parse(localStorage.getItem('mysancho_web_auth_snap') || 'null');
            if (!snap || (snap.active_role !== 'client' && snap.active_role !== 'provider')) {
                return;
            }
            document.documentElement.setAttribute('data-auth-role', snap.active_role);
            // meCache-i natamam doldurma — profil avatarı və s. itib getməsin
            applyRoleUi(snap.active_role);
        } catch (e) {}
    }

    function setAuthStatus() {
        if (!getToken()) {
            meCache = null;
            showGuestAuth();
            applyRoleUi();
            return Promise.resolve();
        }
        return api('/auth/me').then(function (me) {
            meCache = unwrapMe(me);
            if (meCache && (meCache.is_blocked || meCache.status === 'blocked')) {
                handleAccountBlocked('Sizin profiliniz admin tərəfindən bloklanıb.');
                return;
            }
            showUserAuth(meCache);
            if (typeof window.__onAuthReady === 'function') {
                window.__onAuthReady(meCache);
            }
            notifyApprovalChange(meCache);
        }).catch(function (e) {
            if (e && e.message && e.message.indexOf('bloklanıb') >= 0) {
                return;
            }
            clearToken();
            meCache = null;
            showGuestAuth();
            applyRoleUi();
            log('Token etibarsız oldu, silindi');
            toast('warning', 'Sessiya bitdi, yenidən login et.');
        });
    }

    function notifyApprovalChange(me) {
        if (!me || me.active_role !== 'provider' || !me.provider_approval_status) return;
        var key = 'mysancho_approval_status';
        var prev = null;
        try {
            prev = localStorage.getItem(key);
        } catch (e) {}
        var cur = me.provider_approval_status;
        if (prev && prev !== cur) {
            if (cur === 'approved') {
                toast('success', me.provider_approval_message || 'Hesabınız təsdiqləndi');
            } else if (cur === 'rejected') {
                toast('error', me.provider_approval_message || 'Hesabınız rədd edildi');
            } else if (cur === 'pending') {
                toast('info', me.provider_approval_message || 'Təsdiq gözlənilir');
            }
        }
        try {
            localStorage.setItem(key, cur);
        } catch (e) {}
    }

    function logoutEverywhere() {
        clearToken();
        meCache = null;
        localStorage.removeItem(requestKey);
        showGuestAuth();
        toast('info', 'Çıxış edildi');
        log('Çıxış');
        go('/login');
    }

    function requireRole(role) {
        function fail() {
            var msg = role === 'client'
                ? 'Bu əməliyyat yalnız ailə (client) üçündür.'
                : 'Bu əməliyyat yalnız icraçı (provider) üçündür.';
            toast('warning', msg);
            return Promise.reject(new Error(msg));
        }
        if (!meCache) {
            return api('/auth/me').then(function (me) {
                meCache = unwrapMe(me);
                if (meCache.active_role !== role) return fail();
            });
        }
        if (meCache.active_role !== role) return fail();
        return Promise.resolve();
    }

    function setRoleOnce(role) {
        if (meCache && meCache.needs_role === false) {
            if (meCache.active_role === role) return Promise.resolve();
            var msg = 'Rol artıq seçilib və dəyişdirilə bilməz';
            toast('warning', msg);
            return Promise.reject(new Error(msg));
        }
        return api('/auth/role', {
            method: 'POST',
            body: JSON.stringify({ role: role }),
        }).then(function () {
            if (meCache) {
                meCache.active_role = role;
                meCache.needs_role = false;
            }
            toast('success', 'Rol: ' + roleLabel(role));
            log('Rol seçildi', { role: role });
            return setAuthStatus().then(function () {
                applyRoleUi();
            });
        });
    }

    function currentRole() {
        return (meCache && meCache.active_role)
            || document.documentElement.getAttribute('data-auth-role')
            || null;
    }

    function applyRoleUi(forcedRole) {
        var role = forcedRole || currentRole();
        document.querySelectorAll('[data-role]').forEach(function (node) {
            var need = node.getAttribute('data-role');
            if (!need || need === 'any') {
                node.hidden = false;
                return;
            }
            if (!role) {
                // Qonaq: menyu item-ləri gizlə; dashboard tile-lar marketinq üçün açıq qalsın
                node.hidden = !!(node.closest && node.closest('#user-menu-panel'));
                return;
            }
            node.hidden = need !== role;
        });

        var gateClient = el('role-gate-client');
        var gateProvider = el('role-gate-provider');
        if (gateClient) gateClient.hidden = !role || role === 'client';
        if (gateProvider) gateProvider.hidden = !role || role === 'provider';

        var requestForm = el('request-form');
        if (requestForm) {
            requestForm.hidden = role !== 'client';
            requestForm.querySelectorAll('input,button,select,textarea').forEach(function (n) {
                if (n.id === 'switch-to-client' || n.id === 'switch-to-provider') return;
                n.disabled = role !== 'client';
            });
        }
        var results = el('request-results');
        if (results) results.hidden = role !== 'client';

        var jobsPanel = el('jobs-panel');
        if (jobsPanel) jobsPanel.hidden = role !== 'provider';

        var requestsPanel = el('requests-panel');
        if (requestsPanel) requestsPanel.hidden = role !== 'client';
    }

    function embedSrc(lat, lng) {
        return 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) +
            '&z=14&hl=az&output=embed';
    }

    function mountGoogleEmbed(mapEl, lat, lng) {
        mapEl.innerHTML = '';
        var frame = document.createElement('iframe');
        frame.className = 'map-frame';
        frame.title = 'Google Map';
        frame.loading = 'lazy';
        frame.referrerPolicy = 'no-referrer-when-downgrade';
        frame.allowFullscreen = true;
        frame.src = embedSrc(lat, lng);
        mapEl.appendChild(frame);
        return frame;
    }

    function loadGoogleMaps() {
        var useJs = document.body.getAttribute('data-maps-js') === '1';
        var key = document.body.getAttribute('data-maps-key') || '';
        if (!useJs || !key) {
            return Promise.reject(new Error('browser-key-missing'));
        }
        if (mapsAuthFailed) {
            return Promise.reject(new Error('maps-auth-failed'));
        }
        if (window.google && window.google.maps && window.google.maps.Map) {
            return Promise.resolve();
        }
        if (mapsPromise) return mapsPromise;
        mapsPromise = new Promise(function (resolve, reject) {
            var settled = false;
            function fail(err) {
                if (settled) return;
                settled = true;
                mapsAuthFailed = true;
                mapsPromise = null;
                reject(err instanceof Error ? err : new Error(String(err)));
            }
            function ok() {
                if (settled || mapsAuthFailed) {
                    fail(new Error('maps-auth-failed'));
                    return;
                }
                settled = true;
                resolve();
            }
            window.gm_authFailure = function () {
                fail(new Error('maps-auth-failed'));
            };
            window.__mysanchoMapsReady = function () {
                // Auth failure sometimes fires after callback — short grace check.
                setTimeout(function () {
                    if (mapsAuthFailed) {
                        fail(new Error('maps-auth-failed'));
                    } else {
                        ok();
                    }
                }, 50);
            };
            var script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' +
                encodeURIComponent(key) + '&v=weekly&callback=__mysanchoMapsReady';
            script.async = true;
            script.defer = true;
            script.onerror = function () {
                fail(new Error('Google Maps yüklənmədi'));
            };
            document.head.appendChild(script);
        });
        return mapsPromise;
    }

    function readLatLng(latId, lngId, fallbackLat, fallbackLng) {
        var lat = Number(el(latId) && el(latId).value ? el(latId).value : fallbackLat);
        var lng = Number(el(lngId) && el(lngId).value ? el(lngId).value : fallbackLng);
        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            return { lat: fallbackLat, lng: fallbackLng };
        }
        return { lat: lat, lng: lng };
    }

    function fillLocationFields(opts, place) {
        if (!place) return;
        if (opts.cityId && el(opts.cityId)) {
            el(opts.cityId).value = place.city || '';
        }
        if (opts.districtId && el(opts.districtId)) {
            el(opts.districtId).value = place.district || '';
        }
        if (opts.cityIdHidden && el(opts.cityIdHidden)) {
            el(opts.cityIdHidden).value = place.city_id != null ? String(place.city_id) : '';
        }
        if (opts.districtIdHidden && el(opts.districtIdHidden)) {
            el(opts.districtIdHidden).value = place.district_id != null ? String(place.district_id) : '';
        }
        // Legacy: city-only from last hint
        if (opts.cityId && el(opts.cityId) && !place.city && place.hints && place.hints.length) {
            el(opts.cityId).value = place.hints[place.hints.length - 1] || '';
        }
    }

    function fillAddressFromCoords(opts, lat, lng, applyFn) {
        return api('/places/reverse?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng) + '&language=az')
            .then(function (place) {
                var address = place.formatted_address || '';
                if (typeof applyFn === 'function') {
                    applyFn(lat, lng, address || undefined);
                }
                if (opts.searchId && el(opts.searchId) && address) {
                    el(opts.searchId).value = address;
                }
                if (opts.suggestionsId && el(opts.suggestionsId)) {
                    el(opts.suggestionsId).classList.remove('open');
                    el(opts.suggestionsId).innerHTML = '';
                }
                fillLocationFields(opts, place);
                return place;
            })
            .catch(function () {});
    }

    function setLatLng(latId, lngId, lat, lng) {
        if (el(latId)) el(latId).value = Number(lat).toFixed(6);
        if (el(lngId)) el(lngId).value = Number(lng).toFixed(6);
    }

    function addLocateButton(mapEl, apply, opts) {
        if (!mapEl || mapEl.querySelector('.map-locate')) return;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-dark map-locate';
        btn.textContent = 'Mənim yerim';
        btn.addEventListener('click', function () {
            var handle = pickerMaps[opts.mapId];
            if (handle && handle.readOnly) return;
            if (!navigator.geolocation) {
                toast('warning', 'Brauzer geolocation dəstəkləmir');
                return;
            }
            navigator.geolocation.getCurrentPosition(function (pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                apply(lat, lng, 'Cari yer');
                if (opts) {
                    fillAddressFromCoords(opts, lat, lng, apply);
                }
            }, function () {
                toast('warning', 'Yer tapılmadı');
            });
        });
        mapEl.appendChild(btn);
    }

    function initEmbedPicker(opts, start) {
        var mapEl = el(opts.mapId);
        var frame = mountGoogleEmbed(mapEl, start.lat, start.lng);
        function apply(lat, lng, label) {
            setLatLng(opts.latId, opts.lngId, lat, lng);
            if (frame) {
                frame.src = embedSrc(lat, lng);
            }
            if (opts.labelId && el(opts.labelId) && label) {
                el(opts.labelId).textContent = label;
            }
        }
        addLocateButton(mapEl, apply, opts);
        pickerMaps[opts.mapId] = { map: null, marker: null, apply: apply, mode: 'embed' };
        bindPlaceSearch(opts);
        return pickerMaps[opts.mapId];
    }

    function initPickerMap(opts) {
        var mapEl = el(opts.mapId);
        if (!mapEl) return Promise.resolve(null);
        var start = readLatLng(opts.latId, opts.lngId, 40.4093, 49.8671);

        return loadGoogleMaps().then(function () {
            if (mapsAuthFailed) {
                throw new Error('maps-auth-failed');
            }
            mapEl.innerHTML = '';
            var map = new window.google.maps.Map(mapEl, {
                center: start,
                zoom: 12,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
            });
            var marker = new window.google.maps.Marker({
                map: map,
                position: start,
                draggable: true,
            });
            function apply(lat, lng, label, force) {
                var handle = pickerMaps[opts.mapId];
                if (handle && handle.readOnly && !force) return;
                setLatLng(opts.latId, opts.lngId, lat, lng);
                marker.setPosition({ lat: lat, lng: lng });
                map.panTo({ lat: lat, lng: lng });
                if (opts.labelId && el(opts.labelId) && label) {
                    el(opts.labelId).textContent = label;
                }
            }
            map.addListener('click', function (event) {
                var handle = pickerMaps[opts.mapId];
                if (handle && handle.readOnly) return;
                var lat = event.latLng.lat();
                var lng = event.latLng.lng();
                apply(lat, lng);
                fillAddressFromCoords(opts, lat, lng, apply);
            });
            marker.addListener('dragend', function () {
                var handle = pickerMaps[opts.mapId];
                if (handle && handle.readOnly) return;
                var pos = marker.getPosition();
                var lat = pos.lat();
                var lng = pos.lng();
                apply(lat, lng);
                fillAddressFromCoords(opts, lat, lng, apply);
            });
            addLocateButton(mapEl, apply, opts);
            bindPlaceSearch(opts);
            pickerMaps[opts.mapId] = { map: map, marker: marker, apply: apply, mode: 'js', readOnly: false };
            window.google.maps.event.trigger(map, 'resize');
            map.setCenter(start);

            // Late auth failure after Map() — swap to iframe.
            var prevAuth = window.gm_authFailure;
            window.gm_authFailure = function () {
                mapsAuthFailed = true;
                if (typeof prevAuth === 'function') prevAuth();
                log('Maps auth late fail → iframe');
                initEmbedPicker(opts, start);
            };

            return pickerMaps[opts.mapId];
        }).catch(function (e) {
            var authFail = e && (e.message === 'browser-key-missing' || e.message === 'maps-auth-failed');
            if (authFail) {
                toast(
                    'warning',
                    'Maps JS bu hostda rədd edildi (' + window.location.host + ') — iframe. Cloud Console referrer: ' +
                    window.location.origin + '/*'
                );
                log('Google Map iframe fallback', {
                    reason: e.message,
                    origin: window.location.origin,
                    host: window.location.host,
                });
            } else {
                toast('warning', 'Xəritə JS açılmadı, Google Map iframe göstərilir');
                log('Xəritə JS: ' + (e && e.message ? e.message : e));
            }
            return initEmbedPicker(opts, start);
        });
    }

    function bindPlaceSearch(opts) {
        var input = el(opts.searchId);
        var box = el(opts.suggestionsId);
        if (!input || !box) return;
        var timer = null;
        input.addEventListener('input', function () {
            var handle = pickerMaps[opts.mapId];
            if (handle && handle.readOnly) return;
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < 2) {
                box.classList.remove('open');
                box.innerHTML = '';
                return;
            }
            timer = setTimeout(function () {
                api('/places/autocomplete?q=' + encodeURIComponent(q) + '&language=az').then(function (items) {
                    box.innerHTML = '';
                    (items || []).forEach(function (item) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'suggestion';
                        btn.textContent = item.description;
                        btn.addEventListener('click', function () {
                            api('/places/' + encodeURIComponent(item.place_id) + '?language=az').then(function (details) {
                                var handle = pickerMaps[opts.mapId];
                                if (handle) {
                                    handle.apply(
                                        details.latitude,
                                        details.longitude,
                                        details.formatted_address || item.description
                                    );
                                } else {
                                    setLatLng(opts.latId, opts.lngId, details.latitude, details.longitude);
                                }
                                fillLocationFields(opts, details);
                                input.value = details.formatted_address || item.description;
                                box.classList.remove('open');
                                box.innerHTML = '';
                            }).catch(function (e) {
                                toast('error', 'Ünvan tapılmadı');
                                log('Place details xətası: ' + e.message);
                            });
                        });
                        box.appendChild(btn);
                    });
                    box.classList.toggle('open', (items || []).length > 0);
                }).catch(function (e) {
                    log('Places autocomplete: ' + e.message);
                });
            }, 280);
        });
    }

    function flattenCategories(items, out) {
        (items || []).forEach(function (item) {
            if (item.children && item.children.length) {
                flattenCategories(item.children, out);
                return;
            }
            out.push(item);
        });
    }

    function fillRequestCategorySelect(tree, selectedId) {
        var picker = el('request-category-picker');
        var hidden = el('request-category');
        var search = el('request-category-search');
        var menu = el('request-category-menu');
        if (!picker || !hidden || !search || !menu) return;

        var groups = [];

        function walk(nodes, parentLabel) {
            (nodes || []).forEach(function (node) {
                var label = node.name_az || node.name_en || node.slug || ('#' + node.id);
                if (node.children && node.children.length) {
                    walk(node.children, label);
                    return;
                }
                var groupName = parentLabel || 'Digər';
                var group = groups.find(function (g) {
                    return g.label === groupName;
                });
                if (!group) {
                    group = { label: groupName, leaves: [] };
                    groups.push(group);
                }
                group.leaves.push({
                    id: Number(node.id),
                    name: label,
                    search: (groupName + ' ' + label).toLocaleLowerCase('az'),
                });
            });
        }

        walk(tree || [], '');
        picker._groups = groups;
        picker._bound = picker._bound || false;

        function selectedLeaf() {
            var id = Number(hidden.value || 0);
            if (!id) return null;
            for (var i = 0; i < groups.length; i++) {
                for (var j = 0; j < groups[i].leaves.length; j++) {
                    if (groups[i].leaves[j].id === id) {
                        return {
                            group: groups[i].label,
                            leaf: groups[i].leaves[j],
                        };
                    }
                }
            }
            return null;
        }

        function setValue(id, closeMenu) {
            hidden.value = id ? String(id) : '';
            var found = selectedLeaf();
            if (found) {
                search.value = found.leaf.name;
                search.dataset.display = found.group + ' · ' + found.leaf.name;
            } else if (!search.matches(':focus')) {
                search.value = '';
                search.dataset.display = '';
            }
            renderMenu(search.value);
            if (closeMenu) closeCategoryMenu();
        }

        function openMenu() {
            if (picker.classList.contains('is-disabled') || search.disabled) return;
            menu.hidden = false;
            search.setAttribute('aria-expanded', 'true');
            renderMenu(search.value);
        }

        function closeCategoryMenu() {
            menu.hidden = true;
            search.setAttribute('aria-expanded', 'false');
            var found = selectedLeaf();
            if (found) {
                search.value = found.leaf.name;
            } else if (!hidden.value) {
                search.value = '';
            }
        }

        function renderMenu(query) {
            var q = String(query || '').trim().toLocaleLowerCase('az');
            menu.innerHTML = '';
            var any = false;
            groups.forEach(function (group) {
                var leaves = group.leaves.filter(function (leaf) {
                    if (!q) return true;
                    return leaf.search.indexOf(q) !== -1;
                });
                if (!leaves.length) return;
                any = true;
                var wrap = document.createElement('div');
                wrap.className = 'cat-picker-group';
                wrap.innerHTML = '<div class="cat-picker-group-title">' + esc(group.label) + '</div>';
                leaves.forEach(function (leaf) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'cat-picker-option' +
                        (Number(hidden.value) === leaf.id ? ' is-active' : '');
                    btn.setAttribute('role', 'option');
                    btn.setAttribute('data-id', String(leaf.id));
                    btn.textContent = leaf.name;
                    btn.addEventListener('mousedown', function (evt) {
                        evt.preventDefault();
                    });
                    btn.addEventListener('click', function () {
                        setValue(leaf.id, true);
                    });
                    wrap.appendChild(btn);
                });
                menu.appendChild(wrap);
            });
            if (!any) {
                menu.innerHTML = '<p class="cat-picker-empty">Uyğun kateqoriya yoxdur</p>';
            }
        }

        if (!picker._bound) {
            picker._bound = true;
            search.addEventListener('focus', openMenu);
            search.addEventListener('click', openMenu);
            search.addEventListener('input', function () {
                if (hidden.value) {
                    var cur = selectedLeaf();
                    if (!cur || search.value.trim() !== cur.leaf.name) {
                        hidden.value = '';
                    }
                }
                openMenu();
                renderMenu(search.value);
            });
            search.addEventListener('keydown', function (evt) {
                if (evt.key === 'Escape') {
                    closeCategoryMenu();
                    search.blur();
                }
            });
            document.addEventListener('click', function (evt) {
                if (!picker.contains(evt.target)) closeCategoryMenu();
            });
        }

        var wantId = selectedId != null ? Number(selectedId) : Number(hidden.value || 0);
        setValue(wantId || 0, true);
        picker._setCategory = setValue;
        picker._closeMenu = closeCategoryMenu;
    }

    function getRequestCategoryId() {
        return Number((el('request-category') && el('request-category').value) || 0) || 0;
    }

    function getRequestCategoryLabel() {
        var picker = el('request-category-picker');
        var hidden = el('request-category');
        var id = getRequestCategoryId();
        if (!id || !picker || !picker._groups) return '';
        for (var i = 0; i < picker._groups.length; i++) {
            for (var j = 0; j < picker._groups[i].leaves.length; j++) {
                if (picker._groups[i].leaves[j].id === id) {
                    return picker._groups[i].leaves[j].name;
                }
            }
        }
        return (el('request-category-search') && el('request-category-search').value.trim()) || '';
    }

    function paintSearchMeta(request) {
        var box = el('search-meta');
        if (!box) return;
        var meta = (request && request.parsed_criteria && request.parsed_criteria.search_meta) || {};
        var notes = [];
        if (meta.dropped_category) {
            notes.push('Bu kateqoriyada tapılmadı — yaxın digər icraçılar göstərilir.');
        }
        if (meta.expanded && meta.base_radius_km != null && meta.radius_km != null) {
            notes.push(
                'Radius ' + meta.base_radius_km + ' km-dən ' + meta.radius_km + ' km-ə genişləndi.'
            );
        }
        if (meta.dropped_area) {
            notes.push('Seçilmiş ərazidə tapılmadı — daha geniş zona.');
        }
        if (meta.dropped_schedule) {
            notes.push('Seçilmiş vaxt üçün tapılmadı — digər vaxtlar göstərilir.');
        }
        if (!notes.length) {
            box.hidden = true;
            box.textContent = '';
            return;
        }
        box.hidden = false;
        box.textContent = notes.join(' ');
    }

    function renderCategoryChips(targetId, countId, selected, items) {
        var target = el(targetId);
        if (!target) return;
        target.innerHTML = '';
        pruneSelectedCategories(selected, items);
        var leaves = [];
        flattenCategories(items, leaves);
        leaves.forEach(function (c) {
            var id = Number(c.id);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chip' + (selected.indexOf(id) !== -1 ? ' selected' : '');
            btn.textContent = c.name_az || c.name_en || c.slug;
            btn.addEventListener('click', function () {
                var idx = selected.indexOf(id);
                if (idx !== -1) {
                    selected.splice(idx, 1);
                } else {
                    if (selected.length >= 3) {
                        toast('warning', 'Maksimum 3 kateqoriya seçilə bilər');
                        return;
                    }
                    selected.push(id);
                }
                setSelectedCategories(selected);
                renderCategoryChips(targetId, countId, selected, items);
            });
            target.appendChild(btn);
        });
        if (el(countId)) el(countId).textContent = selected.length;
    }

    function providerMarkerIcon(active, isVip) {
        return {
            path: window.google.maps.SymbolPath.CIRCLE,
            scale: active ? 11 : 9,
            fillColor: active ? '#C24E2D' : (isVip ? '#D4A84B' : '#3D4F7C'),
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: active ? 3 : 2,
        };
    }

    function closeAllMatchInfoWindows() {
        Object.keys(matchInfoById).forEach(function (id) {
            var info = matchInfoById[id];
            if (info && info.window) info.window.close();
        });
    }

    function closeMeInfoWindow() {
        var handle = pickerMaps.map;
        if (handle && handle.meInfo) handle.meInfo.close();
    }

    function updateMeMarkerInfo(address) {
        var handle = pickerMaps.map;
        if (!handle || !handle.marker || !handle.map || !window.google) return;

        var title = 'Sizin sorğu göndərdiyiniz ünvan';
        var html =
            '<div class="map-info">' +
            '<strong>' + esc(title) + '</strong>' +
            (address ? '<div>' + esc(address) + '</div>' : '') +
            '</div>';

        if (!handle.meInfo) {
            handle.meInfo = new window.google.maps.InfoWindow();
            handle.marker.addListener('click', function () {
                closeAllMatchInfoWindows();
                selectedMatchId = null;
                document.querySelectorAll('.match-card').forEach(function (card) {
                    card.classList.remove('is-active');
                });
                Object.keys(matchMarkerById).forEach(function (id) {
                    var m = matchMarkerById[id];
                    var info = matchInfoById[id];
                    var vip = !!(info && info.isVip);
                    if (m) m.setIcon(providerMarkerIcon(false, vip));
                });
                handle.meInfo.open(handle.map, handle.marker);
            });
        }
        handle.meInfo.setContent(html);
        handle.marker.setTitle(title);
    }

    function focusMatchOnMap(providerId) {
        var handle = pickerMaps.map;
        var marker = matchMarkerById[providerId];
        if (!handle || !handle.map || !marker || !window.google) return;
        selectedMatchId = providerId;
        closeMeInfoWindow();
        Object.keys(matchMarkerById).forEach(function (id) {
            var m = matchMarkerById[id];
            var info = matchInfoById[id];
            var vip = !!(info && info.isVip);
            m.setIcon(providerMarkerIcon(Number(id) === Number(providerId), vip));
            if (Number(id) !== Number(providerId) && info && info.window) {
                info.window.close();
            }
        });
        handle.map.panTo(marker.getPosition());
        handle.map.setZoom(Math.max(handle.map.getZoom() || 12, 14));
        if (matchInfoById[providerId] && matchInfoById[providerId].window) {
            matchInfoById[providerId].window.open(handle.map, marker);
        }
        document.querySelectorAll('.match-card').forEach(function (card) {
            card.classList.toggle('is-active', Number(card.getAttribute('data-provider-id')) === Number(providerId));
        });
    }

    function fitMatchBounds(requestLat, requestLng, points) {
        var handle = pickerMaps.map;
        if (!handle || !handle.map || !window.google) return;
        if (!points.length) {
            handle.map.panTo({ lat: requestLat, lng: requestLng });
            return;
        }
        var bounds = new window.google.maps.LatLngBounds();
        bounds.extend({ lat: requestLat, lng: requestLng });
        points.forEach(function (p) {
            bounds.extend(p);
        });
        handle.map.fitBounds(bounds, 56);
    }

    function updateMapLegend(matchCount) {
        var label = el('place-label');
        if (!label) return;
        if (matchCount > 0) {
            label.innerHTML =
                '<span class="map-legend">' +
                '<span class="map-legend-item"><i class="dot me"></i> Sənin yerin</span>' +
                '<span class="map-legend-item"><i class="dot provider"></i> Xidmətçi</span>' +
                '<span class="map-legend-item"><i class="dot vip"></i> VIP</span>' +
                '<span class="map-legend-note">Kart və ya markerə bas — xəritədə fokus</span>' +
                '</span>';
        } else {
            label.textContent = 'Google Map. Ünvan axtar və ya “Mənim yerim”.';
        }
    }

    function renderMatches(request) {
        var box = el('matches');
        var badge = el('match-count');
        if (!box || !badge) return;
        var matches = (request && request.matches) ? request.matches : [];
        badge.textContent = matches.length + ' nəticə';
        box.innerHTML = '';
        selectedMatchId = null;
        paintConnectHint(meCache);
        paintSearchMeta(request);

        var handle = pickerMaps.map;
        matchMarkers.forEach(function (m) { m.setMap(null); });
        matchMarkers = [];
        matchMarkerById = {};
        matchInfoById = {};

        var requestLat = Number(
            (request && request.latitude != null) ? request.latitude : (el('lat') && el('lat').value) || 40.4093
        );
        var requestLng = Number(
            (request && request.longitude != null) ? request.longitude : (el('lng') && el('lng').value) || 49.8671
        );
        var providerPoints = [];

        if (handle && handle.map && window.google) {
            if (handle.marker) {
                if (matches.length) {
                    handle.marker.setIcon({
                        path: window.google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: '#5B7FB5',
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 3,
                    });
                } else {
                    handle.marker.setIcon(null);
                }
                updateMeMarkerInfo(
                    (request && (request.address || request._resolvedAddress)) ||
                    (el('place-search') && el('place-search').value) ||
                    ''
                );
            }

            matches.forEach(function (m) {
                var provider = m.provider || {};
                if (provider.latitude == null || provider.longitude == null) return;
                var id = Number(provider.id);
                if (!id) return;
                var name = provider.user_name || provider.title || 'İcraçı';
                var isVip = !!provider.is_vip;
                var score = Math.round(m.match_score || 0);
                var pos = {
                    lat: Number(provider.latitude),
                    lng: Number(provider.longitude),
                };
                providerPoints.push(pos);

                var info = new window.google.maps.InfoWindow({
                    content:
                        '<div class="map-info">' +
                        '<strong>' + esc(name) + (isVip ? ' · VIP' : '') + '</strong>' +
                        '<div>' + score + '% · ' + esc(m.distance_km != null ? m.distance_km : '-') + ' km</div>' +
                        '</div>',
                });
                var marker = new window.google.maps.Marker({
                    map: handle.map,
                    position: pos,
                    title: name,
                    icon: providerMarkerIcon(false, isVip),
                });
                marker.addListener('click', function () {
                    focusMatchOnMap(id);
                    var card = box.querySelector('.match-card[data-provider-id="' + id + '"]');
                    if (card) {
                        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
                matchMarkers.push(marker);
                matchMarkerById[id] = marker;
                matchInfoById[id] = { window: info, isVip: isVip };
            });

            fitMatchBounds(requestLat, requestLng, providerPoints);
        }

        updateMapLegend(matches.length);

        matches.forEach(function (m) {
            var provider = m.provider || {};
            var id = Number(provider.id || 0);
            var hasCoords = provider.latitude != null && provider.longitude != null;
            var card = document.createElement('article');
            card.className = 'match-card';
            if (id) card.setAttribute('data-provider-id', String(id));
            var reasons = renderMatchReasonsHtml(m.reasons);
            card.innerHTML =
                '<h3>' + esc(provider.user_name || provider.title || 'İcraçı') +
                (provider.is_vip ? ' <span class="pill pill-gold">VIP</span>' : '') + '</h3>' +
                reasons +
                '<p class="meta">Skor: <b>' + Math.round(m.match_score || 0) + '%</b> · ' +
                esc(m.distance_km != null ? m.distance_km : '-') + ' km</p>' +
                '<div class="match-actions">' +
                (hasCoords
                    ? '<button type="button" class="btn btn-outline show-on-map" data-id="' + esc(id) + '">Xəritədə</button>'
                    : '') +
                '<button type="button" class="btn btn-outline view-profile" data-id="' + esc(id) + '">Profilə bax</button>' +
                '<button type="button" class="btn btn-primary connect" data-id="' + esc(id) + '">CONNECT</button>' +
                '</div>';

            if (hasCoords) {
                card.addEventListener('click', function (evt) {
                    if (evt.target.closest('button')) return;
                    focusMatchOnMap(id);
                });
                var mapBtn = card.querySelector('.show-on-map');
                if (mapBtn) {
                    mapBtn.addEventListener('click', function () {
                        focusMatchOnMap(id);
                        var mapEl = el('map');
                        if (mapEl) mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                }
            }

            var profileBtn = card.querySelector('.view-profile');
            if (profileBtn) {
                profileBtn.addEventListener('click', function (evt) {
                    var profileId = Number(evt.currentTarget.getAttribute('data-id'));
                    if (!profileId) return;
                    openProviderProfile(profileId, request);
                });
            }

            card.querySelector('.connect').addEventListener('click', function (evt) {
                var profileId = Number(evt.currentTarget.getAttribute('data-id'));
                if (!profileId || !request) return;
                requireRole('client').then(function () {
                    return api('/conversations', {
                        method: 'POST',
                        body: JSON.stringify({
                            provider_profile_id: profileId,
                            service_request_id: request.id,
                        }),
                    });
                }).then(function (conversation) {
                    toast('success', 'CONNECT uğurlu');
                    log('CONNECT uğurlu', { conversation_id: conversation.id });
                    go('/chat/' + conversation.id);
                }).catch(function (e) {
                    toast('error', 'CONNECT xətası: ' + e.message);
                    log('CONNECT xətası: ' + e.message);
                });
            });

            box.appendChild(card);
        });
    }

    var DAY_LABELS = {
        1: 'B.e',
        2: 'Ç.a',
        3: 'Ç',
        4: 'C.a',
        5: 'C',
        6: 'Ş',
        7: 'B',
    };
    var SLOT_LABELS = {
        morning: 'Səhər',
        afternoon: 'Günorta',
        evening: 'Axşam',
        night: 'Gecə',
    };

    function scheduleChipLabel(day, slot) {
        return (DAY_LABELS[day] || day) + ' · ' + (SLOT_LABELS[slot] || slot);
    }

    function providerInitial(provider) {
        var raw = ((provider && (provider.user_name || provider.title)) || '?').trim();
        return raw ? raw.charAt(0).toUpperCase() : '?';
    }

    function renderProviderProfileHtml(provider, opts) {
        opts = opts || {};
        var name = provider.user_name || provider.title || 'İcraçı';
        var title = provider.title && provider.title !== name ? provider.title : '';
        var place = [provider.district, provider.city].filter(Boolean).join(', ');
        var cats = (provider.categories || []).map(function (c) {
            return c.name_az || c.name || '';
        }).filter(Boolean);
        if (!cats.length && provider.category) {
            var one = provider.category.name_az || provider.category.name;
            if (one) cats = [one];
        }
        var slots = (provider.schedules || []).filter(function (s) {
            return s.is_available !== false;
        });
        var html = '';
        if (opts.withHero) {
            html +=
                '<div class="provider-public-hero">' +
                '<div class="provider-preview-avatar" aria-hidden="true">' + esc(providerInitial(provider)) + '</div>' +
                '<div class="provider-preview-head-text">' +
                '<h2 class="m-0 font-brand text-2xl text-brand">' + esc(name) + '</h2>' +
                (title ? '<p class="provider-preview-title">' + esc(title) + '</p>' : '') +
                '</div></div>';
        } else if (title) {
            html += '<p class="provider-preview-title">' + esc(title) + '</p>';
        }

        html += '<div class="provider-meta-row">';
        if (provider.is_verified) {
            html += '<span class="provider-badge provider-badge-verified">Verified</span>';
        }
        if (provider.is_vip) {
            html += '<span class="provider-badge provider-badge-vip">VIP</span>';
        }
        if (provider.rating_count > 0) {
            html +=
                '<span class="provider-badge provider-badge-rating">★ ' +
                Number(provider.rating_avg || 0).toFixed(1) +
                ' · ' +
                esc(provider.rating_count) +
                '</span>';
        }
        if (place) html += '<span>' + esc(place) + '</span>';
        html += '</div>';

        if (cats.length) {
            html +=
                '<div class="provider-chips">' +
                cats.map(function (c) {
                    return '<span class="provider-chip">' + esc(c) + '</span>';
                }).join('') +
                '</div>';
        }

        if (provider.bio) {
            html += '<p class="provider-bio">' + esc(provider.bio) + '</p>';
        }

        if (provider.audio_intro_url) {
            html +=
                '<div><p class="provider-section-label">Audio intro</p>' +
                '<audio controls class="w-full" src="' +
                esc(provider.audio_intro_url) +
                '"></audio></div>';
        }

        if (slots.length) {
            html +=
                '<div><p class="provider-section-label">Cədvəl</p>' +
                '<div class="provider-schedule-grid">' +
                slots
                    .slice(0, opts.maxSlots || 21)
                    .map(function (s) {
                        return (
                            '<span class="provider-schedule-chip">' +
                            esc(scheduleChipLabel(s.day_of_week, s.time_slot)) +
                            '</span>'
                        );
                    })
                    .join('') +
                '</div></div>';
        }

        return html || '<p class="muted">Əlavə məlumat yoxdur</p>';
    }

    function ensureProviderModal() {
        var modal = document.getElementById('provider-modal');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'provider-modal';
        modal.className = 'modal';
        modal.hidden = true;
        modal.innerHTML =
            '<div class="modal-card provider-preview">' +
            '<div class="provider-preview-head">' +
            '<div class="provider-preview-avatar" id="provider-modal-avatar">?</div>' +
            '<div class="provider-preview-head-text">' +
            '<h3 id="provider-modal-title">Xidmətçi</h3>' +
            '<p class="provider-preview-title" id="provider-modal-sub" hidden></p>' +
            '</div></div>' +
            '<div id="provider-modal-body" class="provider-preview-body"></div>' +
            '<div class="provider-preview-actions">' +
            '<button type="button" class="btn btn-outline" id="provider-modal-close">Bağla</button>' +
            '<a class="btn btn-outline" id="provider-modal-detail" href="#">Ətraflı profil</a>' +
            '<button type="button" class="btn btn-primary" id="provider-modal-connect">CONNECT</button>' +
            '</div></div>';
        document.body.appendChild(modal);
        modal.addEventListener('click', function (evt) {
            if (evt.target === modal) modal.hidden = true;
        });
        document.getElementById('provider-modal-close').addEventListener('click', function () {
            modal.hidden = true;
        });
        return modal;
    }

    function openProviderProfile(profileId, request) {
        var modal = ensureProviderModal();
        var titleEl = document.getElementById('provider-modal-title');
        var subEl = document.getElementById('provider-modal-sub');
        var avatarEl = document.getElementById('provider-modal-avatar');
        var bodyEl = document.getElementById('provider-modal-body');
        var connectBtn = document.getElementById('provider-modal-connect');
        var detailLink = document.getElementById('provider-modal-detail');
        titleEl.textContent = 'Xidmətçi';
        if (subEl) {
            subEl.hidden = true;
            subEl.textContent = '';
        }
        if (avatarEl) avatarEl.textContent = '?';
        bodyEl.innerHTML = '<p class="muted">Yüklənir…</p>';
        connectBtn.disabled = true;
        connectBtn.onclick = null;
        var detailHref = '/providers/' + profileId;
        if (request && request.id) {
            detailHref += '?requestId=' + encodeURIComponent(request.id);
        }
        detailLink.href = detailHref;
        modal.hidden = false;

        api('/providers/' + profileId).then(function (provider) {
            var name = provider.user_name || provider.title || 'İcraçı';
            titleEl.textContent = name;
            if (avatarEl) {
                if (provider.user_avatar_url) {
                    avatarEl.innerHTML = '<img src="' + esc(provider.user_avatar_url) + '" alt="">';
                    avatarEl.classList.add('has-photo');
                } else {
                    avatarEl.textContent = providerInitial(provider);
                    avatarEl.classList.remove('has-photo');
                }
            }
            if (subEl) {
                if (provider.title && provider.title !== name) {
                    subEl.hidden = false;
                    subEl.textContent = provider.title;
                } else {
                    subEl.hidden = true;
                }
            }
            bodyEl.innerHTML = renderProviderProfileHtml(provider, { maxSlots: 12 });
            connectBtn.disabled = false;
            connectBtn.onclick = function () {
                if (!request) {
                    toast('warning', 'CONNECT üçün əvvəl sorğu yaradın');
                    return;
                }
                requireRole('client').then(function () {
                    return api('/conversations', {
                        method: 'POST',
                        body: JSON.stringify({
                            provider_profile_id: profileId,
                            service_request_id: request.id,
                        }),
                    });
                }).then(function (conversation) {
                    modal.hidden = true;
                    toast('success', 'CONNECT uğurlu');
                    go('/chat/' + conversation.id);
                }).catch(function (e) {
                    toast('error', 'CONNECT xətası: ' + e.message);
                });
            };
        }).catch(function (e) {
            bodyEl.innerHTML = '<p class="muted">Profil açılmadı: ' + esc(e.message) + '</p>';
        });
    }

    function bindProviderPublicPage() {
        var profileId = Number(document.body.getAttribute('data-provider-id') || 0);
        if (!profileId) return;
        var params = new URLSearchParams(window.location.search);
        var requestId = Number(params.get('requestId') || 0) || null;
        var body = el('pp-body');
        var nameEl = el('pp-name');
        var subEl = el('pp-subtitle');
        var actions = el('pp-actions');
        var connectBtn = el('pp-connect');
        var back = el('pp-back');

        if (back) {
            back.href = requestId
                ? '/request?requestId=' + encodeURIComponent(requestId)
                : '/';
        }

        api('/providers/' + profileId).then(function (provider) {
            var name = provider.user_name || provider.title || 'İcraçı';
            if (nameEl) nameEl.textContent = name;
            if (subEl) {
                subEl.textContent = [provider.district, provider.city].filter(Boolean).join(', ') ||
                    'Ətraflı məlumat, cədvəl və CONNECT.';
            }
            if (body) {
                body.innerHTML = renderProviderProfileHtml(provider, {
                    withHero: true,
                    maxSlots: 28,
                });
            }
            if (actions) actions.hidden = false;
            if (connectBtn && meCache && meCache.active_role === 'client' && requestId) {
                connectBtn.hidden = false;
                connectBtn.onclick = function () {
                    requireRole('client').then(function () {
                        return api('/conversations', {
                            method: 'POST',
                            body: JSON.stringify({
                                provider_profile_id: profileId,
                                service_request_id: requestId,
                            }),
                        });
                    }).then(function (conversation) {
                        toast('success', 'CONNECT uğurlu');
                        go('/chat/' + conversation.id);
                    }).catch(function (e) {
                        toast('error', 'CONNECT xətası: ' + e.message);
                    });
                };
            } else if (connectBtn && meCache && meCache.active_role === 'client') {
                connectBtn.hidden = false;
                connectBtn.textContent = 'Sorğuya keç';
                connectBtn.onclick = function () {
                    go('/request');
                };
            }
            log('İcraçı profili açıldı', { id: profileId, name: name });
        }).catch(function (e) {
            if (nameEl) nameEl.textContent = 'Profil tapılmadı';
            if (body) body.innerHTML = '<p class="muted">' + esc(e.message) + '</p>';
            if (actions) actions.hidden = false;
            log('Profil xətası: ' + e.message);
        });
    }

    function clearRequestId() {
        localStorage.removeItem(requestKey);
    }

    function resetRequestResultsUi() {
        if (el('request-info')) {
            el('request-info').textContent = 'Yeni sorğu yazın — əvvəlki nəticələr göstərilmir';
        }
        paintSearchMeta(null);
        renderMatches(null);
        paintConnectHint(meCache);
    }

    function paintRequestForm(req) {
        if (!req) return Promise.resolve(null);
        if (el('text') && req.transcribed_text) {
            el('text').value = req.transcribed_text;
        }
        if (el('request-category') && req.category_id) {
            var picker = el('request-category-picker');
            if (picker && typeof picker._setCategory === 'function') {
                picker._setCategory(req.category_id, true);
            } else {
                el('request-category').value = String(req.category_id);
            }
        }
        if (el('lat') && req.latitude != null) {
            el('lat').value = String(req.latitude);
        }
        if (el('lng') && req.longitude != null) {
            el('lng').value = String(req.longitude);
        }
        if (el('request-info')) {
            var catName = req.category && (req.category.name_az || req.category.name);
            el('request-info').textContent =
                'Sorğu #' + req.id +
                (catName ? (' · ' + catName) : '') +
                ' · ' + (req.status || '—') +
                (req.matches && req.matches.length
                    ? (' · ' + req.matches.length + ' uyğunluq')
                    : '');
        }
        paintSearchMeta(req);

        function applyAddress(address) {
            req._resolvedAddress = address || '';
            if (el('place-search') && address) {
                el('place-search').value = address;
            }
            if (el('place-label')) {
                el('place-label').textContent = address
                    ? ('Ünvan: ' + address)
                    : 'Google Map. Ünvan axtar və ya “Mənim yerim”.';
            }
            var handle = pickerMaps.map;
            if (handle && handle.map && req.latitude != null && req.longitude != null && window.google) {
                var pos = {
                    lat: Number(req.latitude),
                    lng: Number(req.longitude),
                };
                if (typeof handle.apply === 'function') {
                    handle.apply(pos.lat, pos.lng, address || undefined, true);
                } else {
                    handle.map.setCenter(pos);
                    if (handle.marker) handle.marker.setPosition(pos);
                }
            }
            updateMeMarkerInfo(address || '');
            return req;
        }

        if (req.address) {
            return Promise.resolve(applyAddress(req.address));
        }

        if (req.latitude == null || req.longitude == null) {
            return Promise.resolve(applyAddress(''));
        }

        return api(
            '/places/reverse?lat=' + encodeURIComponent(req.latitude) +
            '&lng=' + encodeURIComponent(req.longitude) +
            '&language=az'
        ).then(function (place) {
            return applyAddress(place.formatted_address || '');
        }).catch(function () {
            return applyAddress('');
        });
    }

    function refreshRequest() {
        var id = getRequestId();
        if (!id) {
            toast('info', 'Hələ aktiv sorğu yoxdur — əvvəl sorğu yaradın');
            return Promise.resolve();
        }
        return api('/service-requests/' + id).then(function (req) {
            return paintRequestForm(req).then(function (painted) {
                renderMatches(painted || req);
                log('Sorğu yeniləndi', { id: req.id, status: req.status });
                return painted || req;
            });
        }).catch(function (e) {
            toast('warning', 'Sorğu yenilənmədi');
            log('Sorğu yenilənmədi: ' + e.message);
            throw e;
        });
    }

    function bindLoginPage() {
        el('send-otp').addEventListener('click', function () {
            api('/auth/otp/send', {
                method: 'POST',
                body: JSON.stringify({ phone: el('phone').value.trim() }),
            }).then(function () {
                toast('success', 'OTP göndərildi');
                log('OTP göndərildi');
            }).catch(function (e) {
                var msg = (e && e.message) || 'OTP göndərilmədi';
                if (msg.indexOf('bloklanıb') >= 0 || msg.indexOf('bloklanib') >= 0) {
                    showAppAlert({
                        title: 'Hesab bloklanıb',
                        message: msg,
                        tone: 'danger',
                        confirmLabel: 'Başa düşdüm',
                    });
                } else {
                    toast('error', msg);
                }
                log('OTP göndərilmədi: ' + msg);
            });
        });

        el('verify-otp').addEventListener('click', function () {
            api('/auth/otp/verify', {
                method: 'POST',
                body: JSON.stringify({
                    phone: el('phone').value.trim(),
                    code: el('otp').value.trim(),
                }),
            }).then(function (data) {
                var token = data.token || data.access_token;
                if (!token) throw new Error('Token qayıtmadı');
                setToken(token);
                toast('success', 'Daxil oldunuz');
                log('Login uğurlu');
                setTimeout(function () {
                    var user = data.user || {};
                    if (data.is_new_user || user.needs_role) {
                        go('/onboarding');
                        return;
                    }
                    go('/');
                }, 180);
            }).catch(function (e) {
                var msg = (e && e.message) || 'Login alınmadı';
                toast('error', msg);
                log('Login alınmadı: ' + msg);
            });
        });
    }

    function bindOnboardingPage() {
        var step = 0;
        var selected = getSelectedCategories();
        var categoryTree = [];
        var mapReady = false;

        function role() {
            return el('onb-role').value;
        }

        function isProvider() {
            return role() === 'provider';
        }

        function maxStep() {
            return 2;
        }

        function paintStepper() {
            var cat = document.querySelector('#onb-stepper [data-step="1"]');
            var loc = document.querySelector('#onb-stepper [data-step="2"]');
            if (cat) cat.hidden = !isProvider();
            if (loc) loc.textContent = isProvider() ? '3. Məkan' : '2. Məkan';
            var hero = el('onb-hero-sub');
            if (hero) {
                hero.textContent = isProvider()
                    ? 'Hesab, kateqoriya, məkan — üç addım.'
                    : 'Hesab və məkan — iki addım.';
            }
        }

        function showStep() {
            paintStepper();
            document.querySelectorAll('[data-step-panel]').forEach(function (panel) {
                panel.classList.toggle('hidden', Number(panel.getAttribute('data-step-panel')) !== step);
            });
            document.querySelectorAll('#onb-stepper li').forEach(function (item) {
                item.classList.toggle('active', Number(item.getAttribute('data-step')) === step);
            });
            el('onb-next').textContent = step === maxStep() ? 'Bitir' : 'Davam et';
            if (step === 2 && !mapReady) {
                mapReady = true;
                initPickerMap({
                    mapId: 'onb-map',
                    latId: 'onb-lat',
                    lngId: 'onb-lng',
                    searchId: 'onb-place-search',
                    suggestionsId: 'onb-place-suggestions',
                    labelId: 'onb-place-label',
                });
            }
        }

        api('/categories').then(function (items) {
            categoryTree = items || [];
            renderCategoryChips('onb-category-list', 'onb-selected-count', selected, categoryTree);
        }).catch(function (e) {
            log('Kateqoriyalar yüklənmədi: ' + e.message);
        });

        api('/auth/me').then(function (me) {
            meCache = unwrapMe(me);
            var roleEl = el('onb-role');
            if (roleEl && meCache && meCache.needs_role === false) {
                roleEl.value = meCache.active_role || 'client';
                roleEl.disabled = true;
            }
            showStep();
        }).catch(function () {});

        var roleSelect = el('onb-role');
        if (roleSelect) {
            roleSelect.addEventListener('change', function () {
                if (!isProvider() && step === 1) step = 0;
                showStep();
            });
        }

        el('onb-back').addEventListener('click', function () {
            if (step === 0) {
                go('/login');
                return;
            }
            if (role() === 'client' && step === 2) {
                step = 0;
            } else {
                step -= 1;
            }
            showStep();
        });

        el('onb-next').addEventListener('click', function () {
            if (step === 0) {
                if (el('onb-name').value.trim().length < 2) {
                    toast('warning', 'Ad daxil edin');
                    return;
                }
                if (role() === 'client') {
                    step = 2;
                } else {
                    step = 1;
                }
                showStep();
                return;
            }
            if (step === 1) {
                if (!selected.length) {
                    toast('warning', 'Ən azı 1 kateqoriya seç');
                    return;
                }
                setSelectedCategories(selected);
                step = 2;
                showStep();
                return;
            }
            finishOnboarding();
        });

        function finishOnboarding() {
            var chosenRole = role();
            api('/auth/profile', {
                method: 'PATCH',
                body: JSON.stringify({ name: el('onb-name').value.trim() }),
            }).then(function () {
                return setRoleOnce(chosenRole);
            }).then(function () {
                if (chosenRole !== 'provider') {
                    return null;
                }
                setSelectedCategories(selected);
                return api('/provider-profiles', {
                    method: 'POST',
                    body: JSON.stringify({
                        category_ids: selected,
                        title: el('onb-name').value.trim(),
                        latitude: Number(el('onb-lat').value || 40.4093),
                        longitude: Number(el('onb-lng').value || 49.8671),
                    }),
                }).catch(function () {
                    return api('/provider-profiles').then(function (items) {
                        if (!items || !items[0]) throw new Error('Profil yaradılmadı');
                        return api('/provider-profiles/' + items[0].id, {
                            method: 'PUT',
                            body: JSON.stringify({
                                category_ids: selected,
                                latitude: Number(el('onb-lat').value || 40.4093),
                                longitude: Number(el('onb-lng').value || 49.8671),
                            }),
                        });
                    });
                });
            }).then(function () {
                if (chosenRole === 'provider') {
                    toast('success', 'Qeydiyyat qəbul olundu — təsdiq gözlənilir');
                    log('Onboarding tamamlandı (pending)', { role: chosenRole });
                    go('/profile');
                    return;
                }
                toast('success', 'Onboarding tamamlandı');
                log('Onboarding tamamlandı', { role: chosenRole });
                go('/request');
            }).catch(function (e) {
                toast('error', 'Onboarding xətası');
                log('Onboarding xətası: ' + e.message);
            });
        }

        showStep();
    }

    function bindCategoriesPage() {
        var selected = getSelectedCategories();

        function syncFromProfile() {
            return api('/provider-profiles').then(function (items) {
                var list = items || [];
                if (!list[0]) return;
                var ids = (list[0].category_ids || []).map(Number).filter(Boolean);
                if (!ids.length && list[0].categories) {
                    ids = (list[0].categories || []).map(function (c) {
                        return Number(c.id);
                    }).filter(Boolean);
                }
                if (!ids.length) return;
                selected.length = 0;
                ids.forEach(function (id) { selected.push(id); });
                setSelectedCategories(selected);
            }).catch(function () {});
        }

        var load = (currentRole() === 'provider')
            ? syncFromProfile()
            : Promise.resolve();

        load.then(function () {
            return api('/categories');
        }).then(function (items) {
            renderCategoryChips('category-list', 'selected-count', selected, items || []);
            log('Kateqoriyalar yükləndi', { count: (items || []).length });
        }).catch(function (e) {
            toast('error', 'Kateqoriyalar yüklənmədi');
            log('Kateqoriya xətası: ' + e.message);
        });

        el('save-categories').addEventListener('click', function () {
            if (!selected.length) {
                toast('warning', 'Ən azı 1 kateqoriya seç');
                return;
            }
            var btn = el('save-categories');
            if (btn) btn.disabled = true;
            setSelectedCategories(selected);
            requireRole('provider').then(function () {
                return api('/provider-profiles');
            }).then(function (items) {
                var list = items || [];
                var payload = { category_ids: selected.slice() };
                if (list[0] && list[0].id) {
                    return api('/provider-profiles/' + list[0].id, {
                        method: 'PUT',
                        body: JSON.stringify(payload),
                    });
                }
                return api('/provider-profiles', {
                    method: 'POST',
                    body: JSON.stringify(Object.assign({
                        title: (meCache && meCache.name) || 'Profil',
                        latitude: 40.4093,
                        longitude: 49.8671,
                    }, payload)),
                });
            }).then(function () {
                toast('success', 'Kateqoriyalar saxlanıldı');
                log('Kateqoriya seçimi saxlanıldı', { selected: selected });
            }).catch(function (e) {
                toast('error', e.message || 'Kateqoriyalar saxlanmadı');
                log('Kateqoriya saxlama xətası: ' + e.message);
            }).finally(function () {
                if (btn) btn.disabled = false;
            });
        });
    }

    function bindProfilePage() {
        var providerProfiles = [];

        function paintUserCard(me) {
            if (!me) return;
            var nameEl = el('profile-name');
            var roleEl = el('profile-role-label');
            var balEl = el('profile-balance');
            var img = el('profile-avatar-img');
            var approval = el('profile-approval-label');
            var pendingBanner = el('provider-pending-banner');
            var pendingText = el('provider-pending-text');
            if (nameEl) nameEl.value = me.name || '';
            if (roleEl) roleEl.textContent = roleLabel(me.active_role);
            if (balEl) balEl.textContent = (Number(me.balance || 0)).toFixed(0) + ' AZN';
            var fallback = el('profile-avatar-fallback');
            if (img) {
                if (me.avatar_url) {
                    img.onload = function () {
                        img.hidden = false;
                        if (fallback) fallback.hidden = true;
                    };
                    img.onerror = function () {
                        img.hidden = true;
                        if (fallback) {
                            fallback.hidden = false;
                            var n0 = (me.name || me.phone || '?').trim();
                            fallback.textContent = n0.charAt(0).toUpperCase();
                        }
                    };
                    img.src = me.avatar_url;
                    img.hidden = false;
                    if (fallback) fallback.hidden = true;
                } else {
                    img.hidden = true;
                    img.removeAttribute('src');
                    if (fallback) {
                        fallback.hidden = false;
                        var n = (me.name || me.phone || '?').trim();
                        fallback.textContent = n.charAt(0).toUpperCase();
                    }
                }
            }
            if (approval) {
                if (me.active_role === 'provider' && me.provider_approval_status) {
                    approval.hidden = false;
                    var map = {
                        pending: 'Təsdiq: gözləyir',
                        approved: 'Təsdiq: təsdiqlənib',
                        rejected: 'Təsdiq: rədd edilib',
                    };
                    approval.textContent = map[me.provider_approval_status] || me.provider_approval_status;
                } else {
                    approval.hidden = true;
                }
            }
            if (pendingBanner) {
                var status = me.active_role === 'provider' ? me.provider_approval_status : null;
                if (!status) {
                    pendingBanner.hidden = true;
                } else {
                    pendingBanner.hidden = false;
                    pendingBanner.classList.remove('is-pending', 'is-approved', 'is-rejected');
                    pendingBanner.classList.add('is-' + status);
                    var titleEl = el('provider-pending-title');
                    var titles = {
                        pending: 'Təsdiq gözlənilir',
                        approved: 'Hesab təsdiqləndi',
                        rejected: 'Hesab rədd edilib',
                    };
                    if (titleEl) titleEl.textContent = titles[status] || 'Təsdiq statusu';
                    if (pendingText) {
                        pendingText.textContent = me.provider_approval_message
                            || (status === 'approved'
                                ? 'İndi iş sorğuları gələ bilər.'
                                : status === 'rejected'
                                    ? 'Dəstəklə əlaqə saxlayın və ya profili yeniləyin.'
                                    : 'Sorğunuz 1 saat ərzində baxılacaq.');
                    }
                }
            }
            applyRoleUi();
        }

        function ensureMe() {
            if (meCache && meCache.id) {
                paintUserCard(meCache);
                return Promise.resolve(meCache);
            }
            return api('/auth/me').then(function (me) {
                meCache = unwrapMe(me);
                paintUserCard(meCache);
                return meCache;
            });
        }

        window.__onAuthReady = function (me) {
            if (me) paintUserCard(me);
        };

        function renderProviderList() {
            var list = el('provider-list');
            if (!list) return;
            if (!providerProfiles.length) {
                list.textContent = 'Hələ profil yoxdur';
                return;
            }
            list.innerHTML = '';
            providerProfiles.forEach(function (p) {
                var div = document.createElement('div');
                div.className = 'match-card';
                div.innerHTML =
                    '<b>' + esc(p.title || 'Başlıq yoxdur') + '</b><br>' +
                    '<span class="muted">' + esc(p.city || '-') + ' / ' + esc(p.district || '-') + '</span>';
                list.appendChild(div);
            });
        }

        function loadProviderProfiles() {
            if (currentRole() !== 'provider') return Promise.resolve();
            return api('/provider-profiles').then(function (items) {
                providerProfiles = items || [];
                renderProviderList();
                if (providerProfiles[0]) {
                    var t = el('provider-title');
                    var b = el('provider-bio');
                    var c = el('provider-city');
                    var d = el('provider-district');
                    var lat = el('provider-lat');
                    var lng = el('provider-lng');
                    if (t) t.value = providerProfiles[0].title || '';
                    if (b) b.value = providerProfiles[0].bio || '';
                    if (c) c.value = providerProfiles[0].city || '';
                    if (d) d.value = providerProfiles[0].district || '';
                    var cityIdEl = el('provider-city-id');
                    var districtIdEl = el('provider-district-id');
                    if (cityIdEl) cityIdEl.value = providerProfiles[0].city_id != null ? String(providerProfiles[0].city_id) : '';
                    if (districtIdEl) districtIdEl.value = providerProfiles[0].district_id != null ? String(providerProfiles[0].district_id) : '';
                    if (lat) lat.value = providerProfiles[0].latitude || '40.4093';
                    if (lng) lng.value = providerProfiles[0].longitude || '49.8671';
                    paintAudioPlayer(providerProfiles[0].audio_intro_url || null);
                    var handle = pickerMaps['provider-map'];
                    if (handle && lat && lng) {
                        handle.apply(Number(lat.value), Number(lng.value));
                    }
                }
            }).catch(function (e) {
                log('Provider profil siyahısı alınmadı: ' + e.message);
            });
        }

        var saveProfile = el('save-profile');
        if (saveProfile) {
            saveProfile.addEventListener('click', function () {
                api('/auth/profile', {
                    method: 'PATCH',
                    body: JSON.stringify({
                        name: el('profile-name').value.trim(),
                    }),
                }).then(function (me) {
                    meCache = unwrapMe(me) || meCache;
                    paintUserCard(meCache);
                    toast('success', 'İstifadəçi profili yeniləndi');
                    log('İstifadəçi profili yeniləndi');
                    return setAuthStatus();
                }).catch(function (e) {
                    toast('error', 'Profil yenilənmədi');
                    log('Profil yenilənmədi: ' + e.message);
                });
            });
        }

        function openAvatarPicker() {
            var fileInput = el('profile-avatar-file');
            if (fileInput) fileInput.click();
        }

        function uploadAvatarFile(file) {
            if (!file) return;
            var btn = el('upload-avatar');
            var pick = el('avatar-pick');
            var fd = new FormData();
            fd.append('avatar', file);
            if (btn) btn.disabled = true;
            if (pick) pick.disabled = true;
            var img = el('profile-avatar-img');
            var fallback = el('profile-avatar-fallback');
            if (img && file.type.indexOf('image/') === 0) {
                img.src = URL.createObjectURL(file);
                img.hidden = false;
                if (fallback) fallback.hidden = true;
            }
            api('/auth/avatar', { method: 'POST', body: fd }).then(function (me) {
                meCache = unwrapMe(me) || meCache;
                paintUserCard(meCache);
                toast('success', 'Şəkil yükləndi');
                return setAuthStatus();
            }).catch(function (e) {
                toast('error', 'Şəkil yüklənmədi: ' + e.message);
                paintUserCard(meCache);
            }).finally(function () {
                if (btn) btn.disabled = false;
                if (pick) pick.disabled = false;
                var fileInput = el('profile-avatar-file');
                if (fileInput) fileInput.value = '';
            });
        }

        var uploadAvatar = el('upload-avatar');
        var avatarPick = el('avatar-pick');
        var avatarFile = el('profile-avatar-file');
        if (uploadAvatar) uploadAvatar.addEventListener('click', openAvatarPicker);
        if (avatarPick) avatarPick.addEventListener('click', openAvatarPicker);
        if (avatarFile) {
            avatarFile.addEventListener('change', function () {
                if (avatarFile.files && avatarFile.files[0]) {
                    uploadAvatarFile(avatarFile.files[0]);
                }
            });
        }

        function setAudioStatus(text) {
            var status = el('audio-intro-status');
            if (status) status.textContent = text;
        }

        function paintAudioPlayer(url) {
            var player = el('provider-audio-player');
            if (!player) return;
            if (url) {
                player.src = url;
                player.hidden = false;
                setAudioStatus('Mövcud intro hazırdır');
            } else {
                player.hidden = true;
                player.removeAttribute('src');
                setAudioStatus('Hələ audio yoxdur');
            }
        }

        function uploadAudioBlob(file, label) {
            if (!providerProfiles[0]) {
                toast('warning', 'Əvvəl xidmətçi profilini yaradın / saxlayın');
                return Promise.reject(new Error('no profile'));
            }
            var fd = new FormData();
            fd.append('audio', file, file.name || 'intro.webm');
            setAudioStatus((label || 'Yüklənir') + '…');
            var recordBtnEl = el('audio-record-btn');
            var pickBtnEl = el('audio-pick-btn');
            if (recordBtnEl) recordBtnEl.disabled = true;
            if (pickBtnEl) pickBtnEl.disabled = true;
            return api('/provider-profiles/' + providerProfiles[0].id + '/audio-intro', {
                method: 'POST',
                body: fd,
            }).then(function (profile) {
                providerProfiles[0] = profile;
                paintAudioPlayer(profile.audio_intro_url);
                toast('success', 'Audio yükləndi');
                return profile;
            }).catch(function (e) {
                toast('error', 'Audio yüklənmədi: ' + e.message);
                setAudioStatus('Yükləmə alınmadı — yenidən cəhd edin');
                throw e;
            }).finally(function () {
                if (recordBtnEl) recordBtnEl.disabled = false;
                if (pickBtnEl) pickBtnEl.disabled = false;
                var fileInput = el('provider-audio-file');
                if (fileInput) fileInput.value = '';
            });
        }

        var audioRecorder = null;
        var audioChunks = [];
        var audioStream = null;
        var audioTimer = null;
        var audioElapsed = 0;
        var audioMaxSec = 20;

        function stopAudioTracks() {
            if (audioStream) {
                audioStream.getTracks().forEach(function (t) { t.stop(); });
                audioStream = null;
            }
        }

        function clearAudioTimer() {
            if (audioTimer) {
                clearInterval(audioTimer);
                audioTimer = null;
            }
            var timerEl = el('audio-intro-timer');
            if (timerEl) {
                timerEl.hidden = true;
                timerEl.classList.remove('is-recording');
                timerEl.textContent = '00:00';
            }
            audioElapsed = 0;
        }

        function formatAudioClock(sec) {
            var mm = String(Math.floor(sec / 60)).padStart(2, '0');
            var ss = String(sec % 60).padStart(2, '0');
            return mm + ':' + ss;
        }

        function setRecordButton(recording) {
            var btn = el('audio-record-btn');
            if (!btn) return;
            var label = btn.querySelector('.audio-btn-label');
            if (recording) {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-dark');
                if (label) label.textContent = 'Dayandır';
            } else {
                btn.classList.add('btn-primary');
                btn.classList.remove('btn-dark');
                if (label) label.textContent = 'Mikrofonla yaz';
            }
        }

        function finishRecording() {
            if (!audioRecorder || audioRecorder.state === 'inactive') return;
            audioRecorder.stop();
        }

        function startRecording() {
            if (!providerProfiles[0]) {
                toast('warning', 'Əvvəl xidmətçi profilini yaradın / saxlayın');
                return;
            }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                toast('error', 'Bu brauzerdə mikrofon dəstəklənmir');
                return;
            }
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
                audioStream = stream;
                audioChunks = [];
                var mime = '';
                if (window.MediaRecorder) {
                    if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
                        mime = 'audio/webm;codecs=opus';
                    } else if (MediaRecorder.isTypeSupported('audio/webm')) {
                        mime = 'audio/webm';
                    } else if (MediaRecorder.isTypeSupported('audio/ogg;codecs=opus')) {
                        mime = 'audio/ogg;codecs=opus';
                    } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
                        mime = 'audio/mp4';
                    }
                }
                try {
                    audioRecorder = mime
                        ? new MediaRecorder(stream, { mimeType: mime })
                        : new MediaRecorder(stream);
                } catch (err) {
                    stopAudioTracks();
                    toast('error', 'Yazma başladıla bilmədi');
                    return;
                }
                audioRecorder.ondataavailable = function (ev) {
                    if (ev.data && ev.data.size > 0) audioChunks.push(ev.data);
                };
                audioRecorder.onstop = function () {
                    clearAudioTimer();
                    setRecordButton(false);
                    stopAudioTracks();
                    var type = (audioRecorder && audioRecorder.mimeType) || mime || 'audio/webm';
                    var blob = new Blob(audioChunks, { type: type });
                    audioRecorder = null;
                    audioChunks = [];
                    if (!blob.size) {
                        toast('warning', 'Boş yazı — yenidən cəhd edin');
                        return;
                    }
                    var ext = type.indexOf('ogg') >= 0 ? 'ogg'
                        : (type.indexOf('mp4') >= 0 || type.indexOf('m4a') >= 0) ? 'm4a'
                        : 'webm';
                    var file = new File([blob], 'intro.' + ext, { type: type });
                    var player = el('provider-audio-player');
                    if (player) {
                        player.src = URL.createObjectURL(blob);
                        player.hidden = false;
                    }
                    uploadAudioBlob(file, 'Yazı göndərilir');
                };
                audioRecorder.start(250);
                setRecordButton(true);
                setAudioStatus('Yazılır… danışın');
                var timerEl = el('audio-intro-timer');
                if (timerEl) {
                    timerEl.hidden = false;
                    timerEl.classList.add('is-recording');
                    timerEl.textContent = '00:00';
                }
                audioElapsed = 0;
                audioTimer = setInterval(function () {
                    audioElapsed += 1;
                    if (timerEl) timerEl.textContent = formatAudioClock(audioElapsed);
                    if (audioElapsed >= audioMaxSec) {
                        finishRecording();
                    }
                }, 1000);
            }).catch(function () {
                toast('error', 'Mikrofon icazəsi lazımdır');
                setAudioStatus('Mikrofon icazəsi verilmədi');
            });
        }

        var recordBtn = el('audio-record-btn');
        if (recordBtn) {
            recordBtn.addEventListener('click', function () {
                if (audioRecorder && audioRecorder.state === 'recording') {
                    finishRecording();
                    return;
                }
                startRecording();
            });
        }

        var pickBtn = el('audio-pick-btn');
        var audioFile = el('provider-audio-file');
        if (pickBtn && audioFile) {
            pickBtn.addEventListener('click', function () {
                if (!providerProfiles[0]) {
                    toast('warning', 'Əvvəl xidmətçi profilini yaradın / saxlayın');
                    return;
                }
                audioFile.click();
            });
            audioFile.addEventListener('change', function () {
                if (audioFile.files && audioFile.files[0]) {
                    uploadAudioBlob(audioFile.files[0], 'Fayl göndərilir');
                }
            });
        }

        var saveProvider = el('save-provider-profile');
        if (saveProvider) {
            saveProvider.addEventListener('click', function () {
                var selected = getSelectedCategories();
                if (!selected.length) {
                    toast('warning', 'Əvvəl kateqoriyalar səhifəsində seçim et');
                    return;
                }
                requireRole('provider').then(function () {
                    var payload = {
                        category_ids: selected,
                        title: el('provider-title').value.trim() || null,
                        bio: el('provider-bio').value.trim() || null,
                        city: el('provider-city').value.trim() || null,
                        district: el('provider-district').value.trim() || null,
                        city_id: el('provider-city-id') && el('provider-city-id').value
                            ? Number(el('provider-city-id').value)
                            : null,
                        district_id: el('provider-district-id') && el('provider-district-id').value
                            ? Number(el('provider-district-id').value)
                            : null,
                        latitude: Number(el('provider-lat').value || 40.4093),
                        longitude: Number(el('provider-lng').value || 49.8671),
                    };
                    if (providerProfiles[0]) {
                        return api('/provider-profiles/' + providerProfiles[0].id, {
                            method: 'PUT',
                            body: JSON.stringify(payload),
                        });
                    }
                    return api('/provider-profiles', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                }).then(function () {
                    toast('success', 'Provider profili saxlanıldı');
                    log('Provider profili saxlanıldı');
                    return loadProviderProfiles();
                }).catch(function (e) {
                    toast('error', 'Provider profili saxlanmadı');
                    log('Provider profili xətası: ' + e.message);
                });
            });
        }

        ensureMe().then(function (me) {
            if (!me || me.active_role !== 'provider') return;
            if (!el('provider-map')) return;
            initPickerMap({
                mapId: 'provider-map',
                latId: 'provider-lat',
                lngId: 'provider-lng',
                searchId: 'provider-place-search',
                suggestionsId: 'provider-place-suggestions',
                cityId: 'provider-city',
                districtId: 'provider-district',
                cityIdHidden: 'provider-city-id',
                districtIdHidden: 'provider-district-id',
            });
            return loadProviderProfiles();
        }).catch(function (e) {
            log('Profil məlumatı alınmadı: ' + e.message);
        });
    }

    function setRequestViewMode(on) {
        ['text', 'place-search', 'lat', 'lng', 'request-category-search'].forEach(function (id) {
            var node = el(id);
            if (node) node.disabled = !!on;
        });
        var picker = el('request-category-picker');
        if (picker) picker.classList.toggle('is-disabled', !!on);
        if (el('request-category')) el('request-category').disabled = !!on;

        var createBtn = el('create-request');
        if (createBtn) {
            createBtn.hidden = !!on;
            createBtn.disabled = !!on;
        }

        var newLink = el('request-new-link');
        if (newLink) newLink.hidden = !on;

        if (el('request-page-title')) {
            el('request-page-title').textContent = on ? 'Sorğuya bax' : 'Sorğu yarat';
        }
        if (el('request-page-sub')) {
            el('request-page-sub').textContent = on
                ? 'Bu sorğunun məlumatı və uyğun icraçılar — forma yalnız baxış üçündür.'
                : 'Kateqoriya seç, əlavə qeyd yaz, ünvanı göstər — uyğun icraçılar çıxır.';
        }
        if (el('request-form-title')) {
            el('request-form-title').textContent = on ? 'Sorğu detalları' : 'Yeni sorğu';
        }
        if (el('request-form-hint')) {
            el('request-form-hint').textContent = on
                ? 'Sahələr kilidlidir. Yeni sorğu üçün yuxarıdakı düymədən keçin.'
                : 'Əvvəl kateqoriya seçin — əlavə qeyd və ünvan dəqiqləşdirir.';
        }

        var editor = el('request-editor');
        if (editor) editor.classList.toggle('is-view-only', !!on);

        var handle = pickerMaps.map;
        if (handle) {
            handle.readOnly = !!on;
            if (handle.marker && typeof handle.marker.setDraggable === 'function') {
                handle.marker.setDraggable(!on);
            }
        }
        var mapEl = el('map');
        var locate = mapEl && mapEl.querySelector('.map-locate');
        if (locate) locate.hidden = !!on;
    }

    function bindRequestPage() {
        var restoreId = Number(
            new URLSearchParams(window.location.search).get('requestId') || 0
        ) || null;

        initPickerMap({
            mapId: 'map',
            latId: 'lat',
            lngId: 'lng',
            searchId: 'place-search',
            suggestionsId: 'place-suggestions',
            labelId: 'place-label',
        }).then(function () {
            return api('/categories').then(function (items) {
                fillRequestCategorySelect(items || []);
            }).catch(function () {
                toast('warning', 'Kateqoriyalar yüklənmədi');
            });
        }).then(function () {
            if (!restoreId) {
                setRequestViewMode(false);
                clearRequestId();
                resetRequestResultsUi();
                return;
            }

            setRequestId(restoreId);
            setRequestViewMode(true);
            if (el('request-info')) {
                el('request-info').textContent = 'Sorğu #' + restoreId + ' yüklənir…';
            }
            if (el('matches')) {
                el('matches').innerHTML = '<p class="muted">Nəticələr yüklənir…</p>';
            }
            return refreshRequest().then(function () {
                setRequestViewMode(true);
                var results = el('request-results');
                if (results && results.scrollIntoView) {
                    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }).catch(function () {
                resetRequestResultsUi();
            });
        });

        var logoutBtn = el('logout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function () {
                api('/auth/logout', { method: 'POST' }).catch(function () {});
                logoutEverywhere();
            });
        }

        function paintRequestRole() {
            var label = el('request-role-label');
            if (!label) return;
            label.textContent = roleLabel(currentRole()) || '—';
        }
        if (meCache) paintRequestRole();
        else if (getToken()) {
            api('/auth/me').then(function (me) {
                meCache = unwrapMe(me);
                paintRequestRole();
            }).catch(function () {});
        }

        el('create-request').addEventListener('click', function () {
            var createBtn = el('create-request');
            if (!createBtn || createBtn.disabled || createBtn.hidden) return;
            if (createBtn.dataset.loading === '1') return;
            var categoryId = getRequestCategoryId();
            if (!categoryId) {
                toast('warning', 'Əvvəl kateqoriya seçin');
                var search = el('request-category-search');
                if (search) search.focus();
                return;
            }
            var note = el('text').value.trim();
            var catLabel = getRequestCategoryLabel();
            var text = note || catLabel || 'Sorğu';
            var idleLabel = createBtn.textContent;

            function setCreating(on) {
                createBtn.dataset.loading = on ? '1' : '';
                createBtn.disabled = !!on;
                createBtn.setAttribute('aria-busy', on ? 'true' : 'false');
                createBtn.textContent = on ? 'Yaradılır…' : idleLabel;
                if (on) showPageLoader();
                else hidePageLoader();
            }

            setCreating(true);
            requireRole('client').then(function () {
                return api('/service-requests/text', {
                    method: 'POST',
                    body: JSON.stringify({
                        text: text,
                        category_id: categoryId,
                        latitude: Number(el('lat').value || 0),
                        longitude: Number(el('lng').value || 0),
                        address: (el('place-search') && el('place-search').value.trim()) || null,
                        is_urgent: false,
                    }),
                });
            }).then(function (data) {
                setRequestId(data.id);
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, '', '/request?requestId=' + encodeURIComponent(data.id));
                }
                el('request-info').textContent = 'Sorğu #' + data.id + ' yaradıldı · ' + data.status;
                toast('success', 'Sorğu yaradıldı');
                log('Sorğu yaradıldı', { id: data.id, status: data.status, category_id: categoryId });
                createBtn.textContent = 'Nəticələr yüklənir…';
                return refreshRequest().then(function () {
                    setRequestViewMode(true);
                });
            }).catch(function (e) {
                if (e && /yalnız ailə/i.test(e.message || '')) return;
                toast('error', 'Sorğu yaradılmadı');
                log('Sorğu yaradılmadı: ' + e.message);
            }).finally(function () {
                if (createBtn.hidden) {
                    hidePageLoader();
                    createBtn.dataset.loading = '';
                    createBtn.textContent = idleLabel;
                    return;
                }
                setCreating(false);
            });
        });

        el('refresh-request').addEventListener('click', function () {
            refreshRequest().then(function () {
                toast('info', 'Nəticələr yeniləndi');
            }).catch(function () {});
        });
    }

    function bindChatPage() {
        function loadChats() {
            return api('/conversations').then(function (items) {
                var list = el('chat-list');
                var rows = Array.isArray(items) ? items : ((items && items.data) || []);
                if (!rows.length) {
                    list.textContent = 'Söhbət yoxdur';
                    return;
                }
                list.innerHTML = '';
                rows.forEach(function (c) {
                    var other = (c.other_user && c.other_user.name) || 'İstifadəçi';
                    var preview = (c.last_message && c.last_message.body) || 'Yeni söhbət';
                    var a = document.createElement('a');
                    a.className = 'chat-item';
                    a.href = '/chat/' + c.id;
                    a.innerHTML =
                        '<b>' + esc(other) + '</b>' +
                        (c.unread_count ? ' <span class="pill">' + esc(c.unread_count) + '</span>' : '') +
                        '<div class="muted">' + esc(preview) + '</div>';
                    list.appendChild(a);
                });
                log('Söhbətlər yükləndi', { count: rows.length });
            }).catch(function (e) {
                toast('error', 'Söhbətlər yüklənmədi');
                log('Chat xətası: ' + e.message);
            });
        }
        el('refresh-chats').addEventListener('click', loadChats);
        loadChats();
    }

    function bindChatThreadPage() {
        var conversationId = Number(document.body.getAttribute('data-conversation-id') || 0);
        if (!conversationId) return;

        var offerModal = el('offer-modal');
        var reviewModal = el('review-modal');
        var openOfferBtn = el('open-offer');

        function statusLabel(status) {
            return ({
                pending: 'Gözləyir',
                accepted: 'Təsdiq',
                declined: 'İmtina',
                completed: 'Bitdi',
                cancelled: 'Ləğv',
            })[status] || status;
        }

        function formatWhen(iso) {
            if (!iso) return '—';
            var d = new Date(iso);
            if (Number.isNaN(d.getTime())) return String(iso).replace('T', ' ').slice(0, 16);
            return d.toLocaleString('az-AZ', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        }

        function myReview(offer, myId) {
            var list = (offer && offer.reviews) || [];
            for (var i = 0; i < list.length; i++) {
                if (asId(list[i].reviewer_id) === myId) return list[i];
            }
            return null;
        }

        function otherReview(offer, myId) {
            var list = (offer && offer.reviews) || [];
            for (var i = 0; i < list.length; i++) {
                if (asId(list[i].reviewer_id) !== myId) return list[i];
            }
            return null;
        }

        function offerAction(path, okMsg) {
            return api(path, { method: 'POST', body: JSON.stringify({}) })
                .then(function () {
                    toast('success', okMsg);
                    return loadThread();
                })
                .catch(function (e) {
                    toast('error', e.message || 'Əməliyyat alınmadı');
                    log('Offer action xətası: ' + e.message);
                });
        }

        function openReview(offerId) {
            el('review-offer-id').value = String(offerId);
            el('review-rating').value = '5';
            el('review-comment').value = '';
            reviewModal.hidden = false;
        }

        function buildOfferCard(offer, me, conversation) {
            var myId = asId(me && me.id);
            var isClientSide = myId != null && myId === asId(conversation && conversation.client_id);
            var isProviderSide = myId != null && myId === asId(conversation && conversation.provider_id);
            var card = document.createElement('div');
            card.className = 'offer-card';

            var hours = offer.duration_hours != null
                ? '<div><span>Müddət:</span> ' + esc(offer.duration_hours) + ' saat</div>'
                : '';
            var note = offer.note
                ? '<div class="offer-note">' + esc(offer.note) + '</div>'
                : '';

            card.innerHTML =
                '<div class="offer-head">' +
                    '<strong>Təklif</strong>' +
                    '<span class="pill">' + esc(statusLabel(offer.status)) + '</span>' +
                '</div>' +
                '<div class="offer-meta">' +
                    '<div><span>Vaxt:</span> ' + esc(formatWhen(offer.scheduled_at)) + '</div>' +
                    hours +
                    '<div><span>Qiymət:</span> ' + esc(offer.price_azn) + ' AZN</div>' +
                '</div>' +
                note +
                '<div class="offer-actions"></div>' +
                '<div class="offer-reviews"></div>';

            var actions = card.querySelector('.offer-actions');
            var reviewsBox = card.querySelector('.offer-reviews');

            if (offer.status === 'pending' && isClientSide) {
                var decline = document.createElement('button');
                decline.type = 'button';
                decline.className = 'btn';
                decline.textContent = 'İmtina';
                decline.addEventListener('click', function () {
                    offerAction('/offers/' + offer.id + '/decline', 'Təklif rədd edildi');
                });
                var accept = document.createElement('button');
                accept.type = 'button';
                accept.className = 'btn btn-primary';
                accept.textContent = 'Qəbul et';
                accept.addEventListener('click', function () {
                    offerAction('/offers/' + offer.id + '/accept', 'Təklif qəbul edildi');
                });
                actions.appendChild(decline);
                actions.appendChild(accept);
            }

            if (offer.status === 'pending' && isProviderSide) {
                var cancel = document.createElement('button');
                cancel.type = 'button';
                cancel.className = 'btn';
                cancel.textContent = 'Ləğv et';
                cancel.addEventListener('click', function () {
                    offerAction('/offers/' + offer.id + '/cancel', 'Təklif ləğv edildi');
                });
                actions.appendChild(cancel);
            }

            if (offer.status === 'accepted') {
                var complete = document.createElement('button');
                complete.type = 'button';
                complete.className = 'btn btn-primary';
                complete.textContent = 'İş tamamlandı';
                complete.addEventListener('click', function () {
                    offerAction('/offers/' + offer.id + '/complete', 'İş tamamlandı');
                });
                actions.appendChild(complete);
            }

            if (offer.status === 'completed') {
                var mine = myReview(offer, myId);
                var theirs = otherReview(offer, myId);
                if (mine) {
                    var y = document.createElement('div');
                    y.textContent = 'Sizin rəyiniz: ★ ' + mine.rating;
                    reviewsBox.appendChild(y);
                }
                if (theirs) {
                    var t = document.createElement('div');
                    t.textContent = 'Onların rəyi: ★ ' + theirs.rating;
                    reviewsBox.appendChild(t);
                }
                if (!mine) {
                    var reviewBtn = document.createElement('button');
                    reviewBtn.type = 'button';
                    reviewBtn.className = 'btn btn-primary';
                    reviewBtn.textContent = 'Rəy yaz';
                    reviewBtn.addEventListener('click', function () {
                        openReview(offer.id);
                    });
                    actions.appendChild(reviewBtn);
                }
            }

            if (!actions.children.length) {
                actions.remove();
            }
            if (!reviewsBox.children.length) {
                reviewsBox.remove();
            }

            return card;
        }

        function renderThread(conversation) {
            var me = unwrapMe(meCache);
            var other = (conversation.other_user && conversation.other_user.name) || 'Söhbət';
            if (el('thread-title')) {
                el('thread-title').textContent = other;
            }
            if (openOfferBtn) {
                openOfferBtn.hidden = conversation.can_send_offer !== true;
            }

            var box = el('thread-messages');
            box.innerHTML = '';
            var myId = asId(me && me.id);
            messageList(conversation).forEach(function (raw) {
                var m = unwrapMsg(raw);
                var mine = isMineMessage(m, conversation, myId);
                var row = document.createElement('div');
                row.className = 'msg-row' + (mine ? ' mine' : ' theirs');
                row.style.justifyContent = mine ? 'flex-end' : 'flex-start';

                if (m.offer && m.offer.id) {
                    row.appendChild(buildOfferCard(m.offer, me, conversation));
                } else {
                    var div = document.createElement('div');
                    div.className = 'msg' + (mine ? ' mine' : '');
                    div.innerHTML =
                        '<div>' + esc(m.body || '') + '</div>' +
                        '<span class="time">' + esc((m.created_at || '').replace('T', ' ').slice(0, 16)) + '</span>';
                    row.appendChild(div);
                }
                box.appendChild(row);
            });
            box.scrollTop = box.scrollHeight;
        }

        function loadThread() {
            var ready = unwrapMe(meCache)
                ? Promise.resolve(unwrapMe(meCache))
                : api('/auth/me').then(function (me) {
                    meCache = unwrapMe(me);
                    return meCache;
                });
            return ready.then(function () {
                return api('/conversations/' + conversationId);
            }).then(function (conversation) {
                renderThread(conversation);
            }).catch(function (e) {
                toast('error', 'Söhbət açılmadı');
                log('Thread xətası: ' + e.message);
            });
        }

        el('send-message').addEventListener('click', function () {
            var body = el('chat-body').value.trim();
            if (!body) {
                toast('warning', 'Mesaj yaz');
                return;
            }
            api('/conversations/' + conversationId + '/messages', {
                method: 'POST',
                body: JSON.stringify({ body: body }),
            }).then(function () {
                el('chat-body').value = '';
                toast('success', 'Göndərildi');
                return loadThread();
            }).catch(function (e) {
                toast('error', 'Mesaj getmədi');
                log('Mesaj xətası: ' + e.message);
            });
        });

        el('chat-body').addEventListener('keydown', function (evt) {
            if (evt.key === 'Enter') {
                el('send-message').click();
            }
        });

        if (openOfferBtn) {
            openOfferBtn.addEventListener('click', function () {
                var def = new Date(Date.now() + 2 * 60 * 60 * 1000);
                var pad = function (n) { return String(n).padStart(2, '0'); };
                el('offer-when').value =
                    def.getFullYear() + '-' + pad(def.getMonth() + 1) + '-' + pad(def.getDate()) +
                    'T' + pad(def.getHours()) + ':' + pad(def.getMinutes());
                el('offer-price').value = '';
                el('offer-hours').value = '';
                el('offer-note').value = '';
                offerModal.hidden = false;
            });
        }

        el('offer-cancel').addEventListener('click', function () {
            offerModal.hidden = true;
        });

        el('offer-submit').addEventListener('click', function () {
            var when = el('offer-when').value;
            var price = Number(el('offer-price').value);
            var hoursRaw = el('offer-hours').value.trim();
            var hours = hoursRaw === '' ? null : Number(hoursRaw);
            if (!when) {
                toast('warning', 'Tarix seç');
                return;
            }
            if (!price || price < 1) {
                toast('warning', 'Qiymət yaz');
                return;
            }
            var payload = {
                scheduled_at: new Date(when).toISOString(),
                price_azn: price,
                note: el('offer-note').value.trim() || null,
            };
            if (hours != null && !Number.isNaN(hours)) {
                payload.duration_hours = hours;
            }
            api('/conversations/' + conversationId + '/offers', {
                method: 'POST',
                body: JSON.stringify(payload),
            }).then(function () {
                offerModal.hidden = true;
                toast('success', 'Təklif göndərildi');
                return loadThread();
            }).catch(function (e) {
                toast('error', e.message || 'Təklif getmədi');
                log('Offer create xətası: ' + e.message);
            });
        });

        el('review-cancel').addEventListener('click', function () {
            reviewModal.hidden = true;
        });

        el('review-submit').addEventListener('click', function () {
            var offerId = el('review-offer-id').value;
            var rating = Number(el('review-rating').value);
            api('/offers/' + offerId + '/reviews', {
                method: 'POST',
                body: JSON.stringify({
                    rating: rating,
                    comment: el('review-comment').value.trim() || null,
                }),
            }).then(function () {
                reviewModal.hidden = true;
                toast('success', 'Rəy göndərildi');
                return loadThread();
            }).catch(function (e) {
                toast('error', e.message || 'Rəy getmədi');
                log('Review xətası: ' + e.message);
            });
        });

        loadThread();
        setInterval(loadThread, 8000);
    }

    function bindJobsPage() {
        function ensureMe() {
            if (meCache) return Promise.resolve(meCache);
            if (!getToken()) return Promise.resolve(null);
            return api('/auth/me').then(function (me) {
                meCache = unwrapMe(me);
                return meCache;
            }).catch(function () {
                return null;
            });
        }

        function loadJobs() {
            var box = el('jobs-list');
            if (box) box.textContent = 'Yüklənir…';

            return ensureMe().then(function () {
                applyRoleUi();
                if (currentRole() !== 'provider') {
                    if (box) {
                        box.textContent = currentRole()
                            ? 'Bu səhifə yalnız icraçı üçündür.'
                            : 'Giriş lazımdır';
                    }
                    return null;
                }
                if (meCache && (
                    meCache.needs_provider_approval === true
                    || meCache.provider_approval_status === 'pending'
                    || meCache.provider_approval_status === 'rejected'
                )) {
                    if (box) {
                        box.textContent = meCache.provider_approval_message
                            || 'Sorğunuz 1 saat ərzində baxılacaq. Təsdiqləndikdən sonra iş sorğuları gələcək.';
                    }
                    return null;
                }
                return api('/jobs');
            }).then(function (items) {
                if (items == null) return;
                if (!box) return;
                var rows = Array.isArray(items) ? items : ((items && items.data) || []);
                if (!rows.length) {
                    box.textContent = 'Hazırda gələn iş yoxdur';
                    return;
                }
                box.innerHTML = '';
                rows.forEach(function (job) {
                    var req = job.request || {};
                    var card = document.createElement('article');
                    card.className = 'match-card';
                    card.innerHTML =
                        (job.is_urgent ? '<span class="pill">URGENT</span>' : '') +
                        '<h3>' + esc((job.client && job.client.name) || 'Müştəri') + '</h3>' +
                        '<p class="reasons">' + esc(req.transcribed_text || req.address || '') + '</p>' +
                        '<p class="meta">Skor: <b>' + Math.round(job.match_score || 0) + '%</b> · ' +
                        esc(job.distance_km != null ? job.distance_km : '-') + ' km</p>' +
                        '<button type="button" class="btn btn-primary reply">Cavab ver</button>';
                    card.querySelector('.reply').addEventListener('click', function () {
                        api('/conversations/reply', {
                            method: 'POST',
                            body: JSON.stringify({
                                service_request_id: req.id,
                                provider_profile_id: job.provider_profile_id,
                                message: 'Salam, işə baxıram.',
                            }),
                        }).then(function (conversation) {
                            toast('success', 'Cavab göndərildi');
                            go('/chat/' + conversation.id);
                        }).catch(function (e) {
                            toast('error', 'Cavab getmədi');
                            log('Job reply xətası: ' + e.message);
                        });
                    });
                    box.appendChild(card);
                });
                log('İşlər yükləndi', { count: rows.length });
            }).catch(function (e) {
                if (box) box.textContent = 'İşlər yüklənmədi';
                toast('error', 'İşlər yüklənmədi');
                log('Jobs xətası: ' + (e && e.message ? e.message : e));
            });
        }

        window.__reloadJobs = loadJobs;

        var refresh = el('refresh-jobs');
        if (refresh && refresh.dataset.bound !== '1') {
            refresh.dataset.bound = '1';
            refresh.addEventListener('click', loadJobs);
        }
        loadJobs();
    }

    function requestStatusLabel(status) {
        var map = {
            processing: 'Emal olunur',
            active: 'Aktiv',
            matched: 'Uyğunlaşıb',
            completed: 'Tamamlanıb',
            cancelled: 'Ləğv edilib',
        };
        return map[status] || status || '—';
    }

    function requestStatusClass(status) {
        var map = {
            processing: 'is-processing',
            active: 'is-active-status',
            matched: 'is-matched',
            completed: 'is-done',
            cancelled: 'is-cancelled',
        };
        return map[status] || 'is-processing';
    }

    function formatRequestWhen(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        if (Number.isNaN(d.getTime())) return '';
        var dd = String(d.getDate()).padStart(2, '0');
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var hh = String(d.getHours()).padStart(2, '0');
        var mi = String(d.getMinutes()).padStart(2, '0');
        return dd + '.' + mm + ' · ' + hh + ':' + mi;
    }

    function bindRequestsPage() {
        function ensureMe() {
            if (meCache) return Promise.resolve(meCache);
            if (!getToken()) return Promise.resolve(null);
            return api('/auth/me').then(function (me) {
                meCache = unwrapMe(me);
                return meCache;
            }).catch(function () {
                return null;
            });
        }

        function openRequest(id) {
            go('/request?requestId=' + encodeURIComponent(id));
        }

        function loadRequests() {
            var box = el('requests-list');
            if (box) box.textContent = 'Yüklənir…';

            return ensureMe().then(function () {
                applyRoleUi();
                if (currentRole() !== 'client') {
                    if (box) {
                        box.textContent = currentRole()
                            ? 'Bu səhifə yalnız ailə üçündür.'
                            : 'Giriş lazımdır';
                    }
                    return null;
                }
                return api('/service-requests');
            }).then(function (items) {
                if (items == null) return;
                if (!box) return;
                var rows = Array.isArray(items) ? items : ((items && items.data) || []);
                if (!rows.length) {
                    box.innerHTML =
                        '<div class="request-history-empty">' +
                        '<p>Hələ sorğu yoxdur.</p>' +
                        '<a href="/request" class="btn btn-primary btn-inline">Yeni sorğu yaz</a>' +
                        '</div>';
                    return;
                }
                box.innerHTML = '';
                rows.forEach(function (req) {
                    var cat = (req.category && (req.category.name_az || req.category.name)) || '';
                    var text = (req.transcribed_text || req.address || '').trim();
                    var count = req.matches_count != null ? req.matches_count : 0;
                    var title = cat || (text ? text.slice(0, 48) : ('Sorğu #' + req.id));
                    var card = document.createElement('article');
                    card.className = 'request-history-item';
                    card.tabIndex = 0;
                    card.setAttribute('role', 'button');
                    card.innerHTML =
                        '<div class="request-history-top">' +
                        '<span class="request-history-id">#' + esc(req.id) + '</span>' +
                        '<span class="request-status ' + requestStatusClass(req.status) + '">' +
                        esc(requestStatusLabel(req.status)) +
                        '</span>' +
                        (req.is_urgent ? '<span class="request-status is-urgent">Təcili</span>' : '') +
                        '</div>' +
                        '<h3 class="request-history-title">' + esc(title) + '</h3>' +
                        (text && text !== title
                            ? '<p class="request-history-text">' + esc(text) + '</p>'
                            : '') +
                        '<div class="request-history-foot">' +
                        '<span class="request-history-meta">' +
                        esc(count) + ' uyğunluq' +
                        (req.created_at ? ' · ' + esc(formatRequestWhen(req.created_at)) : '') +
                        '</span>' +
                        '<span class="request-history-cta">Nəticələrə bax →</span>' +
                        '</div>';
                    card.addEventListener('click', function () {
                        openRequest(req.id);
                    });
                    card.addEventListener('keydown', function (evt) {
                        if (evt.key === 'Enter' || evt.key === ' ') {
                            evt.preventDefault();
                            openRequest(req.id);
                        }
                    });
                    box.appendChild(card);
                });
                log('Sorğular yükləndi', { count: rows.length });
            }).catch(function (e) {
                if (box) box.textContent = 'Sorğular yüklənmədi';
                toast('error', 'Sorğular yüklənmədi');
                log('Requests xətası: ' + (e && e.message ? e.message : e));
            });
        }

        window.__reloadRequests = loadRequests;

        var refresh = el('refresh-requests');
        if (refresh && refresh.dataset.bound !== '1') {
            refresh.dataset.bound = '1';
            refresh.addEventListener('click', loadRequests);
        }
        loadRequests();
    }

    function bindHeaderAuth() {
        var menu = el('user-menu');
        var toggle = el('user-menu-toggle');
        var panel = el('user-menu-panel');
        var btn = el('header-logout');

        function closeMenu() {
            if (!menu || !toggle || !panel) return;
            menu.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            panel.hidden = true;
        }

        function openMenu() {
            if (!menu || !toggle || !panel) return;
            menu.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            panel.hidden = false;
        }

        if (toggle && panel && toggle.dataset.bound !== '1') {
            toggle.dataset.bound = '1';
            toggle.addEventListener('click', function (evt) {
                evt.preventDefault();
                evt.stopPropagation();
                if (panel.hidden) openMenu();
                else closeMenu();
            });
            document.addEventListener('click', function (evt) {
                if (!menu) return;
                if (menu.contains(evt.target)) return;
                closeMenu();
            });
            document.addEventListener('keydown', function (evt) {
                if (evt.key === 'Escape') closeMenu();
            });
        }

        if (btn && btn.dataset.bound !== '1') {
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                closeMenu();
                api('/auth/logout', { method: 'POST' }).catch(function () {});
                logoutEverywhere();
            });
        }

        var toClient = el('menu-switch-client');
        if (toClient) toClient.remove();
        var toProvider = el('menu-switch-provider');
        if (toProvider) toProvider.remove();
    }

    function bindDashboardPage() {
        var guestCta = el('dash-cta-guest');
        var clientCta = el('dash-cta-client');
        var providerCta = el('dash-cta-provider');
        var stats = el('dash-stats');
        var title = el('dash-title');
        var subtitle = el('dash-subtitle');

        function paintGuest() {
            if (guestCta) guestCta.hidden = false;
            if (clientCta) clientCta.hidden = true;
            if (providerCta) providerCta.hidden = true;
            if (stats) stats.hidden = true;
            if (title) title.textContent = 'Evdə lazım olanı tez tap';
            if (subtitle) {
                subtitle.textContent =
                    'Səs və ya mətnlə sorğu göndər — uyğun xidmətçilər çıxır, CONNECT ilə yazış.';
            }
            applyRoleUi();
        }

        function paintUser(me) {
            if (guestCta) guestCta.hidden = true;
            var isProvider = me.active_role === 'provider';
            if (clientCta) clientCta.hidden = isProvider;
            if (providerCta) providerCta.hidden = !isProvider;
            if (stats) stats.hidden = false;
            var name = (me.name && String(me.name).trim()) || me.phone || 'dostum';
            if (title) title.textContent = 'Salam, ' + name;
            if (subtitle) {
                subtitle.textContent = isProvider
                    ? 'Gələn işlərə bax, chat-də təklif göndər.'
                    : 'Yeni sorğu yarat, match-lərdən CONNECT et.';
            }
            var bal = el('dash-balance');
            var conn = el('dash-connect');
            var connLabel = el('dash-connect-label');
            var role = el('dash-role');
            if (bal) {
                bal.textContent = (Number(me.balance || 0)).toFixed(0) + ' AZN';
            }
            if (conn) {
                var q = me.connect_quota || {};
                if (me.active_role === 'client') {
                    if (q.in_free_window) {
                        var left = q.free_remaining != null
                            ? q.free_remaining
                            : Math.max(0, (q.free_quota || 5) - (q.free_used || 0));
                        conn.textContent = left + '/' + (q.free_quota || 5) + ' pulsuz';
                        if (connLabel) connLabel.textContent = 'Pulsuz CONNECT';
                    } else {
                        var fee = Number(q.fee || 0);
                        var feeText = Number.isInteger(fee) ? String(fee) : fee.toFixed(1);
                        conn.textContent = feeText + ' AZN · ' + (q.daily_remaining != null ? q.daily_remaining : '—');
                        if (connLabel) connLabel.textContent = 'CONNECT';
                    }
                } else {
                    conn.textContent = '—';
                    if (connLabel) connLabel.textContent = 'CONNECT';
                }
            }
            if (role) role.textContent = roleLabel(me.active_role);
            applyRoleUi();
        }

        if (!getToken()) {
            paintGuest();
            return;
        }
        if (meCache) {
            paintUser(meCache);
            return;
        }
        api('/auth/me').then(function (me) {
            meCache = unwrapMe(me);
            paintUser(meCache);
        }).catch(function () {
            paintGuest();
        });
    }

    function bindPage() {
        bindHeaderAuth();
        bindPageTransitions();
        if (page === 'dashboard') bindDashboardPage();
        if (page === 'login') bindLoginPage();
        if (page === 'onboarding') bindOnboardingPage();
        if (page === 'categories') bindCategoriesPage();
        if (page === 'profile') bindProfilePage();
        if (page === 'request') bindRequestPage();
        if (page === 'requests') bindRequestsPage();
        if (page === 'provider-public') bindProviderPublicPage();
        if (page === 'chat') bindChatPage();
        if (page === 'chat-thread') bindChatThreadPage();
        if (page === 'jobs') bindJobsPage();
    }

    document.addEventListener('DOMContentLoaded', function () {
        hidePageLoader();
        hydrateRoleFromSnap();
        bindPage();
        setAuthStatus().then(function () {
            var publicPages = { login: 1, dashboard: 1 };
            if (!publicPages[page] && !getToken()) {
                go('/login');
                return;
            }
            if (page === 'login' && getToken()) {
                go('/');
                return;
            }
            if (page === 'dashboard') {
                bindDashboardPage();
            }
            if (page === 'jobs' && typeof window.__reloadJobs === 'function') {
                window.__reloadJobs();
            }
            if (page === 'requests' && typeof window.__reloadRequests === 'function') {
                window.__reloadRequests();
            }
        });
    });
})();
