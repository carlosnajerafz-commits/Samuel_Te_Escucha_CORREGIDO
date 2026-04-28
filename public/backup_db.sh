#!/bin/bash

FECHA=$(date +"%Y-%m-%d_%H-%M-%S")
CARPETA="/var/www/html/backups"
ARCHIVO="$CARPETA/tecamac_$FECHA.sql"

mkdir -p "$CARPETA"

PGPASSWORD="tecamac_password" pg_dump -h db -U tecamac_user -d tecamac > "$ARCHIVO"

find "$CARPETA" -type f -name "*.sql" -mtime +7 -delete