<h2 class="mb-3"><?= $page ? 'Editar' : 'Nova' ?> página pública</h2>

<form method="post" action="<?= BASE_URL ?>?controller=publicpage&action=<?= $page ? 'update&id=' . (int)$page['id'] : 'store' ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Título</label>
            <input class="form-control" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Slug (URL)</label>
            <input class="form-control" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" placeholder="ex.: sobre-nos" required>
        </div>
        <div class="col-12">
            <label class="form-label">Resumo</label>
            <textarea class="form-control" name="excerpt" rows="2" placeholder="Texto curto para destaque no topo"><?= htmlspecialchars($page['excerpt'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Conteúdo principal (aceita HTML simples)</label>
            <textarea class="form-control" name="content" rows="10" placeholder="Podes usar <h3>, <p>, <ul>, <strong>, <a>, etc."><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Imagem de capa (URL)</label>
            <input class="form-control" name="hero_image_url" value="<?= htmlspecialchars($page['hero_image_url'] ?? '') ?>" placeholder="https://...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Modo de apresentação</label>
            <?php $displayMode = ($page['display_mode'] ?? 'section') === 'page' ? 'page' : 'section'; ?>
            <select class="form-select" name="display_mode">
                <option value="section" <?= $displayMode === 'section' ? 'selected' : '' ?>>Setor da home</option>
                <option value="page" <?= $displayMode === 'page' ? 'selected' : '' ?>>Página própria</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo de setor</label>
            <?php $sectionType = $page['section_type'] ?? 'default'; ?>
            <select class="form-select" name="section_type">
                <option value="default" <?= $sectionType === 'default' ? 'selected' : '' ?>>Conteúdo livre</option>
                <option value="about" <?= $sectionType === 'about' ? 'selected' : '' ?>>Sobre nós</option>
                <option value="services" <?= $sectionType === 'services' ? 'selected' : '' ?>>Serviços</option>
                <option value="contact_form" <?= $sectionType === 'contact_form' ? 'selected' : '' ?>>Contactos (formulário)</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Design do setor</label>
            <?php $sectionStyle = $page['section_style'] ?? 'card'; ?>
            <select class="form-select" name="section_style">
                <option value="card" <?= $sectionStyle === 'card' ? 'selected' : '' ?>>Card clássico</option>
                <option value="split" <?= $sectionStyle === 'split' ? 'selected' : '' ?>>Split (imagem + texto)</option>
                <option value="icons" <?= $sectionStyle === 'icons' ? 'selected' : '' ?>>Grelha de ícones</option>
                <option value="highlight" <?= $sectionStyle === 'highlight' ? 'selected' : '' ?>>Highlight escuro</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Ordem</label>
            <input type="number" class="form-control" name="sort_order" value="<?= (int)($page['sort_order'] ?? 0) ?>">
        </div>
        <?php
            $sectionConfig = json_decode((string)($page['section_config_json'] ?? ''), true);
            if (!is_array($sectionConfig)) {
                $sectionConfig = [];
            }
            $serviceRows = $sectionConfig['services'] ?? [];
            $contactFields = $sectionConfig['contact_fields'] ?? ['name', 'email', 'message'];
            if (!is_array($contactFields)) {
                $contactFields = ['name', 'email', 'message'];
            }
        ?>
        <div class="col-12">
            <div class="card mt-2">
                <div class="card-body">
                    <h5 class="mb-3">Configuração especial do setor</h5>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Texto call to action</label>
                            <textarea class="form-control" name="cta_text" rows="2" placeholder="Ex.: Fala connosco para levar humor ao teu evento."><?= htmlspecialchars($sectionConfig['cta_text'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Texto do botão CTA</label>
                            <input class="form-control" name="cta_button_text" value="<?= htmlspecialchars($sectionConfig['cta_button_text'] ?? 'Enviar mensagem') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email de destino (contactos)</label>
                            <input type="email" class="form-control" name="contact_email_to" value="<?= htmlspecialchars($sectionConfig['contact_email_to'] ?? '') ?>" placeholder="booking@...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Campos do formulário de contacto</label>
                            <div class="d-flex flex-wrap gap-3 small">
                                <?php
                                    $fieldLabels = ['name' => 'Nome', 'email' => 'Email', 'phone' => 'Telefone', 'subject' => 'Assunto', 'message' => 'Mensagem'];
                                    foreach ($fieldLabels as $fieldKey => $fieldLabel):
                                ?>
                                    <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="contact_fields[]" value="<?= $fieldKey ?>" <?= in_array($fieldKey, $contactFields, true) ? 'checked' : '' ?>><?= $fieldLabel ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Serviços (nome, ícone Bootstrap e descrição breve)</label>
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <?php $row = $serviceRows[$i] ?? []; ?>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-3"><input class="form-control" name="service_name[]" value="<?= htmlspecialchars($row['name'] ?? '') ?>" placeholder="Nome do serviço"></div>
                                    <div class="col-md-3"><input class="form-control" name="service_icon[]" value="<?= htmlspecialchars($row['icon'] ?? '') ?>" placeholder="mic-fill"></div>
                                    <div class="col-md-6"><input class="form-control" name="service_description[]" value="<?= htmlspecialchars($row['description'] ?? '') ?>" placeholder="Descrição breve"></div>
                                </div>
                            <?php endfor; ?>
                            <p class="small text-muted mb-0">Ícones aceites: nome da libraria Bootstrap Icons (ex.: <code>mic-fill</code>, <code>emoji-laughing</code>, <code>calendar-event</code>).</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_published" id="is_published" <?= (!$page || (int)($page['is_published'] ?? 0) === 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_published">Publicado</label>
            </div>
        </div>
    </div>

    <button class="btn btn-dark mt-3">Guardar página</button>
</form>
