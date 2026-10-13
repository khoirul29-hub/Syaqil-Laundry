let csrf='';
export const rupiah = n => 'Rp ' + Math.round(Number(n)||0).toLocaleString('id-ID');
export const quantity = n => Number(n).toLocaleString('id-ID',{maximumFractionDigits:2});
export function calculateDiscount(subtotal,value) {
 const input=String(value??'').trim().replace(',','.')||'0';
 if(!/^\d+(\.\d{1,2})?$/.test(input)||Number(input)>100) return {percent:0,amount:0,error:'Masukkan diskon 0–100%, maksimal 2 angka desimal.'};
 const percent=Number(input),basisPoints=Math.round(percent*100);
 return {percent,amount:Math.round(subtotal*basisPoints/10000),error:''};
}
export const localPhone = p => String(p||'').replace(/^62/,'0');
export const dateText = (d,withTime=false) => d ? new Date(d.replace(' ','T')).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',...(withTime?{hour:'2-digit',minute:'2-digit'}:{})}) : 'Belum ditentukan';
const API_BASE = (import.meta.env?.VITE_API_BASE_URL || '/api').replace(/\/$/, '');
export async function api(path,body,{timeout=15000}={}) {
 const controller=new AbortController();
 const timer=setTimeout(()=>controller.abort(),timeout);
 try {
  const url=path.startsWith('http')?path:API_BASE+path;
  const res=await fetch(url,{
   signal:controller.signal,
   method:body===undefined?'GET':'POST',
   credentials:'include',
   headers:{
    'Accept':'application/json',
    'X-Requested-With':'XMLHttpRequest',
    'Content-Type':'application/json',
    'X-CSRF-Token':csrf,
   },
   ...(body!==undefined?{body:JSON.stringify(body)}:{})
  });
  const data=await res.json();
  if(!res.ok) { const error=new Error(data.error||data.message||'Permintaan gagal.'); error.status=res.status; error.code=data.code; error.connection=res.status>=500; throw error; }
  if(!data||typeof data!=='object'||Array.isArray(data)) throw new SyntaxError('Respons server tidak valid.');
  if(data.csrf) csrf=data.csrf;
  return data;
 } catch(error) {
  if(error.connection||error instanceof TypeError||error instanceof SyntaxError||error.name==='AbortError') {
   const connectionError=new Error(error.name==='AbortError'?'Server belum merespons. Mencoba menghubungkan kembali.':'Koneksi ke server terputus. Periksa jaringan Anda.',{cause:error});
   connectionError.connection=true;
   connectionError.status=error.status;
   if(typeof window!=='undefined') window.dispatchEvent(new CustomEvent('syaqil:connection-lost'));
   throw connectionError;
  }
  throw error;
 } finally {clearTimeout(timer);}
}
export const statuses=['Diterima','Diproses','Selesai','Diambil'];
export const methodName={cash:'Tunai',qris:'QRIS',transfer:'Transfer'};
