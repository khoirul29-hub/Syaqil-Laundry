import React from 'react';
import {ArrowLeft,ArrowRight,Users,Shirt,Zap,Clock3} from 'lucide-react';

export function ReferenceBanner({variant='customers'}) {
 const regions={customers:{w:1230,h:196},history:{w:1227,h:200},cashier:{w:635,h:92}};
 const region=regions[variant]||regions.customers;
 return <div className={'reference-banner banner-'+variant} role="img" aria-label="Syaqil Laundry · Bersih, cepat, praktis" style={{aspectRatio:`${region.w}/${region.h}`}}/>;
}
export function StatCard({icon:Icon=Users,label,value,tone='blue',note}) {
 return <article className={'ref-stat ref-stat-'+tone}><span className="ref-stat-icon"><Icon size={28}/></span><div><span className="ref-stat-label">{label}</span><div className="ref-stat-value"><strong>{value}</strong>{note&&<small>{note}</small>}</div></div></article>;
}
export function Pagination({page,total,pageSize=10,onPage}) {
 const count=Math.max(1,Math.ceil(total/pageSize)),current=Math.min(Math.max(1,page),count);
 const pages=[...new Set([1,...Array.from({length:5},(_,i)=>current-2+i).filter(n=>n>=1&&n<=count),count])].sort((a,b)=>a-b);
 return <div className="ref-pagination"><span>Menampilkan {total?(current-1)*pageSize+1:0}–{Math.min(current*pageSize,total)} dari {total}</span><nav aria-label="Halaman tabel"><button aria-label="Halaman sebelumnya" disabled={current===1} onClick={()=>onPage(current-1)}><ArrowLeft size={16}/></button>{pages.map((n,index)=><React.Fragment key={n}>{index>0&&n-pages[index-1]>1&&<span>…</span>}<button className={n===current?'active':''} aria-current={n===current?'page':undefined} onClick={()=>onPage(n)}>{n}</button></React.Fragment>)}<button aria-label="Halaman berikutnya" disabled={current===count} onClick={()=>onPage(current+1)}><ArrowRight size={16}/></button></nav></div>;
}
export function ServiceName({name}) {
 const Icon=name?.includes('1 Hari')?Zap:name?.includes('Jam')?Clock3:Shirt;
 return <span className={'ref-service-name '+(Icon===Shirt?'':'express')}><Icon size={18}/><span>{name||'Layanan'}</span></span>;
}
