# Plano de repaginada completa da interface — implementação pelo Claude

## 1. Objetivo e contrato de entrega

Concluir a repaginada de **todas as 49 páginas React existentes**, seus layouts
ativos, componentes de domínio, modais e estados de interação. O objetivo é
uma experiência consistente, clara e confortável em celular, tablet e desktop
para visitantes, participantes, jurados, organizadores e admins.

A tarefa está autorizada pelo usuário. Implementar o plano, validar o resultado
e entregar evidências para uma revisão posterior pelo Codex. Não encerrar
somente com sugestões, alterações na home ou troca global de classes.
Uma página que recebeu apenas cabeçalho ou sidebar novos ainda precisa de
revisão e adequação do próprio conteúdo.

Este documento é um plano, não um registro de conclusão. Marcar os itens
somente depois de implementar e verificar. Reconciliar o inventário com o
repositório se páginas forem adicionadas ou removidas durante o trabalho.

## 2. Ponto de partida real

O worktree já contém mudanças da primeira etapa. Preservá-las e evoluí-las;
não fazer reset, descartar arquivos ou sobrescrever trabalho concorrente.

**Conteúdo alterado diretamente, ainda sujeito à revisão visual:**

- `resources/js/pages/publico/inicio.tsx`
- `resources/js/pages/dashboard.tsx`
- `resources/js/pages/admin/index.tsx`
- `resources/js/pages/jurado/fila.tsx`

**Base compartilhada já alterada:**

- Tokens, foco global, skip link e painéis gráficos em `resources/css/app.css`.
- `components/hackathon/cabecalho-publico.tsx`, `rodape-publico.tsx` e
  `contador-evento.tsx`.
- `components/app-sidebar.tsx`, `app-sidebar-header.tsx`, `nav-main.tsx`.
- `layouts/app/app-sidebar-layout.tsx` e `layouts/auth/auth-split-layout.tsx`.
- Pesos da fonte em `resources/views/app.blade.php`.

Caminhos abreviados de components/layouts acima são relativos a `resources/js/`.
Login e cadastro herdaram o layout, mas os formulários não foram repaginados.
As outras 45 páginas não tiveram seu conteúdo atualizado nesta etapa.

A etapa anterior passou em TypeScript, ESLint, Prettier, Pint, build e
85 testes Pest selecionados (666 assertions). Isso **não** equivale à suíte
completa nem à revisão visual: a captura automática do navegador não concluiu.
Executar novas verificações sobre a implementação final.

## 3. Leitura e diagnóstico antes de implementar

1. Ler `AGENTS.md`, `CLAUDE.md`, `docs/interface.md` e as quatro regras em
   `.claude/rules/` (estrutura, database, security e frontend).
2. Ler `PLANO.md`: decisões travadas e seção 11; antes de mexer em submissão,
   prazo, importação e incidentes, ler também o Anexo A. A tabela histórica de
   cores não substitui os tokens atuais.
3. Mapear `routes/web.php`, `routes/auth.php`, `routes/settings.php`,
   controllers, Form Requests, Policies e tipos das páginas em cada lote.
4. Examinar `git diff` antes de editar. Registrar a base recebida para a review
   distinguir correções desta tarefa das mudanças anteriores.
5. Abrir as telas atuais com dados representativos e registrar problemas
   concretos de hierarquia, navegação, formulário, responsividade e feedback.
6. Se as skills `frontend-design` e `web-design-guidelines` estiverem disponíveis,
   usar a primeira para composição e a segunda para auditoria. Não presumir que
   a pesquisa anterior as instalou. Regras do projeto e a identidade existente
   prevalecem sobre preferências genéricas de skills.

## 4. Direção visual e sistema de componentes

Evoluir a direção de `docs/interface.md`: verde institucional, fundo discreto,
superfícies legíveis, Inter, hierarquia clara e poucos elementos decorativos.
Os painéis verdes editoriais funcionam em apresentação/autenticação; telas de
trabalho precisam privilegiar informação e ações. Preservar o símbolo próprio
do projeto; não introduzir o símbolo oficial do IFPR.

Antes de migrar páginas em massa, consolidar componentes reutilizáveis em
`resources/js/components/hackathon/`, conforme repetição real:

- Contêiner de página com largura por função: leitura, formulário ou operação.
- Cabeçalho com título, contexto, breadcrumb e ação principal opcional.
- Seção de formulário com título, instrução, campos e mensagens associadas.
- Estado vazio com causa e próxima ação válida.
- Status com texto e ícone quando necessário; cor nunca é a única informação.
- Barra de filtros/listagem, paginação e estado “nenhum resultado”.
- Resumo de erro e feedback de salvamento/envio.
- Confirmação acessível de ação sensível, compondo os dialogs existentes.

Não criar um componente universal cheio de flags. Não editar os primitives
gerados em `components/ui/`. Aplicar as composições também às quatro páginas
já modificadas para não manter dois sistemas visuais.

Padrões a fixar e documentar:

- Espaçamentos, bordas, raios, contraste de superfícies e tipografia.
- Uma ação principal por contexto; ações secundárias e destrutivas distinguíveis.
- Campos agrupados por tarefa; obrigatório/opcional explicado sem inventar regras.
- Alvos de toque de aproximadamente 44px nas ações frequentes de celular.
- Tabelas com rolagem interna quando necessária; a página não rola horizontalmente.
- Layouts de formulário não devem virar colunas estreitas por estética.
- Foco visível sem duplicação/confusão entre outline global e rings locais.
- Textos longos, URLs, códigos e nomes de arquivo não podem estourar o contêiner.
- Ícones de ação têm nome acessível; tooltips não substituem rótulos essenciais.
- Claro/escuro com contraste suficiente; animações respeitam movimento reduzido.
- Navegação com URL ativa correta em detalhes, filtros e histórico do navegador.
- Links de download, OAuth e destinos externos mantêm a semântica apropriada;
  usar Inertia Link para navegação interna compatível, não converter tudo cegamente.

## 5. Inventário obrigatório e critérios por página

Os caminhos abaixo são relativos a `resources/js/pages/`.
Cada checkbox inclui implementação, estados aplicáveis e validação visual.
Mesmo as páginas da primeira etapa começam pendentes desta auditoria completa.

### Lote A — Público (10)

- [ ] `publico/inicio.tsx`: revisar a composição já feita; evento ausente,
  inscrições fechadas/abertas, usuário inscrito, em andamento e encerrado.
  Conferir data real, CTA adequado e contador em 320px.
- [ ] `publico/agenda.tsx`: hierarquia por data/horário, tipo, local e trilha;
  filtros existentes, ausência de atividades e download ICS preservados.
- [ ] `publico/projetos.tsx`: vitrine escaneável com equipe e resumo; estados
  visitante, sem inscrição, voto permitido, voto registrado e votação fechada.
  Explicar a ação antes de votar e manter feedback sem permitir envio duplo.
- [ ] `publico/rubrica.tsx`: critérios, pesos e escalas fáceis de comparar;
  explicar como ler a rubrica sem alterar o cálculo.
- [ ] `publico/regulamento.tsx`: leitura confortável, seções e download
  localizável; tratar evento/regulamento ausentes.
- [ ] `publico/resultados.tsx`: separar resultado não publicado, classificação,
  destaques por trilha e prêmio popular; nunca sugerir publicação inexistente.
- [ ] `publico/edicoes.tsx`: identificação e navegação clara entre edições;
  tratar lista vazia e preservar escopo dos resultados.
- [ ] `publico/validar.tsx`: validade, titular e evento com destaque; certificado
  não encontrado não pode se confundir com comprovante válido.
- [ ] `publico/privacidade.tsx`: largura de leitura, títulos e navegação por
  seções; preservar integralmente obrigações, contatos e conteúdo vigente.
- [ ] `publico/cookies.tsx`: mesma linguagem das páginas institucionais;
  manter conteúdo correto e coerente com o aviso de cookies.

### Lote B — Autenticação e conta (9)

- [ ] `auth/login.tsx`: hierarquia Google/e-mail, retorno, recuperação de senha,
  processamento, credenciais inválidas e preenchimento por gerenciador de senhas.
- [ ] `auth/register.tsx`: campos e requisitos claros, erros próximos dos campos
  e acesso a quem já possui conta.
- [ ] `auth/forgot-password.tsx`: envio, confirmação e nova tentativa coerentes
  com a resposta do servidor.
- [ ] `auth/reset-password.tsx`: requisitos, confirmação, token inválido e sucesso.
- [ ] `auth/confirm-password.tsx`: explicar a confirmação e preservar o destino.
- [ ] `auth/verify-email.tsx`: ação principal de verificação/reenvio, feedback e saída.
- [ ] `settings/profile.tsx`: dados pessoais/institucionais, foto, e-mail,
  salvamento e exclusão claramente separados; confirmação da exclusão.
- [ ] `settings/password.tsx`: agrupamento, requisitos e feedback de atualização.
- [ ] `settings/appearance.tsx`: seleção acessível de claro/escuro/sistema,
  estado atual explícito e persistência correta.

Revisar também `layouts/settings/layout.tsx`: navegação responsiva com página
ativa e sem depender de acesso direto a `window` durante renderização.
Verificar layouts de autenticação alternativos antes de alterá-los; não
repaginar arquivos inativos como substituto de validar as telas ativas.

### Lote C — Participante (9)

- [ ] `dashboard.tsx`: revisar jornada e próxima ação da etapa anterior;
  permitir acesso adequado para papéis acumulados e para conta não verificada.
- [ ] `inscricao/criar.tsx`: agrupar campos pessoais/institucionais e tamanhos,
  explicar requisitos e preservar as janelas de inscrição do servidor.
- [ ] `equipe/sem-equipe.tsx`: escolher criar/entrar sem competir com ações
  indisponíveis; explicar bloqueios.
- [ ] `equipe/criar.tsx`: identidade da equipe, trilha e regras de tamanho
  compreensíveis; validação e processamento.
- [ ] `equipe/entrar.tsx`: entrada por código, código inválido/equipe cheia,
  envio e retorno ao contexto correto.
- [ ] `equipe/minha.tsx`: membros, liderança, situação, limites e convites;
  tratar falha de clipboard; confirmar remoção, saída e transferência.
  Não esconder ações legítimas de integrante nem expor ações de líder a todos.
- [ ] `submissao/minha.tsx`: distinguir rascunho salvo de envio confirmado;
  informações, links, arquivos, histórico e prazo organizados por importância.
  Preservar dados após validação; estado bloqueado com motivo; evitar que
  uma barra fixa cubra campos, erros ou teclado.
- [ ] `credencial/mostrar.tsx`: QR com contraste e área livre, identificação,
  instrução de uso; testar leitura real e não apenas aparência do código.
- [ ] `certificados/index.tsx`: identificar tipo/evento, disponibilidade,
  download e estados vazios/processamento existentes.

### Lote D — Jurado (2)

- [ ] `jurado/fila.tsx`: revisar a primeira etapa; progresso, pendências,
  avaliações enviadas e títulos longos; vazio e lista extensa.
- [ ] `jurado/avaliar.tsx`: projeto e critérios consultáveis sem perder a posição;
  nota zero distinta de campo vazio; escala/peso explícitos; comentário e erros
  por critério; salvando/salvo/falha apenas conforme respostas reais.
  Revisar corrida entre autosave e envio final, temporizadores e modo leitura.
  Preservar regras de reabertura e não recalcular a nota oficial no cliente.

### Lote E — Organização e administração (18)

- [ ] `admin/index.tsx`: revisar primeira etapa; pendências reais, prioridades,
  indicadores e atalhos úteis para operação.
- [ ] `admin/sem-evento.tsx`: orientar a criação inicial respeitando permissões.
- [ ] `admin/evento/criar.tsx`: estrutura de formulário por assunto e erros claros.
- [ ] `admin/evento/editar.tsx`: seções para dados, calendário/janelas, limites,
  regulamento e configuração de certificado; salvar sem perder contexto.
  Preservar conversões de fuso e esclarecer efeitos de mudança de fase/prazo.
- [ ] `admin/agenda/index.tsx`: publicação, horário, local, edição e exclusão
  reconhecíveis; lista vazia e itens longos.
- [ ] `admin/agenda/formulario.tsx`: criar/editar, tipo, datas, local e relações;
  campos condicionais e feedback.
- [ ] `admin/checkin/index.tsx`: busca manual e checkpoints fáceis de operar
  no celular; scanner, permissão negada, câmera indisponível e fallback manual.
- [ ] `admin/checkin/confirmar.tsx`: pessoa/checkpoint corretos antes de confirmar;
  retorno para a próxima leitura, presença já registrada e envio duplo.
- [ ] `admin/incidentes/index.tsx`: histórico legível, autor/motivo/horário e
  efeitos da extensão; confirmação informada de ações que afetam o evento.
- [ ] `admin/submissoes/index.tsx`: filtros, status, equipes, paginação e exportação;
  não confundir zero registros com zero resultados do filtro.
- [ ] `admin/submissoes/mostrar.tsx`: projeto, origem, arquivos, histórico e
  situação; ações sensíveis existentes com motivo e confirmação.
- [ ] `admin/submissoes/lancar.tsx`: fluxo de contingência rápido e legível;
  origem, evidência de horário e regularização claramente identificadas.
- [ ] `admin/rubrica/index.tsx`: rubrica ativa, listagem, criação e exclusão.
- [ ] `admin/rubrica/mostrar.tsx`: critérios, pesos e escalas comparáveis;
  edição e ativação sem alterar invariantes ou esconder impedimentos.
- [ ] `admin/jurados/index.tsx`: carga por jurado, atribuições, conflitos,
  distribuição, reatribuição e reabertura; evitar controles densos no celular.
- [ ] `admin/resultados/index.tsx`: distinguir recalcular de publicar;
  pendências bloqueadoras visíveis, confirmação e feedback auditável.
- [ ] `admin/certificados/index.tsx`: emissão, destinatário, tipo, carga horária,
  situação de geração e downloads existentes.
- [ ] `admin/usuarios/index.tsx`: papéis acumuláveis e efeitos da alteração;
  lista/filtros existentes, processamento e acesso exclusivo de admin.

### Lote F — Erros (1) e superfícies transversais

- [ ] `errors/erro.tsx`: revisar cada status efetivamente servido pelo backend;
  título, orientação e retorno válidos, sem depender de usuário/evento presentes.
- [ ] Modais e menus: abrir/fechar por teclado, foco inicial/restaurado, Escape,
  nomes acessíveis, rolagem e confirmação nas ações sensíveis.
- [ ] `aviso-cookies`, `flash-messages`, `app-toaster`, `input-error`,
  `delete-user`, `password-requirements` e controles de aparência.
- [ ] `avatar-upload`, `google-login-button`, `painel-convites`,
  `painel-arquivos`, `historico-envios`, `painel-logo-certificado`,
  `painel-regulamento`, `contador-prazo`, `contador-evento` e `leitor-qr`.
- [ ] Navegação do usuário, breadcrumbs, footer, sidebar aberta/recolhida e
  menu móvel; páginas com query string, detalhe e múltiplos papéis.
- [ ] Skip links devem levar ao conteúdo principal e mover o foco corretamente.
  Revisar o alvo vazio usado inicialmente no cabeçalho público.
- [ ] Revisar textos novos da home: campos opcionais (por exemplo, vídeo) não
  podem parecer obrigatórios por causa da descrição promocional.

PDFs de certificados e templates de e-mail não são páginas React. Incluí-los
na verificação de regressão (downloads, dados, links e legibilidade), preservando
documentos já emitidos, assinatura, validade e conteúdo institucional.
Recriar a arte desses documentos não é requisito para concluir as 49 telas.

## 6. Sequência de execução

1. **Base e amostra:** registrar estado atual, definir composições e adaptar uma
   página pública, um formulário longo e uma listagem operacional. Validar
   desktop/mobile e claro/escuro antes de replicar os padrões.
2. **Jornada do participante:** concluir lotes B e C, incluindo componentes de
   arquivos/convites e validação real dos formulários.
3. **Dia do evento:** concluir lote D e check-in, submissões, incidentes,
   jurados e resultados do lote E. Priorizar confiabilidade com uso no celular.
4. **Gestão restante e público:** concluir os demais itens dos lotes A e E.
5. **Fechamento:** lote F, revisão das quatro páginas já alteradas, cobertura
   completa e documentação final.

Entregar mudanças em lotes revisáveis por área, sem duplicar estilos por página.
Não confundir ordem de execução com permissão para abandonar lotes posteriores.

## 7. Invariantes e limites técnicos

- Laravel 12 + Inertia 2 + React 19 + TS + Tailwind 4. Não migrar para Next,
  não criar API REST, não adicionar biblioteca visual concorrente.
- Aproveitar props existentes. Se faltar informação essencial à UX, ajustar
  controller/tipos com mínimo escopo, respeitando Policies e com testes.
- Não introduzir dados fictícios, porcentagens inventadas ou confirmações de
  salvamento sem resposta. Distinguir rascunho, envio e publicação.
- Autorização e deadlines permanecem no servidor; nenhuma edição deve vazar
  informação de outro evento, equipe ou atribuição.
- Manter formulários controlados e validações no Laravel; preservar limites,
  notas decimais, conflitos, auditoria e upload privado.
- Não adicionar dependências sem necessidade concreta. Não instalar skills
  automaticamente só porque foram mencionadas como referência.
- Não ler/editar `.env`, executar migrações destrutivas ou enviar e-mails reais.
  Dados de navegação/testes devem ser isolados do banco de uso do usuário.
- Mudanças em comportamento de interação exigem testes pertinentes; mudanças
  puramente visuais exigem evidência visual, não testes que apenas procuram classes.
- Não alterar texto jurídico ou regras do regulamento para caber no layout.

## 8. Critérios de aceite e evidências

### Cobertura visual

Criar `docs/revisao-interface.md` durante a implementação com **uma linha por
página**, contendo: caminho/rota, papel, estados vistos, arquivos alterados,
status de implementação, status da validação e links das evidências.

Capturar todas as 49 páginas ao menos em desktop e celular, com dados locais
adequados; registrar o estado quando a rota exigir parâmetros. Usar
320/390px, 768px e 1440px como tamanhos de referência: a matriz pode distribuir
320 e 768px por componentes/telas críticas, mas não omitir mobile de uma área.

Verificar as 49 páginas em claro e escuro. Capturar as duas aparências ao menos
para cada família de layout e para componentes com cores próprias.
Nos fluxos críticos, cobrir também vazio, erro, bloqueado e processamento;
não exigir skeleton artificial em páginas que já recebem props prontas.

Além das imagens:

- Percorrer cada fluxo crítico pelo navegador com interações reais.
- Conferir overflow horizontal, zoom de 200%, textos longos e listas extensas.
- Testar teclado, menu móvel, dialogs, tema e preferência por movimento reduzido.
- Inspecionar console e requisições relevantes; resolver erros e documentar
  limitações externas com evidência.
- Testar rede lenta/falha para upload, autosave e envio: input não se perde e
  “salvo” não aparece diante de falha.
- Se câmera/QR ou navegador não puder ser validado, registrar como pendente,
  sem marcar a tela como integralmente aprovada.
- Capturas usam dados fictícios isolados, nunca dados pessoais reais.

### Regressão funcional

Verificar:

1. Login, Google quando configurado, recuperação/verificação e dados de conta.
2. Inscrição → criar/entrar em equipe → convidar → rascunho → upload → envio.
3. Jurado: fila → notas/comentários → autosave → envio → somente leitura.
4. Organização: check-in, programação, contingência, atribuições, resultados
   e emissão de certificado.
5. Voto único, resultados não publicados e validação pública de certificado.
6. Papéis acumulados, acesso proibido e edição anterior.

Executar verificações focadas por lote. Ao finalizar, rodar:

```bash
./vendor/bin/pint --test
npm run lint:check
npm run format:check
npx tsc --noEmit
npm run build
./vendor/bin/pest
git diff --check
```

Pest usa PostgreSQL e RefreshDatabase; confirmar banco de testes isolado antes
de rodar. Não apontar testes para o banco de desenvolvimento/produção.
Registrar comandos, resultados e falhas preexistentes comprovadas.
Não atribuir falhas ao ambiente sem diagnosticar.

## 9. Entrega para revisão pelo Codex

Entregar:

- Implementação completa e inventário reconciliado de 49/49 páginas.
- `docs/interface.md` atualizado com os padrões realmente implementados.
- `docs/revisao-interface.md` preenchido com evidências, fluxos percorridos,
  comandos/resultados e limitações explícitas.
- Resumo por área com comportamento antes/depois e mudanças de props/backend.
- Local ou instruções para abrir a prévia e reproduzir estados; não incluir segredos.
- Nenhum checkbox marcado apenas porque um layout pai mudou.

A review posterior deve inspecionar o diff e testar as telas, não apenas ler o
relatório. Priorizar perda de dados, envio/publicação indevidos, autorização,
autosave, acessibilidade, mobile e inconsistências entre áreas.
Uma interface visualmente polida com um desses problemas não está concluída.

