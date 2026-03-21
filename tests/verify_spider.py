import json
import urllib.request
import urllib.error

with open('tests/spider_tests.json', 'r') as f:
    data = json.load(f)

print("Running sample api_tenant_checks to verify routing and 401/403/200 behavior...")

# Get token for demo.localhost
req = urllib.request.Request('http://demo.localhost:8000/api/login', data=b'{"email":"admin@benderand.cl","password":"admin1234"}', headers={'Content-Type': 'application/json'})
try:
    with urllib.request.urlopen(req) as response:
        resp = json.loads(response.read().decode('utf-8'))
        token = resp.get('token')
        print(f"Token obtained: {token[:10]}...")
except Exception as e:
    print(f"Failed to get token: {e}")
    token = "INVALID"

headers_with_token = {'Authorization': f'Bearer {token}', 'Accept': 'application/json'}
headers_no_token = {'Accept': 'application/json'}

tests_to_run = data.get('api_tenant_checks', [])[:10]
tests_to_run += [t for t in data.get('api_tenant_checks', []) if 'mi-plan' in t.get('path','')]

for t in tests_to_run:
    url = f"http://demo.localhost:8000{t['path']}"
    method = t.get('method', 'GET')
    expected_no_auth = t.get('expected')
    expected_with_auth = t.get('expected_with_auth')
    
    # Test without auth
    req_no_auth = urllib.request.Request(url, method=method, headers=headers_no_token)
    try:
        with urllib.request.urlopen(req_no_auth) as response:
            status_no_auth = response.status
    except urllib.error.HTTPError as e:
        status_no_auth = e.code
    
    # Test with auth
    req_auth = urllib.request.Request(url, method=method, headers=headers_with_token)
    try:
        with urllib.request.urlopen(req_auth) as response:
            status_auth = response.status
    except urllib.error.HTTPError as e:
        status_auth = e.code

    print(f"[{t['id']}] {method} {t['path']}")
    print(f"  No Auth: Expected {expected_no_auth}, Got {status_no_auth} {'✅' if str(expected_no_auth) == str(status_no_auth) else '❌'}")
    print(f"  Auth:    Expected {expected_with_auth}, Got {status_auth} {'✅' if str(expected_with_auth) == str(status_auth) else '❌'}")

