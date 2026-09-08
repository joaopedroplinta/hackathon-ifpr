# Guia do repositório

## Contexto e fonte de verdade

Este é o sistema de apoio ao 1º Hackathon do IFPR Campus Pinhais. É um monólito Laravel + Inertia: gerencia inscrições, equipes, submissões, avaliação, resultados, voto popular, check-in e certificados.

Antes de alterar escopo, arquitetura, dados ou fluxos críticos, leia `PLANO.md`. Ele é a fonte de verdade para decisões já tomadas, modelo de dados e contingência operacional; o Anexo A é obrigatório para mudanças em prazos, submissões, importação ou operação no dia do evento. Consulte também `docs/diagramas.md` quando a mudança atravessar domínios.

## Stack e arquitetura

- Laravel 12 / PHP 8.2+, PostgreSQL 17, Redis e Pest.
- Inertia v2 + React 19 + TypeScript + Tailwind CSS v4 / shadcn-ui.
- Não crie API REST: controllers respondem com `Inertia::render()` e as páginas recebem props tipadas.
- Controllers são separados por público em `Public`, `Participant`, `Judge`, `Organizer`, `Auth` e `Settings`; páginas React espelham essa divisão.
- Leitura simples fica no controller. Escritas que coordenam regras ou múltiplos modelos ficam em `app/Actions/<Domain>/`, em classe de propósito único com `handle()`.
- Não crie `app/Services`. Models permanecem diretamente em `app/Models`.

## Convenções de implementação

- Interface, rotas e mensagens para pessoas usuárias ficam em português; código, migrations, variáveis, comentários e commits ficam em inglês.
- Toda autorização deve passar por uma Policy (`authorize()` no controller ou middleware `can:`). Não espalhe verificações de papel no controller.
- Toda entrada mutável precisa de Form Request. Validação do front é apenas conveniência; a regra no Laravel é a fonte de verdade.
- Use `useForm` do Inertia em formulários, `<Link>` para navegação interna e props do controller em vez de `fetch`.
- Em TypeScript, não use `any`. Defina props em `resources/js/types/`; para dados de `useForm`, use `type`, não `interface`.
- Arquivos frontend usam nomes em minúsculas com hífen. Não edite `resources/js/components/ui/` diretamente; componha variações em `components/hackathon/`.
- Listas e formulários precisam de estados de vazio, carregamento e erro; textos são em português, UI mobile-first e acessível.

## Invariantes de domínio e segurança

- Jurados só consultam submissões atribuídas a eles; participantes só a própria equipe/submissão; resultados só aparecem depois da publicação, sempre com checagem no servidor.
- Prazos usam `now()` no servidor. Datas são UTC no banco e exibidas em `America/Sao_Paulo`.
- Notas e pesos são `decimal(5,2)`, nunca `float`. Status usam string com cast para enum PHP; todo enum tem `label()` em português.
- Tabelas de domínio carregam `event_id` (direta ou claramente via relação), queries são escopadas por edição e unicidades relevantes incluem `event_id`.
- Toda migration cria uma tabela por vez, tem `down()` real e não edita uma migration já aplicada. Avise antes de mudanças destrutivas de dados.
- Use `$fillable` explícito. Para uploads: allowlist de MIME/extensão, limite explícito, nome gerado pelo sistema e armazenamento privado, servido apenas por rota autorizada.
- Não leia ou edite `.env`; nunca registre credenciais. Não commite `vendor`, `node_modules` ou conteúdo de `storage/app`.

## Testes e verificações

Para uma feature, cubra Policy, Form Request, caminho feliz e ao menos um erro com Pest. Os testes de feature espelham a área do controller; unitários são para enums, `Support` e regras isoladas.

Execute o conjunto proporcional à alteração antes de entregar:

```bash
./vendor/bin/pest
./vendor/bin/pint --test
npm run lint:check
npm run format:check
npx tsc --noEmit
```

Para formatar PHP de propósito, use `./vendor/bin/pint`; para aplicar correção automática do frontend, use `npm run lint` e/ou `npm run format`. O CI também executa build do Vite e Pest contra PostgreSQL.

Evite `migrate:fresh`, `migrate:rollback` e `db:wipe` sem autorização explícita: podem apagar dados locais úteis. Para o ambiente completo: `docker compose up -d`, `php artisan migrate`, `npm run dev`, `php artisan serve` e `php artisan queue:work`.

## Referências locais

- `CLAUDE.md`: panorama, comandos e definição de pronto.
- `.claude/rules/{estrutura,database,security,frontend}.md`: regras detalhadas que devem ser seguidas em mudanças de código.
- `docs/backlog.md`: pendências acompanhadas.
- `deploy/`: instruções de produção, worker e agendador.
