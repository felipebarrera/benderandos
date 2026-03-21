#!/bin/bash
# tests/helpers.sh — BenderAnd ERP Test Framework (v2 — lightpanda syntax fixed)
# Funciones auxiliares para pruebas UI con lightpanda o curl

BASE_SUPER="http://localhost:8000"
BASE_TENANT="http://demo.localhost:8000"
PASS_COUNT=0
FAIL_COUNT=0

# ── run_test ──────────────────────────────────────────────────
run_test() {
  local TC_ID="$1"
  local DESCRIPTION="$2"
  local COMMAND="$3"
  local EXPECTED="$4"

  echo "▶ $TC_ID — $DESCRIPTION"

  OUTPUT=$(eval "$COMMAND" 2>&1)
  EXIT_CODE=$?

  {
    echo "═══════════════════════════════════════"
    echo "$TC_ID — $DESCRIPTION"
    echo "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "Esperado: \"$EXPECTED\""
    echo "Exit code: $EXIT_CODE"
    echo "═══════════════════════════════════════"
    echo ""
    echo "--- SALIDA COMPLETA ---"
    echo "$OUTPUT"
  } > "tests/results/${TC_ID}.log"

  if echo "$OUTPUT" | grep -q "$EXPECTED"; then
    echo "  ✅ PASS"
    echo "PASS|$TC_ID|$DESCRIPTION" >> tests/results/summary.log
    ((PASS_COUNT++))
  else
    echo "  ❌ FAIL — esperado: '$EXPECTED'"
    echo "  Salida (primeras 3 líneas): $(echo "$OUTPUT" | head -3)"
    echo "FAIL|$TC_ID|$DESCRIPTION|esperado:$EXPECTED" >> tests/results/summary.log
    cp "tests/results/${TC_ID}.log" "tests/errors/${TC_ID}.error.log"
    ((FAIL_COUNT++))
  fi
}

# ── check_http ────────────────────────────────────────────────
# Verifica HTTP status code. Usa curl (siempre disponible).
check_http() {
  local LABEL="$1"
  local URL="$2"
  local EXPECTED_CODE="$3"

  local ACTUAL
  ACTUAL=$(curl -s -o /dev/null -w "%{http_code}" --max-time 8 "$URL" 2>/dev/null)

  if [ "$ACTUAL" = "$EXPECTED_CODE" ]; then
    echo "  ✅ $LABEL (HTTP $ACTUAL)"
    echo "PASS|$LABEL|HTTP $ACTUAL" >> tests/results/summary.log
    ((PASS_COUNT++))
  else
    echo "  ❌ $LABEL — esperado HTTP $EXPECTED_CODE, obtenido HTTP $ACTUAL"
    echo "FAIL|$LABEL|esperado:$EXPECTED_CODE|obtenido:$ACTUAL" >> tests/results/summary.log
    ((FAIL_COUNT++))
  fi
}

# ── api_post ──────────────────────────────────────────────────
api_post() {
  local URL="$1"
  local BODY="$2"
  curl -s -X POST "$URL" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$BODY" 2>/dev/null
}

# ── lp_fetch ──────────────────────────────────────────────────
# Wrapper correcto para lightpanda (sintaxis posicional, NO --url)
lp_fetch() {
  local URL="$1"
  lightpanda fetch "$URL" 2>&1
}

# ── lp_dump ───────────────────────────────────────────────────
# Obtener HTML completo con lightpanda
lp_dump() {
  local URL="$1"
  lightpanda fetch --dump html "$URL" 2>&1
}

# ── generate_report ───────────────────────────────────────────
generate_report() {
  local FILE="tests/report/$(date +%Y-%m-%d).md"
  local PASS_TOTAL=$(grep -c "^PASS" tests/results/summary.log 2>/dev/null || echo 0)
  local FAIL_TOTAL=$(grep -c "^FAIL" tests/results/summary.log 2>/dev/null || echo 0)

  cat > "$FILE" <<EOF
# 🧪 Reporte de Pruebas UI — $(date +%Y-%m-%d)

## Resumen
- **$PASS_TOTAL** ✅ pasaron
- **$FAIL_TOTAL** ❌ fallaron
- **Total:** $((PASS_TOTAL + FAIL_TOTAL)) tests

## Tests fallidos
EOF

  grep "^FAIL" tests/results/summary.log 2>/dev/null | while IFS='|' read -r status id desc expected; do
    echo "" >> "$FILE"
    echo "### ❌ $id — $desc" >> "$FILE"
    echo "> Esperado: \`$expected\`" >> "$FILE"
    echo "" >> "$FILE"
    echo '```' >> "$FILE"
    head -20 "tests/errors/${id}.error.log" 2>/dev/null || echo "Log no disponible" >> "$FILE"
    echo '```' >> "$FILE"
  done

  echo "" >> "$FILE"
  echo "## Tests exitosos" >> "$FILE"
  grep "^PASS" tests/results/summary.log 2>/dev/null | while IFS='|' read -r status id desc; do
    echo "- ✅ $id — $desc" >> "$FILE"
  done

  echo ""
  echo "📄 Reporte generado: $FILE"
}
