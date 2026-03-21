# 🐛 BenderAnd ERP — Estrategia de Errores en Pruebas UI
**Complemento de:** `TEST_ACCESO_UI_BENDERAND.md`  
**Herramienta de prueba:** lightpanda browser  
**Fecha:** Marzo 2026

---

## 1. Cómo captura errores lightpanda

Lightpanda no tiene un sistema de reporte propio — los errores se capturan **redirigiendo la salida** a archivos. La estrategia es simple: cada prueba escribe su resultado en un log, y un script final consolida todo.

### 1.1 Estructura de carpetas de logs

```
tests/
├── run_all.sh              ← script maestro que ejecuta todo
├── results/
│   ├── TC-1.1.log          ← salida cruda de cada test
│   ├── TC-1.2.log
│   ├── ...
│   └── summary.log         ← resumen consolidado al final
├── errors/
│   ├── TC-2.3.error.log    ← solo los que fallaron
│   └── ...
└── report/
    └── 2026-03-16.md       ← reporte final del día (auto-generado)
```

### 1.2 Cómo se escribe cada log

```bash
# Cada test redirige stdout y stderr al mismo archivo de log
lightpanda fetch \
  --url "http://localhost:3000/admin/config" \
  --cookie "session=$COOKIE_CAJERO" \
  2>&1 | tee tests/results/TC-8.1.log

# El "2>&1" captura tanto la salida normal como los errores del proceso
# El "tee" muestra en pantalla Y guarda en archivo al mismo tiempo
```

### 1.3 Script wrapper por test

Cada test individual se envuelve en una función que registra PASS o FAIL:

```bash
#!/bin/bash
# tests/helpers.sh

BASE_URL="http://localhost:3000"
PASS_COUNT=0
FAIL_COUNT=0

run_test() {
  local TC_ID="$1"      # ej: "TC-8.1"
  local DESCRIPTION="$2" # ej: "Cajero no puede acceder a /admin/config"
  local COMMAND="$3"    # ej: el comando lightpanda completo
  local EXPECTED="$4"   # ej: "403" o "Paciente" o "redirect:/login"

  echo "▶ $TC_ID — $DESCRIPTION"

  # Ejecutar y capturar salida
  OUTPUT=$(eval "$COMMAND" 2>&1)
  EXIT_CODE=$?

  # Guardar log completo
  echo "$OUTPUT" > "tests/results/${TC_ID}.log"

  # Verificar si la salida contiene el resultado esperado
  if echo "$OUTPUT" | grep -q "$EXPECTED"; then
    echo "  ✅ PASS"
    echo "PASS|$TC_ID|$DESCRIPTION" >> tests/results/summary.log
    ((PASS_COUNT++))
  else
    echo "  ❌ FAIL — esperado: '$EXPECTED'"
    echo "  Salida: $(echo "$OUTPUT" | head -5)"
    echo "FAIL|$TC_ID|$DESCRIPTION|esperado:$EXPECTED" >> tests/results/summary.log
    cp "tests/results/${TC_ID}.log" "tests/errors/${TC_ID}.error.log"
    ((FAIL_COUNT++))
  fi
}
```

### 1.4 Script maestro `run_all.sh`

```bash
#!/bin/bash
source tests/helpers.sh

# Limpiar resultados anteriores
mkdir -p tests/results tests/errors tests/report
rm -f tests/results/*.log tests/errors/*.log
echo "" > tests/results/summary.log

DATE=$(date +%Y-%m-%d)
echo "# Resultados de pruebas — $DATE" >> tests/results/summary.log
echo "" >> tests/results/summary.log

# ── PRUEBA 1: Super Admin ──────────────────────────────────
run_test "TC-1.1" \
  "Super admin ve dashboard global" \
  "lightpanda fetch --url '$BASE_URL/dashboard' --cookie 'session=$COOKIE_SUPERADMIN'" \
  "todos-los-tenants"

run_test "TC-1.2" \
  "Gerente ve MRR pero no impersonar" \
  "lightpanda fetch --url '$BASE_URL/dashboard' --cookie 'session=$COOKIE_GERENTE' --js 'document.querySelector(\"[data-action=impersonar]\")'" \
  "null"  # no debe existir el botón

# ── PRUEBA 8: Accesos denegados ────────────────────────────
run_test "TC-8.1" \
  "Cajero no accede a /admin/config" \
  "lightpanda fetch --url '$BASE_URL/admin/config' --cookie 'session=$COOKIE_CAJERO_PADEL' --dump-headers" \
  "403"

run_test "TC-8.7" \
  "Sin sesión redirige a /login" \
  "lightpanda fetch --url '$BASE_URL/pos' --dump-headers" \
  "Location: /login"

# ... (todos los demás tests)

# ── RESUMEN FINAL ──────────────────────────────────────────
echo ""
echo "══════════════════════════════"
echo "  TOTAL: $((PASS_COUNT + FAIL_COUNT)) tests"
echo "  ✅ PASS: $PASS_COUNT"
echo "  ❌ FAIL: $FAIL_COUNT"
echo "══════════════════════════════"

# Generar reporte markdown del día
generate_report
```

---

## 2. Qué contiene cada archivo de error

Cuando un test falla, `tests/errors/TC-X.X.error.log` contiene:

```
═══════════════════════════════════════
TC-8.1 — Cajero no puede acceder a /admin/config
Fecha: 2026-03-16 14:32:11
Esperado: "403"
Obtenido: HTTP 200
═══════════════════════════════════════

--- SALIDA COMPLETA ---
HTTP/1.1 200 OK
Content-Type: text/html

<!DOCTYPE html>
<html>
  <title>Config — BenderAnd</title>
  ...
  <!-- El cajero SÍ pudo entrar — esto es el bug -->
```

Esto da el contexto exacto: **qué se esperaba**, **qué llegó**, y el **HTML/headers completos** para diagnosticar.

---

## 3. Reporte diario auto-generado

Al final del `run_all.sh` se llama `generate_report` que produce un `.md`:

```bash
generate_report() {
  local FILE="tests/report/$(date +%Y-%m-%d).md"

  cat > "$FILE" << EOF
# 🧪 Reporte de Pruebas UI — $(date +%Y-%m-%d)

## Resumen
$(grep -c "^PASS" tests/results/summary.log) ✅ pasaron  
$(grep -c "^FAIL" tests/results/summary.log) ❌ fallaron

## Tests fallidos
$(grep "^FAIL" tests/results/summary.log | while IFS='|' read status id desc expected; do
  echo "### ❌ $id — $desc"
  echo "> Esperado: \`$expected\`"
  echo ""
  echo "\`\`\`"
  head -20 "tests/errors/${id}.error.log" 2>/dev/null || echo "Log no disponible"
  echo "\`\`\`"
  echo ""
done)

## Tests exitosos
$(grep "^PASS" tests/results/summary.log | sed 's/PASS|/- ✅ /' | sed 's/|.*//')
EOF

  echo "📄 Reporte generado: $FILE"
}
```

---

## 4. Taxonomía de errores — cómo clasificarlos

Antes de corregir, clasificar el error en una de estas categorías:

| Código | Tipo | Descripción | Urgencia |
|---|---|---|---|
| `E-AUTH` | Autenticación | Login no funciona, token inválido | 🔴 Crítico |
| `E-PERM` | Permisos | Rol accede a lo que no debería (o no puede acceder a lo que sí debería) | 🔴 Crítico |
| `E-UI` | Interfaz | Elemento no visible, etiqueta incorrecta, botón ausente | 🟡 Medio |
| `E-LABEL` | Etiqueta | Texto incorrecto para el rubro (ej: "Cliente" en vez de "Paciente") | 🟡 Medio |
| `E-REDIRECT` | Redirección | No redirige al dashboard correcto después del login | 🟡 Medio |
| `E-DOM` | DOM/Frontend | Selector no existe, componente no renderiza | 🟠 Alto |
| `E-HTTP` | HTTP | Status code incorrecto (ej: 200 cuando debería ser 403) | 🔴 Crítico |
| `E-DATA` | Datos | Muestra datos de otro tenant, datos vacíos incorrectos | 🔴 Crítico |

---

## 5. Flujo de resolución de errores

```
Error detectado (FAIL en log)
         │
         ▼
   ┌─────────────┐
   │ Clasificar  │  → ¿Es E-AUTH o E-PERM o E-DATA?
   │ el tipo     │       SÍ → Prioridad crítica, arreglar HOY
   └─────────────┘       NO → Continuar flujo normal
         │
         ▼
   ┌─────────────────────┐
   │ Reproducir          │  → Correr solo ese TC manualmente
   │ el error aislado    │     lightpanda fetch [params del TC]
   └─────────────────────┘
         │
         ▼
   ┌─────────────────────┐
   │ Identificar         │  → ¿Falla en backend (HTTP)?
   │ la capa del error   │     ¿Falla en frontend (DOM)?
   └─────────────────────┘     ¿Falla en datos (seed)?
         │
         ├── Backend → revisar middleware de permisos / guards de ruta
         ├── Frontend → revisar condicionales de render por rol/rubro
         ├── Datos → revisar seed, rubros_config, asignación de rol
         │
         ▼
   ┌─────────────────────┐
   │ Corregir y          │  → Commit con referencia al TC
   │ hacer el fix        │     "fix: TC-8.1 cajero no debe acceder admin"
   └─────────────────────┘
         │
         ▼
   ┌─────────────────────┐
   │ Re-correr solo      │  → bash tests/run_single.sh TC-8.1
   │ el test afectado    │
   └─────────────────────┘
         │
         ▼
   ┌─────────────────────┐
   │ Re-correr suite     │  → bash tests/run_all.sh
   │ completa            │     verificar que no se rompió otro test
   └─────────────────────┘
         │
         ▼
      ✅ Cerrar
```

---

## 6. Dónde buscar el bug según el tipo de error

### E-PERM — El rol accede a donde no debería

```
Archivos a revisar:
├── middleware/auth.js (o auth.ts)    ← guard de sesión
├── middleware/rbac.js                ← control de roles por ruta
├── routes/admin.js                  ← definición de rutas con restricción
└── config/roles.js                  ← matriz de permisos por rol
```

```javascript
// Ejemplo: guard que falta o está mal configurado
// ANTES (bug) — cualquier usuario autenticado entra
router.get('/admin/config', isAuthenticated, handler)

// DESPUÉS (fix) — solo admin puede entrar
router.get('/admin/config', isAuthenticated, requireRole(['admin', 'super_admin']), handler)
```

### E-LABEL — Etiqueta incorrecta en UI

```
Archivos a revisar:
├── config/rubros.js          ← rubros_config con etiqueta_cliente / etiqueta_operario
├── i18n/labels.js            ← si usa sistema de traducciones
└── components/ClienteTag.jsx ← componente que renderiza la etiqueta
```

```javascript
// En rubros_config, verificar que está definido:
medico: {
  etiqueta_cliente: 'Paciente',   // ← ¿está presente?
  etiqueta_operario: 'Médico',
  ...
}

// En el componente:
const label = rubrosConfig[tenant.rubro].etiqueta_cliente ?? 'Cliente'
// Si etiqueta_cliente es undefined, cae en "Cliente" — ese puede ser el bug
```

### E-DOM — Elemento no existe en el DOM

```
Proceso de diagnóstico:
1. Guardar HTML completo del test fallido
2. Buscar el selector esperado en el HTML guardado
3. Si no existe → el componente no se renderizó (problema de condición o estado)
4. Si existe pero lightpanda no lo encuentra → problema de timing (JS async)
```

```bash
# Guardar HTML completo para inspección
lightpanda fetch --url "$BASE_URL/pos" \
  --cookie "session=$COOKIE_MEDICO" \
  --dump-dom > tests/debug/TC-6.2.html

# Buscar el elemento esperado en el HTML guardado
grep -n "notas-cifradas" tests/debug/TC-6.2.html
# Si no aparece → el componente no renderizó
```

### E-DATA — Muestra datos de otro tenant

```
Archivos a revisar:
├── middleware/tenant.js       ← extrae tenant_id del usuario
└── queries/ventas.js          ← ¿filtra por tenant_id en el WHERE?

-- SQL ejemplo bug:
SELECT * FROM ventas WHERE user_id = ?
-- falta: AND tenant_id = ?

-- SQL fix:
SELECT * FROM ventas WHERE user_id = ? AND tenant_id = ?
```

---

## 7. Registro de errores conocidos (bug tracker minimalista)

Mientras no haya un Jira o sistema externo, usar este formato en `tests/BUGS.md`:

```markdown
# BUGS activos

## BUG-001
- **TC:** TC-8.1
- **Tipo:** E-PERM
- **Descripción:** Cajero de pádel puede acceder a /admin/config (debería ser 403)
- **Encontrado:** 2026-03-16
- **Estado:** 🔴 Abierto
- **Asignado a:** —
- **Fix:** pendiente — revisar middleware/rbac.js ruta /admin/*

---

## BUG-002
- **TC:** TC-6.3
- **Tipo:** E-LABEL
- **Descripción:** Recepcionista de clínica ve texto "Cliente" en vez de "Paciente"
- **Encontrado:** 2026-03-16
- **Estado:** 🟡 En progreso
- **Asignado a:** dev frontend
- **Fix:** agregar `etiqueta_cliente` en rubros_config médico

---

## RESUELTOS

## BUG-003 ✅
- **TC:** TC-10.7
- **Tipo:** E-REDIRECT
- **Descripción:** Sin sesión no redirigía a /login en ruta /pos
- **Resuelto:** 2026-03-16 — commit abc1234
```

---

## 8. Comandos de diagnóstico rápido

```bash
# Ver solo los tests que fallaron
grep "^FAIL" tests/results/summary.log

# Contar cuántos fallaron por tipo de prueba
grep "^FAIL" tests/results/summary.log | grep "TC-8" | wc -l

# Ver el log de un error específico
cat tests/errors/TC-8.1.error.log

# Re-correr un solo test
bash tests/run_single.sh TC-8.1

# Re-correr todos y ver solo los nuevos fallos
bash tests/run_all.sh 2>&1 | grep "❌"

# Comparar resultados de dos días (ver si se regresaron bugs)
diff tests/report/2026-03-15.md tests/report/2026-03-16.md
```

---

## 9. Resumen ejecutivo del flujo completo

```
EJECUTAR            CAPTURAR            CLASIFICAR          RESOLVER
─────────           ─────────           ──────────          ────────
run_all.sh    →    .log por TC    →    BUGS.md         →   fix en código
                   summary.log         E-PERM / E-UI       re-run TC
                   .error.log          E-LABEL / etc.      re-run suite
                   reporte .md
```

La regla de oro: **un error no existe hasta que tiene un log**. Nunca corregir de memoria.

---

*Documento complementario de TEST_ACCESO_UI_BENDERAND.md · BenderAnd ERP · v1.0 · Marzo 2026*
