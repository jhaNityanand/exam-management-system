/**
 * Candidate settings — tabs, AJAX account/password/delete.
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function switchRow(name, key, label, help, checked) {
        return [
            '<label class="ca-switch-row">',
            '  <span><strong>' + label + '</strong><span>' + help + '</span></span>',
            '  <span class="ca-switch">',
            '    <input type="checkbox" name="' + name + '[' + key + ']" value="1"' + (checked ? ' checked' : '') + '>',
            '    <span class="ca-switch__ui" aria-hidden="true"></span>',
            '  </span>',
            '</label>',
        ].join('');
    }

    onReady(function () {
        var root = document.getElementById('ca-settings');
        if (!root || !window.CaAccount) return;

        var alertEl = document.getElementById('ca-settings-alert');
        var skeleton = document.getElementById('ca-settings-skeleton');
        var content = document.getElementById('ca-settings-content');
        var modal = document.getElementById('ca-delete-modal');

        document.querySelectorAll('[data-ca-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-ca-tab');
                document.querySelectorAll('[data-ca-tab]').forEach(function (b) {
                    b.classList.toggle('is-active', b === btn);
                });
                document.querySelectorAll('[data-ca-panel]').forEach(function (panel) {
                    var show = panel.getAttribute('data-ca-panel') === tab;
                    panel.hidden = !show;
                    if (show) panel.removeAttribute('hidden');
                    else panel.setAttribute('hidden', 'hidden');
                });
            });
        });

        function openModal() {
            if (!modal) return;
            modal.hidden = false;
            modal.removeAttribute('hidden');
        }
        function closeModal() {
            if (!modal) return;
            modal.hidden = true;
            modal.setAttribute('hidden', 'hidden');
        }
        document.querySelectorAll('[data-ca-modal-close]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });
        var openDelete = document.getElementById('ca-delete-open');
        if (openDelete) openDelete.addEventListener('click', openModal);

        window.CaAccount.fetchJson(root.getAttribute('data-url'))
            .then(function (data) {
                if (skeleton) skeleton.remove();
                if (content) {
                    content.hidden = false;
                    content.removeAttribute('hidden');
                }

                document.getElementById('ca-set-name').value = (data.account && data.account.name) || '';
                document.getElementById('ca-set-email').value = (data.account && data.account.email) || '';

                var n = data.notifications || {};
                document.getElementById('ca-notifications').innerHTML = [
                    switchRow('notifications', 'exam_reminders', 'Exam reminders', 'Upcoming exam and schedule notices.', !!n.exam_reminders),
                    switchRow('notifications', 'result_alerts', 'Result alerts', 'Get notified when results are released.', !!n.result_alerts),
                    switchRow('notifications', 'security_alerts', 'Security alerts', 'Password and login security notices.', !!n.security_alerts),
                    switchRow('notifications', 'marketing', 'Product updates', 'Occasional tips and platform news.', !!n.marketing),
                ].join('');

                var p = data.privacy || {};
                document.getElementById('ca-privacy').innerHTML = [
                    switchRow('privacy', 'show_profile', 'Show profile', 'Allow others to view your basic profile.', !!p.show_profile),
                    switchRow('privacy', 'show_results', 'Show results', 'Make completed results visible where applicable.', !!p.show_results),
                    switchRow('privacy', 'allow_messages', 'Allow messages', 'Receive messages from the platform team.', !!p.allow_messages),
                ].join('');

                var sec = data.security || {};
                document.getElementById('ca-security-stats').innerHTML = [
                    ['Email verified', sec.email_verified ? 'Yes' : 'No'],
                    ['Password set', sec.has_password ? 'Yes' : 'No'],
                    ['2FA', sec.two_factor ? 'On' : 'Off'],
                    ['Sessions', (data.sessions || []).length],
                ].map(function (row) {
                    return '<div class="ca-stat"><span class="ca-stat__label">' + row[0] + '</span><span class="ca-stat__value">' + row[1] + '</span></div>';
                }).join('');

                var sessions = data.sessions || [];
                var sessionsEl = document.getElementById('ca-sessions');
                if (!sessions.length) {
                    sessionsEl.innerHTML = '<div class="ca-empty">No recent sessions found.</div>';
                } else {
                    sessionsEl.innerHTML = sessions.map(function (s) {
                        return [
                            '<div class="ca-list-item">',
                            '  <div><strong>' + (s.device || 'Device') + '</strong>',
                            '  <span>' + (s.ip_address || '—') + ' · ' + (s.last_activity || '—') + '</span></div>',
                            s.is_current ? '<span class="ca-badge is-info">Current</span>' : '',
                            '</div>',
                        ].join('');
                    }).join('');
                }

                if (data.password_reset_url) {
                    var reset = document.getElementById('ca-password-reset');
                    if (reset) reset.setAttribute('href', data.password_reset_url);
                }
            })
            .catch(function (err) {
                if (skeleton) skeleton.remove();
                window.CaAccount.showAlert(alertEl, 'error', err.message || 'Unable to load settings.');
            });

        var accountForm = document.getElementById('ca-account-form');
        if (accountForm) {
            accountForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                var btn = document.getElementById('ca-account-save');
                window.CaAccount.clearFieldErrors(accountForm);
                window.CaAccount.setButtonLoading(btn, true);
                try {
                    var fd = new FormData(accountForm);
                    var payload = {
                        name: fd.get('name'),
                        email: fd.get('email'),
                        notifications: {
                            exam_reminders: !!fd.get('notifications[exam_reminders]'),
                            result_alerts: !!fd.get('notifications[result_alerts]'),
                            security_alerts: !!fd.get('notifications[security_alerts]'),
                            marketing: !!fd.get('notifications[marketing]'),
                        },
                        privacy: {
                            show_profile: !!fd.get('privacy[show_profile]'),
                            show_results: !!fd.get('privacy[show_results]'),
                            allow_messages: !!fd.get('privacy[allow_messages]'),
                        },
                    };
                    var data = await window.CaAccount.fetchJson(root.getAttribute('data-account-url'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                    window.CaAccount.showAlert(alertEl, 'success', data.message || 'Settings saved.');
                } catch (err) {
                    window.CaAccount.showAlert(alertEl, 'error', err.message || 'Unable to save settings.');
                    window.CaAccount.applyFieldErrors(accountForm, err.errors || {});
                } finally {
                    window.CaAccount.setButtonLoading(btn, false);
                }
            });
        }

        var passwordForm = document.getElementById('ca-password-form');
        if (passwordForm) {
            passwordForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                var btn = document.getElementById('ca-password-save');
                window.CaAccount.clearFieldErrors(passwordForm);
                window.CaAccount.setButtonLoading(btn, true);
                try {
                    var fd = new FormData(passwordForm);
                    var data = await window.CaAccount.fetchJson(root.getAttribute('data-password-url'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            current_password: fd.get('current_password'),
                            password: fd.get('password'),
                            password_confirmation: fd.get('password_confirmation'),
                        }),
                    });
                    passwordForm.reset();
                    window.CaAccount.showAlert(alertEl, 'success', data.message || 'Password updated.');
                } catch (err) {
                    window.CaAccount.showAlert(alertEl, 'error', err.message || 'Unable to update password.');
                    window.CaAccount.applyFieldErrors(passwordForm, err.errors || {});
                } finally {
                    window.CaAccount.setButtonLoading(btn, false);
                }
            });
        }

        var deleteForm = document.getElementById('ca-delete-form');
        if (deleteForm) {
            deleteForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                var btn = document.getElementById('ca-delete-submit');
                window.CaAccount.clearFieldErrors(deleteForm);
                window.CaAccount.setButtonLoading(btn, true, 'Deleting…');
                try {
                    var fd = new FormData(deleteForm);
                    var data = await window.CaAccount.fetchJson(root.getAttribute('data-destroy-url'), {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            password: fd.get('password'),
                            confirmation: fd.get('confirmation'),
                        }),
                    });
                    window.location.href = data.redirect || '/';
                } catch (err) {
                    window.CaAccount.showAlert(alertEl, 'error', err.message || 'Unable to delete account.');
                    window.CaAccount.applyFieldErrors(deleteForm, err.errors || {});
                    window.CaAccount.setButtonLoading(btn, false);
                }
            });
        }
    });
})();
