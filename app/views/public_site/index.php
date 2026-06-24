<h2 class="mb-4">Publicar website público</h2>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <p class="text-muted">Esta ação gera o website público (ex.: <code>chorarderir.com</code>) numa pasta fora do EventPlanner, com visual moderno, páginas institucionais, formulário de reservas e newsletter RGPD.</p>

        <form method="post" action="<?= BASE_URL ?>?controller=publicsite&action=publish" class="row g-3">
            <div class="col-12">
                <label class="form-label">Pasta de destino</label>
                <input type="text" name="target_path" class="form-control" value="<?= htmlspecialchars($defaultPath) ?>" required>
                <div class="form-text">Podes indicar uma pasta absoluta, por exemplo <code>/var/www/chorarderir.com</code>.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Texto pequeno (home)</label>
                <input type="text" name="home_tagline" class="form-control" value="<?= htmlspecialchars($homeTagline ?? '') ?>" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Título principal (home)</label>
                <input type="text" name="home_title" class="form-control" value="<?= htmlspecialchars($homeTitle ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Descrição (home)</label>
                <textarea name="home_description" class="form-control" rows="2" required><?= htmlspecialchars($homeDescription ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Imagem de fundo do topo (URL/caminho)</label>
                <input type="text" name="home_background_url" class="form-control" value="<?= htmlspecialchars($homeBackgroundUrl ?? '') ?>" placeholder="https://... ou /imagens/hero.jpg">
                <div class="form-text">Aceita URL absoluta ou caminho relativo para definir a imagem de fundo do header/hero.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Texto de consentimento RGPD (newsletter)</label>
                <textarea name="newsletter_consent_text" class="form-control" rows="2" required><?= htmlspecialchars($newsletterConsentText ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <div class="card border-0 bg-light mt-2">
                    <div class="card-body">
                        <h5 class="mb-3">SEO e ordem do menu</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Meta descrição do website</label>
                                <textarea name="site_meta_description" class="form-control" rows="2" maxlength="160" placeholder="Resumo claro com serviços, localização e proposta de valor"><?= htmlspecialchars($siteMetaDescription ?? '') ?></textarea>
                                <div class="form-text">Usada na tag <code>meta description</code>. Recomenda-se 140–160 caracteres.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">URL canónico / domínio público</label>
                                <input type="url" name="site_canonical_url" class="form-control" value="<?= htmlspecialchars($siteCanonicalUrl ?? '') ?>" placeholder="https://chorarderir.com/">
                                <div class="form-text">Usado no canonical, robots.txt e sitemap.xml.</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Ordem Início</label>
                                <input type="number" name="home_menu_order" class="form-control" value="<?= (int)($homeMenuOrder ?? 0) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Ordem Agenda</label>
                                <input type="number" name="agenda_menu_order" class="form-control" value="<?= (int)($agendaMenuOrder ?? 40) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Ordem Parceiros</label>
                                <input type="number" name="partners_menu_order" class="form-control" value="<?= (int)($partnersMenuOrder ?? 90) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">reCAPTCHA chave de site</label>
                <input type="text" name="recaptcha_site_key" class="form-control" value="<?= htmlspecialchars($recaptchaSiteKey ?? '') ?>" placeholder="6Lc...">
                <div class="form-text">Chave pública usada no HTML dos formulários públicos.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">reCAPTCHA chave secreta</label>
                <input type="text" name="recaptcha_secret_key" class="form-control" value="<?= htmlspecialchars($recaptchaSecretKey ?? '') ?>" placeholder="6Lc...">
                <div class="form-text">Chave privada usada para validar no servidor (reserve/contact/subscribe).</div>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Guardar e publicar website</button>
                <p class="text-muted small mt-2 mb-0">Sempre que alterares páginas/textos, publica novamente para atualizar o site externo.</p>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5>Páginas prontas para publicação</h5>
        <p class="text-muted mb-3">As páginas criadas serão incluídas no menu público após voltares a publicar o website.</p>
        <ul class="mb-0">
            <?php foreach ($pages as $page): ?>
                <li>
                    <strong><?= htmlspecialchars($page['title']) ?></strong>
                    (<code><?= htmlspecialchars($page['slug']) ?></code>)
                    - <?= (($page['display_mode'] ?? 'section') === 'page') ? 'Página própria' : 'Setor da home' ?>
                    - tipo <?= htmlspecialchars((string)($page['section_type'] ?? 'default')) ?>/<?= htmlspecialchars((string)($page['section_style'] ?? 'card')) ?>
                    - <?= (int)$page['is_published'] === 1 ? 'Publicado' : 'Rascunho' ?>
                </li>
            <?php endforeach; ?>
            <?php if (count($pages) === 0): ?>
                <li class="text-muted">Ainda não existem páginas públicas criadas.</li>
            <?php endif; ?>
        </ul>
        <a class="btn btn-outline-dark btn-sm mt-3" href="<?= BASE_URL ?>?controller=publicpage&action=index">Gerir páginas</a>
    </div>
</div>
