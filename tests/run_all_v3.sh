#!/bin/bash
# BenderAnd run_all_v3.sh
# Corre diagnose_tenant.sh como FASE 0 antes de todos los tests
# luego ejecuta la suite completa usando la config real del tenant

source "$(dirname "$0")/helpers_v2.sh"

echo "╔══════════════════════════════════════════════════════╗"
echo "║  BenderAnd — Suite Completa v3                       ║"
echo "║  $RUN_TS                         ║"
echo "╚══════════════════════════════════════════════════════╝"

init_bug_table
> tests/results/summary.log

# ═══════════════════════════════════════════════════════════════════════════════
# FASE 0 — Diagnóstico y fix automático del tenant
# ═══════════════════════════════════════════════════════════════════════════════
echo ""
echo "╔══ FASE 0: DIAGNÓSTICO TENANT ════════════════════════╗"
bash "$(dirname "$0")/diagnose_tenant.sh"

# Cargar config real del tenant detectado
if [ -f tests/results/tenant_config.env ]; then
  source tests/results/tenant_config.env

# Cargar credenciales generadas por gen_credentials.sh
[ -f tests/credentials.env ] && source tests/credentials.env && echo "  credenciales cargadas desde tests/credentials.env"
  echo "  Tenant activo: $TENANT_ID ($TENANT_DB) — $TENANT_URL"
else
  TENANT_URL="$BASE_TENANT"
  warn "tenant_config.env no generado — usando URL por defecto"
fi
echo ""

# ═══════════════════════════════════════════════════════════════════════════════
# Obtener tokens con URL real del tenant
# ═══════════════════════════════════════════════════════════════════════════════
echo "── Obteniendo tokens ───────────────────────────────────"
SA_TOKEN=$(curl -s -X POST "$BASE_SUPER/api/superadmin/login" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin@benderand.cl","password":"password"}' 2>/dev/null \
  | grep -oP '"token":"\K[^"]+')
T_TOKEN=$(curl -s -X POST "$TENANT_URL/api/login" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin@benderand.cl","password":"admin1234"}' 2>/dev/null \
  | grep -oP '"token":"\K[^"]+')

[ -n "$SA_TOKEN" ] && echo "  ✅ SA token OK" \
  || { echo "  ❌ SA token FALLO"; register_bug "BUG-SA-LOGIN" "TC-FASE0" "E-AUTH" "db" "SA login falla" "" "$BASE_SUPER/api/superadmin/login" "200" "401" "critico"; }
[ -n "$T_TOKEN"  ] && echo "  ✅ Tenant token OK ($TENANT_URL)" \
  || { echo "  ❌ Tenant token FALLO ($TENANT_URL)"; register_bug "BUG-T-LOGIN" "TC-FASE0" "E-AUTH" "db" "Tenant login falla en $TENANT_URL" "Diagnose_tenant debió corregir esto" "$TENANT_URL/api/login" "200" "401" "critico"; }
echo ""

# ═══════════════════════════════════════════════════════════════════════════════
# PRUEBA 1 — SuperAdmin
# ═══════════════════════════════════════════════════════════════════════════════
echo "╔══ PRUEBA 1: SUPERADMIN ═══════════════════════════════╗"
api_post "TC-SA-01" "SA login exitoso" \
  "$BASE_SUPER/api/superadmin/login" \
  '{"email":"admin@benderand.cl","password":"password"}' "token"
api_post "TC-SA-02" "SA password incorrecto -> errors" \
  "$BASE_SUPER/api/superadmin/login" \
  '{"email":"admin@benderand.cl","password":"WRONG123"}' "errors"
check_http "TC-SA-03" "SA UI /superadmin HTTP 200" \
  "$BASE_SUPER/superadmin" "200" "E-UI" "ui" "alto"
check_http "TC-SA-04" "SA dashboard sin token -> 401" \
  "$BASE_SUPER/api/superadmin/dashboard" "401" "E-PERM" "laravel" "critico"
[ -n "$SA_TOKEN" ] && run_test "TC-SA-05" "SA dashboard con token -> 200" \
  "curl -s -o /dev/null -w '%{http_code}' $BASE_SUPER/api/superadmin/dashboard \
   -H 'Authorization: Bearer $SA_TOKEN' -H 'Accept: application/json'" "200"
[ -n "$SA_TOKEN" ] && run_test "TC-SA-06" "SA tenants lista -> 200" \
  "curl -s -o /dev/null -w '%{http_code}' $BASE_SUPER/api/superadmin/tenants \
   -H 'Authorization: Bearer $SA_TOKEN' -H 'Accept: application/json'" "200"
echo ""

# ═══════════════════════════════════════════════════════════════════════════════
# PRUEBA 2 — Tenant (con URL real detectada)
# ═══════════════════════════════════════════════════════════════════════════════
echo "╔══ PRUEBA 2: TENANT ($TENANT_URL) ════════╗"
api_post "TC-TA-01" "Tenant login exitoso" \
  "$TENANT_URL/api/login" \
  '{"email":"admin@benderand.cl","password":"admin1234"}' "token"
check_http "TC-TA-02" "Tenant /login HTTP 200" \
  "$TENANT_URL/login" "200" "E-UI" "ui" "alto"
check_http "TC-TA-03" "Tenant /admin/login redirect 301|302|200" \
  "$TENANT_URL/admin/login" "301|302|200" "E-REDIRECT" "laravel" "medio"
check_http "TC-TA-04" "Tenant dashboard sin sesion -> 302|301" \
  "$TENANT_URL/admin/dashboard" "302|301" "E-PERM" "laravel" "critico"
[ -n "$T_TOKEN" ] && run_test "TC-TA-05" "Tenant dashboard con token -> 200" \
  "curl -s -o /dev/null -w '%{http_code}' $TENANT_URL/api/dashboard \
   -H 'Authorization: Bearer $T_TOKEN' -H 'Accept: application/json'" "200"
echo ""

# ═══════════════════════════════════════════════════════════════════════════════
# PRUEBA 3 — Permisos / Roles
# ═══════════════════════════════════════════════════════════════════════════════
echo "╔══ PRUEBA 3: PERMISOS / ROLES ════════════════════════╗"
check_http "TC-ROL-01" "Sin token /api/dashboard -> 401"  "$TENANT_URL/api/dashboard"  "401" "E-PERM" "laravel" "critico"
check_http "TC-ROL-02" "Sin token /api/ventas -> 401"     "$TENANT_URL/api/ventas"     "401" "E-PERM" "laravel" "critico"
check_http "TC-ROL-03" "Sin token /api/productos -> 401"  "$TENANT_URL/api/productos"  "401" "E-PERM" "laravel" "critico"
[ -n "$T_TOKEN" ] && run_test "TC-ROL-04" "Cross-tenant bloqueado (tenant->SA -> 401|403)" \
  "curl -s -o /dev/null -w '%{http_code}' $BASE_SUPER/api/superadmin/dashboard \
   -H 'Authorization: Bearer $T_TOKEN' -H 'Accept: application/json'" "401|403"
echo ""

# ═══════════════════════════════════════════════════════════════════════════════
# PRUEBA 4 — Endpoints API con token
# ═══════════════════════════════════════════════════════════════════════════════
echo "╔══ PRUEBA 4: API ENDPOINTS CON TOKEN ═════════════════╗"
if [ -n "$SA_TOKEN" ]; then
  for EP in dashboard tenants; do
    run_test "TC-API-SA-$EP" "SA /api/superadmin/$EP -> 200" \
      "curl -s -o /dev/null -w '%{http_code}' $BASE_SUPER/api/superadmin/$EP \
       -H 'Authorization: Bearer $SA_TOKEN' -H 'Accept: application/json'" "200"
  done
fi
if [ -n "$T_TOKEN" ]; then
  for EP in dashboard productos ventas clientes; do
    run_test "TC-API-T-$EP" "Tenant /api/$EP -> 200" \
      "curl -s -o /dev/null -w '%{http_code}' $TENANT_URL/api/$EP \
       -H 'Authorization: Bearer $T_TOKEN' -H 'Accept: application/json'" "200"
  done
fi
echo ""

# ═══════════════════════════════════════════════════════════════════════════════
# PRUEBA 5 — Base de datos
# ═══════════════════════════════════════════════════════════════════════════════
echo "╔══ PRUEBA 5: BASE DE DATOS ════════════════════════════╗"
run_test "TC-DB-01" "super_admins tiene registros" \
  "docker exec $DOCKER_PG psql -U $PG_USER -d $PG_DB -t -c 'SELECT COUNT(*) FROM super_admins;' | tr -d ' '" "[1-9]"
run_test "TC-DB-02" "plan_modulos tiene datos" \
  "docker exec $DOCKER_PG psql -U $PG_USER -d $PG_DB -t -c 'SELECT COUNT(*) FROM plan_modulos;' | tr -d ' '" "[1-9]"
run_test "TC-DB-03" "Tenants registrados" \
  "docker exec $DOCKER_PG psql -U $PG_USER -d $PG_DB -t -c 'SELECT COUNT(*) FROM tenants;' | tr -d ' '" "[1-9]"
run_test "TC-DB-04" "bug_reports tabla existe" \
  "docker exec $DOCKER_PG psql -U $PG_USER -d $PG_DB -t -c \
   \"SELECT COUNT(*) FROM information_schema.tables WHERE table_name='bug_reports';\" | tr -d ' '" "1"
# Usuarios en tenant (usa DB detectada por diagnose_tenant)
if [ -n "$TENANT_DB" ] && [ "$TENANT_DB" != "NULL" ]; then
  run_test "TC-DB-05" "Usuarios en tenant DB ($TENANT_DB)" \
    "docker exec $DOCKER_PG psql -U $PG_USER -d $TENANT_DB -t -c 'SELECT COUNT(*) FROM usuarios;' | tr -d ' '" "[1-9]"
fi
echo ""

# ═══════════════════════════════════════════════════════════════════════════════
# PRUEBA 6 — Laravel interno
# ═══════════════════════════════════════════════════════════════════════════════
echo "╔══ PRUEBA 6: LARAVEL INTERNO ══════════════════════════╗"
run_test "TC-LAR-01" "Redis cache OK" \
  "docker exec $DOCKER_APP php artisan tinker --execute=\"Cache::put('spider_ping','ok',10);echo Cache::get('spider_ping');\"" "ok"
run_test "TC-LAR-02" "Sin migraciones pendientes" \
  "docker exec $DOCKER_APP php artisan migrate:status 2>&1 | grep -cE 'Pending'" "0"
run_test "TC-LAR-03" "APP_KEY configurada" \
  "docker exec $DOCKER_APP cat /app/.env | grep APP_KEY | grep -v '^#'" "base64:"
echo ""

# ═══════════════════════════════════════════════════════════════════════════════
# PRUEBA 7 — UI con lightpanda (sintaxis corregida: posicional sin --url)
# ═══════════════════════════════════════════════════════════════════════════════
echo "╔══ PRUEBA 7: UI (lightpanda) ══════════════════════════╗"
lp_dom "TC-UI-01" "SA UI: input login"       "$BASE_SUPER/superadmin" "login-email"
lp_dom "TC-UI-02" "SA UI: funcion doLogin"   "$BASE_SUPER/superadmin" "doLogin"
lp_dom "TC-UI-03" "SA UI: overlay de login"  "$BASE_SUPER/superadmin" "login-overlay"
lp_dom "TC-UI-04" "Tenant UI: form presente" "$TENANT_URL/login"      "password"
echo ""

# ═══════════════════════════════════════════════════════════════════════════════
# REPORTE FINAL
# ═══════════════════════════════════════════════════════════════════════════════
generate_report
