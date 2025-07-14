# Prizly

Com Prizly, você compra mais por menos.

# Para subir a stack

Para subir a stack deve-se entrar no projeto e, na sua raiz, executar o comando abaixo:

```sh
docker compose --env-file ./docker/.env up -d
```

Em seguida deve-se alterar a propriedade do diretório `application` na sua máquina. Para isso deve-se executar o comando abaixo (localmente, fora do container):

```sh
sudo chown -R 1001:1001 ./application
```

Após alterar a propriedade do diretório, deve-se criar a aplicação Laravel. Isso deve ser feito através dos seguintes passos:

1. Entrar no container do PHP:

```sh
docker exec -it api /bin/bash
```

2. Indicar que o diretório é seguro e adicioná-lo como exceção para o git:

```sh
git config --global --add safe.directory /var/www
```

3. Criar a aplicação Laravel:

```
composer create-project --prefer-dist laravel/laravel ./application
```

# Para configurar a aplicação

Depois de criada a aplicação, a primeira coisa é alterar o seu nome no arquivo `application/.env`

```sh
APP_NAME=teste
```

Também no arquivo `application/.env` devem ser alterados os conteúdos das variáveis abaixo para `pt_BR`:

```
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR
```

Ainda no arquivo `application/.env` deve ser adicionada uma variável para definição do timezone conforme segue:

```
APP_TIMEZONE=America/Sao_Paulo
```

Por último, no arquivo `application/config/app.php`, as configurações devem ser alteradas para:

```php
'timezone' => env('APP_TIMEZONE', 'UTC'),
```

# Habilitar rotas de API

A partir da versão 11 do Laravel as rotas de API não são instaladas por padrão, portanto é necessário fazê-lo no início do projeto.

Para isso, basta seguir os passos abaixo:

1. Entrar no container da aplicação:

```sh
docker exec -it api /bin/bash
```

2. Entrar no diretório da aplicação (onde o artisan fica):

```sh
cd application
```

3. Executar o comando que habilita as rotas de API:

```sh
artisan install:api
```

4. Dentro do arquivo `application/.env`, garantir que a URL da aplicação esteja correta:

```
APP_URL=http://localhost:8000
```

# Configurar o Sanctum

Depois de instalado, o Sanctum deve ser configurado para que funcione corretamente.

Para isso, devem ser seguidos os passos abaixo:

1. Configurar domínios `statefull` em `config/sanctum.php`:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
```

2. Ajustar o conteúdo de `application/.env` para que a variável de ambiente tenha os domínios corretos. Ex.:

```
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1
```

# Referências

https://github.com/wleandrooliveira/configuration-env-laravel-docker-postgresql-nginx-redis
