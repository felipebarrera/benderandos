#!/bin/bash
# tests/run_all.sh — BenderAnd ERP Full Test Suite (v2 — lightpanda fixed)

source "$(dirname "$0")/helpers.sh"

echo "╔══════════════════════════════════════════╗"
echo "║  BenderAnd ERP — Suite Completa (v2)     ║"
echo "╚══════════════════════════════════════════╝"
echo "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

mkdir -p tests/results tests/errors tests/report
rm -f tests/results/*.log tests/errors/*.log
echo "# Resultados de pruebas — $(date +%Y-%m-%d)" > tests/results/summary.log
echo "" >> tests/results/summary.log

# ══════════════════════════════════════════════════════════════
# PRUEBA 1: SuperAdmin
# ══════════════════════════════════════════════════════════════
echo "── PRUEBA 1: SuperAdmin ──"

run_test "TC-1.1" \
  "Login superadmin exitoso" \
  "api_post '$BASE_SUPER/api/superadmin/login' '{\"email\":\"admin@benderand.cl\",\"password\":\"password\"}'" \
  "token"

run_test "TC-1.2" \
  "Password incorrecto rechazado" \
  "api_post '$BASE_SUPER/api/superadmin/login' '{\"email\":\"admin@benderand.cl\",\"password\":\"wrongpass\"}'" \
  "incorrectas"

run_test "TC-1.3" \
  "SuperAdmin dashboard requiere auth" \
  "curl -s -o /dev/null -w '%{http_code}' '$BASE_SUPER/api/superadmin/dashboard'" \
  "401"

echo ""

# ══════════════════════════════════════════════════════════════
# PRUEBA 2: Tenant Admin
# ══════════════════════════════════════════════════════════════
echo "── PRUEBA 2: Tenant Admin ──"

run_test "TC-2.1" \
  "Ruta /admin/login responde" \
  "curl -s -o /dev/null -w '%{http_code}' -L '$BASE_TENANT/admin/login'" \
  "200"

run_test "TC-2.2" \
  "Login tenant admin exitoso" \
  "api_post '$BASE_TENANT/auth/login' '{\"email\":\"admin@benderand.cl\",\"password\":\"admin1234\"}'" \
  "token"

run_test "TC-2.3" \
  "Dashboard admin requiere sesión" \
  "curl -s -o /dev/null -w '%{http_code}' '$BASE_TENANT/admin/dashboard'" \
  "302"

echo ""

# ══════════════════════════════════════════════════════════════
# PRUEBA 3: Fixes
# ══════════════════════════════════════════════════════════════
echo "── PRUEBA 3: Verificación de Fixes ──"

run_test "FIX-1" \
  "Admin login redirect funciona" \
  "curl -s -o /dev/null -w '%{http_code}' '$BASE_TENANT/admin/login'" \
  "301"

run_test "FIX-2" \
  "API superadmin login ruta existe" \
  "curl -s -X POST '$BASE_SUPER/api/superadmin/login' -H 'Content-Type: application/json' -H 'Accept: application/json' -d '{\"email\":\"x\",\"password\":\"x\"}' -w '\n%{http_code}'" \
  "422"

run_test "FIX-3" \
  "lightpanda sintaxis posicional" \
  "lightpanda fetch '$BASE_SUPER/superadmin' 2>&1 | head -1" \
  "<"

echo ""

# ══════════════════════════════════════════════════════════════
# PRUEBA 4: Rutas Públicas
# ══════════════════════════════════════════════════════════════
echo "── PRUEBA 4: Rutas Públicas ──"

run_test "TC-4.1" \
  "SuperAdmin page accesible" \
  "curl -s -o /dev/null -w '%{http_code}' '$BASE_SUPER/superadmin'" \
  "200"

run_test "TC-4.2" \
  "Tenant login accesible" \
  "curl -s -o /dev/null -w '%{http_code}' '$BASE_TENANT/login'" \
  "200"

echo ""

# ══════════════════════════════════════════════════════════════
# PRUEBA 5: API Module Endpoints
# ══════════════════════════════════════════════════════════════
echo "── PRUEBA 5: API Module Endpoints ──"

run_test "TC-5.1" \
  "API plan/modulos requiere auth" \
  "curl -s -o /dev/null -w '%{http_code}' '$BASE_SUPER/api/central/plan/modulos'" \
  "401"

echo ""

# ══════════════════════════════════════════════════════════════
# PRUEBA 6: Lightpanda DOM Tests
# ══════════════════════════════════════════════════════════════
echo "── PRUEBA 6: Lightpanda DOM ──"

run_test "TC-6.1" \
  "SuperAdmin page tiene formulario login" \
  "lp_dump '$BASE_SUPER/superadmin'" \
  "login"

run_test "TC-6.2" \
  "Spider QA accesible" \
  "curl -s -o /dev/null -w '%{http_code}' '$BASE_SUPER/superadmin/spider'" \
  "200"

echo ""

# ══════════════════════════════════════════════════════════════
# RESUMEN FINAL
# ══════════════════════════════════════════════════════════════
echo ""
echo "══════════════════════════════"
echo "  TOTAL: $((PASS_COUNT + FAIL_COUNT)) tests"
echo "  ✅ PASS: $PASS_COUNT"
echo "  ❌ FAIL: $FAIL_COUNT"
echo "══════════════════════════════"

generate_report
