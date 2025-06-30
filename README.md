# Mutirão API
API em PHP para registro e sorteio dos participantes do Mutirão do Lixo Eletrônico.

## Tecnologias
- PHP 8.2.4
- MySQL/MariaDB

## Dependências
- Composer
- Fast Route (nikic/fast-route)

## Estrutura
- `config/` arquivos de configuração
- `routes/` definição de rotas e classes
- `src/` contém os módulos
    - `Controllers/`
    - `Middlewares/`
    - `Models/`
- `.env.example` renomeie para `.env` e use como variável de sistema

---

## Como instalar as dependências do projeto

Execute na raiz do projeto, é necessário ter o composer na máquina:

```sh
composer install
```
