<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-4">
    <div class="card shadow-sm w-100" style="max-width:420px;">
        <div class="card-body p-4">
            <h3 class="mb-1 text-center">Login</h3>
            <p class="text-muted text-center mb-4">Acede ao EventPlanner para gerir eventos e equipas.</p>
            <form method="post" action="<?= BASE_URL ?>?controller=auth&action=authenticate">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-dark w-100">Entrar</button>
            </form>
        </div>
    </div>
</div>
