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
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="site_banner_enabled" name="site_banner_enabled" value="1" <?= ($siteBannerEnabled ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="site_banner_enabled">Mostrar banner no website público</label>
                </div>
                <div class="form-text">Útil para parceiros, patrocínios ou uma chamada rápida para ver eventos.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Texto do banner</label>
                <input type="text" name="site_banner_text" class="form-control" value="<?= htmlspecialchars($siteBannerText ?? '') ?>" placeholder="Ex.: Próximos espetáculos já disponíveis">
            </div>
            <div class="col-md-6">
                <label class="form-label">Texto do botão do banner</label>
                <input type="text" name="site_banner_button_text" class="form-control" value="<?= htmlspecialchars($siteBannerButtonText ?? 'Ver eventos') ?>" placeholder="Ver eventos">
            </div>
            <div class="col-md-6">
                <label class="form-label">Link do banner</label>
                <input type="text" name="site_banner_url" class="form-control" value="<?= htmlspecialchars($siteBannerUrl ?? '/#agenda') ?>" placeholder="/#agenda ou https://...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Imagem do banner (URL/caminho)</label>
                <input type="text" name="site_banner_image_url" class="form-control" value="<?= htmlspecialchars($siteBannerImageUrl ?? '') ?>" placeholder="https://... ou /imagens/parceiro.png">
            </div>
            <?php
                $corporateDefaults = [
                    'enabled' => true,
                    'title' => 'Eventos Corporativos de Humor',
                    'description' => 'Humor para empresas, convenções, festas de equipa e ativações internas com linguagem adaptada à marca.',
                    'image_url' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1400&q=80',
                    'heading' => 'Eventos corporativos que aproximam equipas e marcas',
                    'content' => 'Criamos momentos de humor para convenções, jantares de empresa, kick-offs, festas de equipa, ativações internas e apresentações com anfitrião. A proposta inclui curadoria de humoristas, alinhamento do tom com a marca, logística e acompanhamento de produção.',
                    'form_title' => 'Conta-nos o briefing do teu evento corporativo',
                    'form_intro' => 'Partilha os detalhes essenciais para receberes uma proposta ajustada ao objetivo, público e contexto da tua empresa.',
                    'contact_email_to' => 'booking@chorarderir.com',
                ];
                $corporate = is_array($corporateEventsPage ?? null) ? array_merge($corporateDefaults, $corporateEventsPage) : $corporateDefaults;
            ?>
            <div class="col-12">
                <div class="card border-primary-subtle bg-light mt-2" id="eventos-corporativos">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Página /eventos-corporativos</h5>
                                <p class="text-muted mb-0">Edita a landing page de vendas para empresas e controla se fica visível no website público.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="corporate_events_enabled" name="corporate_events_enabled" value="1" <?= !empty($corporate['enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="corporate_events_enabled">Página visível</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Título SEO/H1</label>
                                <input type="text" name="corporate_events_title" class="form-control" value="<?= htmlspecialchars((string)$corporate['title']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email para pedidos de proposta</label>
                                <input type="email" name="corporate_events_contact_email_to" class="form-control" value="<?= htmlspecialchars((string)$corporate['contact_email_to']) ?>" placeholder="booking@chorarderir.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descrição SEO</label>
                                <textarea name="corporate_events_description" class="form-control" rows="2" required><?= htmlspecialchars((string)$corporate['description']) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Imagem da página (URL)</label>
                                <input type="text" name="corporate_events_image_url" class="form-control" value="<?= htmlspecialchars((string)$corporate['image_url']) ?>" placeholder="https://...">
                                <div class="form-text">Usada no cartão visual da página e nas meta tags de partilha (OG image).</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Título do bloco comercial</label>
                                <input type="text" name="corporate_events_heading" class="form-control" value="<?= htmlspecialchars((string)$corporate['heading']) ?>" required>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Texto introdutório/comercial</label>
                                <textarea name="corporate_events_content" class="form-control" rows="3" required><?= htmlspecialchars((string)$corporate['content']) ?></textarea>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Título do formulário</label>
                                <input type="text" name="corporate_events_form_title" class="form-control" value="<?= htmlspecialchars((string)$corporate['form_title']) ?>" required>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Texto de apoio do formulário</label>
                                <textarea name="corporate_events_form_intro" class="form-control" rows="2" required><?= htmlspecialchars((string)$corporate['form_intro']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Texto de consentimento RGPD (newsletter)</label>
                <textarea name="newsletter_consent_text" class="form-control" rows="2" required><?= htmlspecialchars($newsletterConsentText ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="recaptcha-mode">Tipo de reCAPTCHA</label>
                <select class="form-select" id="recaptcha-mode" name="recaptcha_mode">
                    <option value="v3" <?= ($captchaConfig['mode'] ?? 'v2') === 'v3' ? 'selected' : '' ?>>v3 — automático, sem desafios</option>
                    <option value="v2" <?= ($captchaConfig['mode'] ?? 'v2') === 'v2' ? 'selected' : '' ?>>v2 — caixa Não sou um robô</option>
                </select>
                <div class="form-text">Para filtragem silenciosa, escolhe v3 e indica um par de chaves v3 da Google. Ao mudar o tipo, preenche ambas as chaves.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="recaptcha-score">Confiança mínima para v3</label>
                <input class="form-control" id="recaptcha-score" type="number" name="recaptcha_min_score" min="0.1" max="0.9" step="0.1" value="<?= htmlspecialchars((string)($captchaConfig['score'] ?? 0.5)) ?>">
                <div class="form-text">Começa em 0.5. Um valor maior bloqueia mais pedidos e pode recusar visitantes legítimos.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="recaptcha-hosts">Domínios autorizados para v3</label>
                <input class="form-control" id="recaptcha-hosts" name="recaptcha_allowed_hosts" value="<?= htmlspecialchars(implode(', ', $captchaConfig['hosts'] ?? ['chorarderir.com', 'www.chorarderir.com'])) ?>" required>
                <div class="form-text">Domínios separados por vírgulas, sem https://. Autoriza-os também na consola Google.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">reCAPTCHA chave de site</label>
                <input type="text" name="recaptcha_site_key" class="form-control" value="<?= htmlspecialchars($recaptchaSiteKey ?? '') ?>" placeholder="6Lc...">
                <div class="form-text">Chave pública usada no HTML dos formulários públicos.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">reCAPTCHA chave secreta</label>
                <input type="password" name="recaptcha_secret_key" class="form-control" value="" autocomplete="new-password" placeholder="<?= !empty($recaptchaSecretConfigured) ? 'Guardada — deixa vazio para manter' : 'Indica a chave secreta' ?>">
                <div class="form-text"><?= !empty($recaptchaSecretConfigured) ? 'Existe uma chave secreta guardada. Deixa vazio para a manter ou cola uma nova para substituir.' : 'Ainda não existe chave secreta guardada.' ?> As duas chaves devem corresponder ao tipo selecionado. Guarda e publica novamente para aplicar.</div>
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
        <h5>Páginas e secções do menu público</h5>
        <p class="text-muted mb-3">Esta lista mostra tudo o que será incluído na navegação pública depois de voltares a publicar o website.</p>
        <ul class="mb-0">
            <li>
                <strong><?= htmlspecialchars((string)$corporate['title']) ?></strong>
                (<code>eventos-corporativos</code>)
                - Página própria
                - landing page comercial
                - <?= !empty($corporate['enabled']) ? 'Publicado' : 'Rascunho' ?>
                <a class="ms-1" href="#eventos-corporativos">Configurar</a>
            </li>
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
