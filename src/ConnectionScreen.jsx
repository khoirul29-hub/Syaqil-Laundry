import React,{useEffect,useRef} from 'react';
import {Wifi,Check,RefreshCw,House} from 'lucide-react';

export default function ConnectionScreen({connection,onRetry,onDashboard}) {
 const loading=connection.phase==='loading';
 const heading=useRef(null);
 useEffect(()=>{
  const previous=document.activeElement;
  heading.current?.focus();
  return()=>{if(previous?.isConnected)previous.focus();};
 },[loading]);
 return <main className={'connection-screen '+(loading?'is-loading':'is-reconnecting')} aria-labelledby="connection-title">
  <section className="connection-frame">
   <div className="connection-art connection-art-left" aria-hidden="true"/>
   <div className="connection-art connection-art-right" aria-hidden="true"/>
   <div className="connection-window-dots" aria-hidden="true"><i/><i/><i/></div>
   <div className="connection-center">
    <div className="connection-brand"><div className="connection-logo"><img src="/assets/logo.png" alt=""/></div><div><strong>SYAQIL<br/>LAUNDRY</strong><span>BERSIH • CEPAT • PRAKTIS</span></div></div>
    {loading?<div className="connection-loading-content">
     <h1 id="connection-title" ref={heading} tabIndex={-1}>Menyiapkan Aplikasi...</h1>
     <div className="connection-progress-row"><div className="connection-progress" role="progressbar" aria-label="Tahap persiapan aplikasi" aria-valuemin={0} aria-valuemax={100} aria-valuenow={connection.progress} aria-valuetext={connection.progress<65?'Memeriksa sesi':'Memuat data laundry'}><span style={{width:connection.progress+'%'}}/></div><b>{connection.progress}%</b></div>
     <p role="status">{connection.progress===100?'Aplikasi siap. Membuka halaman...':connection.progress<65?'Memeriksa sesi, mohon tunggu sebentar...':'Memuat data, mohon tunggu sebentar...'}</p>
    </div>:<>
     <div className="connection-wifi" aria-hidden="true"><Wifi/><span>!</span></div>
     <h1 id="connection-title" ref={heading} tabIndex={-1}>{connection.phase==='error'?'Data Belum Dapat Dimuat':'Koneksi Terputus'}</h1>
     <p className="connection-description" role="status">{connection.phase==='error'?connection.message:<>Sedang mencoba menghubungkan kembali...<br/>Mohon tunggu sebentar.</>}</p>
     <ol className="connection-steps" aria-label="Tahap pemulihan koneksi">{['Memeriksa jaringan','Menghubungkan ulang...','Sinkronisasi data'].map((label,index)=><li key={label} className={index<connection.step?'complete':index===connection.step?'current':''} aria-current={index===connection.step?'step':undefined}><span>{index<connection.step?<Check size={24}/>:index===connection.step?<i className={connection.busy?'spinning':''}/>:null}</span><small>{label}</small></li>)}</ol>
     <div className="connection-help"><Wifi size={34}/><div><b>Masih belum terhubung?</b><p>{connection.step===0?'Sambungkan jaringan Anda, atau coba lagi.':'Periksa koneksi internet Anda, atau coba lagi.'}</p></div></div>
     <div className="connection-actions"><button className="connection-retry" onClick={onRetry} disabled={connection.busy}><RefreshCw size={22} className={connection.busy?'spinning':''}/>{connection.busy?'Menghubungkan...':'Coba Lagi'}</button><button className="connection-dashboard" onClick={onDashboard} disabled={connection.busy}><House size={23}/>Kembali ke Dashboard</button></div>
    </>}
   </div>
  </section>
 </main>;
}
