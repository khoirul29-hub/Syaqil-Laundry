import assert from 'node:assert/strict';
import {createConnectionController} from '../src/connectionController.js';

const tick=()=>new Promise(resolve=>setTimeout(resolve,15));
let offline=false,fail=false,calls=[],commits=[],states=[];
const user={id:1};
const controller=createConnectionController({
 request:async path=>{calls.push(path);if(fail)throw Object.assign(new Error('Offline'),{connection:true});return path==='/session'?{user}:{orders:[{id:1}]};},
 isOnline:()=>!offline,onState:s=>states.push(s),onData:(s,d)=>commits.push({s,d}),retryDelays:[5],heartbeatMs:60000,
});
try {
 await controller.reload();
 assert.equal(states.at(-1).phase,'ready');
 assert.deepEqual(calls,['/session','/bootstrap']);
 fail=true;controller.disconnect();await tick();
 assert.equal(states.at(-1).phase,'reconnecting');
 assert.equal(commits.length,1,'Failed reconnect must preserve previously loaded data');
 fail=false;assert.equal(await controller.retry(),true,'Dashboard navigation is allowed only after recovery succeeds');
 assert.equal(states.at(-1).phase,'ready');
 assert.equal(commits.length,2);
 offline=true;controller.disconnect();const before=calls.length;await controller.retry();
 assert.equal(calls.length,before,'Do not send requests while browser is offline');
 offline=false;await controller.retry();
 assert.equal(states.at(-1).phase,'ready');
 assert.ok(calls.every(path=>['/session','/bootstrap'].includes(path)),'Reconnect only reads session and data');
 controller.stop();const stoppedCalls=calls.length;await tick();assert.equal(calls.length,stoppedCalls);
 let release,readCount=0,lateCommit=false;
 const pending=createConnectionController({request:()=>{readCount++;return new Promise(resolve=>{release=resolve;});},isOnline:()=>true,onState:()=>{},onData:()=>{lateCommit=true;}});
 const first=pending.reload();pending.retry();assert.equal(readCount,1,'Concurrent retry must share one request');
 pending.stop();release({user:null});await first;assert.equal(lateCommit,false,'Unmount must ignore late responses');
 const timingStates=[];
 let committed=false;
 const timed=createConnectionController({request:async()=>({user:null}),isOnline:()=>true,onState:s=>timingStates.push({...s}),onData:()=>{committed=true;},minimumLoadingMs:80,completionHoldMs:20});
 try {
  const started=performance.now();
  const loading=timed.reload();
  await tick();
  assert.equal(committed,false,'Fast responses must keep the loading screen visible');
  await loading;
  assert.ok(performance.now()-started>=75,'Loading must respect its minimum duration');
  assert.ok(timingStates.some(s=>s.phase==='loading'&&s.progress===100),'Show completed progress before entering the app');
  assert.equal(committed,true);
 }finally{timed.stop();}
 console.log('PASS: automatic recovery, data preservation, offline, deduplication, cleanup, read-only retries, loading duration');
} finally {controller.stop();}
