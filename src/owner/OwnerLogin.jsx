import React,{useState} from 'react';
import {ArrowRight,Check,Eye,EyeOff,LockKeyhole,UserRound} from 'lucide-react';
import './owner-login.css';

function Brand({light=false}){
 const filterId=light?'owner-login-white':'owner-login-blue';
 return <div className={`owner-login-brand ${light?'is-light':''}`} aria-label="Syaqil Laundry">
  <svg viewBox="170 150 950 930" role="img" aria-label="Logo Syaqil Laundry"><defs>{light&&<filter id={filterId} colorInterpolationFilters="sRGB"><feColorMatrix type="matrix" values="0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  -1 0 0 0 1"/></filter>}</defs><image href="/assets/logo.png" width="1254" height="1254" filter={light?`url(#${filterId})`:undefined}/></svg>
  <strong>SYAQIL<br/>LAUNDRY</strong>
 </div>;
}

function Waves(){return <svg className="owner-login-waves" viewBox="0 0 594 490" preserveAspectRatio="xMidYMax slice" aria-hidden="true"><defs><linearGradient id="owner-login-wave-blue" x1="0" y1="0" x2="1" y2="1"><stop stopColor="#006BFF"/><stop offset="1" stopColor="#0046C8"/></linearGradient><linearGradient id="owner-login-wave-cyan" x1="0" y1="0" x2="1" y2="0"><stop stopColor="#07B9FF"/><stop offset="1" stopColor="#0084F8"/></linearGradient></defs><circle cx="505" cy="15" r="21" fill="#0879ff"/><circle cx="463" cy="77" r="28" fill="#0879ff"/><circle cx="512" cy="131" r="14" fill="#0879ff"/><path d="M-47 147C48 49 176 19 291 72c97 45 152 155 157 280-41 39-139 88-235 90C113 442 29 400-47 339Z" fill="url(#owner-login-wave-blue)"/><path d="M-12 235c64-44 114-34 158-65 29-20 58-24 82 0 28 29 60 69 104 22 5 45-36 77-78 72-48-6-69-56-119-30-51 26-90 41-147 36Z" fill="#0878ff"/><path d="M-15 287c84-72 133-44 189-65 37-13 56 13 78 25 28 16 52 10 79-17-11 59-54 75-91 69-45-7-75-55-120-34-42 19-75 48-135 56Z" fill="#0B6DF0"/><path d="M-35 278c105 48 167 97 270 71 91-22 156-112 283-146 77-21 124-8 171 7v99c-122-26-177 10-255 61-132 85-241 124-469 6Z" fill="url(#owner-login-wave-cyan)"/><path d="M-35 339c100 46 203 57 306 21 91-32 169-89 245-101 61-10 120 4 173 25v98c-112-38-176-21-268 26-160 83-303 99-456 41Z" fill="#0056D9"/><path d="M-35 379c99 28 204 29 305 3 108-28 184-94 271-93 53 1 101 18 148 40v58c-92-20-150-18-225 27-132 78-300 122-499 43Z" fill="#0876F6"/><path d="M-35 444c112 29 235 29 350 1 97-24 171-74 252-76 45-2 83 4 122 14v65c-131-28-189 2-283 52H-35Z" fill="#009DF9"/></svg>;}

function SideArtwork(){return <svg className="owner-login-side-art" viewBox="0 0 854 1086" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
 <defs>
  <linearGradient id="owner-login-side-wash" x1="0" y1="0" x2="1" y2="1"><stop stopColor="#dceeff"/><stop offset=".54" stopColor="#a8d2ff"/><stop offset="1" stopColor="#6aaffb"/></linearGradient>
  <linearGradient id="owner-login-side-wave" x1="0" y1="0" x2="1" y2="0"><stop stopColor="#0b63ec"/><stop offset="1" stopColor="#069bfa"/></linearGradient>
 </defs>
 <rect width="854" height="1086" fill="url(#owner-login-side-wash)"/>
 <path d="M597-50c141 48 204 155 270 267v170c-78-51-133-135-223-147-75-10-142 29-227 14 98-42 119-164 180-304Z" fill="#2b88f4"/>
 <path d="M665-28c87 44 144 92 202 164v52c-69-56-126-81-193-101-45-14-75-14-120-3 31-44 57-82 111-112Z" fill="#075ddd"/>
 <circle cx="758" cy="163" r="32" fill="#ccff2e"/>
 <circle cx="61" cy="279" r="15" fill="#075ddd"/>
 <circle cx="805" cy="538" r="13" fill="#075ddd"/>
 <path d="M-52 912c158-96 276-84 423-41 166 49 300 61 533-43v299H-52Z" fill="#a8d5ff"/>
 <path d="M-52 956c133-105 268-110 413-61 161 55 315 75 543-36v284H-52Z" fill="url(#owner-login-side-wave)"/>
 <path d="M-52 1011c138-84 262-74 379-31 179 66 354 37 577-55v218H-52Z" fill="#034ec7"/>
 <path d="M-52 1068c170-67 320-53 437-5 171 69 315 37 519-51v131H-52Z" fill="#08a0f7"/>
 </svg>;}

export default function OwnerLogin({onLogin}){
 const [identifier,setIdentifier]=useState(''),[password,setPassword]=useState(''),[remember,setRemember]=useState(true),[showPassword,setShowPassword]=useState(false),[forgotOpen,setForgotOpen]=useState(false);
 const [error,setError]=useState(''),[busy,setBusy]=useState(false);
 async function submit(event){
  event.preventDefault();
  if(!identifier.trim()||!password.trim()||busy)return;
  setBusy(true);setError('');
  try{await onLogin(identifier,password,remember);}catch(err){setError(err.message||'Gagal masuk.');}finally{setBusy(false);}
 }
 return <main className="owner-login">
  <section className="owner-login-story" aria-label="Owner Syaqil Laundry">
   <div className="owner-login-story-content">
    <Brand light/>
    <div className="owner-login-story-copy"><span className="owner-login-accent"/><h1>Owner Login</h1><p>Masuk untuk mengakses<br/>dashboard Syaqil Laundry<br/>sebagai Owner.</p></div>
   </div>
   <Waves/>
  </section>
  <section className="owner-login-form-side">
   <SideArtwork/>
   <div className="owner-login-card">
    <Brand/>
    <h2>Login Owner</h2>
    <p className="owner-login-subtitle">Masuk dengan akun Anda untuk melanjutkan.</p>
    <form onSubmit={submit}>
     <div className="owner-login-field"><UserRound size={27} strokeWidth={1.9}/><input aria-label="Email atau Username" type="text" name="identifier" autoComplete="username" placeholder="Email atau Username" value={identifier} onChange={event=>setIdentifier(event.target.value)} required/></div>
     <div className="owner-login-field"><LockKeyhole size={26} strokeWidth={1.9}/><input aria-label="Kata Sandi" type={showPassword?'text':'password'} name="password" autoComplete="current-password" placeholder="Kata Sandi" value={password} onChange={event=>setPassword(event.target.value)} required/><button type="button" className="owner-login-reveal" aria-label={showPassword?'Sembunyikan kata sandi':'Tampilkan kata sandi'} onClick={()=>setShowPassword(value=>!value)}>{showPassword?<Eye size={24}/>:<EyeOff size={24}/>}</button></div>
     <div className="owner-login-options"><label className="owner-login-remember"><input type="checkbox" checked={remember} onChange={event=>setRemember(event.target.checked)}/><span className="owner-login-check" aria-hidden="true">{remember&&<Check size={18} strokeWidth={3}/>}</span><span>Ingat saya</span></label><button type="button" className="owner-login-forgot" onClick={()=>setForgotOpen(value=>!value)} aria-expanded={forgotOpen}>Lupa kata sandi?</button></div>
     {forgotOpen&&<p className="owner-login-help" role="status">Pemulihan kata sandi belum tersedia di prototipe ini. Hubungi pengelola akun saat sistem autentikasi sudah aktif.</p>}
     {error&&<p className="owner-login-help" style={{color:'#ef4444',background:'#fef2f2',padding:'8px 12px',borderRadius:'8px',border:'1px solid #fecaca',fontWeight:500}} role="alert">{error}</p>}
     <button className="owner-login-submit" type="submit" disabled={busy}>{busy?'Memeriksa akun...':'Masuk'} <ArrowRight size={25} strokeWidth={2}/></button>
    </form>
    <p className="owner-login-demo-note">Autentikasi terverifikasi backend Syaqil Laundry.</p>
   </div>
  </section>
 </main>;
}
