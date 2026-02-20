<script>
(() => {
    const FORM_LOCK_ATTR = 'data-submit-locked';
    const BUTTON_LOCK_ATTR = 'data-submit-btn-locked';
    const SPINNER_HTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>';

    const isMutatingMethod = (method) => ['POST', 'PUT', 'PATCH', 'DELETE'].includes((method || '').toUpperCase());

    const generateSubmissionId = () => {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return `sub-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    };

    const lockButton = (button) => {
        if (!button || button.getAttribute(BUTTON_LOCK_ATTR) === '1') {
            return;
        }

        button.setAttribute(BUTTON_LOCK_ATTR, '1');
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;

        if (button.tagName === 'BUTTON') {
            button.dataset.originalHtml = button.innerHTML;
            button.innerHTML = `${SPINNER_HTML}<span>Envoi...</span>`;
            return;
        }

        if (button.tagName === 'INPUT') {
            button.dataset.originalValue = button.value;
            button.value = 'Envoi...';
        }
    };

    const unlockButton = (button) => {
        if (!button) {
            return;
        }

        button.removeAttribute(BUTTON_LOCK_ATTR);
        button.removeAttribute('aria-busy');
        button.disabled = false;

        if (button.tagName === 'BUTTON' && button.dataset.originalHtml !== undefined) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }

        if (button.tagName === 'INPUT' && button.dataset.originalValue !== undefined) {
            button.value = button.dataset.originalValue;
            delete button.dataset.originalValue;
        }
    };

    const ensureHiddenSubmissionId = (form) => {
        let input = form.querySelector('input[name="__submission_id"]');

        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = '__submission_id';
            form.appendChild(input);
        }

        if (!input.value) {
            input.value = generateSubmissionId();
        }

        return input.value;
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-submit-lock')) {
            return;
        }

        if (form.getAttribute(FORM_LOCK_ATTR) === '1') {
            event.preventDefault();
            return;
        }

        if (isMutatingMethod(form.method || 'POST')) {
            ensureHiddenSubmissionId(form);
        }

        form.setAttribute(FORM_LOCK_ATTR, '1');

        const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        lockButton(submitter);

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            if (button !== submitter) {
                lockButton(button);
            }
        });

        queueMicrotask(() => {
            if (!event.defaultPrevented) {
                return;
            }

            form.removeAttribute(FORM_LOCK_ATTR);
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(unlockButton);
        });
    }, true);

    window.addEventListener('pageshow', () => {
        document.querySelectorAll(`form[${FORM_LOCK_ATTR}="1"]`).forEach((form) => {
            form.removeAttribute(FORM_LOCK_ATTR);
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(unlockButton);
        });
    });

    const pendingRequests = new Set();

    const buildRequestKey = (method, url, submissionId) => `${method}:${url}:${submissionId || 'none'}`;

    if (window.fetch) {
        const originalFetch = window.fetch.bind(window);

        window.fetch = async (resource, init = {}) => {
            const method = ((init.method || 'GET') + '').toUpperCase();
            if (!isMutatingMethod(method)) {
                return originalFetch(resource, init);
            }

            const headers = new Headers(init.headers || {});
            let submissionId = headers.get('X-Submission-Id') || '';

            if (!submissionId) {
                submissionId = generateSubmissionId();
                headers.set('X-Submission-Id', submissionId);
            }

            const url = typeof resource === 'string' ? resource : (resource && resource.url ? resource.url : window.location.href);
            const key = buildRequestKey(method, url, submissionId);

            if (pendingRequests.has(key)) {
                return Promise.reject(new Error('Duplicate request blocked.'));
            }

            pendingRequests.add(key);

            try {
                return await originalFetch(resource, { ...init, headers });
            } finally {
                pendingRequests.delete(key);
            }
        };
    }

    if (window.XMLHttpRequest) {
        const open = XMLHttpRequest.prototype.open;
        const send = XMLHttpRequest.prototype.send;
        const setRequestHeader = XMLHttpRequest.prototype.setRequestHeader;

        XMLHttpRequest.prototype.open = function(method, url, ...rest) {
            this.__submitGuardMethod = (method || 'GET').toUpperCase();
            this.__submitGuardUrl = url || window.location.href;
            this.__submitGuardHeaderSet = false;
            return open.call(this, method, url, ...rest);
        };

        XMLHttpRequest.prototype.setRequestHeader = function(name, value) {
            if ((name || '').toLowerCase() === 'x-submission-id' && value) {
                this.__submitGuardHeaderSet = true;
                this.__submitGuardSubmissionId = value;
            }

            return setRequestHeader.call(this, name, value);
        };

        XMLHttpRequest.prototype.send = function(body) {
            if (!isMutatingMethod(this.__submitGuardMethod)) {
                return send.call(this, body);
            }

            if (!this.__submitGuardHeaderSet) {
                this.__submitGuardSubmissionId = generateSubmissionId();
                setRequestHeader.call(this, 'X-Submission-Id', this.__submitGuardSubmissionId);
            }

            const key = buildRequestKey(
                this.__submitGuardMethod,
                this.__submitGuardUrl || window.location.href,
                this.__submitGuardSubmissionId
            );

            if (pendingRequests.has(key)) {
                return;
            }

            pendingRequests.add(key);
            this.addEventListener('loadend', () => pendingRequests.delete(key), { once: true });

            return send.call(this, body);
        };
    }
})();
</script>
