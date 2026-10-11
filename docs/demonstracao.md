# Demonstração do fluxo completo (homologação)

A homologação tem uma **edição de demonstração** própria, com dados
fictícios, que dá pra colocar em qualquer ponto do fluxo e **voltar ao
início em segundos**. Serve pra mostrar o sistema inteiro ao cliente sem
depender de ninguém digitando dado na hora.

- Site: <https://hackathon-ifpr-homologacao.up.railway.app>
- Caixa de e-mails de teste: <https://mailpit-homologacao-b306.up.railway.app>
  (nenhum e-mail sai de verdade: tudo cai nessa caixa, protegida por senha)

## Acessos

As três contas do cliente usam **a mesma senha**, que fica na variável
`DEMO_PASSWORD` do serviço `hackathon-ifpr` (Railway → projeto
Hackathon-IFPR → ambiente `homologacao` → Variables). A caixa de e-mails
usa a variável `MP_UI_AUTH` do serviço `mailpit`, no formato `usuário:senha`.

| Papel | E-mail | O que mostra |
|---|---|---|
| Organização | `organizacao.demo@demo.ifpr.test` | painel, equipes, submissões, jurados, resultados, certificados |
| Jurado | `jurado.demo@demo.ifpr.test` | fila de avaliação e a tela de avaliar |
| Participante | `participante.demo@demo.ifpr.test` | líder da equipe "Rota Segura": equipe, envio, crachá, voto |

As demais contas da demonstração (jurados e participantes das outras
equipes) existem, mas não têm senha conhecida.

Para trocar a senha: mude `DEMO_PASSWORD` no Railway e rode o comando de
novo (ele regrava a senha das três contas).

## Preparar um estágio

```bash
railway ssh -p 9ad7c37c-1b99-4d0f-b36f-e6e26da09abb -e homologacao -s hackathon-ifpr -- \
  php artisan hackathon:demo <estagio>
```

A senha vem de `DEMO_PASSWORD` (ou de `--senha=`, com 12 caracteres ou mais).
**Cada execução apaga as equipes, envios, avaliações, resultados, presenças e
certificados da edição de demonstração e refaz tudo do estágio escolhido.**
Cadastros que o cliente fizer com a conta Google dele nessa edição também
somem. A edição real e as edições anteriores nunca são tocadas, e o comando
se recusa a rodar em produção.

| Estágio | Como a edição fica |
|---|---|
| `inicio` | inscrições abertas, 8 equipes em formação, nenhum envio |
| `submissoes` | prazo acabou há 30 min: 6 envios no prazo, **1 atrasado aguardando decisão** e 1 equipe sem envio |
| `avaliacao` | atraso aceito, jurados distribuídos, a maior parte das notas entregue; o **jurado de demonstração ainda tem tudo pendente** |
| `resultado` | todas as notas entregues, 1 equipe já desclassificada, ranking calculado e **não publicado**, presenças registradas, votação aberta |

As notas são fixas: o ranking sai igual a cada reinício.

## Roteiro sugerido (cerca de 15 minutos)

**1. `inicio`: o participante.**
Entre como participante: página inicial com os passos, equipe "Rota Segura"
(código de convite, integrantes), crachá com QR Code. Mostre o site público
(agenda, regulamento, rubrica) sem login.

**2. `submissoes`: a organização.**
Entre como organização. Em *Submissões*, abra o envio **atrasado**: aceite ou
recuse com motivo (fica no registro). Em *Equipes*, veja a equipe sem envio.
Mostre o filtro por situação e o download do .zip.

**3. `avaliacao`: jurados.**
Em *Jurados*, a distribuição e os conflitos de interesse. Entre como jurado,
abra um projeto pendente, preencha as notas (o rascunho é salvo sozinho) e
envie. Volte à organização: *reabrir avaliação* com motivo, e **desclassificar**
uma equipe em *Equipes* (o e-mail com o motivo chega na caixa de testes).

**4. `resultado`: fechamento.**
Em *Resultados*, as pendências e o ranking (a equipe desclassificada fica
fora). **Publique** o resultado e abra a página pública: o pódio aparece.
Como participante, **vote** no projeto de outra equipe (na própria, o sistema
barra). Em *Emitir certificados*, emita os certificados, baixe o PDF e
confira em `/validar/{código}`.

Entre uma apresentação e outra, rode `hackathon:demo inicio` pra zerar.

## E-mails que aparecem na caixa de testes

Convite de equipe, desclassificação e reabilitação (com o motivo), aviso do
resultado publicado, lembrete de prazo, verificação de e-mail e redefinição de
senha. A caixa guarda as últimas 500 mensagens.

## Cuidados

- Tudo é fictício: não cadastre dado real. O comando não apaga usuários, mas
  o que o cliente fizer na edição de demonstração some no próximo reinício.
- O login com Google funciona na homologação (entra como participante).
- A homologação é pública: só passe o endereço, a senha das contas e o acesso
  à caixa de e-mails a quem for ver a demonstração.
