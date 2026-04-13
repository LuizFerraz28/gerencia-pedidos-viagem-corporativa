# Gerência de Pedidos de Viagem Corporativa

API REST para gerenciamento de pedidos de viagem corporativa, desenvolvida em Laravel 13 com arquitetura DDD (Domain-Driven Design) e autenticação via JWT.

---

## 🧭 Decisões de Projeto

### Arquitetura — Domain-Driven Design (DDD)

A principal decisão arquitetural foi estruturar a aplicação em camadas bem definidas, separando responsabilidades e tornando o domínio de negócio independente do framework:

```
app/
├── Domain/          # Regras de negócio puras (Entidades, ValueObjects, Eventos, Exceções, Interface do Repositório)
├── Application/     # Casos de uso (DTOs + UseCases orquestram o domínio)
├── Infrastructure/  # Implementações concretas (Eloquent Models, Repositório, Notificações)
└── Http/            # Camada HTTP (Controllers, Requests, Resources, Middleware)
```

### Autenticação — JWT (tymon/jwt-auth)

Escolhido JWT em vez de Sanctum porque o enunciado menciona microsserviços: JWTs são *stateless* e dispensam consulta ao banco em cada requisição, facilitando a escalabilidade horizontal e a comunicação entre serviços.

### Autorização — Laravel Policies + Gates

A lógica de quem pode aprovar/cancelar/ver pedidos fica em `TravelOrderPolicy`, registrada no `AppServiceProvider`. O controller delega ao `Gate::authorize()`, mantendo o código limpo e testável.

### Status como Enum

`TravelOrderStatus` é um `enum` backed string com o método `canTransitionTo()`, que encapsula a máquina de estados (`solicitado → aprovado | cancelado`, `aprovado → cancelado`). Violações lançam `TravelOrderException`, tratada centralmente no `bootstrap/app.php`.

### Notificações

Ao aprovar ou cancelar um pedido, um evento de domínio (`TravelOrderApproved` / `TravelOrderCancelled`) é disparado. A `TravelOrderStatusChangedNotification` é enviada ao usuário dono do pedido via banco de dados (tabela `notifications`), processada de forma assíncrona pelo worker de filas Redis.

### Testes

- **Feature tests** (`tests/Feature/`) — testam os endpoints HTTP completos, incluindo autenticação, regras de negócio e filtros.
- **Unit tests** (`tests/Unit/Domain/`) — testam o domínio isolado (Entity, ValueObjects, Events), sem banco de dados.

---

## 📦 Tecnologias

| Tecnologia | Versão |
|---|---|
| PHP | 8.4 |
| Laravel | 13.x |
| MySQL | 8.0 |
| Redis | latest (alpine) |
| Nginx | latest (alpine) |
| tymon/jwt-auth | 2.x |
| Docker & Docker Compose | — |

---

## ⚙️ Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

---

## 🚀 Instalação e execução local

### 1. Clone o repositório

```bash
git clone <URL_DO_REPOSITORIO>
cd gerencia-pedidos-viagem-corporativa
```

### 2. Configure as variáveis de ambiente

```bash
cp .env.example .env
```

Edite o `.env` e confirme as seguintes variáveis (já alinhadas com o `docker-compose.yml`):

```dotenv
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=root

CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

JWT_SECRET=           # será gerado no passo 4
```

### 3. Suba os containers

```bash
docker compose up -d --build
```

Isso inicializa os serviços:
- **app** — PHP-FPM (aplicação Laravel)
- **nginx** — servidor web na porta `8000`
- **mysql** — banco de dados MySQL 8
- **redis** — cache e filas
- **queue** — worker `php artisan queue:work`

### 4. Configure a aplicação dentro do container

```bash
docker exec -it app bash
```

Dentro do container:

```bash
# Instala composer
composer install
composer dump-autoload

# Gera APP_KEY e JWT_SECRET no .env
php artisan key:generate
php artisan jwt:secret

# Executa as migrations (cria tabelas users, travel_orders, notifications, etc.)
php artisan migrate

# Popula usuários iniciais (admin e usuário comum)
php artisan db:seed
```

### 5. Acesse a API

```
http://localhost:8000
```

---

## 🌍 Variáveis de ambiente relevantes

| Variável | Descrição | Padrão |
|---|---|---|
| `DB_HOST` | Host do MySQL | `mysql` |
| `DB_DATABASE` | Nome do banco | `app` |
| `DB_USERNAME` | Usuário do banco | `app` |
| `DB_PASSWORD` | Senha do banco | `root` |
| `REDIS_HOST` | Host do Redis | `redis` |
| `QUEUE_CONNECTION` | Driver de filas | `redis` |
| `JWT_SECRET` | Chave secreta JWT | gerado por `php artisan jwt:secret` |
| `JWT_TTL` | Expiração do token (minutos) | `60` |

---

## 🧪 Executando os testes

Os testes usam um banco SQLite em memória (configurado no `phpunit.xml`), portanto **não precisam** do MySQL ativo.

```bash
docker exec -it app php artisan test
```

Para executar somente um grupo:

```bash
# Apenas testes de Feature
docker exec -it app php artisan test --testsuite=Feature

# Apenas testes de Unit
docker exec -it app php artisan test --testsuite=Unit
```

Resultado esperado:

```
Tests:    41 passed (68 assertions)
```

---

## �️ Endpoints da API

### Autenticação

| Método | Rota | Descrição | Auth |
|---|---|---|---|
| `POST` | `/api/auth/register` | Cadastrar novo usuário | ❌ |
| `POST` | `/api/auth/login` | Autenticar e receber JWT | ❌ |
| `GET` | `/api/auth/me` | Dados do usuário autenticado | ✅ |
| `POST` | `/api/auth/logout` | Invalidar token | ✅ |
| `POST` | `/api/auth/refresh` | Renovar token | ✅ |

### Pedidos de Viagem

| Método | Rota | Descrição | Auth |
|---|---|---|---|
| `POST` | `/api/travel-orders` | Criar pedido | ✅ |
| `GET` | `/api/travel-orders` | Listar pedidos (com filtros) | ✅ |
| `GET` | `/api/travel-orders/{id}` | Ver pedido específico | ✅ |
| `PATCH` | `/api/travel-orders/{id}/status` | Atualizar status | ✅ Admin |

#### Filtros disponíveis em `GET /api/travel-orders`

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `status` | string | `solicitado`, `aprovado` ou `cancelado` |
| `destination` | string | Busca parcial no destino |
| `departure_from` | date | Data de ida a partir de (Y-m-d) |
| `departure_until` | date | Data de ida até (Y-m-d) |
| `created_from` | date | Criado a partir de (Y-m-d) |
| `created_until` | date | Criado até (Y-m-d) |

> **Usuários comuns** só enxergam seus próprios pedidos. **Admins** enxergam todos.

#### Valores de status

| Valor | Transições permitidas |
|---|---|
| `solicitado` | → `aprovado`, → `cancelado` (somente admin) |
| `aprovado` | → `cancelado` (admin ou dono do pedido) |
| `cancelado` | — (estado final) |

> Usuário comum pode cancelar apenas pedidos com status `solicitado` que sejam seus.

---

## � Usuários de exemplo (após `db:seed`)

| E-mail | Senha | Papel |
|---|---|---|
| `admin@example.com` | `password` | Administrador |
| `user@example.com` | `password` | Usuário comum |

---

## 🏗️ Estrutura do projeto

```
app/
├── Domain/TravelOrder/
│   ├── Entities/          # TravelOrder (entidade raiz do agregado)
│   ├── ValueObjects/      # TravelOrderStatus (enum), DateRange
│   ├── Events/            # TravelOrderApproved, TravelOrderCancelled
│   ├── Exceptions/        # TravelOrderException
│   └── Repositories/      # TravelOrderRepositoryInterface
├── Application/TravelOrder/
│   ├── DTOs/              # CreateTravelOrderDTO, ListTravelOrdersDTO
│   └── UseCases/          # Create, Get, List, UpdateStatus
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Models/        # TravelOrderModel (Eloquent)
│   │   └── Repositories/  # EloquentTravelOrderRepository
│   └── Notifications/     # TravelOrderStatusChangedNotification
├── Http/
│   ├── Controllers/       # AuthController, TravelOrderController
│   ├── Middleware/        # JwtAuthenticate
│   ├── Requests/          # FormRequests com validação
│   └── Resources/         # TravelOrderResource, UserResource
├── Models/                # User (Eloquent + JWTSubject)
└── Policies/              # TravelOrderPolicy
database/
├── migrations/
├── seeders/
└── factories/
docker/
├── nginx/default.conf
└── php/Dockerfile
tests/
├── Feature/               # AuthTest, TravelOrderTest
└── Unit/Domain/           # TravelOrderTest (domínio isolado)
```

---

## 🛠️ Comandos úteis

```bash
# Parar os containers
docker compose down

# Rebuild completo
docker compose up -d --build

# Acessar o container da aplicação
docker exec -it app bash

# Rodar migrations zeradas
docker exec -it app php artisan migrate:fresh --seed

# Ver logs da aplicação
docker exec -it app tail -f storage/logs/laravel.log

# Verificar status da fila
docker logs queue
```

---

## 👨‍💻 Autor

Desenvolvido por Luiz Ferraz
