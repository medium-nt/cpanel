#!/bin/bash

set -e

RED="\e[31m"
GREEN="\e[32m"
YELLOW="\e[33m"
BLUE="\e[34m"
MAGENTA="\e[35m"
CYAN="\e[36m"
RESET="\e[0m"

block() {
    echo -e "${MAGENTA}\n===== $1 =====${RESET}"
}

echo
echo -e "${CYAN}=== НАЧАЛО ДЕПЛОЯ ===${RESET}"
echo -e "${BLUE}Текущая директория:${RESET} $(pwd)"
echo -e "${BLUE}Время:${RESET} $(date)"
block "==================================="

echo
block "ОБНОВЛЕНИЕ КОДА (git pull)"
git pull origin main
block "==================================="

echo
block "УСТАНОВКА ЗАВИСИМОСТЕЙ (composer)"
/usr/local/bin/php8.4 composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev
block "==================================="

echo
block "ОЧИСТКА КЕШЕЙ LARAVEL"
php8.4 artisan cache:clear
php8.4 artisan config:clear
php8.4 artisan route:clear
php8.4 artisan view:clear
block "==================================="

echo
block "ПЕРЕСБОРКА КЕШЕЙ LARAVEL"
php8.4 artisan config:cache
php8.4 artisan route:cache
php8.4 artisan view:cache
block "==================================="

echo
block "ОПТИМИЗАЦИЯ"
php8.4 artisan optimize
block "==================================="

echo
echo -e "${GREEN}\n=== ДЕПЛОЙ ЗАВЕРШЁН ===${RESET}"
echo -e "${BLUE}Время:${RESET} $(date)"
