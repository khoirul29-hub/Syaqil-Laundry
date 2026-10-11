import React,{useEffect,useRef} from 'react';
import {Shirt,Layers,Weight,Footprints,BriefcaseBusiness,Smile,Printer,Check,X,ArrowUpRight,Zap} from 'lucide-react';
import {rupiah,quantity,dateText,methodName} from './lib';

export function Logo({compact=false}) { return <div className="brand" role="img" aria-label="Syaqil Laundry"><img src="/assets/logo.png" alt=""/><div>Syaqil<br/>Laundry{!compact&&<small>BERSIH • CEPAT • PRAKTIS</small>}</div></div>; }
export function Hero() {return <section className="hero" aria-label="Syaqil Laundry — Bersih, cepat, praktis"><div className="hero-brand"><img src="/assets/logo.png" alt=""/><div><h1>SYAQIL<br/>LAUNDRY</h1><span className="hero-slogan">CUCI • BERSIH • BERES!</span></div></div><div className="express-sticker"><span>EXPRESS?</span><strong>GAS!</strong></div><div className="hero-art" aria-hidden="true"><img src="/assets/reference.png" alt=""/></div><span className="hero-note">Cucian beres,<br/>hari lebih ringan!</span></section>;}
export function SidebarSticker(){return <div className="reference-sidebar-art" aria-hidden="true"><img src="/assets/sidebar-art-hd.png" alt=""/></div>;}
export const categories=['Kemeja','Celana','Jas','Bedcover','Sepatu','Kiloan'];
export function ItemIcon({type,size=34}) {
 const props={size,strokeWidth:1.8,'aria-hidden':true};
 if(type==='Celana') return <svg width={size} height={size} viewBox="0 0 36 36" fill="currentColor" aria-hidden="true"><path d="M10 3h16l4 29H20l-2-17-2 17H6L10 3Z"/><path d="M11 7h14" stroke="white" strokeWidth="2"/></svg>;
 if(type==='Kemeja') return <Shirt {...props}/>;
 if(type==='Jas') return <BriefcaseBusiness {...props}/>;
 if(type==='Bedcover') return <Layers {...props}/>;
 if(type==='Sepatu') return <Footprints {...props}/>;
 return <Weight {...props}/>;
}
export function Status({value}) {return <span className={'status status-'+value.toLowerCase()}><span/>{value==='Selesai'?'Siap diambil':value}</span>;}
export function Modal({title,children,onClose,wide=false}) {
 const ref=useRef(null);
 useEffect(()=>{ref.current?.showModal();return()=>ref.current?.close();},[]);
 return <dialog ref={ref} className={'modal '+(wide?'modal-wide':'')} onCancel={e=>{e.preventDefault();onClose();}} onClick={e=>{if(e.target===e.currentTarget)onClose();}}><div className="modal-heading"><h2>{title}</h2><button className="icon-button" onClick={onClose} aria-label="Tutup dialog"><X size={21}/></button></div>{children}</dialog>;
}
export function Receipt({order,settings,preview=false}) {
 if(!order)return null;
 return <div className={'receipt '+(preview?'receipt-preview':'')}><div className="receipt-brand"><img src="/assets/logo.png" alt=""/><b>Syaqil<br/>Laundry</b></div><b className="receipt-title">SYAQIL LAUNDRY</b><p>Bersih • Cepat • Praktis</p>{settings?.demo&&<p className="receipt-demo">NOTA DEMO · BUKAN TRANSAKSI NYATA</p>}{settings?.address&&<p>{settings.address}</p>}{settings?.phone&&<p>{settings.phone}</p>}<div className="receipt-rule"/><div className="receipt-meta"><span>No. Nota</span><strong>{order.number||'DRAFT — belum disimpan'}</strong><span>Tanggal</span><span>{dateText(order.created_at||new Date().toISOString(),true)}</span><span>Pelanggan</span><span>{order.customer_name||'Belum dipilih'}</span>{order.cashier_name&&<><span>Kasir</span><span>{order.cashier_name}</span></>}</div><div className="receipt-rule"/>{order.items.map((i,index)=><div className="receipt-line" key={index}><div><b>{i.category}</b><small>{i.service_name} · {quantity(i.quantity)} {i.unit} × {rupiah(i.price)}</small></div><span>{rupiah(i.subtotal)}</span></div>)}<div className="receipt-rule"/><div className="receipt-sum"><span>Subtotal</span><span>{rupiah(order.subtotal)}</span></div>{Number(order.discount)>0&&<div className="receipt-sum"><span>Diskon{order.discount_percent!=null?` (${quantity(order.discount_percent)}%)`:""}</span><span>−{rupiah(order.discount)}</span></div>}<div className="receipt-total"><b>Total</b><b>{rupiah(order.total)}</b></div><div className="receipt-sum"><span>{methodName[order.method]||'Tunai'}</span><span>{rupiah(order.paid)}</span></div><div className="receipt-sum"><span>Kembalian</span><span>{rupiah(order.change_amount)}</span></div>{order.number&&<div className="paid-stamp"><Check size={13}/> LUNAS</div>}{order.notes&&<p className="receipt-notes">Catatan: {order.notes}</p>}<div className="receipt-rule"/><p>Terima kasih! <br/>Simpan nota untuk pengambilan cucian.</p><Smile className="receipt-smile" size={20}/></div>;
}
export function ReceiptDecoration(){return <div className="receipt-decoration" aria-hidden="true"><div className="paper-teaser"><Logo compact/><div className="paper-line"/><span>CUCIAN BERES,<br/>HARI LEBIH RINGAN.</span><div className="paper-line"/><b>Terima kasih! :)</b></div><span className="print-sticker">STRUK<br/>LANGSUNG<br/>SIAP CETAK</span><span className="fresh-sticker">FRESH<br/>CLOTHES,<br/>BETTER<br/>DAYS <Smile size={27}/></span></div>;}
export function Empty({title,description,action}){return <div className="empty"><Shirt size={40}/><h3>{title}</h3><p>{description}</p>{action}</div>;}
export function ErrorBox({message}) {return message?<div className="error-box" role="alert">{message}</div>:null;}
