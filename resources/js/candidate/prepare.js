/**
 * @deprecated Prepare UI is driven by public/js/candidate/prepare-boot.js.
 * Kept as a no-op export so accidental imports do not reintroduce the old
 * photo / no-challenge_token start contract.
 */
export function initPrepare() {
    if (typeof console !== 'undefined' && console.warn) {
        console.warn('[candidate] initPrepare is deprecated. Use prepare-boot.js.');
    }
}
