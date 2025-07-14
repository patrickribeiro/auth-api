# Dockerfile.schedule
FROM php:8.4-cli

# 🧩 Instala extensões necessárias
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev cron \
    && docker-php-ext-install zip pdo pdo_mysql

# 📂 Copia o projeto Laravel
WORKDIR /var/www
COPY . .

# ⚙️ Instala dependências
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-dev --optimize-autoloader

# 🧹 Prepara cron para executar schedule:run a cada minuto
RUN echo "* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/scheduler-cron \
    && chmod 0644 /etc/cron.d/scheduler-cron && crontab /etc/cron.d/scheduler-cron

CMD ["cron", "-f"]
