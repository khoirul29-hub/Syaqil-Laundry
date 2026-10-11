import React from 'react';
import {ArrowUpRight,Check,ChevronRight,Clock3,FileText,PackageCheck,Shirt,Users,Printer,Repeat2,HandHeart} from 'lucide-react';
import {dateText,rupiah,quantity,statuses,methodName} from './lib';
import {weeklyActivity} from './insights';
import './pages.css';

export const initials=name=>name.trim().split(/\s+/).map(n=>n[0]).slice(0,2).join('');

export function DashboardPanels({orders,onSelect}) {
 const days=weeklyActivity(orders),total=days.reduce((n,d)=>n+d.count,0),max=Math.max(1,...days.map(d=>d.count));
 const ready=orders.filter(o=>o.status==='Selesai').sort((a,b)=>a.created_at.localeCompare(b.created_at));
 return <div className="dashboard-panels">
  <section className="activity-panel" aria-labelledby="activity-title">
   <div className="panel-heading"><div><span className="panel-kicker">RITME CUCIAN</span><h2 id="activity-title">Seminggu di laundry</h2></div><span className="date-chip">7 hari terakhir</span></div>
   <div className="activity-total"><strong>{total}</strong><span>pesanan masuk<small>{dateText(days[0].date)} sampai {dateText(days[6].date)}</small></span></div>
   <ol className="activity-chart" aria-label="Jumlah pesanan per hari">{days.map(day=><li key={day.date} className={day.today?'is-today':''} aria-label={`${dateText(day.date)}: ${day.count} pesanan${day.today?', hari ini':''}`}><div className="activity-bar-space"><span className="bar-value">{day.count}</span><div className="activity-bar" style={{height:`${day.count/max*92}px`}}/></div><b>{day.label}</b><small>{day.today?'Hari ini':day.date.slice(8)}</small></li>)}</ol>
  </section>
  <section className="pickup-panel" aria-labelledby="pickup-title"><div className="pickup-heading"><div><span className="panel-kicker">SIAP KEMBALI KE PELANGGAN</span><h2 id="pickup-title">Bersih, rapi.<br/>Tinggal dijemput.</h2></div><span className="pickup-count">{ready.length}<small>pesanan</small></span></div>
   <div className="pickup-list">{ready.length?ready.slice(0,3).map(order=><button key={order.id} onClick={()=>onSelect(order)}><span className="pickup-icon"><PackageCheck size={21}/></span><span><b>{order.customer_name}</b><small>{order.number}</small></span><ChevronRight size={17}/></button>):<div className="pickup-empty"><PackageCheck size={25}/><b>Belum ada yang menunggu jemput.</b><span>Pesanan berstatus selesai akan muncul di sini.</span></div>}</div>
   <p className="panel-footnote">{ready.length>3?`${ready.length-3} pesanan lainnya ada di halaman Pesanan.`:'Periksa nota sebelum menyerahkan cucian.'}</p>
  </section>
 </div>;
}

const stageInfo=[
 {Icon:Shirt,label:'Diterima',hint:'Menunggu giliran cuci'},
 {Icon:Clock3,label:'Diproses',hint:'Sedang dibuat bersih'},
 {Icon:PackageCheck,label:'Siap diambil',hint:'Siap bertemu pemiliknya'},
 {Icon:HandHeart,label:'Diambil',hint:'Sudah kembali ke pelanggan'}
];
export function OrderStages({orders,filter,onFilter}) {
 const active=orders.filter(o=>o.status!=='Diambil').length;
 return <section className="order-overview" aria-label="Ringkasan tahapan pesanan">
  <div className="order-overview-heading"><div><span className="panel-kicker">DARI KERANJANG KE PELANGGAN</span><h2>Setiap cucian, terpantau.</h2></div><span className="active-counter"><b>{active}</b> pesanan aktif</span></div>
  <div className="stage-grid">{statuses.map((status,index)=>{const {Icon,label,hint}=stageInfo[index],count=orders.filter(o=>o.status===status).length;return <button key={status} className={`stage-card stage-${index} ${filter===status?'selected':''}`} aria-pressed={filter===status} onClick={()=>onFilter(filter===status?'Semua':status)}><div className="stage-top"><span><Icon size={20}/>{label}</span><span className="stage-number">0{index+1}</span></div><div className="stage-value"><strong>{count}</strong><span>pesanan</span>{filter===status&&<Check size={19}/>}</div><small>{hint}</small></button>;})}</div>
  <div className="stage-caption"><span>Klik tahapan untuk menyaring daftar pesanan.</span><button className="text-button" onClick={()=>onFilter('Semua')} aria-pressed={filter==='Semua'}>Semua pesanan ({orders.length})</button></div>
 </section>;
}

export function CustomerHighlights({customers,onSelect}) {
 const repeat=customers.filter(c=>c.visits>=2).length,active=customers.filter(c=>c.active>0).length;
 const frequent=[...customers].filter(c=>c.visits>0).sort((a,b)=>b.visits-a.visits||a.name.localeCompare(b.name)).slice(0,3);
 return <>
  <section className="customer-banner" aria-label="Ringkasan pelanggan"><div className="customer-banner-copy"><span className="panel-kicker">CUCIAN BERES, PELANGGAN SENANG</span><h2>Kenal namanya.<br/>Ingat cuciannya.</h2><div className="customer-avatar-stack" aria-hidden="true">{customers.slice(0,4).map((c,i)=><span key={c.id} className={'customer-tone-'+i%3}>{initials(c.name)}</span>)}<span className="stack-caption">Buku pelanggan Syaqil</span></div></div><div className="customer-banner-stats"><div className="customer-total"><Users size={24}/><strong>{customers.length}</strong><span>pelanggan terdaftar</span></div><div className="customer-substats"><span><b>{repeat}</b> pernah datang lagi</span><span><b>{active}</b> punya cucian aktif</span></div></div></section>
  {frequent.length>0&&<section className="frequent-section" aria-label="Pelanggan yang paling sering datang"><div className="frequent-title"><Repeat2 size={19}/><span><b>Sering mampir</b><small>Berdasarkan jumlah transaksi</small></span></div><div className="frequent-list">{frequent.map((customer,index)=><button key={customer.id} onClick={()=>onSelect(customer)}><span className={'customer-initial customer-tone-'+index%3}>{initials(customer.name)}</span><span><b>{customer.name}</b><small>{customer.visits} transaksi</small></span><ArrowUpRight size={17}/></button>)}</div></section>}
 </>;
}

export function ReceiptHighlights({orders,onSelect,print,periodLabel}) {
 const latest=[...orders].sort((a,b)=>b.created_at.localeCompare(a.created_at)||Number(b.id)-Number(a.id))[0];
 const total=orders.reduce((n,o)=>n+Number(o.total),0),discount=orders.reduce((n,o)=>n+Number(o.discount),0);
 return <section className="receipt-overview" aria-label="Ringkasan arsip nota"><div className="receipt-overview-copy"><span className="panel-kicker">ARSIP TRANSAKSI / {periodLabel}</span><h2>Nota tersimpan.<br/>Gampang ditemukan.</h2><div className="archive-stats"><div><strong>{orders.length}</strong><span>nota lunas</span></div><div><strong>{rupiah(total)}</strong><span>total nilai nota</span></div><div><strong>{rupiah(discount)}</strong><span>potongan tercatat</span></div></div></div><div className="latest-receipt"><div className="latest-receipt-title"><FileText size={18}/><span>NOTA TERBARU</span><span className="mini-paid">{latest?'LUNAS':'ARSIP'}</span></div>{latest?<><b className="latest-customer">{latest.customer_name}</b><span className="latest-number">{latest.number}</span><div className="latest-receipt-total"><span>{dateText(latest.created_at)}</span><b>{rupiah(latest.total)}</b></div><p>{methodName[latest.method]} · {quantity(latest.items.reduce((n,i)=>n+Number(i.quantity),0))} {latest.items[0]?.unit}</p><div className="latest-receipt-actions"><button onClick={()=>onSelect(latest)}>Lihat detail <ChevronRight size={15}/></button><button aria-label={'Cetak nota terbaru '+latest.number} onClick={()=>print(latest)}><Printer size={17}/></button></div></>:<div className="latest-receipt-empty"><FileText size={30}/><b>Belum ada nota</b><span>Pilih periode lain atau buat transaksi baru.</span></div>}</div></section>;
}
