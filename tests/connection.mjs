import assert from 'node:assert/strict';
import {api} from '../src/lib.js';

const originalFetch=globalThis.fetch;
const events=[];
globalThis.window={dispatchEvent:event=>events.push(event.type)};
globalThis.CustomEvent=class {constructor(type){this.type=type;}};
try {
 let calls=0;
 globalThis.fetch=async()=>{calls++;throw new TypeError('Failed to fetch');};
 await assert.rejects(api('/orders',{request_key:'keep-this-key'}),error=>error.connection===true);
 assert.equal(calls,1,'A failed payment must never be replayed automatically');
 assert.deepEqual(events,['syaqil:connection-lost']);
 events.length=0;
 globalThis.fetch=async()=>new Response(JSON.stringify({error:'Berat tidak valid'}),{status:422});
 await assert.rejects(api('/orders',{}),error=>error.status===422&&!error.connection);
 assert.equal(events.length,0,'Validation errors must not show reconnection');
 globalThis.fetch=async()=>new Response('Unavailable',{status:503});
 await assert.rejects(api('/session'),error=>error.connection===true);
 assert.equal(events.length,1);
 events.length=0;
 globalThis.fetch=async()=>new Response('<html>Proxy error</html>',{status:200});
 await assert.rejects(api('/bootstrap'),error=>error.connection===true);
 globalThis.fetch=(_url,{signal})=>new Promise((resolve,reject)=>signal.addEventListener('abort',()=>reject(new DOMException('Aborted','AbortError'))));
 await assert.rejects(api('/session',undefined,{timeout:10}),error=>error.connection===true);
 globalThis.fetch=async()=>new Response(JSON.stringify({user:null,csrf:'valid'}),{status:200});
 assert.equal((await api('/session')).csrf,'valid');
 console.log('PASS: transport failures, timeout, invalid response, validation, and no payment replay');
} finally {globalThis.fetch=originalFetch;delete globalThis.window;delete globalThis.CustomEvent;}
