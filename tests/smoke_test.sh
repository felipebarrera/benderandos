#!/bin/bash
# tests/smoke_test.sh — BenderAnd ERP Smoke Test (v2 — lightpanda syntax fixed)

source "$(dirname "$0")/helpers.sh"

echo "=== BenderAnd Smoke Test (v2) ==="
echo "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

mkdir -p tests/results tests/errors tests/report
echo "# Smoke Test — $(date +%Y-%m-%d)" > tests/results/summary.log
echo "" >> tests/results/summary.log

echo "── Rutas públicas ──"
check_http "SuperAdmin login page"          "$BASE_SUPER/superadmin"            200
check_http "Tenant login page (/login)"     "$BASE_TENANT/login"                200

echo ""
echo "── Rutas protegidas (sin sesión) ──"
check_http "Tenant dashboard sin sesión"     "$BASE_TENANT/admin/dashboard"      302

echo ""
echo "── API endpoints ──"
check_http "API superadmin login (GET→405)"  "$BASE_SUPER/api/superadmin/login"  405

echo ""
echo "── Verificación de fixes ──"

# FIX-1: /admin/login ya no da 404
run_test "FIX-1" \
  "Admin login redirect funciona" \
  "curl -s -o /dev/null -w '%{http_code}' -L '$BASE_TENANT/admin/login'" \
  "200"

# FIX-2: API superadmin usa prefijo /api
run_test "FIX-2" \
  "API superadmin login ruta existe" \
  "curl -s -X POST '$BASE_SUPER/api/superadmin/login' -H 'Content-Type: application/json' -H 'Accept: application/json' -d '{\"email\":\"test\",\"password\":\"test\"}' -w '\n%{http_code}'" \
  "422"

# FIX-3: lightpanda con sintaxis posicional
run_test "FIX-3" \
  "lightpanda sintaxis posicional funciona" \
  "lightpanda fetch '$BASE_SUPER/superadmin' 2>&1 | head -1" \
  "<"

echo ""
echo "══════════════════════════"
echo "PASS: $PASS_COUNT  |  FAIL: $FAIL_COUNT"
echo "══════════════════════════"
