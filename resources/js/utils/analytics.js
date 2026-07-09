// resources/js/utils/analytics.js
const CONSENT_KEY = 'cookie_consent';

let gaLoaded = false;

export function getConsent() {
    try {
        const raw = localStorage.getItem(CONSENT_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

export function setConsent(analytics) {
    const consent = {analytics, decidedAt: new Date().toISOString()};
    localStorage.setItem(CONSENT_KEY, JSON.stringify(consent));

    if (analytics) {
        loadGoogleAnalytics();
    }

    return consent;
}

export function loadGoogleAnalytics() {
    const measurementId = import.meta.env.VITE_GA_MEASUREMENT_ID;

    if (!measurementId || gaLoaded) return;
    gaLoaded = true;

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`;
    document.head.appendChild(script);

    window.dataLayer = window.dataLayer || [];
    function gtag() {
        window.dataLayer.push(arguments);
    }
    window.gtag = gtag;
    gtag('js', new Date());
    gtag('config', measurementId, {anonymize_ip: true});
}

/**
 * Call once on app boot - resumes analytics if the visitor already consented previously.
 */
export function initAnalyticsFromStoredConsent() {
    const consent = getConsent();
    if (consent?.analytics) {
        loadGoogleAnalytics();
    }
}
