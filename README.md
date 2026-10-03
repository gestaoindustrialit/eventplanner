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
- Séries de eventos com URL pública permanente e sessões/reservas independentes

## Séries de eventos

No painel, abra **Séries**, crie a série e defina nome, slug, descrição, capa e local habitual. Depois, em **Novo Evento**, selecione a série no campo “Série / Evento recorrente”. Cada sessão mantém data, lotação, publicação e reservas próprias; a página `/eventos/{slug-da-serie}` agrega automaticamente apenas as próximas sessões publicadas.

A atualização do esquema é automática e idempotente ao abrir a aplicação: cria `event_series`, adiciona `events.series_id` e um slug estável a `events`. Se existir o evento “Lustre Comedy Club”, a migração cria/associa a série sem alterar o ID do evento nem os `event_id` das reservas existentes.

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
