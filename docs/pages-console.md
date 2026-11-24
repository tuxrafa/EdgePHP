# Pages Console

O Pages Console é a área administrativa de páginas do Author. Ele organiza o site em colunas, no espírito da Column View do AEM Sites, sem substituir o editor visual e sem transformar a lista compacta do painel lateral em colunas.

## Áreas

| Área | Rota | Função |
|---|---|---|
| Pages Console | `/author/pages` | Navegar, selecionar e, nas fases seguintes, gerenciar páginas |
| Page Editor | `/author/editor/{pageId}` | Canvas, Inspector, blocos e Preview EDS já existentes |
| Editor legado | `/author/` | O mesmo editor, para o fluxo atual e os testes E2E |

A lista do painel Pages continua no editor. O link **Abrir gerenciador de páginas** só troca de tela. A busca e a carga da árvore passam por `api.listPageChildren`; a lista compacta continua em `api.listPages` / `loadPages`.

Voltar ao console usa **Back to Pages**. A trilha de colunas e a seleção ficam em `sessionStorage` (`edgephp.pagesConsole.nav`). Se o editor tiver alterações não salvas, a saída pede confirmação.

## Hierarquia

A fonte de verdade do endereço público continua sendo `pages.path`. Não há um segundo campo `parentId`.

Um path `/blog/artigo-a` produz, na raiz, o nó `/blog`. Se não existir documento em `/blog`, o nó é uma **pasta virtual**. Se existir, o mesmo documento é uma **página** e, quando há paths mais profundos, `hasChildren` é verdadeiro. Não se cria outro documento para “ser a pasta”.

Pastas explícitas (documento de pasta sem HTML público) ficam para a fase de gerenciamento. O formato do nó já distingue `kind: folder` com `explicit: false` (virtual) de `kind: page` com `explicit: true`. Uma pasta explícita futura entra no mesmo endpoint, com `kind: folder` e `explicit: true`, sem duplicar a página que porventura ocupe o mesmo path.

Escopo atual da consulta: `siteId` (padrão `main`) e `locale` opcional. Vários sites e idiomas usam esses campos; a árvore não mistura locales quando `locale` é informado.

## Endpoint

```http
GET /api/v1/pages/children?parent=/blog&siteId=main&locale=pt-BR&limit=200&offset=0
```

`parent` vazio ou `/` é a raiz. A resposta traz só o nível seguinte, com metadados leves (sem revisão nem seções):

- `path`, `segment`, `title`
- `kind`: `page` ou `folder`
- `explicit`, `pageId`, `status`, `templateId`
- `hasChildren`, `updatedAt`

`meta.total` e `meta.hasMore` permitem carregar mais itens sem trazer o conteúdo editorial. A consulta projeta `path`, `title`, `status`, `templateId` e `updated_at`, filtrando por prefixo. O índice `pages_site_locale_path` cobre `siteId + locale + path`.

O conteúdo completo continua em `GET /api/v1/pages/{id}`.

## Fases

**Fase 1 (esta entrega).** Rota do console, toolbar com as ações que já funcionam (editar, abrir publicada, atualizar), Column View, pastas virtuais, seleção, abrir no editor e voltar preservando a trilha. Ações sem API (criar pasta, mover, copiar, excluir, renomear, list view, busca global) não aparecem na toolbar.

**Fase 2.** Pastas explícitas, criação com template, propriedades editáveis, rename, move com relatório de referências, copy e delete.

**Fase 3.** List view, busca paginada, seleção múltipla, publicação em lote e virtualização.

## O que não muda

`scripts/aem.js`, os blocos em `blocks/**` e o renderer EDS. O console não publica HTML e não decora blocos. A página pública continua resolvida pelo path do documento `pages`.

O servidor embutido do PHP trata `public/author/index.html` como front controller do diretório. `public/index.php` devolve `SCRIPT_NAME` para `/index.php` antes do Laravel ler a rota, senão `/author/pages` chega como path `/pages` e cai no 404 público.
