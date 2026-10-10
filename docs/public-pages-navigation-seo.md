# Páginas Públicas: navegação e SEO

A implementação mantém PHP puro, SQLite, Bootstrap 5, o editor HTML, os campos existentes e o mecanismo de exportação do website. A posição no menu é independente do slug. Não são criadas páginas de exemplo na base de dados da aplicação.

## Ficheiros envolvidos

| Ficheiro | Alteração |
| --- | --- |
| `app/models/PublicPage.php` | Persistência dos novos campos, disponibilidade de slugs e histórico de redirecionamentos transacional. |
| `app/controllers/PublicPageController.php` | Opções do menu real, conteúdos publicados, validação, verificação de slug e proteção CSRF na gravação. |
| `app/views/public_pages/form.php` | Navegação, SEO expansível, conteúdos relacionados, contadores, pré-visualizações e aviso de alteração de slug. |
| `app/controllers/PublicSiteController.php` | Integração no menu atual, submenus acessíveis, metadados, JSON-LD, ligações relacionadas, 301, 404 e sitemap. |
| `app/config/database.php` | Aplicação automática da migração ao abrir o painel. |
| `app/helpers/public_pages.php` | Regras partilhadas de navegação, URLs, ligações e HTML seguro; incorporadas no website exportado. |
| `app/helpers/public_page_migration.php` | Migração SQLite idempotente sem atualização dos conteúdos existentes. |
| `database/migrations/2026_10_10_public_pages_navigation_seo.php` | Execução opcional da mesma migração pela linha de comandos. |
| `tests/PublicPagesNavigationSeoTest.php` | 96 verificações de integração, incluindo execução do PHP e XML gerados. |
| `docs/public-pages-navigation-seo.md` | Instruções de aplicação e verificação. |

## Base de dados

São acrescentados 13 campos a `public_pages`:

| Campo | Valor inicial nas páginas existentes |
| --- | --- |
| `menu_location` | `main` — mantém a presença atual no menu |
| `menu_parent` | Texto vazio; referência `page:ID` ou `static:identificador` |
| `menu_label` | Texto vazio; usa o título da página |
| `menu_order` | `NULL` — mantém a ordem anterior |
| `meta_title`, `meta_description` | Texto vazio; utiliza os valores de recurso |
| `canonical_url`, `og_image_url` | Texto vazio; utiliza o URL público e a capa/imagem social |
| `allow_indexing` | `1` |
| `schema_type` | `WebPage` |
| `service_area` | Texto vazio |
| `related_json` | `[]` — referências a IDs, sem duplicar conteúdos |
| `updated_at` | `NULL`; preenchido com a data real na próxima gravação |

A nova tabela `public_page_redirects` contém `old_slug` (chave primária), `page_id` e `old_mode`. Os destinos são resolvidos pelo ID da página; várias alterações de slug redirecionam diretamente para o URL atual. As referências desaparecem ao eliminar a página. Nenhuma tabela de eventos, reservas, CRM ou comediantes é modificada por esta funcionalidade.

## Aplicar a atualização

1. Colocar os ficheiros do pacote nas respetivas pastas do projeto, incluindo ambos os novos helpers.
2. Abrir o painel. A atualização do esquema é automática e preserva os dados existentes. Não executar `install.php`, que pertence ao fluxo de reinstalação.
3. Opcionalmente, executar a migração antes de abrir o painel:

   ```sh
   SQLITE_PATH=/caminho/da/base.sqlite php database/migrations/2026_10_10_public_pages_navigation_seo.php
   ```

   Pode repetir-se o comando. Sem `SQLITE_PATH`, utiliza `database/eventplanner.sqlite`. Não cria uma base de dados nova quando o ficheiro indicado não existe.

4. Em **Publicar website**, utilizar a pasta de destino habitual e republicar uma vez. Isto atualiza o `index.php` e o gerador do sitemap exportados; mantém `/sitemap.xml` e as regras de rotas atuais.
5. Depois desta exportação, a criação e edição das páginas são lidas da mesma base de dados pelo website, como anteriormente. Não é necessário editar manualmente o código do site para cada página.

## Criar e verificar uma página

1. Abrir **Páginas Públicas → Nova página** e escolher **Página própria** para obter um URL como `/contratar-humorista`.
2. Definir título, slug, resumo e conteúdo nos campos existentes.
3. Em **Configuração de navegação**, escolher nenhuma entrada, item principal ou submenu. Para submenu, selecionar **Serviços**, definir o texto e a ordem. Também estão disponíveis os itens fixos atualmente visíveis, como Blog e Corporativos.
4. Em **SEO e partilha**, definir os metadados opcionais, a imagem social por URL, a indexação e, quando aplicável, `Service` e a área geográfica real. Os contadores são recomendações, sem corte dos textos personalizados.
5. Selecionar conteúdos relacionados publicados e guardar com **Publicado** ativo.
6. Abrir o URL público e verificar o menu no computador e no telemóvel. O botão de submenu abre/fecha; o link do pai mantém o seu destino. Verificar Tab, Enter, setas, Escape e foco visível.
7. Consultar o código-fonte: `title`, descrição, canónica, Open Graph, Twitter e JSON-LD. Em `Service`, a entidade prestadora referencia a Organization existente, sem preços ou avaliações inventados.
8. Consultar `/sitemap.xml`. A página publicada e indexável aparece uma vez, com `lastmod` real quando disponível. Uma canónica externa exclui o URL local; uma canónica própria com barra final é incluída com essa grafia.
9. Alterar apenas a localização no menu e confirmar que o URL permanece igual. Despublicar e confirmar que desaparece da navegação, do sitemap e dos conteúdos relacionados; o acesso direto devolve 404.
10. Alterar o slug de uma página publicada e verificar o aviso no editor. O URL antigo deve responder com HTTP 301 para o novo, incluindo após várias alterações consecutivas.

## Particularidades da arquitetura preservada

- **Setores da home** continuam a usar `/#slug` e a partilhar o documento, a canónica e o SEO da homepage. Para SEO e indexação individuais, selecionar **Página própria**. Os setores não recebem URLs inventados no sitemap.
- Fragmentos `#slug` não são enviados ao servidor. Quando um setor muda de slug, o navegador resolve o fragmento antigo para o atual; o 301 HTTP aplica-se aos URLs de páginas próprias.
- Submenus têm um único nível. Se o pai deixar de estar visível no menu principal, os filhos deixam de aparecer na navegação; as páginas publicadas mantêm o acesso direto.
- A área geográfica do serviço é opcional e introduzida pelo administrador. Os dados globais da marca são reutilizados. Nas páginas próprias, omite-se o bloco genérico antigo de LocalBusiness para evitar atribuir preços ou áreas não definidos ao serviço.
- O menu mantém a sequência anterior enquanto não se configurar uma ordem própria. As entradas dinâmicas não duplicam os destinos fixos.
- O HTML simples conserva as tags aceites pelo editor. A renderização remove atributos executáveis, estilos embutidos e esquemas perigosos nas ligações; preserva ligações relativas, HTTP(S), fragmentos e `mailto:` seguros.

## Validação realizada

No ambiente local, com PHP 8.4.24 e SQLite:

- As 11 suites PHP passaram, incluindo as regressões existentes de reservas, admissões, eventos/séries, datas, base de dados, reescrita de URLs e compatibilidade de sintaxe com o alojamento.
- O novo teste executou 96 verificações: migração repetida, preservação dos campos antigos, menu principal/submenus, pais fixos, publicação, URLs, duplicados, metadados sem truncagem, HTML/JSON-LD seguro, sitemap, canónicas, relacionados e histórico de slugs.
- O navegador Chromium executou 37 verificações de rato, teclado, foco, toque, tablet a 768 px, telemóvel a 390 px, criação/edição pelo painel, pré-visualizações, validação, HTTP 301/404 e proteção CSRF.
- A migração CLI foi executada duas vezes com sucesso.
- A análise de sintaxe PHP e `git diff --check` passaram.

Os testes usaram bases de dados temporárias. Não foi publicada uma atualização em produção. A compatibilidade com o alojamento foi verificada pelos testes estáticos existentes; não foi executada uma instância da versão PHP de produção.

Para repetir os testes PHP:

```sh
php tests/PublicPagesNavigationSeoTest.php
for test in tests/*Test.php; do php "$test" || exit 1; done
```
