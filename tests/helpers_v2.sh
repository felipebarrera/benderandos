#!/bin/bash
# helpers_v2.sh — BenderAnd Spider QA v3

DOCKER_APP="benderandos_app"
DOCKER_PG="benderandos_pg"
PG_USER="postgres"
PG_DB="benderandos_rest"
BASE_SUPER="http://localhost:8000"
PASS_COUNT=0
FAIL_COUNT=0
RESULTS_DIR="tests/results"
ERRORS_DIR="tests/errors"
REPORT_DIR="tests/report"

mkdir -p "$RESULTS_DIR" "$ERRORS_DIR" "$REPORT_DIR"

init_bug_table() {
  echo "# Reporte de Bugs — $(date +%Y-%m-%d)" > tests/BUGS.md
  echo "" >> tests/BUGS.md
}

run_test() {
  local ID="$1"
  local DESC="$2"
  local CMD="$3"
  local EXPECT="$4"

  echo -n "▶ $ID — $DESC: "
  
  OUTPUT=$(eval "$CMD" 2>&1)
  status=$?

  echo "$OUTPUT" > "$RESULTS_DIR/$ID.log"

  if [ $status -eq 0 ] && echo "$OUTPUT" | grep -qE "$EXPECT"; then
    echo "✅ PASS"
    echo "PASS|$ID|$DESC" >> "$RESULTS_DIR/summary.log"
    ((PASS_COUNT++))
  else
    echo "❌ FAIL"
    echo "FAIL|$ID|$DESC|esperado:$EXPECT" >> "$RESULTS_DIR/summary.log"
    echo "$OUTPUT" > "$ERRORS_DIR/$ID.error.log"
    ((FAIL_COUNT++))
  fi
}

lp_dom() {
  local ID="$1"
  local DESC="$2"
  local URL="$3"
  local SELECTOR="$4"
  
  run_test "$ID" "$DESC" "lightpanda fetch --dump html $URL | grep -c '$SELECTOR'" "1"
}

generate_report() {
  local REPORT_FILE="$REPORT_DIR/$(date +%Y-%m-%d).md"
  cat > "$REPORT_FILE" << EOF
# 🧪 Reporte Spider QA v3 — $(date +%Y-%m-%d)

## Resumen
- **Total:** $((PASS_COUNT + FAIL_COUNT))
- **✅ PASS:** $PASS_COUNT
- **❌ FAIL:** $FAIL_COUNT

## Detalle de Fallos
$(grep "FAIL" "$RESULTS_DIR/summary.log" | while IFS='|' read status id desc err; do
  echo "### ❌ $id — $desc"
  echo "**Error:** $err"
  echo ""
  echo "\`\`\`"
  head -n 20 "$ERRORS_DIR/$id.error.log" 2>/dev/null
  echo "\`\`\`"
  echo ""
done)

## Listado Completo
$(cat "$RESULTS_DIR/summary.log" | sed 's/PASS|/- ✅ /' | sed 's/FAIL|/- ❌ /')
EOF
  echo "📄 Reporte generado: $REPORT_FILE"
}
