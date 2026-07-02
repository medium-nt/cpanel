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

BEFORE_SHA=$(git rev-parse HEAD)

block "ОБНОВЛЕНИЕ КОДА (git pull)"
git pull origin main
echo -e "${CYAN}======================================${RESET}"

block "ПРОВЕРКА МИГРАЦИЙ"
# Сравниваем состояние прода до и после pull: какие файлы из database/migrations/
# были добавлены (--diff-filter=A) именно в этом деплое.
NEW_MIGRATIONS=$(git diff --name-only --diff-filter=A "$BEFORE_SHA" HEAD -- database/migrations/ | sed 's#^database/migrations/##')

if [ -n "$NEW_MIGRATIONS" ]; then
    echo -e "${YELLOW}⚠  ОБНАРУЖЕНЫ НОВЫЕ МИГРАЦИИ — НЕОБХОДИМО ПРИМЕНИТЬ ВРУЧНУЮ!${RESET}"
    echo -e "${YELLOW}   Файлы:${RESET}"
    echo "$NEW_MIGRATIONS" | sed 's/^/     - /'
    echo
    echo -e "${YELLOW}   Команда:${RESET} php8.4 artisan migrate --force"
    echo -e "${YELLOW}   Проверка:${RESET} php8.4 artisan migrate:status"
else
    echo -e "${GREEN}✓ Новых миграций в этом деплое нет.${RESET}"
fi
echo -e "${CYAN}=================================${RESET}"

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
