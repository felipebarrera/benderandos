#!/bin/bash
# BenderAnd — gen_credentials.sh
# Genera credentials.env con todas las URLs, usuarios y passwords
# reales descubiertos en la DB. Se llama al final de diagnose_tenant.sh
# y al inicio de run_all.sh. NUNCA commitear — está en .gitignore

DOCKER_APP="benderandos_app"
DOCKER_PG="benderandos_pg"
PG_USER="benderand"
PG_DB="benderand"
OUT="tests/credentials.env"
RUN_TS=$(date "+%Y-%m-%d %H:%M:%S")

mkdir -p tests
> "$OUT"

cat >> "$OUT" << HEADER
# BenderAnd ERP — Credenciales de desarrollo
# Generado automáticamente: $RUN_TS
# NO commitear — agregar a .gitignore
# Regenerar: bash tests/gen_credentials.sh

HEADER

# ── SuperAdmin ────────────────────────────────────────────────────────────────
echo "# ── SUPERADMIN ──────────────────────────────────────" >> "$OUT"
echo "SA_URL=http://localhost:8000/superadmin" >> "$OUT"
echo "SA_API=http://localhost:8000/api/superadmin/login" >> "$OUT"

SA_EMAIL=$(docker exec "$DOCKER_PG" psql -U "$PG_USER" -d "$PG_DB" -t -A -c \
  "SELECT email FROM super_admins LIMIT 1;" 2>/dev/null | tr -d ' ')
echo "SA_EMAIL=${SA_EMAIL:-admin@benderand.cl}" >> "$OUT"
echo "SA_PASSWORD=password" >> "$OUT"
echo "" >> "$OUT"

# ── Tenants ───────────────────────────────────────────────────────────────────
echo "# ── TENANTS ─────────────────────────────────────────" >> "$OUT"

TENANTS=$(docker exec "$DOCKER_PG" psql -U "$PG_USER" -d "$PG_DB" -t -A -F'|' -c \
  "SELECT t.id, COALESCE(t.tenancy_db_name,''), COALESCE(d.domain,'')
   FROM tenants t LEFT JOIN domains d ON d.tenant_id = t.id
   ORDER BY t.id;" 2>/dev/null)

TENANT_COUNT=0
while IFS='|' read -r tid tdb tdom; do
  [ -z "$tid" ] && continue
  ((TENANT_COUNT++))
  PREFIX="TENANT_${TENANT_COUNT}"
  TURL="http://${tdom}:8000"

  echo "# Tenant $TENANT_COUNT: $tid" >> "$OUT"
  echo "${PREFIX}_ID=$tid" >> "$OUT"
  echo "${PREFIX}_DB=$tdb" >> "$OUT"
  echo "${PREFIX}_DOMAIN=$tdom" >> "$OUT"
  echo "${PREFIX}_URL=$TURL" >> "$OUT"
  echo "${PREFIX}_LOGIN_URL=${TURL}/login" >> "$OUT"
  echo "${PREFIX}_API_LOGIN=${TURL}/api/login" >> "$OUT"

  # Leer usuarios reales de la DB del tenant
  if [ -n "$tdb" ] && [ "$tdb" != "NULL" ]; then
    USERS=$(docker exec "$DOCKER_PG" psql -U "$PG_USER" -d "$tdb" -t -A -F'|' -c \
      "SELECT email, COALESCE(rol,'admin') FROM usuarios LIMIT 20;" 2>/dev/null)

    USER_IDX=0
    while IFS='|' read -r uemail urol; do
      [ -z "$uemail" ] && continue
      ((USER_IDX++))
      echo "${PREFIX}_USER_${USER_IDX}_EMAIL=$uemail" >> "$OUT"
      echo "${PREFIX}_USER_${USER_IDX}_ROL=$urol" >> "$OUT"
      # Password por convención: admin1234 para admin, Test1234! para el resto
      if [ "$urol" = "admin" ]; then
        echo "${PREFIX}_USER_${USER_IDX}_PASSWORD=admin1234" >> "$OUT"
      else
        echo "${PREFIX}_USER_${USER_IDX}_PASSWORD=Test1234!" >> "$OUT"
      fi
    done <<< "$USERS"

    echo "${PREFIX}_USER_COUNT=$USER_IDX" >> "$OUT"
  fi
  echo "" >> "$OUT"
done <<< "$TENANTS"

echo "TENANT_COUNT=$TENANT_COUNT" >> "$OUT"
echo "" >> "$OUT"

# ── Docker ────────────────────────────────────────────────────────────────────
echo "# ── DOCKER ──────────────────────────────────────────" >> "$OUT"
echo "DOCKER_APP=$DOCKER_APP" >> "$OUT"
echo "DOCKER_PG=$DOCKER_PG" >> "$OUT"
echo "PG_USER=$PG_USER" >> "$OUT"
echo "PG_DB=$PG_DB" >> "$OUT"
echo "" >> "$OUT"

# ── Tokens activos (obtenidos ahora) ─────────────────────────────────────────
echo "# ── TOKENS ACTIVOS (regenerar si expiran) ───────────" >> "$OUT"
SA_TOKEN=$(curl -s -X POST "http://localhost:8000/api/superadmin/login" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d "{\"email\":\"${SA_EMAIL:-admin@benderand.cl}\",\"password\":\"password\"}" 2>/dev/null \
  | grep -oP '"token":"\K[^"]+')
echo "SA_TOKEN=${SA_TOKEN:-}" >> "$OUT"

# Token del primer tenant
FIRST_TENANT_URL=$(grep "^TENANT_1_API_LOGIN=" "$OUT" | cut -d'=' -f2)
if [ -n "$FIRST_TENANT_URL" ]; then
  FIRST_EMAIL=$(grep "^TENANT_1_USER_1_EMAIL=" "$OUT" | cut -d'=' -f2)
  FIRST_PASS=$(grep "^TENANT_1_USER_1_PASSWORD=" "$OUT" | cut -d'=' -f2)
  T_TOKEN=$(curl -s -X POST "$FIRST_TENANT_URL" \
    -H "Content-Type: application/json" -H "Accept: application/json" \
    -d "{\"email\":\"$FIRST_EMAIL\",\"password\":\"$FIRST_PASS\"}" 2>/dev/null \
    | grep -oP '"token":"\K[^"]+')
  echo "TENANT_1_TOKEN=${T_TOKEN:-}" >> "$OUT"
fi
echo "" >> "$OUT"

# ── Resumen legible ───────────────────────────────────────────────────────────
echo "# ── RESUMEN LEGIBLE ─────────────────────────────────" >> "$OUT"
echo "# SuperAdmin:  http://localhost:8000/superadmin" >> "$OUT"
echo "#   ${SA_EMAIL:-admin@benderand.cl} / password" >> "$OUT"
echo "#" >> "$OUT"

while IFS='|' read -r tid tdb tdom; do
  [ -z "$tid" ] && continue
  echo "# Tenant $tid:  http://${tdom}:8000/login" >> "$OUT"
  if [ -n "$tdb" ] && [ "$tdb" != "NULL" ]; then
    USUM=$(docker exec "$DOCKER_PG" psql -U "$PG_USER" -d "$tdb" -t -A -F'|' -c \
      "SELECT email, COALESCE(rol,'?') FROM usuarios LIMIT 5;" 2>/dev/null)
    while IFS='|' read -r ue ur; do
      [ -z "$ue" ] && continue
      PW="Test1234!"; [ "$ur" = "admin" ] && PW="admin1234"
      echo "#   $ue / $PW  [$ur]" >> "$OUT"
    done <<< "$USUM"
  fi
  echo "#" >> "$OUT"
done <<< "$TENANTS"

# ── Asegurar .gitignore ───────────────────────────────────────────────────────
GITIGNORE="/app/.gitignore"
docker exec "$DOCKER_APP" bash -c "grep -q 'credentials.env' $GITIGNORE 2>/dev/null \
  || echo 'tests/credentials.env' >> $GITIGNORE" 2>/dev/null
# También en host
GITIGNORE_HOST="$(dirname "$OUT")/../.gitignore"
grep -q "credentials.env" "$GITIGNORE_HOST" 2>/dev/null \
  || echo "tests/credentials.env" >> "$GITIGNORE_HOST" 2>/dev/null

# ── Salida ────────────────────────────────────────────────────────────────────
echo "✅ Credenciales generadas: $OUT"
echo ""
echo "── Resumen ──────────────────────────────────────────────"
grep "^#" "$OUT" | grep -v "^# ──\|^# Tenant [0-9]\|^# $" | head -30
echo "─────────────────────────────────────────────────────────"
