<div class="card shadow-lg rounded-4 overflow-hidden login-form-wrapper">
    
    <div class="card-img-top">
    </div>

    <div class="p-4">
        <h3 class="mb-3 text-center">JsonSQL Admin Login</h3>
        <p class="text-muted text-center">👀 Bitte mit dem Adminpasswort anmelden.</p>
        <p class="text-center small text-secondary mt-2">
            Problem beim Anmelden?<br>
            Wenden Sie sich an den <a href="mailto:<?= htmlspecialchars($admin_email) ?>">Administrator</a>.
        </p>        

        <form method="post" class="login-form">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="password" class="form-label">Passwort</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary toggle-eye" onclick="togglePassword()" tabindex="-1" aria-label="Passwort anzeigen/verbergen">
                        <i id="togglePwdIcon" class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="remember_me" class="form-check-input" id="remember_me">
                <label class="form-check-label" for="remember_me">Angemeldet bleiben</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">🔐 Einloggen</button>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('togglePwdIcon');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>
