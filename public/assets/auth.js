function ensureTextVisibility() {
    const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
    inputs.forEach(input => {
        // Set text color to ensure visibility
        input.style.color = '#e5e7eb';

        // Check for autofill
        if (input.matches(':-webkit-autofill') || input.matches(':autofill')) {
            input.style.webkitTextFillColor = '#e5e7eb';
            input.style.color = '#e5e7eb';
        }
    });
}

document.addEventListener('DOMContentLoaded', ensureTextVisibility);
document.addEventListener('readystatechange', () => {
    if (document.readyState === 'interactive') {
        ensureTextVisibility();
    }
});

setTimeout(ensureTextVisibility, 100);
