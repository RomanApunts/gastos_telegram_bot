#!/usr/bin/env bash
#
# Script de despliegue para Plesk (Git → "Acciones de despliegue adicionales").
#
# En Plesk: panel del dominio → Git → tu repositorio → en el campo
# "Acciones de despliegue adicionales" pon:
#
#     bash deploy.sh
#
# Se ejecuta tras cada despliegue (pull). Instala dependencias de producción,
# aplica las migraciones de base de datos y reconstruye la caché.
#
# Requisitos en el servidor (una sola vez):
#   - Un .env.local con APP_ENV=prod, APP_SECRET, DATABASE_URL, TELEGRAM_BOT_TOKEN
#     y TELEGRAM_WEBHOOK_SECRET (no se versiona).
#   - La base de datos ya creada (este script NO la crea).

set -euo pipefail

# --- Configuración -----------------------------------------------------------
# Ruta al binario de PHP. En Plesk suele ser la versión del dominio, por ejemplo:
#   /opt/plesk/php/8.4/bin/php   (o 8.5)
# Si "php" en el PATH no es la versión correcta, fija PHP_BIN a esa ruta.
PHP_BIN="${PHP_BIN:-php}"

# Entorno de Symfony (debe coincidir con el del .env.local).
export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"

# Situarse en la raíz del proyecto (donde está este script).
cd "$(dirname "$0")"

# Composer: global si existe, si no el composer.phar del proyecto.
if command -v composer >/dev/null 2>&1; then
    COMPOSER="composer"
elif [ -f composer.phar ]; then
    COMPOSER="$PHP_BIN composer.phar"
else
    echo "ERROR: no se encontró 'composer' ni 'composer.phar'." >&2
    exit 1
fi

echo "▶ Proyecto : $(pwd)"
echo "▶ PHP      : $($PHP_BIN -v | head -n1)"
echo "▶ Entorno  : $APP_ENV"

# --- 1) Dependencias de producción ------------------------------------------
echo "▶ Instalando dependencias (composer install --no-dev)…"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress

# --- 2) Migraciones de base de datos ----------------------------------------
echo "▶ Aplicando migraciones…"
$PHP_BIN bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# --- 3) Caché de producción --------------------------------------------------
echo "▶ Reconstruyendo la caché…"
$PHP_BIN bin/console cache:clear --no-interaction
$PHP_BIN bin/console cache:warmup --no-interaction

echo "✅ Despliegue completado."
