"""Run only against the separate demo test database/server on port 8081."""
import http.cookiejar
import json
import sys
import urllib.error
import urllib.request
import uuid

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8081'
if ':8081' not in BASE:
    raise SystemExit('Use the isolated test server on port 8081, never the operational server.')
csrf = ''
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def request(path, data=None, use_csrf=True):
    global csrf
    req = urllib.request.Request(BASE + '/api' + path,
        data=json.dumps(data).encode() if data is not None else None,
        headers={'Content-Type': 'application/json', **({'X-CSRF-Token': csrf} if use_csrf else {})})
    try:
        with client.open(req) as response:
            status, payload = response.status, json.load(response)
    except urllib.error.HTTPError as error:
        status, payload = error.code, json.load(error)
    if 'csrf' in payload:
        csrf = payload['csrf']
    return status, payload


def check(ok, message):
    assert ok, message
    print('PASS:', message)


request('/session')
check(request('/customers/1', {'name': 'Anonim'})[0] == 401, 'Anonymous edit denied')
check(request('/login', {'username': 'kasir', 'password': 'KasirDemo!2026'})[0] == 200, 'Cashier login')
phone = '62817' + str(int(uuid.uuid4().hex[:9], 16)).zfill(11)[-8:]
status, created = request('/customers', {'name': 'Profil Uji', 'phone': phone, 'address': 'Alamat awal'})
check(status == 201, 'Customer created')
customer_id = created['customer']['id']
body = {'name': 'Profil Diperbarui', 'phone': phone.replace('62', '0', 1), 'address': 'Jl. Baru No. 8', 'notes': 'Pisahkan pakaian putih.\nTanpa pewangi.'}
check(request('/customers/' + str(customer_id), body, False)[0] == 419, 'Edit requires CSRF token')
status, edited = request('/customers/' + str(customer_id), body)
check(status == 200, 'Profile edit endpoint available')
check(edited['customer']['name'] == body['name'] and edited['customer']['phone'] == phone, 'Name and normalized phone saved')
check(edited['customer']['address'] == body['address'] and edited['customer']['notes'] == body['notes'], 'Address and multiline notes saved')
_, bootstrap = request('/bootstrap')
loaded = next(row for row in bootstrap['customers'] if row['id'] == customer_id)
check(loaded['notes'] == body['notes'] and loaded['name'] == body['name'], 'Updated profile persists in bootstrap')
for invalid, label in [({'name': 'A'}, 'Short name rejected'), ({'name': 'N' * 101}, 'Long name rejected'), ({'phone': '08012345678'}, 'Invalid phone rejected'), ({'address': 'A' * 301}, 'Long address rejected'), ({'notes': 'N' * 1001}, 'Long notes rejected')]:
    check(request('/customers/' + str(customer_id), {**body, **invalid})[0] == 422, label)
other = next(row for row in bootstrap['customers'] if row['id'] != customer_id)
check(request('/customers/' + str(customer_id), {**body, 'phone': other['phone']})[0] == 409, 'Duplicate phone rejected')
check(request('/customers/9223372036854775806', body)[0] == 404, 'Missing customer returns 404')
status, cleared = request('/customers/' + str(customer_id), {**body, 'notes': '', 'address': ''})
check(status == 200 and cleared['customer']['notes'] == '' and cleared['customer']['address'] == '', 'Optional profile fields can be cleared')
