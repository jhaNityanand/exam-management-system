<?php

namespace App\Support;

/**
 * Human-readable titles/messages for proctoring violations.
 * Shared by persistence, result pages, and learning feedback.
 */
class ProctoringViolationMessages
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{title:string, message:string, advice:string}
     */
    public static function describe(string $event, array $payload = [], ?int $count = null, ?int $limit = null): array
    {
        $title = self::title($event);
        $message = self::message($event, $payload, $count, $limit);
        $advice = self::advice($event);

        return [
            'title' => $title,
            'message' => $message,
            'advice' => $advice,
        ];
    }

    public static function title(string $event): string
    {
        return match ($event) {
            'tab_switch' => 'Tab switch detected',
            'window_blur' => 'Window focus lost',
            'fullscreen_exit' => 'Fullscreen exited',
            'copy_attempt' => 'Copy blocked',
            'paste_attempt' => 'Paste blocked',
            'cut_attempt' => 'Cut blocked',
            'drag_attempt' => 'Drag and drop blocked',
            'right_click' => 'Right-click blocked',
            'devtools_open', 'detools_open' => 'Developer tools detected',
            'page_refresh' => 'Page refresh blocked',
            'navigation_back' => 'Back navigation blocked',
            'media_lost' => 'Camera or microphone lost',
            'media_grace_expired' => 'Media not restored in time',
            'keyboard_lock_bypass' => 'Keyboard shortcut blocked',
            'mouse_lock_bypass' => 'Mouse action blocked',
            'session_warning' => 'Session warning',
            default => 'Rule warning',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function message(string $event, array $payload = [], ?int $count = null, ?int $limit = null): string
    {
        $base = match ($event) {
            'tab_switch' => 'You left or switched away from the exam tab. Stay on the exam tab until you finish.',
            'window_blur' => 'The exam window lost focus. Keep this exam window focused until you finish.',
            'fullscreen_exit' => 'You left fullscreen mode. Fullscreen is required for this exam.',
            'copy_attempt' => 'Copying content is not allowed during this exam.',
            'paste_attempt' => 'Pasting content is not allowed during this exam.',
            'cut_attempt' => 'Cut is not allowed during this exam.',
            'drag_attempt' => 'Dragging content is not allowed during this exam.',
            'right_click' => 'Right-click is disabled during the exam.',
            'devtools_open', 'detools_open' => 'Developer tools were detected. Close them and continue only in the exam window.',
            'page_refresh' => 'Refreshing or reloading the page is blocked during this exam.',
            'navigation_back' => 'Leaving this page with the back button is not allowed.',
            'media_lost' => 'Camera or microphone connection was lost. Restore access promptly to avoid auto-submit.',
            'media_grace_expired' => 'Required camera/microphone was not restored in time, so the exam was submitted.',
            'keyboard_lock_bypass' => self::keyboardBypassMessage($payload),
            'mouse_lock_bypass' => 'Mouse actions outside the exam area are restricted during this exam.',
            'session_warning' => 'A session integrity warning was recorded.',
            default => 'A monitoring rule was triggered during your exam.',
        };

        if ($count !== null && $limit !== null && ! in_array($event, ['right_click', 'media_lost', 'keyboard_lock_bypass', 'mouse_lock_bypass'], true)) {
            if ($limit === 0) {
                return $base.' No warnings are allowed for this exam.';
            }

            return $base.sprintf(' Warning %d of %d.', max(1, $count), max(0, $limit));
        }

        return $base;
    }

    public static function advice(string $event): string
    {
        return match ($event) {
            'tab_switch', 'window_blur' => 'Next time: stay on this exam tab and do not open other windows or apps.',
            'fullscreen_exit' => 'Next time: remain in fullscreen for the entire exam.',
            'copy_attempt', 'paste_attempt', 'cut_attempt', 'drag_attempt' => 'Next time: type answers yourself — do not copy or paste.',
            'right_click' => 'Next time: use only normal left-click and exam controls.',
            'devtools_open', 'detools_open' => 'Next time: do not open developer tools or inspect panels.',
            'page_refresh', 'navigation_back' => 'Next time: do not refresh, reload, or use browser back during the exam.',
            'media_lost', 'media_grace_expired' => 'Next time: keep camera/microphone connected and allow browser permissions.',
            'keyboard_lock_bypass' => 'Next time: avoid system shortcuts (Alt+Tab, Ctrl combinations) during the exam.',
            'mouse_lock_bypass' => 'Next time: keep your pointer inside the exam area.',
            default => 'Follow all exam rules carefully to avoid warnings next time.',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected static function keyboardBypassMessage(array $payload): string
    {
        $key = trim((string) ($payload['key'] ?? $payload['code'] ?? ''));
        if ($key !== '') {
            return 'Blocked keyboard action ('.$key.'). System shortcuts are locked during this exam.';
        }

        return 'That keyboard shortcut is locked during this exam.';
    }
}
