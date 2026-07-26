/**
 * Shared user avatar helpers (mirrors App\Support\UserAvatar).
 * Initials: "Admin User" → "AU"; "Admin" → "AD"
 */
(function (global) {
    'use strict';

    function initials(name, fallback) {
        fallback = String(fallback || 'U');
        var cleaned = String(name || '').replace(/\s+/g, ' ').trim();
        if (!cleaned) {
            return fallback.slice(0, 2).toUpperCase();
        }

        var parts = cleaned.split(' ').filter(Boolean);
        if (parts.length >= 2) {
            var first = parts[0].charAt(0);
            var last = parts[parts.length - 1].charAt(0);
            return (first + last).toUpperCase();
        }

        var single = parts[0] || fallback;
        var chars = Array.from(single).slice(0, 2).join('');
        return (chars || fallback.slice(0, 2)).toUpperCase();
    }

    function color(seed) {
        var palette = [
            '#0f766e', '#0369a1', '#7c3aed', '#be185d', '#c2410c',
            '#15803d', '#1d4ed8', '#a16207', '#0e7490', '#4f46e5',
        ];
        var hash = 0;
        var str = String(seed || 'user');
        for (var i = 0; i < str.length; i++) {
            hash = ((hash << 5) - hash) + str.charCodeAt(i);
            hash |= 0;
        }
        return palette[Math.abs(hash) % palette.length];
    }

    global.EmsUserAvatar = {
        initials: initials,
        color: color,
    };
})(window);
