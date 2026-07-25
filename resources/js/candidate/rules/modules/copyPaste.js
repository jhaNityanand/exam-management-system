import { isEditableTarget } from '../helpers';

export function createCopyPasteRule({ policy, send, examRoot }) {
    if (!policy.block_copy_paste) return null;

    if (examRoot) examRoot.classList.add('is-no-select');

    const block = (eventName) => (e) => {
        e.preventDefault();
        send(eventName);
    };

    const onSelectStart = (e) => {
        if (isEditableTarget(e.target)) return;
        e.preventDefault();
    };
    const onDragStart = (e) => {
        e.preventDefault();
        send('drag_attempt');
    };
    const onDrop = (e) => {
        e.preventDefault();
        send('drag_attempt');
    };

    const onCopy = block('copy_attempt');
    const onCut = block('cut_attempt');
    const onPaste = block('paste_attempt');

    document.addEventListener('copy', onCopy);
    document.addEventListener('cut', onCut);
    document.addEventListener('paste', onPaste);
    document.addEventListener('selectstart', onSelectStart);
    document.addEventListener('dragstart', onDragStart);
    document.addEventListener('drop', onDrop);

    return () => {
        document.removeEventListener('copy', onCopy);
        document.removeEventListener('cut', onCut);
        document.removeEventListener('paste', onPaste);
        document.removeEventListener('selectstart', onSelectStart);
        document.removeEventListener('dragstart', onDragStart);
        document.removeEventListener('drop', onDrop);
        if (examRoot) examRoot.classList.remove('is-no-select');
    };
}
