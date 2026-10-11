import {useCallback,useEffect,useRef,useState} from 'react';
import {api} from './lib';
import {createConnectionController} from './connectionController';

export default function useConnection(onData) {
 const [connection,setConnection]=useState({phase:'loading',step:0,progress:20,busy:true,message:''});
 const controller=useRef(null),dataCallback=useRef(onData);
 dataCallback.current=onData;
 useEffect(()=>{
  const manager=createConnectionController({request:api,onState:setConnection,onData:(session,data)=>dataCallback.current(session,data)});
  controller.current=manager;
  const retry=()=>manager.retry(),lost=()=>manager.disconnect();
  window.addEventListener('offline',lost);
  window.addEventListener('online',retry);
  window.addEventListener('syaqil:connection-lost',lost);
  manager.reload();
  return()=>{manager.stop();window.removeEventListener('offline',lost);window.removeEventListener('online',retry);window.removeEventListener('syaqil:connection-lost',lost);};
 },[]);
 const reload=useCallback(()=>controller.current?.reload(),[]);
 const retry=useCallback(()=>controller.current?.retry(),[]);
 return {connection,reload,retry};
}
