import React from 'react';
import {ArrowDown,ArrowUp,ChevronLeft,ChevronRight,Search} from 'lucide-react';
import {initials} from './mockData';

const crops={
 employees:{file:'employees',x:235,y:80,w:1187,h:167},
 services:{file:'services',x:235,y:80,w:1187,h:169},
 dashboard:{file:'dashboard',x:238,y:82,w:1178,h:165},
 customers:{file:'customers',x:255,y:114,w:1158,h:181},
 receipts:{file:'receipts',x:238,y:75,w:1176,h:155}
};
export function ReferenceArt({kind,className='',label,children}){
 if(['employeePromo','servicePromo','growth'].includes(kind))return null;
 const {file,x,y,w,h,fullWidth=1448,fullHeight=1086}=crops[kind];
 return <div className={`ow-art ow-art--${kind} ${className}`} role={label?'img':undefined} aria-label={label} aria-hidden={!label&&!children?true:undefined} style={{aspectRatio:`${w}/${h}`,backgroundImage:`url('/assets/owner-${file}-reference.png')`,backgroundSize:`${fullWidth/w*100}% ${fullHeight/h*100}%`,backgroundPosition:`${x/(fullWidth-w)*100}% ${y/(fullHeight-h)*100}%`}}>{children}</div>;
}
export function PageBanner({kind,title,description}){
 return <section className={`ow-banner ow-banner--${kind}`} aria-label={title}><ReferenceArt kind={kind}/><div className="ow-banner-copy"><h1>{title}</h1><p>{description}</p></div></section>;
}
export function MiniBars({values=[2,5,4,7,6,10],tone='blue'}){
 const peak=Math.max(1,...values);
 return <div className={`ow-minibars ow-tone-${tone}`} aria-hidden="true">{values.map((value,i)=><i key={i} style={{height:`${6+value/peak*30}px`}}/>)}</div>;
}
export function Metric({label,value,icon:Icon,tone='blue',trend,caption='dari bulan sebelumnya',soft=false,values,detail}){
 const Down=trend<0?ArrowDown:ArrowUp;
 const unfavorable=label.includes('Pengeluaran')?trend>0:trend<0;
 return <article className={`ow-metric ow-tone-${tone} ${soft?'is-soft':''}`}><span className="ow-metric-icon"><Icon size={31} strokeWidth={2.3}/></span><div className="ow-metric-copy"><span>{label}</span><strong className={typeof value==='string'&&value.length>17?'is-long':''}>{value}</strong>{trend!=null?<><b className={`ow-trend ${unfavorable?'is-cost':''}`}><Down size={15}/>{trend>0?'+':''}{trend}%</b><small>{caption}</small></>:<small className="ow-metric-detail">{detail}</small>}</div><MiniBars tone={tone} values={values}/></article>;
}
export function Panel({title,icon:Icon,action,children,className=''}){
 return <section className={`ow-panel ${className}`}><header className="ow-panel-head"><h2>{Icon&&<Icon size={24}/>}<span>{title}</span></h2>{action}</header>{children}</section>;
}
export function SearchField({label='Cari',placeholder='Cari...',value,onChange}){
 return <label className="ow-search"><Search size={18}/><input aria-label={label} value={value} onChange={onChange} placeholder={placeholder}/></label>;
}
export function Avatar({name,id=0,large=false}){return <span className={`ow-avatar ow-avatar--${id%6} ${large?'is-large':''}`}>{initials(name)}</span>;}
export function Category({value}){return <span className={`ow-category ${value==='Member'?'is-member':''}`}>{value}</span>;}
export function Pagination({page,total,size=10,onChange,label='pelanggan'}){
 const pages=Math.max(1,Math.ceil(total/size)),start=Math.max(1,Math.min(page-2,pages-4));
 return <footer className="ow-pagination"><span>{total?`Menampilkan ${(page-1)*size+1}–${Math.min(page*size,total)} dari ${total} ${label}`:`0 ${label}`}</span><nav aria-label={`Halaman ${label}`}><button aria-label="Halaman sebelumnya" disabled={page<=1} onClick={()=>onChange(page-1)}><ChevronLeft size={17}/></button>{Array.from({length:Math.min(5,pages)},(_,i)=>start+i).map(p=><button key={p} aria-label={`Halaman ${p}`} aria-current={p===page?'page':undefined} className={p===page?'is-active':''} onClick={()=>onChange(p)}>{p}</button>)}<button aria-label="Halaman berikutnya" disabled={page>=pages} onClick={()=>onChange(page+1)}><ChevronRight size={17}/></button></nav></footer>;
}
export function PrototypeFooter(){return <p className="ow-prototype-note">Prototipe Owner · Data demo · Perubahan hanya berlaku selama sesi ini.</p>;}
