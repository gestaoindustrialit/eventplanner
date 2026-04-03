<h2 class="mb-4">Publicar website público</h2>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="text-muted">Esta ação gera o website público (ex.: <code>chorarderir.com</code>) numa pasta fora do EventPlanner, com lista de eventos e formulário de reservas.</p>

        <form method="post" action="<?= BASE_URL ?>?controller=publicsite&action=publish" class="row g-3">
            <div class="col-12">
                <label class="form-label">Pasta de destino</label>
                <input type="text" name="target_path" class="form-control" value="<?= htmlspecialchars($defaultPath) ?>" required>
                <div class="form-text">Podes indicar uma pasta absoluta, por exemplo <code>/var/www/chorarderir.com</code>.</div>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Publicar website</button>
            </div>
        </form>
    </div>
</div>
