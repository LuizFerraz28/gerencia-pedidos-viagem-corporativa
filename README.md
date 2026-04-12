# 🚀 Microservice - Gerência de Pedidos de Viagem Corporativa

API desenvolvida com Laravel, utilizando arquitetura de microsserviços e ambiente totalmente containerizado com Docker.

---

# 📦 Tecnologias utilizadas

* PHP 8.4
* Laravel (última versão)
* MySQL 8
* Redis
* Nginx
* Docker & Docker Compose

---

# ⚙️ Pré-requisitos

Antes de começar, você precisa ter instalado:

* Docker
* Docker Compose

---

# 🚀 Como rodar o projeto

Clone o repositório:

```bash
git clone <URL_DO_REPOSITORIO>
cd gerencia-pedidos-viagem-corporativa
```

---

## 🐳 Subir os containers

```bash
docker compose up -d --build
```

---

## 🔑 Configurar o ambiente

Acesse o container da aplicação:

```bash
docker exec -it app bash
```

Dentro do container, execute:

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

---

# 🌐 Acesso à aplicação

Após subir o ambiente, acesse:

```
http://localhost:8000
```

---

# 🧪 Rodando testes

Dentro do container:

```bash
php artisan test
```

---

# 📁 Estrutura do projeto

```bash
.
├── app/
├── routes/
├── database/
├── docker/
│   ├── nginx/
│   └── php/
├── docker-compose.yml
```

---

# 🔄 Filas (Queue)

O projeto já está configurado com Redis.

O worker é iniciado automaticamente via container:

```
queue
```

---

# 🛠️ Comandos úteis

Parar containers:

```bash
docker compose down
```

Rebuild completo:

```bash
docker compose up -d --build
```

Acessar container:

```bash
docker exec -it app bash
```

---

# 📌 Boas práticas adotadas

* Estrutura preparada para microsserviços
* Separação de responsabilidades (Controller, Service, etc.)
* Uso de filas com Redis
* Ambiente isolado com Docker
* Versionamento com Git

---

# 👨‍💻 Autor

Desenvolvido por Luiz Ferraz