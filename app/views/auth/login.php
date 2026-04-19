<div class="login-page">
    <div class="login-layout">
        <section class="login-panel">
            <div class="login-brand-wrap">
                <img src="<?= BASE_PATH ?>/assets/branding/chorarderir-logo.svg" alt="Chorar de Rir" class="brand-logo-login">
            </div>

            <h2 class="login-title">LOGIN</h2>
            <p class="login-subtitle">Acede ao EventPlanner para gerir eventos e equipas.</p>

            <form class="login-form" method="post" action="<?= BASE_URL ?>?controller=auth&action=authenticate">
                <div class="mb-3">
                    <label class="form-label login-label">Email</label>
                    <input type="email" name="email" class="form-control login-input" placeholder="Email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label login-label">Password</label>
                    <input type="password" name="password" class="form-control login-input" placeholder="Password" required>
                </div>
                <button class="btn login-btn w-100">Entrar</button>
            </form>

            <p class="login-hint">Novo na plataforma? <a href="#" aria-disabled="true">Fala com o administrador</a></p>
        </section>

        <aside class="login-showcase" aria-hidden="true">
            <div class="login-showcase-shape"></div>
            <article class="login-showcase-card">
                <div class="login-showcase-icon">
                    <img src="<?= BASE_PATH ?>/assets/branding/cdr-label.jpg" alt="CDR Label" class="login-showcase-label">
                </div>
                <h3>Planeia sem stress</h3>
                <p>Centraliza eventos, equipas e reservas num painel simples e moderno.</p>
            </article>
        </aside>
    </div>
</div>
