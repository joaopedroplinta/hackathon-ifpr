# Revalidação das correções de segurança

Data: 12/10/2026, horário de Brasília. Código local: `4cc60abceba50a3b39ff1c3a95f6e34e0cbb935b` (v1.3.2).

## Atualização após a segunda correção

As duas pendências deste relatório foram encerradas por revalidação HTTP em homologação: os mesmos identificadores malformados retornaram 422, com erro em `scores.0.criterion_id`, tanto em rascunho quanto em envio. A correção correspondente está no commit `221a32e`, presente nas branches de correção e homologação; a branch main local ainda estava em `4cc60ab` durante esta rodada.

Os seis casos autenticados foram repetidos (e-mail e CPF em lista, scores como texto nas duas rotas e criterion_id em lista aninhada nas duas rotas). Todos retornaram 422 nos campos esperados; nenhum retornou 500. Foram 19 requisições e dez asserções/observações, todas passaram, incluindo preservação do perfil e da submissão. As observações de mudança do cookie cifrado não constituem prova adicional de rotação de sessão.

As duas sessões de demonstração foram encerradas. Não houve criação de objetos/votos, alteração de código ou acesso à produção nesta nova rodada. Evidências sem credenciais: `/tmp/hackathon-security-recheck-ptbi8ial/http-results.json` e `checks.json`. Esta rodada confirma o comportamento implantado em homologação, sem atribuir os resultados ao código antigo da main nem repetir os testes locais com ele.

O restante abaixo registra a primeira revalidação, antes dessa correção.

## Resultado da primeira revalidação

Os 14 testes originais passaram no código atualizado: dez da auditoria pública e quatro da auditoria autenticada, com 57 asserções. Os quatro payloads autenticados originais também passaram em homologação, retornando 422 com erros nos campos esperados. Os cabeçalhos antes ausentes foram observados no login público de produção e nas respostas de homologação.

Porém, a validação das avaliações continua incompleta: uma variação do identificador do critério ainda provoca 500 tanto ao salvar rascunho quanto ao enviar. Os dois casos foram reproduzidos no banco temporário isolado e em homologação. Não foi demonstrada invasão, acesso a dados alheios ou alteração de notas.

## Correções confirmadas

| Verificação | Evidência atual |
|---|---|
| Cadastro com e-mail em lista | Teste isolado: 422, nenhum usuário/notificação criado |
| Token de recuperação em lista com token válido pendente | Teste isolado: 422, senha preservada |
| Senha com byte nulo no cadastro | Teste isolado: 422, nenhum usuário/notificação criado |
| Resposta de recuperação distinguir e-mail existente/inexistente | Teste isolado: mensagens iguais para token inválido |
| Limites nas rotas de cadastro e recuperação | Teste de middleware passou nas três rotas |
| Perfil com e-mail em lista | Teste isolado e homologação: 422 no campo email |
| Perfil com CPF em lista | Teste isolado e homologação: 422 no campo cpf |
| Notas como texto no rascunho e envio | Testes isolados e homologação: 422 no campo scores |
| Proteção HTTP contra enquadramento e sniffing | Produção em HEAD /login e homologação: X-Frame-Options DENY, CSP com frame-ancestors none, X-Content-Type-Options nosniff |

Os demais testes públicos de integridade também passaram: token de outra conta, token expirado, reuso de token, resposta genérica de forgot-password e rejeição de campos administrativos no cadastro. Os testes públicos que dependem de usuário/token utilizaram banco isolado e notificações simuladas; não foram repetidos com contas reais de produção. O teste de throttle acima confere middleware, sem provocar saturação remota.

## Pendência histórica, agora encerrada: identificador do critério malformado

Payload fictício, com sessão de participante e CSRF válido:

```json
{"scores":[{"criterion_id":[["invalid"]],"score":"1.00"}]}
```

| Rota em homologação | Retorno esperado | Retorno observado |
|---|---|---|
| POST /jurado/avaliar/88/rascunho | 422, erro em scores.0.criterion_id | 500 |
| POST /jurado/avaliar/88/enviar | 422, erro em scores.0.criterion_id | 500 |

O ID 88 foi obtido da fila atual do jurado de demonstração, sem reaproveitar os IDs da auditoria anterior. As tentativas foram feitas pela conta participante.demo, sem atribuição de jurado. Não se trata de envio legítimo de nota.

A correção verifica se scores é uma lista, mas os callbacks after continuam executando com campos internos inválidos, mesmo quando a regra integer de criterion_id já falhou:

- `app/Http/Requests/Judge/SaveEvaluationDraftRequest.php:59`: Collection::has recebe o identificador como lista aninhada e lança TypeError de array_key_exists.
- `app/Http/Requests/Judge/SubmitEvaluationRequest.php:56`: keyBy tenta usar uma lista como chave e lança TypeError de acesso por índice inválido.

Os dois testes locais de proteção falharam com esperado 422 / recebido 500. Em homologação, apareceu a página errors/erro; não foram encontrados os indicadores de stack trace pesquisados.

Correção recomendada: interromper a validação adicional quando a validação básica já tiver erros, antes de consultar/iterar a rubrica ou usar keyBy/has. Validar também cada linha de scores como array e impedir que identificadores de tipos inválidos cheguem às operações de coleção. Manter a Policy para a autorização. Acrescentar esses dois casos aos testes de regressão e repetir após a correção/deploy.

## Integridade e artefatos

Nenhum arquivo da aplicação foi alterado pela revalidação. Não houve criação de equipe, submissão, arquivo, certificado ou voto, nem envio real de e-mail. Perfil e conteúdo da submissão da conta de teste permaneceram iguais nas comparações antes/depois. As sessões utilizadas foram encerradas. Produção recebeu somente uma requisição pública HEAD para verificar cabeçalhos.

As execuções locais usaram exclusivamente o banco temporário `hackathon_security_audit_5qmby`, com guarda explícita nos testes, separado dos bancos úteis de desenvolvimento e de produção.

- `/tmp/hackathon-prod-deep-audit-5qMbyS8N/PublicSecurityAuditTest.php`: dez testes originais, agora passaram.
- `/tmp/hackathon-auth-audit-xdtgBZKn/AuthenticatedValidationAuditTest.php`: quatro testes originais, agora passaram.
- `/tmp/hackathon-auth-audit-xdtgBZKn/EvaluationShapeRecheckTest.php`: dois novos casos de proteção, ainda falham.
- `/tmp/hackathon-auth-audit-xdtgBZKn/evaluation-shape-recheck.xml`: evidências das falhas locais.
- `/tmp/hackathon-security-recheck-369l7_mq/`: metadados HTTP e asserções da execução remota concluída, sem senha, cookies ou CSRF.

Os relatórios anteriores permanecem como registro histórico. Esta revalidação não repete toda a auditoria nem comprova ausência de outras falhas.
