"""Run against a separate seeded test database/server, never the operational database."""
import json, urllib.request, urllib.error, http.cookiejar, uuid, sys
BASE=sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:8081'
csrf=''
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(path, data=None, use_csrf=True):
    global csrf
    req=urllib.request.Request(BASE+'/api'+path, data=json.dumps(data).encode() if data is not None else None, headers={'Content-Type':'application/json', **({'X-CSRF-Token':csrf} if use_csrf else {})})
    try:
        with client.open(req) as r: status,payload=r.status,json.load(r)
    except urllib.error.HTTPError as e: status,payload=e.code,json.load(e)
    if 'csrf' in payload: csrf=payload['csrf']
    return status,payload
def check(ok,msg):
    assert ok,msg
    print('PASS:',msg)
check(request('/bootstrap')[0]==401,'Anonymous data access denied')
request('/session')
check(request('/login',{'username':'kasir','password':'bad'},False)[0]==419,'Missing CSRF token rejected')
check(request('/login',{'username':'kasir','password':'bad'})[0]==401,'Invalid login rejected')
check(request('/login',{'username':'kasir','password':'KasirDemo!2026'})[0]==200,'Cashier can log in')
status,data=request('/bootstrap')
check(status==200 and len(data['services'])==3,'PRD services loaded from database')
check([int(s['price']) for s in data['services']]==[6000,10000,14000],'PRD prices match')
check(request('/customers',{'name':'Tes','phone':'123'})[0]==422,'Invalid customer phone rejected')
phone='62819'+str(uuid.uuid4().int)[0:8]
status,c=request('/customers',{'name':'Pengujian Sistem','phone':phone,'address':'Data uji otomatis'})
check(status==201,'Customer is persisted')
cid=c['customer']['id']
check(request('/customers',{'name':'Duplikat','phone':phone})[0]==409,'Duplicate phone rejected')
body={'request_key':str(uuid.uuid4()),'customer_id':cid,'items':[{'service_id':1,'quantity':4.25,'price':1,'category':'Kiloan'}],'discount_percent':10,'method':'cash','paid':30000,'notes':'Tes transaksi','expected_total':22950}
for invalid in [-1,100.01,'1.234','abc']:
    check(request('/orders',{**body,'discount_percent':invalid})[0]==422,'Invalid discount percentage rejected: '+str(invalid))
legacy={k:v for k,v in body.items() if k!='discount_percent'}
check(request('/orders',{**legacy,'discount':500})[0]==422,'Old cashier client cannot silently use rupiah discount')
check(request('/orders',{**body,'items':[]})[0]==422,'Empty order rejected')
check(request('/orders',{**body,'paid':22949})[0]==422,'Insufficient cash rejected server-side')
check(request('/orders',{**body,'expected_total':1})[0]==409,'Stale or manipulated total rejected')
check(request('/orders',{**body,'method':'transfer','confirmed':True})[0]==422,'Unconfigured transfer cannot be marked paid')
check(request('/orders',{**body,'method':'qris','confirmed':True})[0]==422,'Unconfigured QRIS cannot be marked paid')
status,p=request('/orders',body);o=p.get('order',{})
check(status==201 and int(o['total'])==22950 and int(o['change_amount'])==7050,'Percentage checkout and cash change persisted')
check(float(o['discount_percent'])==10 and int(o['discount'])==2550,'Both discount percentage and rupiah amount persisted')
check(int(o['items'][0]['price'])==6000,'Client price ignored; server price snapshot used')
check(o['number'].startswith('SYQ-'),'Unique invoice number assigned')
status,duplicate=request('/orders',body)
check(status==200 and duplicate['order']['id']==o['id'],'Retry does not duplicate an order')
check(request('/orders',{**body,'notes':'A different draft in another tab'})[0]==409,'A checkout key cannot silently consume a different draft')
check(request('/orders',{**body,'discount_percent':10.01})[0]==409,'A changed fractional discount cannot reuse a checkout key')
path='/orders/'+str(o['id'])+'/status'
check(request(path,{'status':'Selesai'})[0]==422,'Status cannot skip processing')
for state in ['Diproses','Selesai','Diambil']:
    status,result=request(path,{'status':state})
    check(status==200 and result['order']['status']==state,'Status advances to '+state)
    if state=='Selesai': check(result['order']['notification']['status']=='not_configured','WhatsApp remains honest when no provider is configured')
check(len(result['order']['history'])==4,'All status changes are recorded')
check(request(path,{'status':'Diterima'})[0]==422,'Closed order cannot be silently rewound')
check(request('/services',{'price':1})[0]==404,'No master-data edit endpoint exposed to cashier')
check(request('/orders/'+str(o['id'])+'/cancel',{})[0]==404,'No cancellation endpoint exposed to cashier')
request('/logout',{})
check(request('/bootstrap')[0]==401,'Logout revokes authenticated access')
request('/session');request('/login',{'username':'kasir','password':'KasirDemo!2026'})
_,after=request('/bootstrap')
persisted=next(x for x in after['orders'] if x['id']==o['id'])
check(persisted['status']=='Diambil' and int(persisted['total'])==22950 and float(persisted['discount_percent'])==10,'Order and percentage survive a fresh session')
print('ALL API CHECKS PASSED')
