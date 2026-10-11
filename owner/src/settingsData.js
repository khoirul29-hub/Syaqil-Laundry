export const SETTINGS_VERSION=1;
export const MAX_LOGO_BYTES=2*1024*1024;
export const MAX_BACKUP_BYTES=3*1024*1024;
export const dayNames=['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
export const initialSettings={
 profile:{name:'Syaqil Laundry',owner:'Desi Gusrianita',phone:'081234567890',email:'syaqillaundry@gmail.com',address:'Jl. Raya Andalas No. 123, Padang, Sumatera Barat',logo:'/assets/logo.png'},
 hours:dayNames.map((day,i)=>({day,enabled:i<6,open:'07:00',close:'21:00'})),
 general:{currency:'IDR',dateFormat:'long',timeFormat:'24',decimals:'0',timezone:'Asia/Jakarta',showStock:true,loyalty:true,sound:false},
 receipt:{width:'80',prefix:'SYQ',showLogo:true,showOwner:true,showPhone:true,showAddress:true,showCashier:true,footer:'Terima kasih\nTelah mempercayakan cucian Anda kepada kami',heart:true},
 notifications:{orderReady:true,payment:false,expenses:true,channel:'in-app',provider:'demo',phone:'081234567890',template:'Halo {pelanggan}, pesanan {nota} sudah selesai dan siap diambil di {usaha}. Terima kasih!'}
};
const string=value=>typeof value==='string'?value.trim():'';
export function normalizeSettingsPhone(value){const phone=string(value).replace(/[\s()+-]/g,'');return phone.startsWith('62')?'0'+phone.slice(2):phone;}
const validPhone=value=>/^0\d{9,12}$/.test(normalizeSettingsPhone(value));
const validTime=value=>/^([01]\d|2[0-3]):[0-5]\d$/.test(value);
const validLogo=value=>value===null||value==='/assets/logo.png'||typeof value==='string'&&value.length<=Math.ceil(MAX_LOGO_BYTES/3)*4+32&&/^data:image\/(png|jpeg);base64,[A-Za-z0-9+/]+={0,2}$/.test(value);
export function validateSettings(input){
 const errors={},profile=input?.profile||{},general=input?.general||{},receipt=input?.receipt||{},notifications=input?.notifications||{};
 const field=(key,value,min,max,label)=>{if(string(value).length<min||string(value).length>max)errors[key]=`${label} harus ${min}–${max} karakter.`;};
 field('name',profile.name,2,60,'Nama usaha');field('owner',profile.owner,2,80,'Nama pemilik');field('address',profile.address,5,240,'Alamat');
 if(!validPhone(profile.phone))errors.phone='Isi nomor telepon Indonesia yang valid (10–13 digit).';
 if(profile.email&&(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(string(profile.email))||profile.email.length>120))errors.email='Format email belum valid.';
 if(!validLogo(profile.logo))errors.logo='Logo harus PNG/JPG, maksimal 2 MB.';
 if(!Array.isArray(input?.hours)||input.hours.length!==7)errors.hours='Jadwal harus memuat tujuh hari.';
 else input.hours.forEach((row,i)=>{if(!row||row.day!==dayNames[i]||typeof row.enabled!=='boolean'||!validTime(row.open)||!validTime(row.close)||row.enabled&&row.open>=row.close)errors[`hours-${i}`]='Jam tutup harus setelah jam buka pada hari yang sama.';});
 for(const [key,allowed] of Object.entries({currency:['IDR'],dateFormat:['long','numeric','iso'],timeFormat:['24','12'],decimals:['0','2'],timezone:['Asia/Jakarta','Asia/Makassar','Asia/Jayapura']}))if(!allowed.includes(general[key]))errors[key]='Pilih format yang tersedia.';
 for(const key of ['showStock','loyalty','sound'])if(typeof general[key]!=='boolean')errors[key]='Preferensi tidak valid.';
 if(!['58','80'].includes(receipt.width))errors.width='Pilih ukuran kertas 58 atau 80 mm.';
 if(!/^[A-Z0-9-]{2,8}$/.test(receipt.prefix||''))errors.prefix='Gunakan 2–8 huruf kapital, angka, atau tanda hubung.';
 field('footer',receipt.footer,1,180,'Pesan penutup');
 for(const key of ['showLogo','showOwner','showPhone','showAddress','showCashier','heart'])if(typeof receipt[key]!=='boolean')errors[key]='Preferensi nota tidak valid.';
 for(const key of ['orderReady','payment','expenses'])if(typeof notifications[key]!=='boolean')errors[key]='Preferensi notifikasi tidak valid.';
 if(!['in-app','whatsapp'].includes(notifications.channel)||notifications.provider!=='demo')errors.channel='Saluran notifikasi tidak valid.';
 if(!validPhone(notifications.phone))errors.notificationPhone='Isi nomor WhatsApp Indonesia yang valid.';
 field('template',notifications.template,10,500,'Template pesan');
 const tokens=string(notifications.template).match(/\{[^}]*\}/g)||[];
 if(tokens.some(t=>!['{pelanggan}','{nota}','{usaha}'].includes(t)))errors.template='Variabel yang tersedia: {pelanggan}, {nota}, dan {usaha}.';
 return {errors,values:{profile:{name:string(profile.name),owner:string(profile.owner),phone:normalizeSettingsPhone(profile.phone),email:string(profile.email),address:string(profile.address),logo:profile.logo},hours:Array.isArray(input?.hours)?input.hours.map(r=>({day:r?.day,enabled:r?.enabled,open:r?.open,close:r?.close})):[],general:{...general},receipt:{...receipt,footer:string(receipt.footer)},notifications:{...notifications,phone:normalizeSettingsPhone(notifications.phone),template:string(notifications.template)}}};
}
export function settingsDate(settings,iso='2026-10-04T03:30:00Z'){
 const date=new Date(iso),timezone=settings.general.timezone;
 if(settings.general.dateFormat==='iso')return new Intl.DateTimeFormat('en-CA',{timeZone:timezone,year:'numeric',month:'2-digit',day:'2-digit'}).format(date);
 return new Intl.DateTimeFormat('id-ID',{timeZone:timezone,day:'2-digit',month:settings.general.dateFormat==='numeric'?'2-digit':'short',year:'numeric'}).format(date);
}
export function settingsTime(settings,iso='2026-10-04T03:30:00Z'){return new Intl.DateTimeFormat(settings.general.timeFormat==='12'?'en-US':'id-ID',{timeZone:settings.general.timezone,hour:'2-digit',minute:'2-digit',hour12:settings.general.timeFormat==='12'}).format(new Date(iso)).replace('.',':');}
export function previewMessage(settings){return settings.notifications.template.replace(/\{(pelanggan|nota|usaha)\}/g,(_,key)=>({pelanggan:'Andi Pratama',nota:`${settings.receipt.prefix}-20261004-0001`,usaha:settings.profile.name}[key]));}
export function makeSettingsBackup(settings,createdAt=new Date().toISOString()){
 const result=validateSettings(settings);if(Object.keys(result.errors).length)throw new Error('Perbaiki pengaturan sebelum membuat salinan.');
 return {application:'syaqil-owner-settings-demo',version:SETTINGS_VERSION,createdAt,settings:result.values};
}
export function parseSettingsBackup(text){
 if(new TextEncoder().encode(text).length>MAX_BACKUP_BYTES)throw new Error('File pengaturan maksimal 3 MB.');
 let data;try{data=JSON.parse(text);}catch{throw new Error('File bukan JSON yang valid.');}
 if(data?.application!=='syaqil-owner-settings-demo'||data.version!==SETTINGS_VERSION)throw new Error('Gunakan file backup pengaturan Syaqil Owner versi 1.');
 const source=data.settings;if(!source||!source.general||!source.receipt||!source.notifications)throw new Error('Isi backup pengaturan tidak lengkap.');
 const clean={};
 for(const section of Object.keys(initialSettings))clean[section]=section==='hours'?source.hours:Object.fromEntries(Object.keys(initialSettings[section]).map(key=>[key,source[section]?.[key]]));
 const result=validateSettings(clean);if(Object.keys(result.errors).length)throw new Error('Isi backup tidak valid: '+Object.values(result.errors)[0]);
 return {settings:result.values,createdAt:typeof data.createdAt==='string'&&!Number.isNaN(Date.parse(data.createdAt))?data.createdAt:null};
}
