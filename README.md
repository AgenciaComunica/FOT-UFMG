# Sistema de Secretaria - FOT/UFMG

MVP em Laravel para o processo de inscrição e homologação:

- Inscrição pública por edital (sem login)
- Homologação/indeferimento pela secretaria (admin)
- Liberação de acesso do aluno somente após homologação
- Download privado de documentos PDF
- Relatórios CSV por edital

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/MariaDB (produção) ou SQLite (testes)

## Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan storage:link
```

Usuário seed:

- `admin@teste.com`
- senha: `12345678`

## Fluxo completo (manual)

1. Acesse `/` e clique em inscrição no edital aberto.
2. Envie inscrição pública com documentos PDF.
3. Faça login como admin e acesse `/admin/editais`.
4. Entre nas inscrições do edital e abra o detalhe.
5. Homologue ou indefira:
   - Homologar exige todos os documentos obrigatórios.
   - Indeferir exige motivo.
6. Após homologar:
   - o sistema cria/vincula usuário `role=aluno`.
   - tenta enviar link de reset de senha.
   - sem SMTP, gera senha temporária e mostra uma vez na tela do admin.
7. Aluno faz login e acessa `/aluno/painel`.
8. Admin exporta CSV:
   - `/admin/editais/{edital}/relatorios/inscricoes-recebidas.csv`
   - `/admin/editais/{edital}/relatorios/inscricoes-homologadas.csv`

## Mail (SMTP) e fallback

Configurar `.env` para envio real de reset de senha:

```env
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME="FOT-UFMG"
```

Se `MAIL_MAILER=log` (ou reset falhar), o sistema usa fallback com senha temporária exibida apenas no retorno da homologação.

## Testes

```bash
php artisan test
```

## Rotas principais

Público:

- `GET /editais/{edital}/inscricao`
- `POST /editais/{edital}/inscricao`
- `GET /editais/{edital}/inscricao/confirmacao/{protocolo}`

Admin:

- `GET /admin/editais`
- `GET /admin/editais/{edital}/inscricoes`
- `GET /admin/inscricoes/{id}`
- `POST /admin/inscricoes/{id}/homologar`
- `POST /admin/inscricoes/{id}/indeferir`
- `GET /admin/inscricoes/{inscricao}/documentos/{doc}/download`

Aluno:

- `GET /aluno/painel`
- `GET /aluno/inscricoes`
- `GET /aluno/inscricoes/{id}`
- `GET /aluno/documentos/{doc}/download`
