#!/bin/bash
# tests/run_single.sh — Ejecutar un solo test case
# Uso: bash tests/run_single.sh TC-1.1

if [ -z "$1" ]; then
  echo "Uso: bash tests/run_single.sh <TC_ID>"
  echo "Ejemplo: bash tests/run_single.sh TC-1.1"
  exit 1
fi

TC_ID="$1"
source "$(dirname "$0")/helpers.sh"

echo "=== Ejecutando test individual: $TC_ID ==="
echo ""

# Buscar y ejecutar el test desde run_all.sh
# Por ahora, indicar que se debe correr desde run_all.sh
echo "Para ejecutar un test individual, busca el TC en run_all.sh y cópialo aquí."
echo "O ejecuta la suite completa con: bash tests/run_all.sh"
