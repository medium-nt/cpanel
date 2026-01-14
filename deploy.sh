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
    echo -e "${CYAN}\n===== $1 =====${RESET}"
}

echo
echo -e "${GREEN}=========== НАЧАЛО ДЕПЛОЯ ===========${RESET}"
echo -e "${BLUE}Текущая директория:${RESET} $(pwd)"
echo -e "${BLUE}Время:${RESET} $(date)"

block "ОБНОВЛЕНИЕ КОДА (git pull)"
git pull origin main
echo -e "${CYAN}======================================${RESET}"

block "УСТАНОВКА ЗАВИСИМОСТЕЙ (composer)"
/usr/local/bin/php8.4 composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev
echo -e "${CYAN}========================================${RESET}"

block "ОЧИСТКА КЕШЕЙ LARAVEL"
php8.4 artisan cache:clear
php8.4 artisan config:clear
php8.4 artisan route:clear
php8.4 artisan view:clear
echo -e "${CYAN}=================================${RESET}"

block "ПЕРЕСБОРКА КЕШЕЙ LARAVEL"
php8.4 artisan config:cache
php8.4 artisan route:cache
php8.4 artisan view:cache
echo -e "${CYAN}===================================${RESET}"

block "ОПТИМИЗАЦИЯ"
php8.4 artisan optimize
echo -e "${CYAN}======================${RESET}"

echo -e "${GREEN}\n====== ДЕПЛОЙ ЗАВЕРШЁН ======${RESET}"
echo -e "${BLUE}Время:${RESET} $(date)"
echo -e "${GREEN}=============================${RESET}"
echo
