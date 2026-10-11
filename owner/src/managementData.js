import {DEMO_DAY} from './mockData.js';

export const initialServices=[
 {id:1,name:'Reguler',unit:'kg',price:6000,kind:'Pakaian',duration:'normal',hours:0,active:true,description:'Laundry harian',tone:'blue',symbol:'shirt'},
 {id:2,name:'Ekspres 1 Hari',unit:'kg',price:10000,kind:'Pakaian',duration:'day',hours:24,active:true,description:'Selesai maksimal 1 hari',tone:'amber',symbol:'zap'},
 {id:3,name:'Ekspres Jam',unit:'kg',price:14000,kind:'Pakaian',duration:'hours',hours:6,active:true,description:'Prioritas paling cepat',tone:'red',symbol:'timer'}
];
export const initialPriceHistory=[
 {id:1,date:'2026-10-05',service:'Ekspres 1 Hari',oldPrice:9000,newPrice:10000,actor:'Desi Gusrianita',reason:'Penyesuaian biaya operasional'},
 {id:2,date:'2026-10-03',service:'Ekspres Jam',oldPrice:12000,newPrice:14000,actor:'Desi Gusrianita',reason:'Penyesuaian layanan ekspres'},
 {id:3,date:'2026-10-01',service:'Reguler',oldPrice:5000,newPrice:6000,actor:'Desi Gusrianita',reason:'Penyesuaian tarif reguler'},
 {id:4,date:'2026-09-26',service:'Ekspres Jam',oldPrice:10000,newPrice:12000,actor:'Desi Gusrianita',reason:'Penyesuaian tarif'}
];
export const initialEmployees=[
 ['Desi Gusrianita','desi','Owner',true,'081234567890','Fleksibel','09:12'],
 ['Ari Pratama','ari123','Kasir',true,'082123456789','Pagi','08:45'],
 ['Rina Wulandari','rina','Kasir',true,'081377770022','Sore','17:20'],
 ['Fajar Ramadhan','fajar','Kasir',false,'085299990033','Pagi','14:30'],
 ['Nadia Putri','nadia','Kasir',true,'089612340044','Sore','19:10'],
 ['Ucok Saputra','ucok','Kasir',true,'081277770055','Pagi','16:05'],
 ['Siska Melani','siska','Kasir',true,'085288880066','Sore','09:30'],
 ['Dimas Putra','dimas','Kasir',false,'081366660077','Pagi','08:10']
].map(([name,username,role,active,phone,shift,time],i)=>({id:i+1,name,username,role,active,phone,shift,lastLogin:`${active?DEMO_DAY:'2026-10-03'}T${time}`,resets:0}));
export const initialAccountActivity=[
 {id:1,date:DEMO_DAY+'T09:12',name:'Ari Pratama',type:'Login',detail:'Login ke sistem melalui web (contoh).'},
 {id:2,date:'2026-10-05T17:00',name:'Rina Wulandari',type:'Update Data',detail:'Mengubah nomor WhatsApp.'},
 {id:3,date:'2026-10-04T14:30',name:'Fajar Ramadhan',type:'Reset Password',detail:'Simulasi pengaturan ulang akun.'},
 {id:4,date:'2026-10-04T10:15',name:'Desi Gusrianita',type:'Status Diubah',detail:'Mengaktifkan akun Nadia Putri.'}
];

export function validateService(values,services,editId){
 const errors={},name=values.name.trim(),price=Number(values.price),hours=Number(values.hours);
 if(name.length<3)errors.name='Nama layanan minimal 3 karakter.';
 else if(services.some(s=>s.id!==editId&&s.name.toLowerCase()===name.toLowerCase()))errors.name='Nama layanan sudah digunakan.';
 if(!Number.isSafeInteger(price)||price<=0||price>10000000)errors.price='Harga harus berupa rupiah utuh antara 1 dan 10.000.000.';
 if(values.duration==='hours'&&(!Number.isInteger(hours)||hours<1||hours>168))errors.hours='Durasi harus 1 sampai 168 jam.';
 if(!['kg','pcs','pasang'].includes(values.unit))errors.unit='Pilih satuan layanan.';
 return {errors,values:{...values,name,price,hours:values.duration==='day'?24:values.duration==='normal'?0:hours}};
}
export function validateEmployee(values,employees,editId){
 const errors={},name=values.name.trim(),username=values.username.trim().toLowerCase();
 let phone=values.phone.replace(/[\s()-]/g,'').replace(/^\+?62/,'0');
 if(name.length<3)errors.name='Nama lengkap minimal 3 karakter.';
 if(!/^[a-z0-9._]{3,24}$/.test(username))errors.username='Gunakan 3–24 huruf, angka, titik, atau underscore.';
 else if(employees.some(e=>e.id!==editId&&e.username.toLowerCase()===username))errors.username='Username sudah digunakan.';
 if(!/^08\d{8,11}$/.test(phone))errors.phone='Masukkan nomor WhatsApp Indonesia yang valid.';
 const original=employees.find(e=>e.id===editId);
 if(original?.active&&original.role==='Owner'&&(!values.active||values.role!=='Owner')&&!employees.some(e=>e.id!==editId&&e.active&&e.role==='Owner'))errors.role='Minimal satu akun Owner harus tetap aktif.';
 return {errors,values:{...values,name,username,phone}};
}
export const serviceDuration=s=>s.duration==='normal'?'Antrean normal':s.duration==='day'?'1 Hari (24 Jam)':`${s.hours} Jam`;
export const unitLabel=unit=>({kg:'Kilogram (kg)',pcs:'Satuan (pcs)',pasang:'Pasang'}[unit]||unit);

const correctionSeed=[
 ['20261006-0008','Rian Pratama','2026-10-06T18:11','Pembatalan','Pelanggan minta batal','Selesai',5,5,6000,6000,'Reguler','Reguler'],
 ['20261006-0007','Nabila Putri','2026-10-06T15:20','Koreksi Berat','Berat diubah dari 5 kg menjadi 3,5 kg','Selesai',5,3.5,6000,6000,'Reguler','Reguler'],
 ['20261005-0006','Andi Pratama','2026-10-05T14:33','Penyesuaian Harga','Diskon member tidak terinput','Selesai',3,3,10000,9000,'Ekspres 1 Hari','Ekspres 1 Hari'],
 ['20261004-0005','Fajar Ramadhan','2026-10-04T23:07','Pembatalan','Salah input layanan','Selesai',4,4,6000,6000,'Reguler','Reguler'],
 ['20261004-0004','Siti Aisyah','2026-10-04T10:15','Koreksi Layanan','Reguler → Ekspres 1 Hari','Diproses',5,5,6000,10000,'Reguler','Ekspres 1 Hari'],
 ['20261003-0003','Dewi Lestari','2026-10-03T16:42','Penyesuaian Harga','Harga layanan disesuaikan','Selesai',4,4,6000,5500,'Reguler','Reguler'],
 ['20261002-0002','Ahmad Fauzi','2026-10-02T09:18','Koreksi Berat','Berat diubah dari 8 kg menjadi 6 kg','Selesai',8,6,6000,6000,'Reguler','Reguler'],
 ['20261001-0001','Maya Sari','2026-10-01T08:20','Pembatalan','Pelanggan tidak jadi ambil','Selesai',3,3,6000,6000,'Reguler','Reguler']
];
export const initialCorrections=correctionSeed.map(([number,customer,date,type,reason,status,oldWeight,newWeight,oldPrice,newPrice,oldService,newService],i)=>({id:i+1,number:'SYQ-'+number,customer,date,type,reason,status,phone:'081234567890',actor:'Desi Gusrianita (Owner)',requestedAt:date.slice(0,10)+'T10:30',before:{weight:oldWeight,price:oldPrice,service:oldService,total:oldWeight*oldPrice},after:{weight:newWeight,price:newPrice,service:newService,total:type==='Pembatalan'?0:newWeight*newPrice},note:type==='Koreksi Layanan'?'Pelanggan mengubah layanan dari Reguler menjadi Ekspres 1 Hari.':reason,decisionNote:status==='Selesai'?'Disetujui pada data contoh.':''}));
export function decideCorrection(record,decision,note){
 if(record.status!=='Diproses')throw new Error('Koreksi ini sudah diputuskan.');
 if(!['approve','reject'].includes(decision))throw new Error('Keputusan tidak valid.');
 if(note.trim().length<5)throw new Error('Catatan keputusan minimal 5 karakter.');
 return {...record,status:decision==='approve'?'Selesai':'Ditolak',decisionNote:note.trim(),decidedAt:new Date().toISOString()};
}
export const effectiveCorrectionTotal=record=>record.status==='Selesai'?record.after.total:record.before.total;
