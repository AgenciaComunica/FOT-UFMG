# TemplateLaravel

Template base para novos projetos Laravel.

## Como começar

1. Instale as dependências:

```bash
composer install
npm install
```

2. Crie o arquivo de ambiente:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure banco de dados no `.env` e rode as migrations:

```bash
php artisan migrate
```

4. Rode o projeto localmente:

```bash
composer run dev
```

## Sobre Laravel

Laravel é um framework web com sintaxe elegante e ferramentas para acelerar o desenvolvimento.

- [Documentação oficial](https://laravel.com/docs)
- [Laravel Bootcamp](https://bootcamp.laravel.com)
- [Laracasts](https://laracasts.com)

## Licença

Este projeto está sob licença [MIT](https://opensource.org/licenses/MIT).
