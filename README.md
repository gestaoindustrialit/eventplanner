# StandUp Event Planner

Aplicação web em **PHP puro + SQLite + Bootstrap 5** para gestão de eventos de stand-up comedy.

## Funcionalidades
- Autenticação com sessões (`admin` e `comedian`)
- Dashboard admin com métricas e filtros por data
- CRUD completo de comediantes, clientes e eventos
- Gestão de lineup (host/opener/headliner + cachet)
- Área privada para comediante (vê apenas os seus eventos)
- Pesquisa simples em tabelas e confirmação antes de apagar
- Website público exportável para pasta externa (ex.: `chorarderir.com`)
- Gestão de páginas públicas (conteúdos institucionais, menu e ordem de destaque)
- Reservas públicas por evento com gestão de estado no painel admin
- Séries de eventos com página pública permanente e sessões/reservas independentes

## Séries de eventos

No painel, abra **Séries** para criar a página permanente, definir slug, descrição,
capa e local sugerido. Depois, em **Eventos → Novo Evento**, selecione a série.
Cada sessão continua a ser um evento autónomo: data, lotação, estado e reservas
permanecem associados ao respetivo `events.id`.

As URLs públicas são:

- `/eventos/{slug-da-serie}` para a página permanente;
- `/eventos/{slug-da-serie}/{slug-da-sessao}` para uma sessão específica;
- `/eventos/{slug-do-evento}` para eventos independentes e links antigos.

Ao abrir a aplicação, a migração incremental cria `event_series` e acrescenta
`events.series_id`, `events.slug` e `events.public_price_label`, apenas quando não
existem. Se existir o evento legado com slug `lustre-comedy-club`, é criada de
forma idempotente a série correspondente e o evento é associado sem alterar
reservas, bilhetes ou qualquer outro dado operacional.

## Instalação rápida
1. Crie a base de dados e dados iniciais (CLI):
   ```bash
   ./scripts/setup_db.sh
   ```
   > Opcional: pode definir `SQLITE_PATH` para gravar a base de dados noutro local.
2. Alternativa web: abra `http://localhost:8000/install.php` para instalar/resetar a BD (útil para erro 500).
3. Inicie servidor local:
   ```bash
   php -S localhost:8000
   ```
4. Abra `http://localhost:8000/public/index.php`.

## Utilizadores de teste
- **Admin**: `admin@standup.local` / `admin123`
- **Comediante 1**: `ana@standup.local` / `comedy123`
- **Comediante 2**: `bruno@standup.local` / `comedy123`

## Estrutura
```text
/app
  /controllers
  /models
  /views
  /config
/public
/assets
  /css
  /js
/includes
/database
```
