/**
 * AnatomIQ – Login Page JavaScript
 * Handles authentication via the PHP backend API.
 */

// ── App & API Base Paths ───────────────────────────────────────
// Dynamically resolve base paths so they work at any URL depth or trailing slash.
const APP_BASE = (() => {
    const parts = window.location.pathname.split('/');
    const idx   = parts.findIndex(p => p.toLowerCase() === 'prototype2');
    return idx !== -1 ? parts.slice(0, idx + 1).join('/') : '';
})();
const API_BASE = APP_BASE + '/api';

// ── Role Selection ─────────────────────────────────────────────
let selectedRole = 'student';

function selectRole(role) {
    selectedRole = role;
    document.querySelectorAll('.role-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.role === role);
    });
    // Update placeholder hints
    const user = document.getElementById('username');
    if (user) {
        user.placeholder = role === 'teacher' ? 'e.g. admin or teacher' : 'e.g. student001';
    }
}

// ── Demo Credential Fill ───────────────────────────────────────
function fillDemo(username, password, role) {
    selectRole(role);
    const userEl = document.getElementById('username');
    const passEl = document.getElementById('password');
    if (userEl) {
        userEl.value = username;
        userEl.parentElement?.classList.add('highlight');
        setTimeout(() => userEl.parentElement?.classList.remove('highlight'), 600);
    }
    if (passEl) {
        passEl.value = password;
        passEl.parentElement?.classList.add('highlight');
        setTimeout(() => passEl.parentElement?.classList.remove('highlight'), 600);
    }
    showToast(`Credentials filled for ${role}`, 'info');
}

// ── Password Toggle ────────────────────────────────────────────
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    if (btn) {
        btn.innerHTML = isHidden
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
}

// ── Login Handler ──────────────────────────────────────────────
async function handleLogin(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    const username   = document.getElementById('username')?.value.trim();
    const password   = document.getElementById('password')?.value;
    const loginBtn   = document.getElementById('loginBtn');
    const errorEl    = document.getElementById('loginError');
    const rememberMe = document.getElementById('rememberMe')?.checked || false;

    // Basic client-side validation
    if (!username) {
        showLoginError('Please enter your username.'); return;
    }
    if (!password) {
        showLoginError('Please enter your password.'); return;
    }

    // Show loading state
    setLoading(true);
    clearLoginError();

    try {
        const response = await fetch(`${API_BASE}/auth/login.php`, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body:        JSON.stringify({ username, password }),
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            // Server returned an error
            showLoginError(result.message || 'Login failed. Please check your credentials.');
            setLoading(false);
            return;
        }

        const user = result.data;

        // Align the selected role if user was on the other tab
        const serverRole = user.role;
        const validRoles = selectedRole === 'teacher'
            ? ['teacher', 'admin']
            : ['student'];

        if (!validRoles.includes(serverRole)) {
            selectRole(serverRole === 'student' ? 'student' : 'teacher');
        }

        // Store minimal user info in sessionStorage for client-side use
        // (PHP session is the authoritative source; this is just for UI)
        const sessionData = {
            user_id:   user.user_id,
            username:  user.username,
            role:      user.role,
            full_name: user.full_name,
            context:   user.context,
            role_id:   user.role_id,
        };
        sessionStorage.setItem('anatomiq_user', JSON.stringify(sessionData));

        // Handle "Remember Me" — extend session lifetime via cookie approach
        if (rememberMe) {
            localStorage.setItem('anatomiq_remember', username);
        } else {
            localStorage.removeItem('anatomiq_remember');
        }

        showToast('Welcome, ' + user.full_name + '!', 'success');

        // Redirect based on role with robust path
        setTimeout(() => {
            const dest = (serverRole === 'teacher' || serverRole === 'admin')
                ? `${APP_BASE}/teacher/dashboard.html`
                : `${APP_BASE}/student/dashboard.html`;
            window.location.href = dest;
        }, 500);

    } catch (err) {
        console.error('Login error:', err);
        showLoginError(
            'Cannot connect to the server. Please make sure XAMPP is running and try again.'
        );
        setLoading(false);
    }
}

function setLoading(loading) {
    const btn = document.getElementById('loginBtn');
    if (!btn) return;
    btn.disabled = loading;
    btn.innerHTML = loading
        ? '<span class="spinner"></span> Signing in...'
        : 'Sign In';
}

function showLoginError(msg) {
    const container = document.getElementById('loginError');
    const text      = document.getElementById('loginErrorText');
    if (text) text.textContent = msg;
    if (container) container.style.display = 'block';
}

function clearLoginError() {
    const container = document.getElementById('loginError');
    if (container) container.style.display = 'none';
}

// ── Forgot Password ────────────────────────────────────────────
async function handleForgotPassword(e) {
    if (e) e.preventDefault();

    const identifier  = document.getElementById('resetIdentifier')?.value.trim();
    const resetBtn    = document.getElementById('resetBtn');
    const resultEl    = document.getElementById('resetResult');

    if (!identifier) {
        if (resultEl) {
            resultEl.textContent = 'Please enter your email or student ID.';
            resultEl.className   = 'reset-error';
            resultEl.style.display = 'block';
        }
        return;
    }

    // Show loading
    if (resetBtn) { resetBtn.disabled = true; resetBtn.textContent = 'Sending...'; }
    if (resultEl)   resultEl.style.display = 'none';

    try {
        const response = await fetch(`${API_BASE}/auth/reset-password.php`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ action: 'request', identifier }),
        });

        const result = await response.json();

        if (result.success) {
            if (resultEl) {
                // In dev mode, show the token. In production this would say "check your email".
                const tokenInfo = result.data?.token
                    ? `<br><small style="opacity:0.7">Dev token: <code>${result.data.token.slice(0,12)}…</code> (check console)</small>`
                    : '';
                resultEl.innerHTML = `Reset instructions prepared for <strong>${result.data?.user_name || 'your account'}</strong>.${tokenInfo}`;
                resultEl.className = 'reset-success';
                resultEl.style.display = 'block';
                console.info('[AnatomIQ Dev] Reset token:', result.data?.token);
            }
        } else {
            if (resultEl) {
                resultEl.textContent = result.message || 'Account not found.';
                resultEl.className   = 'reset-error';
                resultEl.style.display = 'block';
            }
        }
    } catch (err) {
        if (resultEl) {
            resultEl.textContent = 'Server unavailable. Make sure XAMPP is running.';
            resultEl.className   = 'reset-error';
            resultEl.style.display = 'block';
        }
    } finally {
        if (resetBtn) { resetBtn.disabled = false; resetBtn.textContent = 'Send Reset Link'; }
    }
}

// ── Toast (local, since app.js isn't loaded on login page) ────
function showToast(msg, type = 'info') {
    const existing = document.getElementById('loginToast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id    = 'loginToast';
    const borderColors = { success: '#1a7a4a', error: '#c53030', info: '#1a6b4a', warning: '#b45309' };
    toast.style.cssText = `
        position:fixed;bottom:1.5rem;right:1.5rem;padding:0.75rem 1.25rem;
        background:#ffffff;border:1px solid #e0e3e8;border-left:3px solid ${borderColors[type] || borderColors.info};
        color:#1a1d23;border-radius:6px;font-size:0.8125rem;font-weight:500;
        z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,0.08);
    `;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}

// ── Particle Background (Disabled) ─────────────────────────────
function initParticles() {
    // Decorative particles disabled for clean, professional design
}

// ── Init ───────────────────────────────────────────────────────
function initLogin() {
    // Clean any lingering query parameters from previous GET submissions
    if (window.location.search) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    initParticles();

    // Re-fill username from "Remember Me"
    const remembered = localStorage.getItem('anatomiq_remember');
    if (remembered) {
        const userEl = document.getElementById('username');
        if (userEl) userEl.value = remembered;
        const remEl = document.getElementById('rememberMe');
        if (remEl) remEl.checked = true;
    }

    // Wire up login form
    const form = document.getElementById('loginForm');
    if (form) form.addEventListener('submit', handleLogin);

    // Wire up forgot password form
    const resetForm = document.getElementById('forgotPasswordForm');
    if (resetForm) resetForm.addEventListener('submit', handleForgotPassword);

    // Role buttons
    document.querySelectorAll('.role-btn').forEach(btn => {
        btn.addEventListener('click', () => selectRole(btn.dataset.role));
    });

    // Detect opening directly via file://
    if (window.location.protocol === 'file:') {
        showLoginError('Notice: You opened this page via file://. Please open http://localhost/Prototype2/ in your browser so the PHP backend and database can connect.');
    }

    // Check if already authenticated — redirect immediately
    const stored = sessionStorage.getItem('anatomiq_user');
    if (stored) {
        // Verify session with server before redirecting
        fetch(`${API_BASE}/auth/session.php`, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    const role = result.data.role;
                    const dest = (role === 'teacher' || role === 'admin')
                        ? `${APP_BASE}/teacher/dashboard.html`
                        : `${APP_BASE}/student/dashboard.html`;
                    window.location.href = dest;
                } else {
                    sessionStorage.removeItem('anatomiq_user');
                }
            })
            .catch(() => {
                // Server unavailable; clear stale session
                sessionStorage.removeItem('anatomiq_user');
            });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLogin);
} else {
    initLogin();
}
