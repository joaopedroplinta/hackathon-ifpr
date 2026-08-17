# Changelog

Mudanças notáveis deste projeto, por versão. Formato baseado em
[Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/); o projeto segue
[SemVer](https://semver.org/lang/pt-BR/) — enquanto a major for `0`, um
minor pode trazer mudança que quebra compatibilidade.

A versão exibida no rodapé da sidebar vem de `composer.json` (`version`);
bump manual a cada tag.

## [Não lançado]

### Planejado (ver `PLANO.md`, cronograma semana 7-8)

- Certificados em PDF com validação por código
- Notificações por e-mail em fila (deadline, resultado)
- Ensaio geral: carga, acessibilidade, responsivo, deploy, plano B

## [0.6.0] - 2026-08-17

Fecha as semanas 0 a 6 do cronograma: fluxo de ponta a ponta do participante
e do organizador, do cadastro ao resultado publicado.

### Adicionado

- Autenticação por Google (Socialite, domínio institucional) e e-mail/senha
  com verificação
- Equipes: criar, convidar por e-mail e código, entrar, gerenciar, travas de
  tamanho e prazo
- Submissões com upload, versionamento e deadline verificado no servidor
- Agenda pública e do organizador, com exportação `.ics`
- Check-in por QR Code (leitor de câmera com fallback manual)
- Rubrica configurável e atribuição de jurados com checagem de conflito
- Painel do jurado com autosave, pensado para avaliação pelo celular
- Cálculo de resultados (`hackathon:compute-results`), publicação
  controlada, página pública de resultados e voto popular
- Página de privacidade LGPD, RoPA e minuta de designação do DPO
- Número de versão exibido no rodapé da sidebar (`v0.6.0` em produção,
  `v0.6.0-dev+<commit>` fora dela)

[Não lançado]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v0.6.0...HEAD
[0.6.0]: https://github.com/joaopedroplinta/hackathon-ifpr/releases/tag/v0.6.0
