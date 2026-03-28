# StandUp Event Planner

Aplicação web em **PHP puro + MySQL + Bootstrap 5** para gestão de eventos de stand-up comedy.

## Funcionalidades
- Autenticação com sessões (`admin` e `comedian`)
- Dashboard admin com métricas e filtros por data
- CRUD completo de comediantes, clientes e eventos
- Gestão de lineup (host/opener/headliner + cachet)
- Área privada para comediante (vê apenas os seus eventos)
- Pesquisa simples em tabelas e confirmação antes de apagar

## Instalação rápida
1. Crie a base de dados e dados iniciais:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
2. Ajuste credenciais em `app/config/database.php` se necessário.
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
