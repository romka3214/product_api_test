#!/bin/bash
set -e

docker info > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "Docker не запущен"
    exit 1
fi

echo "Установка зависимостей Composer"
docker run --rm \
    -v "$(pwd)":/var/www/html \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

docker run --rm \
    -v "$(pwd)":/var/www/html \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    php artisan key:generate

if [ ! -d "vendor/laravel/sail" ]; then
    echo "Установка Laravel Sail"
    docker run --rm \
        -v "$(pwd)":/var/www/html \
        -w /var/www/html \
        laravelsail/php84-composer:latest \
        php artisan sail:install --with=mysql,redis,meilisearch
fi

echo "Билдим контейнеры"
./vendor/bin/sail pull mysql redis meilisearch
./vendor/bin/sail build

if command -v sudo &>/dev/null; then
    sudo chown -R $USER: .
elif command -v doas &>/dev/null; then
    doas chown -R $USER: .
fi

echo ""
echo "Готово"
