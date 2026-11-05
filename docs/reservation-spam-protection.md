# Atualização para CAPTCHA v3 automático

Para instalação rápida, descarregar `downloads/reservas-captcha-v3.zip`: contém apenas os nove ficheiros necessários e `INSTALAR.txt`, com os passos. Não contém base de dados nem chaves privadas de produção.

Escolher reCAPTCHA v3 no painel, colar as duas chaves v3 do mesmo registo, usar pontuação mínima inicial 0.5 e indicar os domínios autorizados. Depois carregar em **Guardar e publicar website**. O modo guardado anteriormente mantém-se até esta alteração explícita, para não aplicar v3 a chaves v2 existentes. Trocar o tipo exige preencher ambas as chaves. v2 invisível pode pedir desafios; v3 não apresenta puzzles nem caixa de seleção. O selo da Google mantém-se visível.

O token é criado no envio e o servidor verifica sucesso, pontuação, ação, domínio e idade máxima de 120 segundos (tolerância de relógio futura de 30 segundos). Reservas, contactos e newsletter têm ações distintas. Indisponibilidade da Google, token ausente ou resposta inválida não aprovam pedidos v3. A interface preserva os dados em falhas de carregamento/rede e permite tentar novamente, gerando novo token.

Testes específicos atuais: proteção de reservas 36, ações em grupo 24, gravação/publicação CAPTCHA 21 e validação v3 32: **113 verificações**. Chromium local verificou envio silencioso, inclusão do token, prevenção de duplo envio e recuperação após falha. A verificação real das chaves continua a exigir o teste no domínio publicado.

---

# Reservas: proteção contra spam, ações em grupo e CAPTCHA

A ação administrativa Eliminar não cria reservas. As novas entradas podem ser submissões ao `reserve.php` público que ficam visíveis quando a lista é atualizada. Confirmar a origem real nos registos HTTP do alojamento; o código e as imagens não comprovam uma infeção.

## Aplicar no alojamento

1. Durante o incidente, fechar as reservas do evento em Reservas → Configuração por evento. O handler já consulta este estado na base de dados.
2. Fazer backup da base de dados SQLite e dos ficheiros públicos publicados.
3. Descarregar o ZIP da versão indicada no pull request e extrair para uma pasta temporária. Substituir **apenas os nove ficheiros da lista abaixo**, mantendo os caminhos relativos à pasta `eventplanner` do alojamento. Não substituir a base de dados nem executar `install.php` ou `setup_db.sh`.
4. Em Publicar website, configurar as duas chaves válidas para o tipo selecionado (**v3** para verificação automática) no domínio. A antiga chave secreta predefinida estava no repositório: substituí-la na consola Google e guardar a nova apenas nas definições do alojamento. Esta alteração remove o valor predefinido do código; não elimina versões históricas nem roda a chave automaticamente.
5. Carregar em **Guardar e publicar website**. É obrigatório regenerar **index.php e reserve.php em conjunto**: atualizar só o painel não protege o handler já exportado. Limpar caches de página/CDN para não servir formulários antigos. Não usar cache partilhada nas páginas com os formulários e respeitar `Cache-Control: private, no-store`.
6. Confirmar a configuração PHP de sessões, incluindo uma pasta de sessões gravável. A sessão pública usa o cookie separado `cdr_reservations`.
7. Reabrir as reservas e verificar manualmente uma reserva legítima com o CAPTCHA real. Apagar a reserva de teste. Confirmar nos registos que os pedidos abusivos já não criam reservas.
8. Rever e eliminar apenas as reservas falsas identificadas. A eliminação passa a remover também os bilhetes associados e a libertar a lotação. Esta alteração não apaga dados em produção automaticamente. Bilhetes órfãos de eliminações anteriores precisam de revisão separada antes da limpeza.

## Ficheiros a substituir

- `app/controllers/PublicSiteController.php`
- `app/controllers/ReservationController.php`
- `app/models/Reservation.php`
- `app/models/SiteSetting.php`
- `app/helpers/reservation_security.php`
- `app/helpers/public_captcha.php` (novo)
- `app/helpers/recaptcha_v3.js` (novo)
- `app/views/public_site/index.php`
- `app/views/reservations/index.php`

O ZIP do GitHub contém o projeto completo; para esta atualização bastam os nove ficheiros acima. Os testes e este documento servem para revisão e não precisam de ser copiados para o alojamento. A tabela de limites de submissão é criada automaticamente; não é necessário resetar a base de dados.

## Ações em grupo

Em **Reservas**, marcar as caixas à esquerda, escolher **Confirmar**, **Cancelar** ou **Eliminar** e carregar em **Aplicar às selecionadas**. O cabeçalho seleciona até 500 reservas visíveis; o contador mostra o total. Filtrar por evento antes de selecionar ajuda a limitar a operação. Ao ocultar uma linha através da pesquisa, a seleção dessa linha é limpa.

A confirmação envia o mesmo email usado na confirmação individual, apenas às reservas que mudam para confirmadas. Repetir a confirmação não reenvia os emails. Falhas de email são apresentadas separadamente; o estado confirmado fica guardado. Cancelar preserva o histórico e impede a validação dos bilhetes. Eliminar remove as reservas e os respetivos bilhetes numa única transação. Todas as ações exigem confirmação no navegador, permissão de reservas, POST e token de sessão válido.

## Correção da gravação CAPTCHA

O código anterior guardava `''` se o campo secreto viesse vazio ou ausente no POST de publicação. Isso podia apagar uma chave que já estava configurada; não foi consultada a chave real em produção nem se comprovou que esta foi a causa concreta do incidente.

A gravação passa a preservar os valores quando os campos estão vazios/ausentes. O segredo não é devolvido no formulário HTML: aparece apenas o indicador de que existe uma chave guardada. Uma nova chave substitui a anterior; ao mudar a chave de site é obrigatória a chave secreta correspondente. As duas chaves são gravadas em transação, e a publicação usa exatamente esses valores. A sintaxe é validada; só um teste real após a publicação confirma se o par é aceite pela Google para este domínio.

## Comportamento

- Exige token da sessão obtido no formulário, com validade de duas horas e uso único. Recusa repetições e pedidos com o campo de armadilha preenchido.
- Exige validação reCAPTCHA no servidor. Chave secreta ausente, resposta ausente, falha de rede ou resposta negativa impedem a reserva.
- Valida nome, email, telefone opcional, notas e número inteiro de bilhetes no servidor. Permite **1–20 bilhetes por pedido**; pedidos maiores devem ser tratados pela organização. O painel administrativo mantém a edição de quantidades.
- Aceita até cinco tentativas por IP em dez minutos ou por email em uma hora (contagem combinada). O registo não depende das reservas nem da sessão do navegador, pelo que apagar reservas ou trocar cookies não repõe o limite. Os identificadores são hashes e os registos expiram após uma hora.
- Usa `REMOTE_ADDR`; não confia em cabeçalhos de encaminhamento enviados pelo cliente. Se houver proxy/CDN, configurar o servidor para expor o IP real apenas a partir dos proxies de confiança; caso contrário o limite pode agrupar visitantes no mesmo IP.
- Verifica lotação e grava reserva/bilhetes numa transação SQLite para impedir sobrelotação por pedidos concorrentes.

Bots com sessões, IPs distintos e CAPTCHA resolvido continuam a poder enviar pedidos. Analisar os registos e aplicar regras no alojamento/CDN se o ataque persistir.

## Verificação local

`php tests/ReservationSpamProtectionTest.php` testa os PHP exportados num servidor local, numa base de dados temporária, com resposta CAPTCHA simulada e email enviado apenas para `/bin/true`. Nenhum pedido é enviado ao site real ou à Google. O CAPTCHA real e a configuração do alojamento exigem a verificação do passo 7.

Também passaram `php tests/CaptchaSettingsTest.php` (15 verificações, incluindo publicação com campo secreto vazio) e `php tests/ReservationBulkActionsTest.php` (24 verificações, incluindo rollback e prevenção de emails repetidos). A proteção contra spam tem 31 verificações: **70 no total**. A seleção foi ainda verificada num Chromium local (selecionar todas, seleção parcial, limpar, envio do formulário e linhas ocultas).

Na suite existente, três testes de SEO não puderam terminar neste ambiente por faltarem as extensões PHP DOM/SimpleXML. A sintaxe dos ficheiros alterados e a compatibilidade com o PHP do alojamento passaram.
