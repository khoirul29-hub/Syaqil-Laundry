export const jakartaDate=(now=new Date())=>now.toLocaleDateString('en-CA',{timeZone:'Asia/Jakarta'});
const offsetDay=(date,offset)=>{
 const day=new Date(date+'T00:00:00Z');
 day.setUTCDate(day.getUTCDate()+offset);
 return day.toISOString().slice(0,10);
};
export function inPeriod(order,period,now=new Date()) {
 if(period==='all')return true;
 const today=jakartaDate(now),date=order.created_at.slice(0,10);
 return date<=(today)&&date>=(period==='today'?today:offsetDay(today,-6));
}
export function weeklyActivity(orders,now=new Date()) {
 const today=jakartaDate(now),counts=new Map();
 for(const order of orders){const date=order.created_at.slice(0,10);counts.set(date,(counts.get(date)||0)+1);}
 return Array.from({length:7},(_,i)=>{
  const date=offsetDay(today,i-6);
  return {date,count:counts.get(date)||0,label:new Date(date+'T00:00:00Z').toLocaleDateString('id-ID',{weekday:'short',timeZone:'UTC'}),today:date===today};
 });
}
export function customerSummaries(customers,orders) {
 const grouped=new Map();
 for(const order of orders){const id=Number(order.customer_id);if(!grouped.has(id))grouped.set(id,[]);grouped.get(id).push(order);}
 return customers.map(customer=>{
  const visits=grouped.get(Number(customer.id))||[];
  const lastOrder=visits.reduce((last,order)=>!last||order.created_at>last.created_at||order.created_at===last.created_at&&Number(order.id)>Number(last.id)?order:last,null);
  return {...customer,visits:visits.length,active:visits.filter(o=>o.status!=='Diambil').length,lastOrder};
 });
}
