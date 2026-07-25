import { api } from '../api';
import { createEventSender } from './helpers';
import { createCopyPasteRule } from './modules/copyPaste';
import { createDevtoolsRule } from './modules/devtools';
import { createFullscreenRule } from './modules/fullscreen';
import { createKeyboardLockRule } from './modules/keyboardLock';
import { createMediaMonitorRule } from './modules/mediaMonitor';
import { createMouseLockRule } from './modules/mouseLock';
import { createPageLockRule } from './modules/pageLock';
import { createRightClickRule } from './modules/rightClick';
import { createTabSwitchRule } from './modules/tabSwitch';

/**
 * Central exam rule engine. Each rule is an isolated module with optional enablement.
 */
export function createRuleEngine({
    eventsUrl,
    policy = {},
    examRoot = null,
    onAutoSubmit,
    onWarning,
    onFullscreenExit,
    onDevtoolsChange,
    onMediaGraceTick,
    onMediaStatus,
    videoEl = null,
    statusEl = null,
}) {
    const rawLimit = policy.focus_violation_limit;
    const limit = rawLimit === null || rawLimit === undefined || rawLimit === ''
        ? 3
        : Math.max(0, Number(rawLimit));
    const allowUnloadRef = { current: false };
    const cleanups = [];

    const send = createEventSender({
        eventsUrl,
        api,
        allowUnloadRef,
        onAutoSubmit,
        onWarning,
        limit,
    });

    const ctx = {
        policy,
        send,
        examRoot,
        allowUnloadRef,
        onWarning,
        onFullscreenExit,
        onDevtoolsChange,
        onAutoSubmit,
        eventsUrl,
        api,
        videoEl,
        statusEl,
        onStatus: onMediaStatus,
        onGraceTick: onMediaGraceTick,
    };

    const factories = [
        createRightClickRule,
        createTabSwitchRule,
        createDevtoolsRule,
        createCopyPasteRule,
        createFullscreenRule,
        createPageLockRule,
        createKeyboardLockRule,
        createMouseLockRule,
    ];

    factories.forEach((factory) => {
        const dispose = factory(ctx);
        if (typeof dispose === 'function') cleanups.push(dispose);
    });

    // Media monitor is optional and returns { reconnect, destroy }.
    let mediaApi = null;
    mediaApi = createMediaMonitorRule(ctx);
    if (mediaApi?.destroy) {
        cleanups.push(() => mediaApi.destroy());
    } else if (typeof mediaApi === 'function') {
        cleanups.push(mediaApi);
        mediaApi = null;
    }

    return {
        allowNavigation() {
            allowUnloadRef.current = true;
        },
        reconnectMedia() {
            return mediaApi?.reconnect?.() ?? Promise.resolve(false);
        },
        destroy() {
            allowUnloadRef.current = true;
            while (cleanups.length) {
                try { cleanups.pop()?.(); } catch (e) {}
            }
            mediaApi = null;
        },
    };
}

/** @deprecated Prefer createRuleEngine — kept for compatibility with older imports. */
export function bindProctoring(options) {
    const engine = createRuleEngine(options);
    return {
        destroy: () => engine.destroy(),
        allowNavigation: () => engine.allowNavigation(),
    };
}

/** @deprecated Media monitoring is owned by the rule engine. */
export function startWebcamMonitor() {
    return () => {};
}
