<div class="container py-5" style="max-width:420px;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="mb-3 text-center">Login</h3>
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
