/**
 * Authentication page helpers (FR-1, FR-2, FR-3).
 *
 * Two small progressive enhancements, written in plain JavaScript:
 *   1. show / hide a password field,
 *   2. show the optional "subject" field only for teachers.
 *
 * The forms still work if this file fails to load; every field has a proper
 * label and a normal server-side validation fallback.
 */

function setupPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (! input) {
            return;
        }

        button.addEventListener('click', () => {
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            button.setAttribute(
                'aria-label',
                isHidden ? button.dataset.hideLabel : button.dataset.showLabel,
            );

            const icon = button.querySelector('i');

            if (icon) {
                icon.classList.toggle('bi-eye', ! isHidden);
                icon.classList.toggle('bi-eye-slash', isHidden);
            }

            // Keep the cursor in the field after changing its type.
            input.focus();
        });
    });
}

function setupRoleFields() {
    const roleControl = document.querySelector('[data-role-select]');
    const subjectField = document.querySelector('[data-subject-field]');

    if (! roleControl || ! subjectField) {
        return;
    }

    const roleSelect = roleControl.matches('select') ? roleControl : null;
    const roleRadios = roleControl.querySelectorAll('[data-role-radio]');

    const currentRole = () => {
        if (roleSelect) {
            return roleSelect.value;
        }

        const checked = roleControl.querySelector('[data-role-radio]:checked');

        return checked ? checked.value : 'student';
    };

    const syncSubjectField = () => {
        subjectField.hidden = currentRole() !== 'teacher';

        // Keep the whole card highlighted, including in browsers without :has().
        roleRadios.forEach((radio) => {
            const card = radio.closest('.edu-role-card');

            if (card) {
                card.classList.toggle('is-selected', radio.checked);
            }
        });
    };

    if (roleSelect) {
        roleSelect.addEventListener('change', syncSubjectField);
    }

    roleRadios.forEach((radio) => radio.addEventListener('change', syncSubjectField));

    syncSubjectField();
}

/**
 * Password strength meter (register page only).
 *
 * Four independent signals keep the advice understandable: length, mixed
 * case, a number and a symbol. The label is announced through aria-live, so
 * a screen reader hears the same feedback as the colour bars.
 */
function setupPasswordStrength() {
    document.querySelectorAll('[data-password-strength-input]').forEach((input) => {
        const meter = input.closest('.mb-3')?.querySelector('[data-password-strength]');

        if (! meter) {
            return;
        }

        const label = meter.querySelector('[data-password-strength-label]');
        const labels = [
            meter.dataset.strengthEmpty || 'Use at least 8 characters, with a number or a symbol.',
            meter.dataset.strengthWeak || 'Weak password — add more characters.',
            meter.dataset.strengthFair || 'Fair password — mix letters, numbers and a symbol.',
            meter.dataset.strengthGood || 'Good password — almost there.',
            meter.dataset.strengthStrong || 'Strong password. Well done!',
        ];

        const update = () => {
            const value = input.value;
            let score = 0;

            if (value.length >= 8) {
                score += 1;
            }
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
                score += 1;
            }
            if (/\d/.test(value)) {
                score += 1;
            }
            if (/[^A-Za-z0-9]/.test(value)) {
                score += 1;
            }

            // A very short password never looks strong, whatever it contains.
            if (value.length > 0 && value.length < 8) {
                score = Math.min(score, 1);
            }

            meter.dataset.score = String(score);

            if (label) {
                label.textContent = value.length === 0 ? labels[0] : labels[score];
            }
        };

        input.addEventListener('input', update);
        update();
    });
}

function bootAuthHelpers() {
    setupPasswordToggles();
    setupRoleFields();
    setupPasswordStrength();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAuthHelpers);
} else {
    bootAuthHelpers();
}
