import json
import urllib.request
import urllib.error

with open('tests/spider_tests.json', 'r') as f:
    data = json.load(f)

print("Running verification on demo-legal.localhost (has data)...")

req = urllib.request.Request('http://demo-legal.localhost:8000/api/login', 
                           data=b'{"email":"admin@demo-legal.cl","password":"demo1234"}', 
                           headers={'Content-Type': 'application/json', 'Accept': 'application/json'})
try:
    with urllib.request.urlopen(req) as response:
        resp = json.loads(response.read().decode('utf-8'))
        token = resp.get('token')
except Exception as e:
    token = "INVALID"

headers_with_token = {'Authorization': f'Bearer {token}', 'Accept': 'application/json'}
headers_no_token = {'Accept': 'application/json'}

tests_to_run = data.get('http_checks', [])

for t in tests_to_run:
    url = f"http://demo-legal.localhost:8000{t.get('path', '/')}"
    method = t.get('method', 'GET')
    expected_no_auth = t.get('expected')
    expected_with_auth = t.get('expected_with_auth')
    
    # Test with auth Only
    req_auth = urllib.request.Request(url, method=method, headers=headers_with_token)
    try:
        with urllib.request.urlopen(req_auth) as response:
            status_auth = response.status
    except urllib.error.HTTPError as e:
        status_auth = e.code
        if status_auth == 500:
            print(f"FAILED 500: {method} {url}")
            break
