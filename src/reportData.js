export const offsetDay=(date,offset)=>{const day=new Date(date+'T00:00:00Z');day.setUTCDate(day.getUTCDate()+offset);return day.toISOString().slice(0,10);};
export const daysBetween=(from,to)=>Math.round((Date.parse(to)-Date.parse(from))/86400000)+1;
export function previousRange(from,to,comparison){
 if(comparison==='none')return null;
 if(comparison==='year'){
  const lastYear=date=>{const [year,month,day]=date.split('-').map(Number);const end=new Date(Date.UTC(year-1,month,0)).getUTCDate();return `${year-1}-${String(month).padStart(2,'0')}-${String(Math.min(day,end)).padStart(2,'0')}`;};
  return {from:lastYear(from),to:lastYear(to)};
 }
 const days=daysBetween(from,to);return {from:offsetDay(from,-days),to:offsetDay(from,-1)};
}
export function aggregateReport(data,from,to){
 const inside=date=>date&&date.slice(0,10)>=from&&date.slice(0,10)<=to;
 const orders=data.orders.filter(order=>inside(order.created_at));
 const customers=data.customers.filter(customer=>inside(customer.created_at));
 const days=new Map();
 for(let date=from;date<=to;date=offsetDay(date,1))days.set(date,{date,count:0,revenue:0,weight:0,customers:0});
 const services=new Map((data.services||[]).map(service=>[String(service.id),{name:service.name,count:0}]));
 const methods=new Map([['cash',0],['qris',0],['transfer',0],['other',0]]);
 const statuses=new Map(['Selesai','Diproses','Diterima','Diambil','Dibatalkan'].map(status=>[status,0]));
 let revenue=0,weight=0;
 for(const order of orders){
  const row=days.get(order.created_at.slice(0,10));
  const kg=order.items.filter(item=>item.unit==='kg').reduce((sum,item)=>sum+Number(item.quantity),0);
  revenue+=Number(order.total);weight+=kg;row.count++;row.revenue+=Number(order.total);row.weight+=kg;
  const method=methods.has(order.method)?order.method:'other';methods.set(method,methods.get(method)+1);
  statuses.set(order.status,(statuses.get(order.status)||0)+1);
  const seen=new Set();
  for(const item of order.items){const key=String(item.service_id??item.service_name);if(seen.has(key))continue;seen.add(key);if(!services.has(key))services.set(key,{name:item.service_name,count:0});services.get(key).count++;}
 }
 for(const customer of customers)days.get(customer.created_at.slice(0,10)).customers++;
 return {orders,revenue,weight,count:orders.length,customers:customers.length,daily:[...days.values()],services:[...services.values()].sort((a,b)=>b.count-a.count),methods:[...methods],statuses:[...statuses]};
}
export function growth(current,previous){return previous===0?(current===0?'0%':'Baru'):`${current>=previous?'+':''}${Number(((current-previous)/previous*100).toFixed(1)).toLocaleString('id-ID')}%`;}
