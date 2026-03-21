#!/bin/bash
# tests/diagnose_tenant.sh — FASE 0: Diagnóstico y auto-reparación de la DB del tenant demo
# Ejecutar antes de la suite de tests para asegurar que el ambiente está listo.

echo "╔══════════════════════════════════════════╗"
echo "║  FASE 0 — Diagnóstico Tenant Demo        ║"
echo "╚══════════════════════════════════════════╝"
echo ""

DOCKER_APP="benderandos_app"
DOCKER_PG="benderandos_pg"
DB_USER="benderand"
DB_CENTRAL="benderand"

# ── 1. Verificar /etc/hosts (BUG-009) ──
echo "── 1. /etc/hosts ──"
if grep -q "demo.localhost" /etc/hosts; then
    echo "  ✅ demo.localhost ya está en /etc/hosts"
else
    echo "  ⚠️  demo.localhost NO está en /etc/hosts"
    echo "  Para agregar, ejecuta: echo '127.0.0.1 demo.localhost' | sudo tee -a /etc/hosts"
fi
echo ""

# ── 2. Verificar tenants existentes ──
echo "── 2. Tenants existentes ──"
TENANT_INFO=$(docker exec $DOCKER_APP php artisan tinker --execute="\$t = \App\Models\Central\Tenant::whereHas('domains', function(\$q){\$q->where('domain','demo.localhost');})->first(); echo \$t ? \$t->id . ',' . \$t->tenancy_db_name : 'NOT_FOUND';" 2>/dev/null | tail -n1)

if [ "$TENANT_INFO" != "NOT_FOUND" ]; then
    TENANT_ID=$(echo "$TENANT_INFO" | cut -d',' -f1)
    TENANT_DB=$(echo "$TENANT_INFO" | cut -d',' -f2)
    echo "  ✅ Tenant '$TENANT_ID' existe para demo.localhost"
else
    echo "  ❌ Tenant para demo.localhost no existe — creando..."
    docker exec $DOCKER_APP php artisan tinker --execute="
        \$t = \App\Models\Central\Tenant::create(['id' => 'demo']);
        \$t->domains()->create(['domain' => 'demo.localhost']);
    " 2>/dev/null
    docker exec $DOCKER_APP php artisan tenants:migrate --tenants=demo 2>/dev/null
    TENANT_ID="demo"
    TENANT_DB="tenantdemo" # Typical default, though usually it gets determined by tinker again
fi
echo ""

# ── 3. Verificar dominios ──
echo "── 3. Dominios registrados ──"
docker exec $DOCKER_PG psql -U $DB_USER -d $DB_CENTRAL -c "SELECT domain, tenant_id FROM domains;" 2>/dev/null
echo ""

# ── 4. Verificar DB del tenant ──
echo "── 4. DB del tenant: $TENANT_DB ──"
if [ -n "$TENANT_DB" ]; then
    USER_COUNT=$(docker exec $DOCKER_PG psql -U $DB_USER -d "$TENANT_DB" -t -c "SELECT COUNT(*) FROM usuarios;" 2>/dev/null | tr -d ' ')
    echo "  Usuarios en DB: $USER_COUNT"
    
    if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
        echo "  ⚠️  Sin usuarios — ejecutando TenantSeeder para tenant $TENANT_ID..."
        docker exec $DOCKER_APP php artisan tenants:seed --class=TenantSeeder --tenants="$TENANT_ID" 2>/dev/null
        echo "  Verificando nuevamente..."
        USER_COUNT=$(docker exec $DOCKER_PG psql -U $DB_USER -d "$TENANT_DB" -t -c "SELECT COUNT(*) FROM usuarios;" 2>/dev/null | tr -d ' ')
        echo "  Usuarios ahora: $USER_COUNT"
    fi
    
    echo ""
    echo "  Usuarios existentes:"
    docker exec $DOCKER_PG psql -U $DB_USER -d "$TENANT_DB" -c "SELECT id, email, nombre, rol FROM usuarios LIMIT 5;" 2>/dev/null
fi
echo ""

# ── 5. Verificar SuperAdmin seeder (BUG-004) ──
echo "── 5. SuperAdmin ──"
SA_COUNT=$(docker exec $DOCKER_PG psql -U $DB_USER -d $DB_CENTRAL -t -c "SELECT COUNT(*) FROM super_admins;" 2>/dev/null | tr -d ' ')
echo "  SuperAdmins en DB: $SA_COUNT"
if [ "$SA_COUNT" = "0" ] || [ -z "$SA_COUNT" ]; then
    echo "  ⚠️  Sin SuperAdmins — ejecutando seeder..."
    docker exec $DOCKER_APP php artisan db:seed --class=SuperAdminSeeder
fi
echo ""

# ── 6. Verificar plan_modulos (BUG-011) ──
echo "── 6. Plan Módulos (H19) ──"
PM_COUNT=$(docker exec $DOCKER_PG psql -U $DB_USER -d $DB_CENTRAL -t -c "SELECT COUNT(*) FROM plan_modulos;" 2>/dev/null | tr -d ' ')
echo "  Módulos en plan_modulos: $PM_COUNT"
if [ "$PM_COUNT" = "0" ] || [ -z "$PM_COUNT" ]; then
    echo "  ⚠️  Sin módulos — ejecutando migración..."
    docker exec $DOCKER_APP php artisan migrate
fi
echo ""

# ── 7. Verificar bug_reports table (BUG-008) ──
echo "── 7. Bug Reports ──"
BR_EXISTS=$(docker exec $DOCKER_PG psql -U $DB_USER -d $DB_CENTRAL -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_name='bug_reports';" 2>/dev/null | tr -d ' ')
echo "  Tabla bug_reports existe: $BR_EXISTS"
if [ "$BR_EXISTS" = "0" ] || [ -z "$BR_EXISTS" ]; then
    echo "  ⚠️  Tabla no existe — ejecutando migración..."
    docker exec $DOCKER_APP php artisan migrate
fi
echo ""

# ── 8. Test rápido de login ──
echo "── 8. Test Login ──"

echo -n "  SA Login: "
SA_RESULT=$(curl -s -X POST http://localhost:8000/api/superadmin/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@benderand.cl","password":"password"}' 2>/dev/null)
echo "$SA_RESULT" | grep -q "token" && echo "✅ OK" || echo "❌ FALLO — $SA_RESULT"

echo -n "  Tenant Login: "
TENANT_RESULT=$(curl -s -X POST http://demo.localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@benderand.cl","password":"admin1234"}' 2>/dev/null)
echo "$TENANT_RESULT" | grep -q "token" && echo "✅ OK" || echo "❌ FALLO — $TENANT_RESULT"

echo ""
echo "══════════════════════════════"
echo "  FASE 0 completada"
echo "══════════════════════════════"

# Guardar config para el runner
mkdir -p tests/results
cat > tests/results/tenant_config.env <<EOF
TENANT_ID=demo
TENANT_DB=$TENANT_DB
TENANT_URL=http://demo.localhost:8000
SA_URL=http://localhost:8000
EOF
echo "Config guardada en tests/results/tenant_config.env"
