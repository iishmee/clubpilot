const togglePassword = document.querySelector('#toggle-password');
const passwordInput = document.querySelector('#password');

togglePassword?.addEventListener('click', () => {
    const showPassword = passwordInput.type === 'password';

    passwordInput.type = showPassword ? 'text' : 'password';
    togglePassword.textContent = showPassword ? 'Hide' : 'Show';
    togglePassword.setAttribute('aria-pressed', String(showPassword));
});
