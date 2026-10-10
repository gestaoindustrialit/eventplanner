<?php $editing = !empty($page['id']) && empty($isNew); ?>
<?php if (!empty($formError)): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($formError) ?></div><?php endif; ?>
<h2 class="mb-3"><?= $editing ? 'Editar' : 'Nova' ?> página pública</h2>

<form method="post" action="<?= BASE_URL ?>?controller=publicpage&action=<?= $editing ? 'update&id=' . (int)$page['id'] : 'store' ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['public_page_csrf']) ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Título</label>
            <input class="form-control" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Slug (URL)</label>
            <input class="form-control" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" placeholder="ex.: sobre-nos" required>
            <p id="slug-status" class="small" aria-live="polite"></p>
            <p id="slug-warning" class="small text-warning" hidden>Ao alterar o slug publicado, será criado um redirecionamento permanente 301 do URL antigo.</p>
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
                    <p class="text-muted small mb-3">Mostramos apenas os campos relevantes para o tipo de setor selecionado.</p>
                    <div class="row g-3">
                        <div class="col-md-8 js-config-block" data-types="about,contact_form,services,default">
                            <label class="form-label">Texto call to action</label>
                            <textarea class="form-control" name="cta_text" rows="2" placeholder="Ex.: Fala connosco para levar humor ao teu evento."><?= htmlspecialchars($sectionConfig['cta_text'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4 js-config-block" data-types="about,contact_form,services,default">
                            <label class="form-label">Texto do botão CTA</label>
                            <input class="form-control" name="cta_button_text" value="<?= htmlspecialchars($sectionConfig['cta_button_text'] ?? 'Enviar mensagem') ?>">
                        </div>
                        <div class="col-md-6 js-config-block" data-types="contact_form">
                            <label class="form-label">Email de destino (contactos)</label>
                            <input type="email" class="form-control" name="contact_email_to" value="<?= htmlspecialchars($sectionConfig['contact_email_to'] ?? '') ?>" placeholder="booking@...">
                        </div>
                        <div class="col-md-6 js-config-block" data-types="contact_form">
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
                        <div class="col-12 js-config-block" data-types="services">
                            <label class="form-label">Serviços (nome, ícone Bootstrap e descrição breve)</label>
                            <div class="row small text-muted fw-semibold mb-1 d-none d-md-flex">
                                <div class="col-md-3">Nome</div>
                                <div class="col-md-3">Ícone Bootstrap</div>
                                <div class="col-md-6">Descrição</div>
                            </div>
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
        <div class="col-12"><fieldset class="card card-body"><legend class="h5">Configuração de navegação</legend>
            <label for="menu_location" class="form-label">Localização no menu</label>
            <select id="menu_location" class="form-select" name="menu_location">
            <?php foreach (['none'=>'Não apresentar no menu','main'=>'Item principal do menu','submenu'=>'Submenu de um item existente'] as $key=>$label): ?>
                <option value="<?= $key ?>" <?= ($page['menu_location'] ?? ($editing ? 'main' : 'none')) === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?></select>
            <div id="menu-parent-field"><label for="menu_parent" class="form-label mt-2">Menu-pai</label>
            <select id="menu_parent" name="menu_parent" class="form-select"><option value="">Selecionar…</option>
            <?php foreach ($menuParents as $item): ?><option value="<?= htmlspecialchars($item['key']) ?>" <?= ($page['menu_parent'] ?? '') === $item['key'] ? 'selected' : '' ?>><?= htmlspecialchars($item['title']) ?></option><?php endforeach; ?>
            </select></div>
            <div id="menu-label-fields" class="row g-2 mt-1"><div class="col-md-8"><label for="menu_label" class="form-label">Texto do menu</label><input id="menu_label" name="menu_label" class="form-control" value="<?= htmlspecialchars($page['menu_label'] ?? '') ?>" placeholder="Título da página"></div>
            <div class="col-md-4"><label for="menu_order" class="form-label">Ordem no menu</label><input id="menu_order" type="number" name="menu_order" class="form-control" value="<?= htmlspecialchars((string)($page['menu_order'] ?? '')) ?>" placeholder="Ordem atual"></div></div>
            <p id="menu-preview" class="small mt-3 mb-0" aria-live="polite"></p>
        </fieldset></div>
        <div class="col-12"><details class="card card-body"><summary class="h5">SEO e partilha</summary><p class="small text-muted mt-2">As tags SEO, a indexação e o Schema aplicam-se ao modo Página própria. Os setores partilham o documento e o SEO da homepage.</p>
            <label for="meta_title" class="form-label mt-3">Meta title</label><input id="meta_title" class="form-control" name="meta_title" value="<?= htmlspecialchars($page['meta_title'] ?? '') ?>"><p id="title-count" class="small text-muted"></p>
            <label for="meta_description" class="form-label">Meta description</label><textarea id="meta_description" class="form-control" name="meta_description" rows="3"><?= htmlspecialchars($page['meta_description'] ?? '') ?></textarea><p id="description-count" class="small text-muted"></p>
            <label for="canonical_url" class="form-label">URL canónica (opcional)</label><input type="url" id="canonical_url" class="form-control" name="canonical_url" value="<?= htmlspecialchars($page['canonical_url'] ?? '') ?>"><p class="small text-muted">Em branco: usa automaticamente o URL público real. Setores da home usam a homepage.</p>
            <label for="og_image_url" class="form-label">Imagem Open Graph (URL)</label><input type="url" id="og_image_url" class="form-control" name="og_image_url" value="<?= htmlspecialchars($page['og_image_url'] ?? '') ?>"><p class="small text-muted">Em branco: usa a capa ou a imagem social do website.</p>
            <label class="form-check-label"><input class="form-check-input me-2" type="checkbox" name="allow_indexing" <?= (int)($page['allow_indexing'] ?? 1) === 1 ? 'checked' : '' ?>>Permitir indexação nos motores de pesquisa</label>
            <label for="schema_type" class="form-label mt-3">Dados estruturados</label><select id="schema_type" name="schema_type" class="form-select"><option value="WebPage">Página geral</option><option value="Service" <?= ($page['schema_type'] ?? '') === 'Service' ? 'selected' : '' ?>>Serviço comercial</option></select>
            <label for="service_area" class="form-label mt-2">Área geográfica do serviço (opcional)</label><input id="service_area" name="service_area" class="form-control" value="<?= htmlspecialchars($page['service_area'] ?? '') ?>">
            <div class="border rounded p-3 mt-3"><p class="small">Pré-visualização indicativa no Google</p><div id="seo-url" class="small"></div><div id="seo-title" class="text-primary fs-5"></div><div id="seo-description"></div></div>
        </details></div>
        <div class="col-12"><details class="card card-body"><summary class="h5">Conteúdos relacionados</summary><p class="small text-muted mt-2">Seleciona conteúdos publicados. As ligações são ocultadas se deixarem de estar disponíveis.</p>
        <?php $selectedRelated = json_decode((string)($page['related_json'] ?? '[]'), true) ?: []; ?>
        <?php foreach ($relatedOptions as $key=>$label): ?><label class="form-check-label mb-2"><input class="form-check-input me-2" type="checkbox" name="related[]" value="<?= htmlspecialchars($key) ?>" <?= in_array($key, $selectedRelated, true) ? 'checked' : '' ?>><?= htmlspecialchars($label) ?></label><?php endforeach; ?>
        </details></div>
        <div class="col-md-12 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_published" id="is_published" <?= (!$page || (int)($page['is_published'] ?? 0) === 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_published">Publicado</label>
            </div>
        </div>
    </div>

    <button class="btn btn-dark mt-3">Guardar página</button>
</form>

<script>
(() => {
  const typeSelect = document.querySelector('select[name="section_type"]');
  const blocks = document.querySelectorAll('.js-config-block');
  if (!typeSelect || !blocks.length) return;

  const syncBlocks = () => {
    const selected = typeSelect.value;
    blocks.forEach((block) => {
      const allowed = (block.dataset.types || '').split(',').map((v) => v.trim()).filter(Boolean);
      const show = allowed.includes(selected);
      block.style.display = show ? '' : 'none';
    });
  };

  typeSelect.addEventListener('change', syncBlocks);
  syncBlocks();
})();
</script>

<script>
(() => {
  const field = name => document.querySelector('[name="' + name + '"]');
  const originalSlug = field('slug').value;
  const published = <?= !empty($page['is_published']) && $editing ? 'true' : 'false' ?>;
  let timer, revision = 0;
  function preview() {
    const title = field('title').value;
    const slug = field('slug').value;
    const canonical = field('display_mode').value === 'page' ? 'https://chorarderir.com/' + slug : 'https://chorarderir.com/';
    field('canonical_url').placeholder = canonical;
    document.getElementById('seo-url').textContent = field('canonical_url').value || canonical;
    document.getElementById('seo-title').textContent = field('meta_title').value || title + ' | Chorar de Rir';
    const contentText = new DOMParser().parseFromString(field('content').value, 'text/html').body.textContent || '';
    const description = (field('excerpt').value || contentText).replace(/\s+/g, ' ').trim();
    document.getElementById('seo-description').textContent = field('meta_description').value || (Array.from(description).length > 158 ? Array.from(description).slice(0, 157).join('') + '…' : description);
    document.getElementById('title-count').textContent = Array.from(field('meta_title').value).length + ' caracteres — recomendação: 50–60';
    document.getElementById('description-count').textContent = Array.from(field('meta_description').value).length + ' caracteres — recomendação: 140–160';
    const location = field('menu_location').value;
    document.getElementById('menu-parent-field').hidden = location !== 'submenu';
    field('menu_parent').required = location === 'submenu';
    document.getElementById('menu-label-fields').hidden = location === 'none';
    const parent = field('menu_parent').selectedOptions[0]?.textContent || '';
    document.getElementById('menu-preview').textContent = location === 'none' ? 'Não aparece no menu. O URL público mantém-se acessível.' : (location === 'submenu' ? parent + ' → ' : 'Menu principal → ') + (field('menu_label').value || title) + ' · ' + (field('display_mode').value === 'page' ? '/' + slug : '/#' + slug);
    document.getElementById('slug-warning').hidden = !published || slug === originalSlug;
  }
  document.querySelector('form').addEventListener('input', preview);
  document.querySelector('form').addEventListener('change', preview);
  field('slug').addEventListener('input', () => {
    clearTimeout(timer);
    const current = ++revision;
    timer = setTimeout(async () => {
      try {
        const url = new URL(window.location.href);
        url.searchParams.set('controller', 'publicpage'); url.searchParams.set('action', 'checkSlug');
        url.searchParams.set('slug', field('slug').value);
        const data = await (await fetch(url)).json();
        if (current === revision) document.getElementById('slug-status').textContent = data.available ? 'Slug disponível: ' + data.slug : 'Slug duplicado ou reservado.';
      } catch (_) { document.getElementById('slug-status').textContent = 'A disponibilidade será verificada ao guardar.'; }
    }, 300);
  });
  preview();
})();
</script>
