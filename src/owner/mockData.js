// Explicit prototype fixtures. Nothing in this module reads or writes cashier data.
export const DEMO_DAY='2026-10-06';
export const money=value=>'Rp '+Number(value||0).toLocaleString('id-ID');
export const dateLabel=(value,long=false)=>value?new Date(value.slice(0,10)+'T12:00:00').toLocaleDateString('id-ID',{day:'2-digit',month:long?'long':'short',year:'numeric'}):'Belum ada transaksi';
export const initials=name=>name.trim().split(/\s+/).slice(0,2).map(part=>part[0]).join('').toUpperCase();
export const phoneLabel=value=>value.replace(/^(\d{4})(\d{4})(\d+)$/,'$1 $2 $3');

const people=[
 ['Rian Pratama','085277129011','Member',28,'2026-10-06'],
 ['Siti Aisyah','081234567890','Member',20,'2026-10-05'],
 ['Budi Santoso','081323456789','Reguler',12,'2026-10-04'],
 ['Nabila Putri','089611223344','Reguler',10,'2026-10-03'],
 ['Andi Wijaya','082199887766','Member',18,'2026-10-03'],
 ['Dewi Lestari','085723334455','Reguler',8,'2026-10-02'],
 ['Ahmad Fauzi','081266778899','Reguler',7,'2026-10-02'],
 ['Maya Sari','083144556677','Member',16,'2026-10-01'],
 ['Fajar Maulana','085898881122','Reguler',5,'2026-10-01'],
 ['Putri Anggraini','082377665544','Reguler',4,'2026-09-30']
];
export const initialCustomers=Array.from({length:124},(_,i)=>{
 const [name,phone,category,transactions,last]=people[i]||[`Pelanggan Demo ${String(i+1).padStart(3,'0')}`,`0813${String(70000000+i)}`,i<44?'Member':'Reguler',1+i%9,`2026-09-${String(29-i%20).padStart(2,'0')}`];
 return {id:i+1,name,phone,category,transactions,last,spent:i===0?540000:transactions*24000,email:i===0?'rianpratama@gmail.com':`${name.toLowerCase().replaceAll(' ','.')}@example.com`,address:i===0?'Jl. Khatib Sulaiman No.12\nPadang, Sumatera Barat':'Padang, Sumatera Barat',notes:i===0?'Pisahkan pakaian putih. Hubungi melalui WhatsApp sebelum pengambilan.':'',joined:i>=112?'2026-10-01':'2026-08-12'};
});

const receiptSeed=[
 ['Andi Pratama','Ekspres 1 Hari',45000,4.5,'Tunai','2026-10-06','Tersimpan'],
 ['Siska Melani','Reguler',30000,5,'QRIS','2026-10-06','Tersimpan'],
 ['Budi Suprata','Ekspres Jam',56000,4,'Transfer','2026-10-05','Dicetak Ulang'],
 ['Rina Putri','Reguler',24000,4,'Tunai','2026-10-05','Tersimpan'],
 ['Dewi Lestari','Ekspres 1 Hari',38000,3.8,'QRIS','2026-10-04','Tersimpan'],
 ['M. Ilham','Reguler',27000,4.5,'Transfer','2026-10-04','Tersimpan'],
 ['Ani Wijaya','Ekspres Jam',60000,4.286,'Tunai','2026-10-03','Dicetak Ulang'],
 ['Ahmad Fauzi','Reguler',22000,3.667,'QRIS','2026-10-03','Tersimpan']
];
export const initialReceipts=Array.from({length:286},(_,i)=>{
 const day=i<30?'2026-10-06':`2026-10-${String(5-(i%5)).padStart(2,'0')}`;
 const [customer,service,total,weight,method,date,status]=receiptSeed[i]||[initialCustomers[i%124].name,['Reguler','Ekspres 1 Hari','Ekspres Jam'][i%3],18000+i%9*6000,3+i%9,['Tunai','QRIS','Transfer'][i%3],day,i>=8&&i<24?'Dicetak Ulang':'Tersimpan'];
 return {id:i+1,number:`NT-${date.replaceAll('-','')}-${String(i+1).padStart(3,'0')}`,customer,service,total,weight,method,date,time:i===0?'14:32':`${String(8+i%9).padStart(2,'0')}:${String(i%60).padStart(2,'0')}`,status,paid:method==='Tunai'?Math.ceil(total/50000)*50000:total};
});
export const initialActivities=[
 {id:1,type:'print',number:initialReceipts[2].number,date:'06 Okt 2026 16:20'},
 {id:2,type:'download',number:initialReceipts[3].number,date:'05 Okt 2026 14:12'},
 {id:3,type:'view',number:initialReceipts[5].number,date:'04 Okt 2026 11:08'},
 {id:4,type:'print',number:initialReceipts[6].number,date:'03 Okt 2026 17:45'}
];

export const dailyBusiness=[
 {date:'2026-09-30',transactions:18,revenue:320000,expense:150000},
 {date:'2026-10-01',transactions:27,revenue:760000,expense:250000},
 {date:'2026-10-02',transactions:29,revenue:980000,expense:280000},
 {date:'2026-10-03',transactions:36,revenue:1210000,expense:380000},
 {date:'2026-10-04',transactions:41,revenue:1780000,expense:420000},
 {date:'2026-10-05',transactions:38,revenue:890000,expense:310000},
 {date:'2026-10-06',transactions:24,revenue:546000,expense:120000}
];
export const dashboardPeriods={
 month:{label:'Bulan ini',revenue:12486000,transactions:542,expense:3850000,previous:{revenue:10581000,transactions:484,expense:3565000},status:[298,132,78,34],services:[244,190,108]},
 week:{label:'7 hari terakhir',...dailyBusiness.reduce((sum,day)=>({revenue:sum.revenue+day.revenue,transactions:sum.transactions+day.transactions,expense:sum.expense+day.expense}),{revenue:0,transactions:0,expense:0}),previous:{revenue:5900000,transactions:194,expense:1760000},status:[117,52,30,14],services:[96,75,42]},
 today:{label:'Hari ini',revenue:546000,transactions:24,expense:120000,previous:{revenue:890000,transactions:38,expense:310000},status:[12,6,4,2],services:[11,8,5]}
};

export function validateCustomer(values,customers,editId){
 const errors={},phone=values.phone.replace(/[\s()+-]/g,'').replace(/^62/,'0');
 if(values.name.trim().length<2)errors.name='Nama minimal 2 karakter.';
 if(!/^08\d{8,11}$/.test(phone))errors.phone='Gunakan nomor WhatsApp Indonesia yang valid (08… atau +62…).';
 else if(customers.some(row=>row.id!==editId&&row.phone===phone))errors.phone='Nomor WhatsApp sudah dipakai pelanggan lain.';
 if(!values.address.trim())errors.address='Alamat wajib diisi.';
 if(values.email&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email))errors.email='Format email belum benar.';
 return {errors,phone};
}

export function parseCustomerCsv(text){
 const rows=[];let row=[],cell='',quoted=false;
 const source=text.replace(/^\uFEFF/,'');
 for(let i=0;i<=source.length;i++){
  const c=source[i];
  if(c==='"'){if(quoted&&source[i+1]==='"'){cell+='"';i++;}else quoted=!quoted;}
  else if((c===','||c===';'||c==='\n'||c===undefined)&&!quoted){row.push(cell.trim());cell='';if(c==='\n'||c===undefined){if(row.some(Boolean))rows.push(row);row=[];}}
  else if(c!=='\r'&&c!==undefined)cell+=c;
 }
 if(quoted)throw new Error('Tanda kutip pada CSV belum ditutup.');
 const headers=rows.shift()?.map(value=>value.toLowerCase())||[];
 if(!['nama','whatsapp','alamat'].every(key=>headers.includes(key)))throw new Error('CSV harus memiliki kolom nama, whatsapp, alamat.');
 if(!rows.length)throw new Error('File CSV tidak memiliki data pelanggan.');
 if(rows.length>500)throw new Error('Maksimal 500 pelanggan per impor.');
 return rows.map(values=>Object.fromEntries(headers.map((key,i)=>[key,values[i]||''])));
}
