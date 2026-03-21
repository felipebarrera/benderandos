#!/bin/bash
# sync_spider_tests.sh — BenderAnd Spider QA v3 (Robust version)
# Auto-descubre rutas desde Laravel y actualiza spider_tests.json

DOCKER_APP="benderandos_app"
BASE_DIR="$(cd "$(dirname "$0")" && pwd)"
JSON_FILE="$BASE_DIR/spider_tests.json"
TEMP_FILE="/tmp/routes_raw.json"

echo "⏳ Sincronizando rutas desde Laravel..."

# Obtener lista de rutas en JSON y guardar en temp file
docker exec $DOCKER_APP php artisan route:list --json > "$TEMP_FILE" 2>/dev/null

if [ ! -s "$TEMP_FILE" ]; then
    echo "❌ Error: No se pudo obtener la lista de rutas o el archivo está vacío."
    exit 1
fi

# Usar python3 para procesar el archivo
python3 -c "
import json, os, datetime

temp_path = '$TEMP_FILE'
dest_path = '$JSON_FILE'

try:
    with open(temp_path, 'r') as f:
        routes = json.load(f)
except Exception as e:
    print(f'❌ Error al leer JSON temporal: {e}')
    exit(1)

# Cargar actual o iniciar vacío
data = {
    '_meta': {'version': '3.0', 'created': str(datetime.datetime.now().isoformat())},
    'http_checks': [],
    'api_sa_checks': [],
    'api_tenant_checks': [],
    'auth_checks': [],
    'db_checks': [],
    'ui_checks': []
}

if os.path.exists(dest_path):
    try:
        with open(dest_path, 'r') as f:
            old_data = json.load(f)
            # Preservar secciones que no son auto-generadas si existen
            for k in ['auth_checks', 'db_checks', 'ui_checks']:
                if k in old_data: data[k] = old_data[k]
    except:
        pass

seen_uris = set()
new_http = []
new_sa = []
new_tenant = []

for r in routes:
    uri = r.get('uri')
    name = r.get('name', '') or ''
    # El campo method puede ser string o lista dependiendo de la versión de Laravel
    methods = r.get('method', '')
    if isinstance(methods, list): methods = '|'.join(methods)
    
    if not uri or uri in seen_uris: continue
    seen_uris.add(uri)
    
    # Filtros
    if any(x in uri for x in ['_debugbar', 'sanctum', 'telescope', 'ignition', 'up']):
        continue
        
    is_bot = uri.startswith('api/bot/') or uri.startswith('api/internal/')
    has_param = '{' in uri
    
    if uri.startswith('api/superadmin') or uri.startswith('api/central') or 'spider' in uri:
        exp_auth = None if has_param else 200
        new_sa.append({'id': f'SA-{len(new_sa)+1}', 'path': '/'+uri, 'label': f'API SA: {name or uri}', 'method': methods.split('|')[0], 'url_key': 'super', 'expected': 401, 'expected_with_auth': exp_auth})
    elif uri.startswith('api/'):
        # Rutas de bot y internas usan JWT distinto, esperan 401 con token Sanctum
        exp_auth = 401 if is_bot else (None if has_param else 200)
        exp_no_auth = 422 if uri == 'api/login' else 401
        new_tenant.append({'id': f'T-{len(new_tenant)+1}', 'path': '/'+uri, 'label': f'API Tenant: {name or uri}', 'method': methods.split('|')[0], 'url_key': 'tenant', 'expected': exp_no_auth, 'expected_with_auth': exp_auth})
    elif not uri.startswith('api') and '{' not in uri and 'broadcasting' not in uri:
        # Lógica para asignar url_key
        urlKey = 'super'
        if any(uri.startswith(x) for x in ['admin/', 'pos', 'portal/', 'rentas', 'operario', 'auth/']):
            urlKey = 'tenant'
        if any(uri.startswith(x) for x in ['central/', 'webhook/', 'api/spider/', 'api/central/']):
            urlKey = 'super'
        
        new_http.append({'id': f'H-{len(new_http)+1}', 'path': '/'+uri, 'label': f'Vista: {uri}', 'expected': '200|301|302', 'url_key': urlKey})

data['api_sa_checks'] = new_sa
data['api_tenant_checks'] = new_tenant
data['http_checks'] = new_http
data['_meta']['updated'] = str(datetime.datetime.now().isoformat())
data['_meta']['total_tests'] = len(new_sa) + len(new_tenant) + len(new_http) + len(data.get('auth_checks', [])) + len(data.get('db_checks', [])) + len(data.get('ui_checks', []))

with open(dest_path, 'w') as f:
    json.dump(data, f, indent=2)

print(f'✅ Sincronización completa: {data[\"_meta\"][\"total_tests\"]} tests en spider_tests.json')
"

rm -f "$TEMP_FILE"
echo "✅ Proceso finalizado."
