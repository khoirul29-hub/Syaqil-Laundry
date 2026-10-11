export function createConnectionController({request,onState,onData,isOnline=()=>navigator.onLine,retryDelays=[2000,5000,10000,20000,30000],heartbeatMs=30000,minimumLoadingMs=1500,completionHoldMs=300}) {
 let stopped=false,pending=null,retryTimer,heartbeatTimer,loadingTimer,finishLoadingWait,attempt=0,userId=null;
 let state={phase:'loading',step:0,progress:20,busy:true,message:''};
 const publish=patch=>{state={...state,...patch};if(!stopped)onState(state);};
 const clearTimers=()=>{clearTimeout(retryTimer);clearTimeout(heartbeatTimer);};
 const waitForLoading=ms=>new Promise(resolve=>{finishLoadingWait=resolve;loadingTimer=setTimeout(()=>{finishLoadingWait=null;resolve();},Math.max(0,ms));});
 function scheduleRetry(){
  clearTimeout(retryTimer);
  if(!stopped&&state.phase==='reconnecting')retryTimer=setTimeout(()=>run(false),retryDelays[Math.min(Math.max(0,attempt-1),retryDelays.length-1)]);
 }
 function disconnect(){
  if(stopped)return;
  clearTimeout(heartbeatTimer);
  publish({phase:'reconnecting',step:isOnline()?1:0,busy:!!pending,message:isOnline()?'Server belum dapat dihubungi.':'Perangkat sedang offline. Sambungkan kembali jaringan Anda.'});
  scheduleRetry();
 }
 function heartbeat(){
  clearTimeout(heartbeatTimer);
  if(stopped||state.phase!=='ready')return;
  heartbeatTimer=setTimeout(async()=>{
   if(stopped)return;
   if(!isOnline()){disconnect();return;}
   try {
    const session=await request('/session');
    if(stopped)return;
    if((session.user?.id??null)!==userId)await run(false);
    else heartbeat();
   }catch(error){if(!stopped){if(error.connection)disconnect();else heartbeat();}}
  },heartbeatMs);
 }
 function run(initial){
  if(stopped)return Promise.resolve();
  if(pending)return pending;
  clearTimers();
  if(!isOnline()){disconnect();return Promise.resolve();}
  const started=Date.now();
  publish({phase:initial?'loading':'reconnecting',step:1,progress:20,busy:true,message:''});
  pending=(async()=>{
   try {
    const session=await request('/session');
    if(stopped)return;
    publish({step:2,progress:65});
    const data=session.user?await request('/bootstrap'):null;
    if(initial&&!stopped&&state.phase==='loading'){
     await waitForLoading(minimumLoadingMs-completionHoldMs-(Date.now()-started));
     if(stopped||state.phase!=='loading')return;
     publish({progress:100});
     await waitForLoading(completionHoldMs);
     if(stopped||state.phase!=='loading')return;
    }
    if(stopped)return;
    if(!isOnline()){disconnect();return;}
    onData(session,data);
    userId=session.user?.id??null;
    attempt=0;
    publish({phase:'ready',step:3,progress:100,busy:false,message:''});
    return true;
   }catch(error){
    if(stopped)return;
    attempt++;
    publish({phase:error.connection?'reconnecting':'error',step:isOnline()?1:0,busy:false,message:error.message});
   }finally{
    pending=null;
    if(!stopped){publish({busy:false});if(state.phase==='ready')heartbeat();else scheduleRetry();}
   }
  })();
  return pending;
 }
 return {reload:()=>run(true),retry:()=>run(false),disconnect,stop:()=>{stopped=true;clearTimers();clearTimeout(loadingTimer);finishLoadingWait?.();finishLoadingWait=null;}};
}
