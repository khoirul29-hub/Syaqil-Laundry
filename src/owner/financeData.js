import {initialCustomers} from './mockData.js';

export const FINANCE_TODAY='2026-10-06';
export const categories=['Operasional','Karyawan','Peralatan','Lainnya'];
export const paymentMethods=['Tunai','Transfer','E-Wallet','QRIS'];
export const serviceNames=['Reguler','Ekspres 1 Hari','Ekspres Jam','Cuci + Setrika','Setrika Saja'];
export const financeColors=['#2775ff','#91c5ff','#ffb53e','#ff8fa0','#64bc92'];
export const monthNames=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
const customerCounts=[28,20,18,16,14];
const financeCustomers=['Rian Pratama','Siti Aisyah','Andi Pratama','Fajar Ramadhan','Nabila Putri',...initialCustomers.map(c=>c.name).filter(name=>!['Rian Pratama','Siti Aisyah','Andi Pratama','Fajar Ramadhan','Nabila Putri'].includes(name))];
const octoberCounts=[62,58,42,18,6],octoberRevenue=[3250000,2180000,1520000,540000,360000];
const monthlyRevenue=[3250000,3400000,5120000,3450000,4200000,5100000,5750000,6200000,7000000,7850000];
const monthlyCounts=[92,96,120,108,118,134,148,159,172,186];

function distribute(total,weights){
 const sum=weights.reduce((a,b)=>a+b,0),values=weights.map(w=>Math.floor(total*w/sum));
 for(let i=0,left=total-values.reduce((a,b)=>a+b,0);i<left;i++)values[i%values.length]++;
 return values;
}
function makeMonth(month){
 const count=monthlyCounts[month-1],counts=distribute(count,octoberCounts),revenues=distribute(monthlyRevenue[month-1],octoberRevenue),rows=[];
 const days=new Date(Date.UTC(2026,month,0)).getUTCDate();
 for(let s=0;s<5;s++)for(let j=0;j<counts[s];j++){
  const i=rows.length,day=1+(i*7+Math.floor(i/31))%days,customerIndex=i<96?customerCounts.reduce((a,n,k)=>a.index<0&&i<a.sum+n?{index:k,sum:a.sum+n}:{index:a.index,sum:a.sum+n},{index:-1,sum:0}).index:5+i-96;
  const date=`2026-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
  const amount=Math.floor(revenues[s]/counts[s])+(j<revenues[s]%counts[s]?1:0);
  const method=[...Array(8).fill('Tunai'),...Array(6).fill('Transfer'),...Array(4).fill('E-Wallet'),...Array(2).fill('QRIS')][(i*7)%20];
  rows.push({id:`FIN-${month}-${i+1}`,number:`DM-${date.replaceAll('-','')}-${String(i+1).padStart(3,'0')}`,date,hour:[8,10,11,12,12,13,13,14,14,15,15,16,17,18,20][(i*3+day)%15],service:serviceNames[s],customer:financeCustomers[customerIndex],employee:['Ari Pratama','Rina Wulandari','Nadia Putri','Ucok Saputra','Siska Melani'][i%5],method,amount});
 }
 return rows;
}
export const financeTransactions=Array.from({length:10},(_,i)=>makeMonth(i+1)).flat();

const currentExpenses=[
 ['2026-10-06','Operasional','Pembelian deterjen',350000,'Tunai'],
 ['2026-10-05','Karyawan','Gaji kasir bulan Oktober',500000,'Transfer'],
 ['2026-10-04','Peralatan','Pembelian hanger',120000,'Tunai'],
 ['2026-10-03','Operasional','Token listrik',200000,'E-Wallet'],
 ['2026-10-02','Operasional','Air PDAM',150000,'Transfer'],
 ['2026-10-01','Lainnya','Biaya kebersihan',80000,'Tunai'],
 ['2026-10-01','Operasional','Sewa dan perawatan mesin',600000,'Transfer'],
 ['2026-10-01','Operasional','Persediaan pewangi',320000,'Tunai'],
 ['2026-10-01','Karyawan','Uang makan tim',220000,'Transfer']
];
export const initialExpenses=currentExpenses.map(([date,category,description,amount,method],i)=>({id:i+1,date,category,description,amount,method,actor:'Desi Gusrianita'}));
for(let month=1;month<10;month++){
 const total=[950000,1000000,1400000,980000,1350000,1450000,1680000,1920000,2260000][month-1];
 const amounts=distribute(total,[64,28,5,3]);
 categories.forEach((category,i)=>initialExpenses.push({id:month*10+20+i,date:`2026-${String(month).padStart(2,'0')}-15`,category,description:`Rekap demo ${category.toLowerCase()} ${monthNames[month-1]}`,amount:amounts[i],method:i%2?'Transfer':'Tunai',actor:'Desi Gusrianita'}));
}
export const defaultPeriod={preset:'month',from:'2026-10-01',to:'2026-10-31'};
export function validDate(value){return /^\d{4}-\d{2}-\d{2}$/.test(value)&&!Number.isNaN(Date.parse(value))&&new Date(value+'T12:00:00Z').toISOString().slice(0,10)===value;}
export function periodError(period){return !validDate(period.from)||!validDate(period.to)?'Lengkapi tanggal awal dan akhir.':period.from>period.to?'Tanggal akhir tidak boleh sebelum tanggal awal.':'';}
export function inPeriod(row,period){return !periodError(period)&&row.date>=period.from&&row.date<=period.to;}
export function previousPeriod(period){
 if(periodError(period))return period;
 const start=Date.parse(period.from+'T00:00:00Z'),end=Date.parse(period.to+'T00:00:00Z');
 if(period.preset==='month'||period.preset==='previous'){
  const date=new Date(start),year=date.getUTCFullYear(),month=date.getUTCMonth();
  return {from:new Date(Date.UTC(year,month-1,1)).toISOString().slice(0,10),to:new Date(Date.UTC(year,month,0)).toISOString().slice(0,10)};
 }
 const duration=end-start+86400000;return {from:new Date(start-duration).toISOString().slice(0,10),to:new Date(start-86400000).toISOString().slice(0,10)};
}
export const sumAmounts=rows=>rows.reduce((sum,r)=>sum+r.amount,0);
export const percent=(n,total)=>total?Math.round(n/total*100):0;
export const growth=(current,previous)=>previous?Math.round((current-previous)/previous*100):null;
export const shortMoney=n=>n>=1000000?`Rp${(n/1000000).toLocaleString('id-ID',{maximumFractionDigits:2})}M`:n>=1000?`Rp${(n/1000).toLocaleString('id-ID',{maximumFractionDigits:1})}K`:`Rp${n}`;
export function grouped(rows,key,ordered){
 const map=new Map((ordered||[]).map(label=>[label,{label,count:0,total:0}]));
 for(const row of rows){const label=row[key];if(!map.has(label))map.set(label,{label,count:0,total:0});const entry=map.get(label);entry.count++;entry.total+=row.amount;}
 return [...map.values()];
}
export function serviceOverview(rows){return [...rows.slice(0,3),{label:'Lainnya',count:rows.slice(3).reduce((n,r)=>n+r.count,0),total:rows.slice(3).reduce((n,r)=>n+r.total,0)}];}
export function dailySummary(rows){
 const groups=new Map();for(const row of rows){if(!groups.has(row.date))groups.set(row.date,{date:row.date,count:0,total:0,...Object.fromEntries(paymentMethods.map(m=>[m,0]))});const item=groups.get(row.date);item.count++;item.total+=row.amount;item[row.method]+=row.amount;}
 return [...groups.values()].sort((a,b)=>a.date.localeCompare(b.date));
}
export function trendRows(rows,granularity='month'){
 const map=new Map();for(const row of rows){const date=row.date;let key,label;
  if(granularity==='month'){key=date.slice(0,7);label=monthNames[Number(date.slice(5,7))-1];}
  else if(granularity==='week'){const day=Number(date.slice(8));key=date.slice(0,7)+'-'+Math.floor((day-1)/7);label=`${Math.floor((day-1)/7)*7+1} ${monthNames[Number(date.slice(5,7))-1]}`;}
  else{key=date;label=`${Number(date.slice(8))} ${monthNames[Number(date.slice(5,7))-1]}`;}
  if(!map.has(key))map.set(key,{key,label,total:0,count:0,...Object.fromEntries(paymentMethods.map(m=>[m,0]))});const item=map.get(key);item.total+=row.amount;item.count++;if(row.method)item[row.method]+=row.amount;
 }
 return [...map.values()].sort((a,b)=>a.key.localeCompare(b.key));
}
export function financeSummary(transactions,expenses,period){
 const rows=transactions.filter(r=>inPeriod(r,period)),costs=expenses.filter(r=>inPeriod(r,period)),previous=transactions.filter(r=>inPeriod(r,previousPeriod(period))),revenue=sumAmounts(rows),expense=sumAmounts(costs);
 return {rows,costs,revenue,expense,profit:revenue-expense,count:rows.length,customers:new Set(rows.map(r=>r.customer)).size,average:rows.length?Math.round(revenue/rows.length):0,previousRevenue:sumAmounts(previous),previousCount:previous.length,services:grouped(rows,'service',serviceNames),payments:grouped(rows,'method',paymentMethods),days:dailySummary(rows)};
}
export function validateExpense(values){
 const errors={},amount=Number(values.amount),description=values.description.trim();
 if(!validDate(values.date))errors.date='Pilih tanggal pengeluaran yang valid.';
 if(!categories.includes(values.category))errors.category='Pilih kategori pengeluaran.';
 if(description.length<3)errors.description='Deskripsi minimal 3 karakter.';
 if(!Number.isSafeInteger(amount)||amount<=0||amount>1000000000)errors.amount='Jumlah harus berupa rupiah utuh antara 1 dan 1.000.000.000.';
 if(!paymentMethods.includes(values.method))errors.method='Pilih metode pembayaran.';
 return {errors,values:{...values,description,amount}};
}
