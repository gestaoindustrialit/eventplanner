# Sitemap e Google Search Console

O website exportado mantém a geração dinâmica em `sitemap.php`. O `.htaccess` encaminha `/sitemap.xml` para esse gerador sem redirecionamento; ambos os endereços continuam disponíveis. O `robots.txt` permite o acesso público e anuncia `https://chorarderir.com/sitemap.xml`. Não se adicionou autenticação nem uma exceção baseada no user-agent.

## Correções no gerador

- Datas SQLite são convertidas para W3C (`2026-10-10T14:15:00+00:00`). Datas vazias ou inválidas são omitidas, sem inventar uma data de alteração.
- Os slugs dos artigos são preservados. Os eventos usam a mesma ordem e resolução de slugs repetidos do website exportado.
- São excluídos rascunhos, páginas noindex, canónicas externas, setores da homepage, eventos ocultos/passados, séries inativas e origens de redirecionamentos. Os URLs duplicados são eliminados. Páginas próprias que ocupam um slug estático são respeitadas.
- Uma resposta válida começa pela declaração XML, usa UTF-8 e devolve HTTP 200 com `Content-Type: application/xml; charset=UTF-8`.
- Os diagnósticos PHP ficam no log. Falhas na leitura/consulta da base de dados devolvem HTTP 503 com `Retry-After: 300`, em vez de um sitemap parcial com HTTP 200. Uma base inexistente não é criada pelo gerador.

## Instalação e verificação em produção

1. Integrar a correção e atualizar a aplicação.
2. Republicar em **Publicar website**, na pasta realmente servida por `chorarderir.com`. Isto atualiza `sitemap.php`, `.htaccess` e `robots.txt`. Confirmar a pasta configurada no alojamento; a mensagem do painel e o campo de destino podem indicar pastas diferentes.
3. Consultar ambas as rotas sem sessão autenticada. Por exemplo:

   ```sh
   curl -sS -D sitemap-headers.txt https://chorarderir.com/sitemap.xml -o sitemap.xml
   curl -sS -D sitemap-googlebot-headers.txt -A 'Googlebot/2.1 (+http://www.google.com/bot.html)' https://chorarderir.com/sitemap.xml -o sitemap-googlebot.xml
   curl -sS -D sitemap-php-headers.txt https://chorarderir.com/sitemap.php -o sitemap-php.xml
   curl -sS https://chorarderir.com/robots.txt
   ```

   Esperado: HTTP 200, XML UTF-8, sem login/challenge HTML, sem avisos ou espaços antes de `<?xml`, e conteúdo equivalente nas duas rotas. Verificar também o `robots.txt` efetivamente servido, incluindo regras de CDN e do diretório pai.
4. Se houver HTTP 503, consultar o log PHP: `Sitemap generation failed:`. Verificar a existência, as permissões e o caminho da base de dados exportada. Se houver HTTP 403, autenticação, challenge ou bloqueio, consultar as regras do alojamento, diretórios pai, WAF/CDN e logs de acesso. Essas regras não são controladas pelo repositório.
5. Submeter `https://chorarderir.com/sitemap.xml` no Search Console. Confirmar a data da última leitura e acompanhar os pedidos reais do Google nos logs do servidor. Um pedido local com user-agent Googlebot verifica o tratamento desse cabeçalho, mas não comprova o acesso a partir da infraestrutura do Google.
6. Rever os URLs do sitemap servido em produção: HTTP 200, canónica igual ao URL e ausência de `noindex` no HTML e em `X-Robots-Tag`. Os testes locais não verificam os dados, ficheiros físicos, permissões ou regras de produção.

## Validação realizada

- 14 suites PHP passaram, incluindo `SitemapSearchConsoleTest.php` com 34 verificações do XML gerado e filtros de publicação, datas, slugs repetidos, redirecionamentos, erro de base de dados e caminho com apóstrofo.
- Apache 2.4.68 com PHP 8.4 via FastCGI passou 83 verificações HTTP numa base temporária: as duas rotas, utilizador anónimo, user-agent Googlebot, HEAD, tipo de conteúdo, XML sem prefixo, `robots.txt`, equivalência dos documentos e tratamento HTTP 503.
- Todos os 31 URLs desse sitemap de teste devolveram HTTP 200, com canónica coincidente e sem noindex.
- Os testes estáticos de compatibilidade PHP do alojamento e `git diff --check` passaram. Não foi executada a versão PHP de produção.

O acesso público a `chorarderir.com` foi bloqueado pelo proxy do ambiente de desenvolvimento (CONNECT 403). Por isso, não foi possível determinar a causa específica do erro no Search Console nem confirmar o HTTP, o firewall ou os URLs atuais em produção. As correções tratam defeitos identificados no gerador; a validação de produção acima continua necessária.
