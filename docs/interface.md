# Direção de interface

Repaginada de setembro de 2026. Complementa a seção 11 do PLANO.md;
os tokens implementados continuam em resources/css/app.css.

## Identidade

Verde institucional para ações e indicação de estado, superfícies claras
(com equivalentes escuros) e tipografia Inter com hierarquia bem definida.
Painéis editoriais em verde profundo usam a assinatura “Conectar. Criar.
Transformar.” e formas geométricas feitas em CSS. Não usam a marca oficial
do Instituto Federal nem dependem de imagens externas.

## Experiência

- Site público: nome e situação reais do evento, chamada contextual para
  inscrição/painel, data em horário de São Paulo e explicação da participação.
- Navegação pública: menu compacto abaixo de 1024px, página ativa indicada,
  acesso à conta e opção de aparência em ambas as versões.
- Páginas públicas internas usam `PublicLayout`: contexto, título, descrição,
  ação opcional e uma área de conteúdo com largura adequada à tarefa.
- Agenda prioriza decisão rápida (dia, tipo, horário e local); projetos usam
  busca e confirmação antes do voto; resultados mostram ranking em lista para
  manter títulos e pontuações legíveis em qualquer tela.
- Páginas institucionais longas usam navegação interna por seções, que também
  recebe foco quando acionada por teclado.
- Área interna: navegação agrupada por participação, avaliação, organização
  e administração; visibilidade por papel não substitui as Policies.
- Participante: jornada existente acompanhada de uma próxima ação derivada
  das props do servidor. O crachá é um atalho, não uma etapa que bloqueia
  a recomendação de envio do projeto.
- Organização: pendências reais e atalho de check-in em destaque.
- Jurado: progresso nomeado para leitores de tela e linhas de avaliação
  com espaço para toque e títulos longos.
- Autenticação: mesma linguagem visual, retorno ao evento e links de
  privacidade e regulamento.

## Manutenção

Preservar estados de vazio e regras de acesso. Não inventar números, datas,
disponibilidade de inscrição ou resultado publicado para compor uma tela.
Manter foco visível, alvos de toque confortáveis e suporte a movimento reduzido.
Contadores não devem anunciar atualizações a cada segundo em leitores de tela.
Para variações, compor componentes do domínio; não editar os primitives
gerados em components/ui.
