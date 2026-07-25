/**
 * Heuristic developer-tools detection (browser limitations apply).
 *
 * IMPORTANT: Do not treat absolute outer-inner gaps as "open". Browser chrome
 * (tabs, bookmarks, address bar) often makes heightGap > 160px with DevTools closed.
 * Compare against a chrome baseline captured at startup instead.
 */

function currentGaps() {
    return {
        width: Math.abs((window.outerWidth || 0) - (window.innerWidth || 0)),
        height: Math.abs((window.outerHeight || 0) - (window.innerHeight || 0)),
    };
}

let baseline = null;

export function captureDevtoolsBaseline() {
    const gaps = currentGaps();
    if (!baseline) {
        baseline = gaps;
        return baseline;
    }
    // Keep the smallest observed chrome size as baseline (most "closed" state).
    baseline = {
        width: Math.min(baseline.width, gaps.width),
        height: Math.min(baseline.height, gaps.height),
    };
    return baseline;
}

export function resetDevtoolsBaseline() {
    baseline = null;
}

export function isDevtoolsLikelyOpen(options = {}) {
    const dockThreshold = Number(options.dockThreshold || 120);
    const gaps = currentGaps();

    if (!baseline) {
        captureDevtoolsBaseline();
        return false;
    }

    // Docked DevTools usually grow one axis beyond normal browser chrome.
    const widthDelta = gaps.width - baseline.width;
    const heightDelta = gaps.height - baseline.height;
    if (widthDelta >= dockThreshold || heightDelta >= dockThreshold) {
        return true;
    }

    if (window.Firebug && window.Firebug.chrome && window.Firebug.chrome.isInitialized) {
        return true;
    }

    return false;
}

export function createDevtoolsWatcher({ intervalMs = 1200, onChange } = {}) {
    let last = false;
    let timer = null;
    let samples = 0;

    captureDevtoolsBaseline();

    function tick() {
        samples += 1;
        // Refine baseline for the first few seconds while assuming tools are closed.
        if (samples <= 5 && !last) {
            captureDevtoolsBaseline();
        }

        const open = isDevtoolsLikelyOpen();
        if (open !== last) {
            last = open;
            onChange?.(open);
        }
    }

    tick();
    timer = window.setInterval(tick, intervalMs);

    return {
        isOpen: () => last,
        stop() {
            if (timer) window.clearInterval(timer);
            timer = null;
        },
    };
}
