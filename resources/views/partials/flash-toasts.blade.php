@php
    $emsToasts = [];

    if (session()->has('success')) {
        $emsToasts[] = ['type' => 'success', 'message' => (string) session('success')];
    }

    if (session()->has('error')) {
        $emsToasts[] = ['type' => 'error', 'message' => (string) session('error')];
    }

    if (session()->has('warning')) {
        $emsToasts[] = ['type' => 'warning', 'message' => (string) session('warning')];
    }

    if (session()->has('info')) {
        $emsToasts[] = ['type' => 'info', 'message' => (string) session('info')];
    }

    if (session()->has('status')) {
        $status = session('status');
        $statusMessages = [
            'profile-updated' => 'Profile updated successfully.',
            'password-updated' => 'Password updated successfully.',
            'verification-link-sent' => 'A new verification link has been sent.',
        ];

        if (is_string($status) && isset($statusMessages[$status])) {
            $emsToasts[] = ['type' => 'success', 'message' => $statusMessages[$status]];
        } elseif (is_string($status) && $status !== '' && ! isset($statusMessages[$status])) {
            $emsToasts[] = ['type' => 'success', 'message' => $status];
        }
    }

    // Validation failures (redirect back) — show first error in toast
    if (isset($errors) && $errors->any()) {
        $first = (string) $errors->first();
        $extra = $errors->count() - 1;
        if ($first !== '') {
            $message = $extra > 0
                ? $first.' (+'.$extra.' more)'
                : $first;
            $emsToasts[] = ['type' => 'error', 'message' => $message];
        }
    }
@endphp

<link rel="stylesheet" href="{{ asset('css/backend/toast.css') }}">
<script src="{{ asset('js/core/toast.js') }}"></script>
@if (count($emsToasts) > 0)
    <script>
        window.__emsFlashToasts = @json($emsToasts);
        (function showFlashToasts() {
            const run = () => window.EmsToast?.fromFlash(window.__emsFlashToasts);
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', run);
            } else {
                run();
            }
        })();
    </script>
@endif
