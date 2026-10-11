import React, {useMemo, useState} from 'react';
import {ArrowUpRight, ChartColumn, ChevronRight, Clock3, Eye, Package, Printer, Settings2, Shirt, ShoppingCart, Users, Wallet, Zap} from 'lucide-react';
import {dateText, quantity, rupiah, statuses} from './lib';
import {jakartaDate} from './insights';
import './dashboard-reference.css';

const money=value=>rupiah(value).replace('Rp','Rp ');
const stateColors=['#0754ff','#ffcd24','#51d76c','#9650f6'];
const serviceIcon=name=>/jam/i.test(name)?Clock3:/ekspres|express/i.test(name)?Zap:Shirt;
const dayOffset=(date,amount)=>{
 const result=new Date(date+'T00:00:00Z');
 result.setUTCDate(result.getUTCDate()+amount);
 return result.toISOString().slice(0,10);
};
const shortDate=date=>new Date(date+'T00:00:00Z').toLocaleDateString('id-ID',{day:'numeric',month:'short',timeZone:'UTC'});

function DashboardMetric({icon:Icon,label,value,tone,detail,onClick}) {
 return <button className={`rd-metric rd-metric-${tone}`} onClick={onClick}>
  <span className="rd-metric-icon"><Icon size={27} strokeWidth={1.8}/></span>
  <span className="rd-metric-copy"><span className="rd-metric-label">{label}</span><strong>{value}</strong></span>
  <span className="rd-metric-detail">{detail}<ArrowUpRight size={14}/></span>
 </button>;
}

function RevenueChart({orders}) {
 const [period,setPeriod]=useState(7);
 const [hovered,setHovered]=useState(null);
 const today=jakartaDate();
 const days=useMemo(()=>Array.from({length:period},(_,index)=>{
  const date=dayOffset(today,index-period+1);
  return {date,total:orders.filter(order=>order.created_at.slice(0,10)===date).reduce((sum,order)=>sum+Number(order.total),0)};
 }),[orders,period,today]);
 const largest=Math.max(1,...days.map(day=>day.total));
 const magnitude=10**Math.floor(Math.log10(largest));
 const ceiling=Math.max(10000,Math.ceil(largest/magnitude)*magnitude);
 const ticks=[ceiling,ceiling*.75,ceiling*.5,ceiling*.25,0];
 return <section className="rd-panel rd-revenue" aria-labelledby="rd-revenue-title">
  <div className="rd-panel-heading"><h2 id="rd-revenue-title"><ChartColumn size={18}/>Grafik Pendapatan</h2><select aria-label="Periode grafik pendapatan" value={period} onChange={event=>{setPeriod(Number(event.target.value));setHovered(null);}}><option value={7}>7 Hari Terakhir</option><option value={14}>14 Hari Terakhir</option><option value={30}>30 Hari Terakhir</option></select></div>
  <div className="rd-chart"><div className="rd-axis" aria-hidden="true">{ticks.map(value=><span key={value}>{money(value)}</span>)}</div><div className="rd-chart-body"><div className="rd-chart-lines" aria-hidden="true">{ticks.map(value=><i key={value}/>)}</div><div className={'rd-bars'+(period>7?' rd-bars-dense':'')} role="list" aria-label="Pendapatan per hari">{days.map((day,index)=><div className="rd-chart-day" role="listitem" key={day.date}>
   <button className={'rd-bar-track'+(hovered===index?' is-focused':'')} aria-label={`${shortDate(day.date)}: ${money(day.total)}`} onMouseEnter={()=>setHovered(index)} onMouseLeave={()=>setHovered(null)} onFocus={()=>setHovered(index)} onBlur={()=>setHovered(null)}><span className="rd-bar" style={{height:`${day.total/ceiling*100}%`}}/>{hovered===index&&<span className="rd-bar-tooltip"><b>{money(day.total)}</b><small>{shortDate(day.date)}</small></span>}</button>
   <span className="rd-date-label">{period===7||index%Math.ceil(period/7)===0||index===period-1?shortDate(day.date):''}</span>
  </div>)}</div></div></div>
 </section>;
}

function StatusChart({orders,go}) {
 const counts=statuses.map(status=>orders.filter(order=>order.status===status).length);
 let cumulative=0;
 const segments=counts.map((count,index)=>{const start=cumulative;cumulative+=orders.length?count/orders.length*100:0;return `${stateColors[index]} ${start}% ${cumulative}%`;});
 return <section className="rd-panel rd-status" aria-labelledby="rd-status-title"><div className="rd-panel-heading"><h2 id="rd-status-title"><Settings2 size={18}/>Status Pesanan</h2></div><div className="rd-status-body"><button className="rd-donut" aria-label={`Lihat ${orders.length} pesanan`} onClick={()=>go('pesanan')} style={{background:orders.length?`conic-gradient(${segments.join(',')})`:'#eef2fb'}}><span><strong>{orders.length}</strong><small>Total</small></span></button><div className="rd-status-legend">{statuses.map((status,index)=><div key={status}><i style={{background:stateColors[index]}}/><span>{status}</span><b>{counts[index]}</b><small>{orders.length?Math.round(counts[index]/orders.length*100):0}%</small></div>)}</div></div></section>;
}

function PopularServices({orders,services}) {
 const popular=services.map(service=>({...service,count:orders.filter(order=>order.items.some(item=>Number(item.service_id)===Number(service.id))).length})).sort((a,b)=>b.count-a.count);
 const total=popular.reduce((sum,service)=>sum+service.count,0);
 return <section className="rd-panel rd-popular" aria-labelledby="rd-popular-title"><div className="rd-panel-heading"><h2 id="rd-popular-title"><Shirt size={18}/>Layanan Terpopuler</h2></div><div className="rd-service-list">{popular.map(service=>{const Icon=serviceIcon(service.name),percent=total?Math.round(service.count/total*100):0;return <div className="rd-service" key={service.id}><Icon size={27} className={/jam/i.test(service.name)?'rd-icon-express':''}/><div><b>{service.name}</b><small>{service.count} pesanan</small><span className="rd-service-track"><i style={{width:`${percent}%`}}/></span></div><span>{percent}%</span></div>;})}{!popular.length&&<p className="rd-empty">Belum ada layanan aktif.</p>}</div></section>;
}

export default function ReferenceDashboard({data,user,go,onSelect,print}) {
 const today=jakartaDate();
 const todayOrders=data.orders.filter(order=>order.created_at.slice(0,10)===today);
 const todayRevenue=todayOrders.reduce((sum,order)=>sum+Number(order.total),0);
 const newCustomers=data.customers.filter(customer=>customer.created_at?.slice(0,7)===today.slice(0,7)).length;
 const processing=data.orders.filter(order=>order.status==='Diproses').length;
 const latest=[...data.orders].sort((a,b)=>b.created_at.localeCompare(a.created_at)||Number(b.id)-Number(a.id)).slice(0,3);
 return <div className="reference-dashboard">
  <section className="rd-welcome"><div className="rd-welcome-copy"><h1>Selamat Datang,<br/><span>{user.name}!</span></h1><p>Yuk kelola laundry dengan lebih mudah hari ini.</p></div></section>
  <div className="rd-metrics">
   <DashboardMetric icon={ShoppingCart} label="Pesanan Hari Ini" value={todayOrders.length} tone="blue" detail="pesanan masuk" onClick={()=>go('pesanan')}/>
   <DashboardMetric icon={Wallet} label="Pendapatan Hari Ini" value={money(todayRevenue)} tone="green" detail="dari nota hari ini" onClick={()=>go('riwayat')}/>
   <DashboardMetric icon={Users} label="Pelanggan Baru" value={newCustomers} tone="purple" detail="bulan ini" onClick={()=>go('pelanggan')}/>
   <DashboardMetric icon={Package} label="Pesanan Diproses" value={processing} tone="orange" detail="dalam pengerjaan" onClick={()=>go('pesanan')}/>
  </div>
  <div className="rd-chart-grid"><RevenueChart orders={data.orders}/><StatusChart orders={data.orders} go={go}/><PopularServices orders={data.orders} services={data.services}/></div>
  <div className="rd-bottom"><section className="rd-panel rd-recent"><div className="rd-panel-heading"><h2><ShoppingCart size={18}/>Pesanan Terbaru</h2><button className="rd-see-all" onClick={()=>go('pesanan')}>Lihat semua <ChevronRight size={14}/></button></div><div className="rd-table-wrap"><table><thead><tr><th>No.</th><th>No. Pesanan</th><th>Pelanggan</th><th>Layanan</th><th>Berat</th><th>Total</th><th>Status</th><th>Waktu</th><th className="rd-action-col">Aksi</th></tr></thead><tbody>{latest.map((order,index)=>{const Icon=serviceIcon(order.items[0]?.service_name||'');return <tr key={order.id}><td>{index+1}</td><td><button className="rd-order-number" onClick={()=>onSelect(order)}>{order.number}</button></td><td>{order.customer_name}</td><td><span className="rd-table-service"><Icon size={18}/><span>{order.items[0]?.service_name}{order.items.length>1&&<small> +{order.items.length-1} item</small>}</span></span></td><td>{quantity(order.items.reduce((sum,item)=>sum+Number(item.quantity),0))} kg</td><td className="rd-table-money">{money(order.total)}</td><td><span className={'rd-state rd-state-'+statuses.indexOf(order.status)}>{order.status}</span></td><td title={dateText(order.created_at,true)}>{order.created_at.slice(11,16)}</td><td><div className="rd-row-actions"><button onClick={()=>onSelect(order)} aria-label={'Detail '+order.number}><Eye size={15}/></button><button onClick={()=>print(order)} aria-label={'Cetak '+order.number}><Printer size={15}/></button></div></td></tr>;})}{!latest.length&&<tr><td colSpan={9} className="rd-empty">Belum ada pesanan. Transaksi baru akan tampil di sini.</td></tr>}</tbody></table></div></section><button className="rd-laundry-promo" onClick={()=>go('kasir')} aria-label="Laundry hari ini lebih mudah. Buat transaksi baru."/></div>
 </div>;
}
