/**
 * Cookie consent + deferred analytics/tag loading.
 */
(function () {
    'use strict';

    const cfg = window.ExamtubeIntegrations || null;
    if (!cfg) return;

    const STORAGE_KEY = 'et_cookie_consent_v1';

    const readConsent = () => {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    };

    const writeConsent = (value) => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
        } catch {
            // ignore
        }
    };

    const injectHtml = (html, target) => {
        if (!html || !String(html).trim()) return;
        const wrap = document.createElement('div');
        wrap.innerHTML = html;
        Array.from(wrap.childNodes).forEach((node) => {
            if (node.nodeName === 'SCRIPT') {
                const script = document.createElement('script');
                if (node.src) script.src = node.src;
                script.async = node.async;
                script.text = node.textContent || '';
                (target || document.head).appendChild(script);
            } else {
                (target || document.head).appendChild(node);
            }
        });
    };

    const loadTracking = () => {
        if (!cfg.analyticsEnabled || window.__etTrackingLoaded) return;
        window.__etTrackingLoaded = true;

        if (cfg.gtmId) {
            (function (w, d, s, l, i) {
                w[l] = w[l] || [];
                w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
                const f = d.getElementsByTagName(s)[0];
                const j = d.createElement(s);
                const dl = l !== 'dataLayer' ? '&l=' + l : '';
                j.async = true;
                j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', cfg.gtmId);
        }

        if (cfg.gaId) {
            const s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.gaId);
            document.head.appendChild(s);
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', cfg.gaId);
        }

        if (cfg.pixelId) {
            !function (f, b, e, v, n, t, s) {
                if (f.fbq) return; n = f.fbq = function () {
                    n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
                };
                if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
                t = b.createElement(e); t.async = !0; t.src = v;
                s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
            }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
            window.fbq('init', cfg.pixelId);
            window.fbq('track', 'PageView');
        }

        injectHtml(cfg.customHead, document.head);
        injectHtml(cfg.customBody, document.body);
    };

    const shouldLoadNow = () => {
        if (!cfg.analyticsEnabled) return false;
        if (!cfg.needsConsentGate) return true;
        const consent = readConsent();
        return Boolean(consent && consent.analytics);
    };

    const showBannerIfNeeded = () => {
        const banner = document.getElementById('et-cookie-banner');
        if (!banner || !cfg.cookies?.enabled) return;

        const consent = readConsent();
        const mode = cfg.cookies.mode || 'opt_in';

        if (mode === 'info_only') {
            if (!consent) banner.hidden = false;
            return;
        }

        if (!consent) {
            banner.hidden = false;
            return;
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (shouldLoadNow() && cfg.needsConsentGate) {
            loadTracking();
        }

        showBannerIfNeeded();

        const banner = document.getElementById('et-cookie-banner');
        banner?.querySelector('[data-et-cookie-accept]')?.addEventListener('click', () => {
            writeConsent({ analytics: true, marketing: true, ts: Date.now() });
            banner.hidden = true;
            if (cfg.needsConsentGate) loadTracking();
        });
        banner?.querySelector('[data-et-cookie-reject]')?.addEventListener('click', () => {
            writeConsent({ analytics: false, marketing: false, ts: Date.now() });
            banner.hidden = true;
        });
    });
}());
