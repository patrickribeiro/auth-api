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

# Referências

https://github.com/wleandrooliveira/configuration-env-laravel-docker-postgresql-nginx-redis
