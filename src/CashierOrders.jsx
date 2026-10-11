import React,{useEffect,useRef,useState} from 'react';
import {createPortal} from 'react-dom';
import {
 ArrowDownUp,CalendarDays,Check,CheckCircle2,Clock3,Download,Eye,
 FileText,MoreVertical,Package,Plus,Printer,Search,ShoppingBag
} from 'lucide-react';
import {dateText,localPhone,methodName,quantity,rupiah,statuses} from './lib';
import {inPeriod,weeklyActivity} from './insights';
import {Empty,ErrorBox,Status} from './components';
import {Pagination,ServiceName} from './ReferenceUI';
import {dateMatches,exportExcel,orderWeight} from './referenceUtils';
import {downloadReceipt} from './receiptPdf';

const STATUS_ICONS={Semua:ShoppingBag,Diterima:Package,Diproses:Clock3,Selesai:CheckCircle2,Diambil:Package};
const SUMMARY_CARDS=[
 {label:'Total Pesanan',status:'Semua',icon:FileText,tone:'blue'},
 {label:'Selesai',status:'Selesai',icon:Check,tone:'green'},
 {label:'Diproses',status:'Diproses',icon:Clock3,tone:'amber'},
 {label:'Diambil',status:'Diambil',icon:Package,tone:'purple'}
];

function OrdersSummary({orders}) {
 const [period,setPeriod]=useState('all');
 const scoped=orders.filter(order=>inPeriod(order,period));
 return <section className="orders-summary" aria-labelledby="orders-summary-title">
  <header className="orders-panel-heading">
   <h2 id="orders-summary-title"><span className="orders-heading-icon"><Package size={17}/></span>Ringkasan Pesanan</h2>
   <select aria-label="Periode ringkasan pesanan" value={period} onChange={event=>setPeriod(event.target.value)}>
    <option value="all">Semua</option><option value="today">Hari Ini</option><option value="week">7 Hari</option>
   </select>
  </header>
  <div className="orders-summary-grid">
   {SUMMARY_CARDS.map(({label,status,icon:Icon,tone})=>{
    const matching=scoped.filter(order=>status==='Semua'||order.status===status);
    const activity=weeklyActivity(matching),peak=Math.max(1,...activity.map(day=>day.count));
    return <article key={status} className={`orders-summary-card orders-summary-card--${tone}`}>
     <span className="orders-summary-icon"><Icon size={28} strokeWidth={2}/></span>
     <div><strong>{matching.length}</strong><span>{label}</span></div>
     <div className="orders-mini-bars" aria-hidden="true">{activity.map(day=><i key={day.date} style={{height:`${3+day.count/peak*25}px`}}/>)}</div>
    </article>;
   })}
  </div>
 </section>;
}

function OrdersStatusGuide() {
 return <section className="orders-status-guide" aria-labelledby="orders-guide-title">
  <header className="orders-panel-heading"><div>
   <h2 id="orders-guide-title"><span className="orders-heading-icon"><ShoppingBag size={15}/></span>Status Pesanan</h2>
   <p>Pantau progres pesanan dengan mudah</p>
  </div><span className="orders-doodle" aria-hidden="true">✦</span></header>
  <ol>{statuses.map((status,index)=>{
   const Icon=STATUS_ICONS[status];
   return <li key={status} className={`orders-stage orders-stage--${index}`}><span><Icon size={19}/></span><b>{status}</b></li>;
  })}</ol>
 </section>;
}

export default function CashierOrders({data,onSelect,print,go}) {
 const [search,setSearch]=useState(''),[status,setStatus]=useState('Semua');
 const [period,setPeriod]=useState('all'),[from,setFrom]=useState(''),[to,setTo]=useState('');
 const [sort,setSort]=useState('newest'),[page,setPage]=useState(1);
 const [menu,setMenu]=useState(null),[pdfError,setPdfError]=useState(''),[pdfBusy,setPdfBusy]=useState(false);
 const menuRef=useRef(null),triggerRef=useRef(null);
 const invalidRange=period==='custom'&&from&&to&&from>to;
 const orders=data.orders.filter(order=>
  (status==='Semua'||order.status===status)&&
  !invalidRange&&(period==='custom'?dateMatches(order,from,to):inPeriod(order,period))&&
  [order.number,order.customer_name,order.customer_phone,localPhone(order.customer_phone)].join(' ').toLowerCase().includes(search.trim().toLowerCase())
 ).sort((a,b)=>sort==='oldest'
  ?a.created_at.localeCompare(b.created_at)||Number(a.id)-Number(b.id)
  :b.created_at.localeCompare(a.created_at)||Number(b.id)-Number(a.id));
 const current=Math.min(page,Math.max(1,Math.ceil(orders.length/10)));
 const visible=orders.slice((current-1)*10,current*10);
 const selectedMenuOrder=menu&&data.orders.find(order=>order.id===menu.id);

 useEffect(()=>{
  if(!menu)return;
  menuRef.current?.querySelector('button')?.focus();
  const pointer=event=>{if(!event.target.closest('.orders-action-menu,.orders-more-button'))setMenu(null);};
  const key=event=>{if(event.key==='Escape'){setMenu(null);triggerRef.current?.focus();}};
  const dismiss=()=>setMenu(null);
  document.addEventListener('pointerdown',pointer);
  window.addEventListener('keydown',key);
  window.addEventListener('resize',dismiss);
  window.addEventListener('scroll',dismiss,true);
  return()=>{document.removeEventListener('pointerdown',pointer);window.removeEventListener('keydown',key);window.removeEventListener('resize',dismiss);window.removeEventListener('scroll',dismiss,true);};
 },[menu]);

 function change(setter,value){setter(value);setPage(1);setMenu(null);}
 function openMenu(event,order){
  const rect=event.currentTarget.getBoundingClientRect();
  triggerRef.current=event.currentTarget;
  setMenu(menu?.id===order.id?null:{id:order.id,left:Math.max(8,Math.min(rect.right-174,window.innerWidth-182)),top:rect.bottom+134>window.innerHeight?rect.top-134:rect.bottom+6});
 }
 async function savePdf(order){
  setMenu(null);setPdfError('');setPdfBusy(true);
  try{await downloadReceipt(order,data.settings);}catch{setPdfError('PDF belum dapat dibuat. Coba cetak nota melalui browser.');}
  finally{setPdfBusy(false);}
 }
 function excel(){
  exportExcel('Pesanan-Syaqil',['No. Nota','Tanggal','Pelanggan','Layanan','Berat (kg)','Total','Diskon (%)','Potongan','Metode','Status'],orders.map(order=>[
   order.number,order.created_at,order.customer_name,order.items.map(item=>item.service_name).join(', '),orderWeight(order),Number(order.total),
   order.discount_percent==null?'':Number(order.discount_percent),Number(order.discount),methodName[order.method],order.status
  ]));
 }
 return <div className="ref-page cashier-orders-page">
  <section className="orders-banner" aria-labelledby="orders-title"><div className="orders-banner-copy">
   <h1 id="orders-title">Semua Pesanan</h1><p>Kelola dan pantau status pesanan laundry dengan mudah dan real-time!</p>
  </div></section>
  <div className="orders-toolbar">
   <div className="orders-status-tabs" role="group" aria-label="Filter status pesanan">
    {['Semua',...statuses].map(value=>{const Icon=STATUS_ICONS[value];return <button key={value} className={status===value?'active':''} aria-pressed={status===value} onClick={()=>change(setStatus,value)}>
     <Icon size={24}/><span>{value}<small>({data.orders.filter(order=>value==='Semua'||order.status===value).length})</small></span>
    </button>;})}
   </div>
   <div className="orders-toolbar-actions"><button className="secondary" onClick={excel}><Download size={17}/>Export Excel</button><button className="primary" onClick={()=>go('kasir')}><Plus size={20}/>Tambah Pesanan</button></div>
  </div>
  <div className="orders-workspace">
   <div className="orders-main">
    <div className="orders-filters">
     <label className="orders-select"><CalendarDays size={21}/><select aria-label="Periode transaksi" value={period} onChange={event=>change(setPeriod,event.target.value)}>
      <option value="all">Semua tanggal</option><option value="today">Hari ini</option><option value="week">7 hari terakhir</option><option value="custom">Pilih rentang tanggal</option>
     </select></label>
     <label className="orders-select orders-sort"><ArrowDownUp size={17}/><select aria-label="Urutan pesanan" value={sort} onChange={event=>change(setSort,event.target.value)}><option value="newest">Urutkan: Terbaru</option><option value="oldest">Urutkan: Terlama</option></select></label>
     <div className="orders-search"><Search size={21}/><input aria-label="Cari pesanan" placeholder="Cari nomor nota, nama pelanggan..." value={search} onChange={event=>change(setSearch,event.target.value)}/></div>
    </div>
    {period==='custom'&&<div className="ref-date-inputs"><label>Dari tanggal<input type="date" value={from} onInput={event=>change(setFrom,event.target.value)} onChange={event=>change(setFrom,event.target.value)}/></label><label>Sampai tanggal<input type="date" min={from||undefined} value={to} onInput={event=>change(setTo,event.target.value)} onChange={event=>change(setTo,event.target.value)}/></label></div>}
    <ErrorBox message={invalidRange?'Tanggal akhir harus setelah tanggal awal.':pdfError}/>
    {pdfBusy&&<p className="orders-pdf-status" role="status">Menyiapkan PDF nota...</p>}
    <div className="ref-table-wrap orders-table-wrap" tabIndex={0} aria-label="Daftar pesanan, geser untuk melihat seluruh kolom">
     <table className="ref-table orders-table"><thead><tr><th>No.</th><th>No. Pesanan</th><th>Pelanggan</th><th>Layanan</th><th>Berat</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
      <tbody>{visible.map((order,index)=><tr key={order.id}>
       <td>{(current-1)*10+index+1}</td><td><button className="ref-table-link" onClick={()=>onSelect(order)}>{order.number}</button></td>
       <td>{order.customer_name}</td><td><ServiceName name={order.items[0]?.service_name}/>{order.items.length>1&&<small className="ref-extra-items">+{order.items.length-1} layanan</small>}</td>
       <td>{quantity(orderWeight(order))} kg</td><td>{rupiah(order.total)}</td><td><Status value={order.status}/></td><td>{dateText(order.created_at,true)}</td>
       <td><div className="orders-row-actions">
        <button aria-label={'Lihat '+order.number} title="Lihat detail" onClick={()=>onSelect(order)}><Eye size={19}/></button>
        <button aria-label={'Cetak '+order.number} title="Cetak nota" onClick={()=>print(order)}><Printer size={19}/></button>
        <button className="orders-more-button" aria-label={'Opsi '+order.number} title="Opsi pesanan" aria-expanded={menu?.id===order.id} onClick={event=>openMenu(event,order)}><MoreVertical size={18}/></button>
       </div></td>
      </tr>)}</tbody>
     </table>
     {!orders.length&&<Empty title="Tidak ada pesanan yang cocok" description="Ubah filter tanggal, status, atau kata kunci pencarian."/>}
    </div>
    <Pagination page={current} total={orders.length} pageSize={10} onPage={value=>{setPage(value);setMenu(null);}}/>
   </div>
   <aside className="orders-aside" aria-label="Ringkasan dan alur pesanan">
    <OrdersSummary orders={data.orders}/><OrdersStatusGuide/>
    <div className="orders-promo" role="img" aria-label="Pesanan lebih terkelola dan rapi. Laundry makin mudah!"/>
   </aside>
  </div>
  {selectedMenuOrder&&createPortal(<div ref={menuRef} className="orders-action-menu" style={{left:menu.left,top:menu.top}} aria-label={'Opsi '+selectedMenuOrder.number}>
   <button onClick={()=>{setMenu(null);onSelect(selectedMenuOrder);}}><Eye size={16}/>Lihat detail</button>
   <button onClick={()=>{setMenu(null);print(selectedMenuOrder);}}><Printer size={16}/>Cetak nota</button>
   <button onClick={()=>savePdf(selectedMenuOrder)}><Download size={16}/>Download PDF</button>
  </div>,document.body)}
 </div>;
}
