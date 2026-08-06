---
description: Ensaio geral — simula o dia do evento contra o sistema e valida o plano B
---

Simulação completa do evento (semana 8 do PLANO.md + Anexo A.9). Rode isto
antes do hackathon acontecer, não depois.

Trabalhe **contra o banco de desenvolvimento**, nunca produção. Confirme qual
ambiente está ativo antes de qualquer escrita.

## 1. Popular

Seed realista, não três registros de brinquedo:

- 1 evento com datas coerentes (inscrição fechada, submissão aberta)
- 3 trilhas
- 30 equipes, sendo algumas incompletas e uma sem submissão
- 5 jurados, um deles com conflito de interesse declarado
- Rubrica com 4 critérios de pesos diferentes

## 2. Percorrer os fluxos

Para cada um, execute de verdade (teste, tinker ou navegador) e reporte o
resultado observado — não o esperado:

- [ ] Cadastro por Google e por e-mail/senha
- [ ] Criar equipe, convidar, aceitar, sair, transferir liderança
- [ ] Submeter, reenviar (gera versão nova), submeter **após o prazo** → `late`
- [ ] Check-in por QR e busca manual pelo nome
- [ ] Jurado avalia 3 projetos, salva rascunho, fecha e volta — nota preservada
- [ ] Jurado tenta abrir submissão que não é dele → bloqueado
- [ ] `hackathon:compute-results` e conferência de um ranking na mão
- [ ] Resultado antes de publicar → invisível ao público
- [ ] Voto popular: segundo voto do mesmo usuário → rejeitado pelo banco
- [ ] Certificado gerado e validado em `/validar/{code}`

## 3. Plano B (Anexo A)

- [ ] Importar CSV do formulário externo com 1 conflito proposital
- [ ] Lançar submissão manual → aparece marcada no painel
- [ ] Declarar incidente com extensão → prazo estendido **para todos**
- [ ] Restaurar backup completo (banco + arquivos) do zero, cronometrado

## 4. Carga

30 submissões simultâneas perto do deadline. Meça o tempo de resposta e o
tamanho da fila. Se a fila não drenar, o e-mail de confirmação chega depois
do evento acabar.

## 5. Relatório

Liste em duas seções: **Impede o evento** e **Aceitável no dia**.
Para cada item que falhou, diga o que aconteceu de fato e onde está o problema.
Se algo passou, diga que passou — sem inventar pendência.

$ARGUMENTS
