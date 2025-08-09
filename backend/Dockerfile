FROM php:8.2-fpm-alpine

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 安裝系統依賴和 PHP 擴展
# 注意：這裡安裝的 nginx 和 supervisor 是為了在同一容器內運行，
# 在生產環境中，推薦將 Web Server (nginx) 和 PHP-FPM 放入不同的容器。
RUN apk update && apk add --no-cache \
    nginx \
    mysql-client \
    curl \
    git \
    supervisor \
    libzip-dev \
    # Add GD for image manipulation if needed by Scribe (e.g. for screenshot generation)
    # imagemagick-dev \
    # && docker-php-ext-install gd \
    && docker-php-ext-install pdo_mysql opcache zip \
    && rm -rf /var/cache/apk/*

# 設定 Nginx
# COPY ./nginx/default.conf /etc/nginx/http.d/default.conf # 由 docker-compose.yml 進行 volume 掛載

# 設定 Supervisor
# COPY ./etc/supervisor/conf.d/supervisord.conf /etc/supervisor/conf.d/supervisord.conf # 由 docker-compose.yml 進行 volume 掛載

# 複製應用程式代碼 (不複製 vendor，因為會在容器內安裝)
COPY . /var/www/html

# 設定權限並安裝 Composer 依賴
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && composer install --no-dev --optimize-autoloader

# Expose port 80 (Laravel 應用程式的預設 HTTP 端口)
EXPOSE 80

# 設置 Composer cache 目錄，可以透過 Docker volume 掛載以加速構建
ENV COMPOSER_HOME /tmp/composer-cache
RUN chmod +x /var/www/html/artisan

# 啟動 Supervisor 來管理 Nginx 和 PHP-FPM
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
