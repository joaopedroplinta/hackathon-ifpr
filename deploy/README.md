# Deploy — serviços de fundo

Sem isso rodando, a fila (e-mails, PDFs) e o lembrete de prazo de submissão
não disparam sozinhos em produção — ver PLANO.md § Próximos passos e Anexo A.6.

## Instalar

```bash
sudo cp deploy/hackathon-queue.service deploy/hackathon-schedule.service /etc/systemd/system/
sudo nano /etc/systemd/system/hackathon-queue.service     # ajustar WorkingDirectory e User
sudo nano /etc/systemd/system/hackathon-schedule.service   # idem

sudo systemctl daemon-reload
sudo systemctl enable --now hackathon-queue hackathon-schedule
```

## Conferir

```bash
sudo systemctl status hackathon-queue hackathon-schedule
sudo journalctl -u hackathon-queue -f     # logs ao vivo
```

## Depois de um deploy novo

Job em andamento na fila não é interrompido, mas código novo só entra depois
do worker reiniciar:

```bash
sudo systemctl restart hackathon-queue
```

`hackathon-schedule` não precisa reiniciar a cada deploy — ele só dispara
comandos artisan, que já pegam o código atual a cada execução.
