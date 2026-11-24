(function () {
    'use strict';

    var toggle = document.querySelector('[data-password-toggle]');
    if (!toggle) return;

    var input = document.getElementById(toggle.getAttribute('aria-controls'));
    if (!input) return;

    var eyeOpen = toggle.querySelector('[data-eye-open]');
    var eyeClosed = toggle.querySelector('[data-eye-closed]');

    toggle.addEventListener('click', function () {
        var showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
        toggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        if (eyeOpen) eyeOpen.hidden = showPassword;
        if (eyeClosed) eyeClosed.hidden = !showPassword;
    });
}());
