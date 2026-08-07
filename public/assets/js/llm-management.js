const providerDefaultModels = {
    mistral: 'mistral-small-latest',
    groq: 'llama-3.3-70b-versatile',
    gemini: 'gemini-flash-latest',
    openrouter: 'openrouter/auto'
};

function onProviderChange(val) {
    const modelInput = document.getElementById('formModel');
    if (modelInput && providerDefaultModels[val]) {
        if (!modelInput.value || Object.values(providerDefaultModels).includes(modelInput.value)) {
            modelInput.value = providerDefaultModels[val];
        }
    }
}

function clearAllErrors() {
    document.querySelectorAll('[data-error-for]').forEach(el => {
        el.innerText = '';
        el.classList.add('hidden');
    });

    const alertBanner = document.getElementById('formAlertBanner');
    if (alertBanner) alertBanner.classList.add('hidden');

    const inputs = document.querySelectorAll('#accountForm input, #accountForm select, #accountForm textarea');
    inputs.forEach(input => {
        input.classList.remove('border-2', 'border-rose-500', 'ring-2', 'ring-rose-500/20');
    });
}

function clearFieldError(field) {
    const errorEl = document.querySelector(`[data-error-for="${field}"]`);
    if (errorEl) {
        errorEl.innerText = '';
        errorEl.classList.add('hidden');
    }

    const inputIdMap = {
        'provider': 'formProvider',
        'account_name': 'formAccountName',
        'model': 'formModel',
        'priority': 'formPriority',
        'api_key': 'formApiKey',
        'base_url': 'formBaseUrl',
        'organization_id': 'formOrganizationId',
        'daily_request_limit': 'formDailyReqLimit',
        'daily_token_limit': 'formDailyTokenLimit',
        'notes': 'formNotes'
    };

    const inputId = inputIdMap[field] || `form${field.charAt(0).toUpperCase() + field.slice(1)}`;
    const inputEl = document.getElementById(inputId);
    if (inputEl) {
        inputEl.classList.remove('border-2', 'border-rose-500', 'ring-2', 'ring-rose-500/20');
    }
}

function showFieldError(field, message) {
    const errorEl = document.querySelector(`[data-error-for="${field}"]`);
    if (errorEl) {
        errorEl.innerText = message;
        errorEl.classList.remove('hidden');
    }

    const inputIdMap = {
        'provider': 'formProvider',
        'account_name': 'formAccountName',
        'model': 'formModel',
        'priority': 'formPriority',
        'api_key': 'formApiKey',
        'base_url': 'formBaseUrl',
        'organization_id': 'formOrganizationId',
        'daily_request_limit': 'formDailyReqLimit',
        'daily_token_limit': 'formDailyTokenLimit',
        'notes': 'formNotes'
    };

    const inputId = inputIdMap[field] || `form${field.charAt(0).toUpperCase() + field.slice(1)}`;
    const inputEl = document.getElementById(inputId);
    if (inputEl) {
        inputEl.classList.add('border-2', 'border-rose-500', 'ring-2', 'ring-rose-500/20');
    }
}

function openAddAccountModal() {
    const modal = document.getElementById('accountModal');
    const form = document.getElementById('accountForm');
    if (!modal || !form) return;

    clearAllErrors();
    form.reset();
    document.getElementById('accountId').value = '';
    document.getElementById('modalTitle').innerText = 'Add LLM Provider Account';
    document.getElementById('apiKeyHelp').classList.add('hidden');
    document.getElementById('formProvider').value = 'mistral';
    document.getElementById('formModel').value = 'mistral-small-latest';
    document.getElementById('formIsActive').checked = true;

    modal.classList.remove('hidden');
}

function editAccount(account) {
    const modal = document.getElementById('accountModal');
    const form = document.getElementById('accountForm');
    if (!modal || !form || !account) return;

    clearAllErrors();
    form.reset();
    document.getElementById('accountId').value = account.id;
    document.getElementById('modalTitle').innerText = 'Edit LLM Account: ' + account.account_name;
    document.getElementById('formProvider').value = account.provider;
    document.getElementById('formAccountName').value = account.account_name;
    document.getElementById('formApiKey').value = '';
    document.getElementById('apiKeyHelp').classList.remove('hidden');
    document.getElementById('formModel').value = account.model;
    document.getElementById('formPriority').value = account.priority;
    document.getElementById('formBaseUrl').value = account.base_url || '';
    document.getElementById('formOrganizationId').value = account.organization_id || '';
    document.getElementById('formDailyReqLimit').value = account.daily_request_limit || '';
    document.getElementById('formDailyTokenLimit').value = account.daily_token_limit || '';
    document.getElementById('formNotes').value = account.notes || '';
    document.getElementById('formIsActive').checked = !!account.is_active;

    modal.classList.remove('hidden');
}

function closeAccountModal() {
    const modal = document.getElementById('accountModal');
    if (modal) modal.classList.add('hidden');
    clearAllErrors();
}

async function submitAccountForm(e) {
    e.preventDefault();
    clearAllErrors();

    const id = document.getElementById('accountId').value;
    const provider = document.getElementById('formProvider').value.trim();
    const accountName = document.getElementById('formAccountName').value.trim();
    const model = document.getElementById('formModel').value.trim();
    const priority = document.getElementById('formPriority').value.trim();
    const apiKey = document.getElementById('formApiKey').value.trim();

    let hasError = false;
    let firstInvalidInput = null;

    if (!provider) {
        showFieldError('provider', 'Provider selection is required.');
        if (!firstInvalidInput) firstInvalidInput = document.getElementById('formProvider');
        hasError = true;
    }

    if (!accountName) {
        showFieldError('account_name', 'Account name is required.');
        if (!firstInvalidInput) firstInvalidInput = document.getElementById('formAccountName');
        hasError = true;
    }

    if (!model) {
        showFieldError('model', 'Model name is required.');
        if (!firstInvalidInput) firstInvalidInput = document.getElementById('formModel');
        hasError = true;
    }

    if (!priority || isNaN(priority) || parseInt(priority) < 1) {
        showFieldError('priority', 'Priority must be a valid number (1 or higher).');
        if (!firstInvalidInput) firstInvalidInput = document.getElementById('formPriority');
        hasError = true;
    }

    if (!id && !apiKey) {
        showFieldError('api_key', 'API key is required for new accounts.');
        if (!firstInvalidInput) firstInvalidInput = document.getElementById('formApiKey');
        hasError = true;
    }

    if (hasError) {
        const alertBanner = document.getElementById('formAlertBanner');
        if (alertBanner) {
            document.getElementById('formAlertText').innerText = 'Please correct the validation errors below.';
            alertBanner.classList.remove('hidden');
        }
        if (firstInvalidInput) firstInvalidInput.focus();
        return false;
    }

    const btn = document.getElementById('saveAccountBtn');
    const originalText = btn.innerText;

    btn.disabled = true;
    btn.innerText = 'Saving...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData(e.target);

    const url = id 
        ? `/admin/settings/llm/accounts/${id}`
        : '/admin/settings/llm/accounts';

    if (id) {
        formData.append('_method', 'PUT');
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (response.ok && data.success) {
            window.location.reload();
        } else if (response.status === 422 && data.errors) {
            const alertBanner = document.getElementById('formAlertBanner');
            if (alertBanner) {
                document.getElementById('formAlertText').innerText = data.message || 'Validation failed. Check the fields below.';
                alertBanner.classList.remove('hidden');
            }

            let firstField = null;
            Object.keys(data.errors).forEach((key, index) => {
                const message = data.errors[key][0];
                showFieldError(key, message);
                if (index === 0) firstField = key;
            });

            if (firstField) {
                const firstEl = document.getElementById('form' + firstField.charAt(0).toUpperCase() + firstField.slice(1));
                if (firstEl) firstEl.focus();
            }
        } else {
            const alertBanner = document.getElementById('formAlertBanner');
            if (alertBanner) {
                document.getElementById('formAlertText').innerText = data.message || 'An error occurred while saving.';
                alertBanner.classList.remove('hidden');
            }
        }
    } catch (err) {
        const alertBanner = document.getElementById('formAlertBanner');
        if (alertBanner) {
            document.getElementById('formAlertText').innerText = 'Unexpected error: ' + err.message;
            alertBanner.classList.remove('hidden');
        }
    } finally {
        btn.disabled = false;
        btn.innerText = originalText;
    }
}

async function deleteAccount(id, name) {
    if (!confirm(`Are you sure you want to delete LLM Account "${name}"?`)) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const response = await fetch(`/admin/settings/llm/accounts/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        if (response.ok && data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to delete account.'));
        }
    } catch (err) {
        alert('An unexpected error occurred: ' + err.message);
    }
}

async function resetAccountCooldown(id) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const response = await fetch(`/admin/settings/llm/accounts/${id}/reset-cooldown`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        if (response.ok && data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to reset cooldown.'));
        }
    } catch (err) {
        alert('An unexpected error occurred: ' + err.message);
    }
}

async function testAccountConnection(id) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const showSwal = (options) => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire(options);
        }

        const message = options.text || options.title || '';
        if (options.icon === 'success') {
            alert(message);
        } else {
            alert((options.title ? options.title + '\n\n' : '') + message);
        }

        return Promise.resolve();
    };

    showSwal({
        title: 'Testing API connection',
        text: 'Please wait while we verify the provider credentials.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            if (window.Swal) {
                window.Swal.showLoading();
            }
        },
    });

    try {
        const response = await fetch(`/admin/settings/llm/accounts/${id}/test`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        if (response.ok && data.success) {
            await showSwal({
                icon: 'success',
                title: 'Connection successful',
                html: `
                    <div class="text-left text-sm space-y-1">
                        <p><strong>Provider:</strong> ${data.provider || '—'}</p>
                        <p><strong>Model:</strong> ${data.model || '—'}</p>
                        <p><strong>Message:</strong> ${data.message || 'API connection verified.'}</p>
                        <p><strong>Tokens used:</strong> ${data.tokens ?? 'N/A'}</p>
                    </div>
                `,
                confirmButtonText: 'Done',
            });
        } else {
            await showSwal({
                icon: 'error',
                title: 'Connection failed',
                text: data.message || 'Could not connect to the provider.',
            });
        }
    } catch (err) {
        await showSwal({
            icon: 'error',
            title: 'Connection test failed',
            text: err.message || 'An unexpected error occurred.',
        });
    }
}

function showRecordIdsModal(jsonStringOrArray) {
    let ids = [];
    try {
        ids = typeof jsonStringOrArray === 'string' ? JSON.parse(jsonStringOrArray) : jsonStringOrArray;
    } catch (e) {
        ids = [jsonStringOrArray];
    }

    const container = document.getElementById('recordIdsContent');
    if (container) {
        container.innerHTML = `<pre>${JSON.stringify(ids, null, 2)}</pre>`;
    }
    const modal = document.getElementById('recordIdsModal');
    if (modal) modal.classList.remove('hidden');
}

function closeRecordIdsModal() {
    const modal = document.getElementById('recordIdsModal');
    if (modal) modal.classList.add('hidden');
}

// Close modals on Escape key or backdrop click
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeAccountModal();
        closeRecordIdsModal();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const accountModal = document.getElementById('accountModal');
    if (accountModal) {
        accountModal.addEventListener('click', (e) => {
            if (e.target === accountModal) {
                closeAccountModal();
            }
        });
    }

    const recordIdsModal = document.getElementById('recordIdsModal');
    if (recordIdsModal) {
        recordIdsModal.addEventListener('click', (e) => {
            if (e.target === recordIdsModal) {
                closeRecordIdsModal();
            }
        });
    }
});
