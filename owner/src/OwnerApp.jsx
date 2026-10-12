import React,{useEffect,useRef,useState} from 'react';
import {ArrowRight,BarChart3,Bell,Check,ChevronDown,ClipboardList,FileBarChart2,FilePenLine,Home,LogOut,Menu,PanelLeftClose,PanelLeftOpen,Receipt,Search,Settings as Settings2,Shirt,UsersRound,WalletCards,X} from 'lucide-react';
import {Button,Card,EmptyState,LoadingState,StatusBadge} from './ui';
import {initialActivities,initialCustomers,initialReceipts} from './mockData';
import OwnerDashboard from './OwnerDashboard';
import OwnerCustomers from './OwnerCustomers';
import OwnerReceipts from './OwnerReceipts';
import OwnerEmployees from './OwnerEmployees';
import OwnerServices from './OwnerServices';
import OwnerCorrections from './OwnerCorrections';
import OwnerExpenses from './OwnerExpenses';
import OwnerReports from './OwnerReports';
import OwnerSettings from './OwnerSettings';
import OwnerLogin from './OwnerLogin';
import {initialSettings} from './settingsData';
import {initialExpenses} from './financeData';
import {initialEmployees,initialAccountActivity,initialServices,initialPriceHistory,initialCorrections} from './managementData';

const NAV=[
 {group:'',items:[['dashboard','Dashboard',Home],['pelanggan','Pelanggan',UsersRound],['riwayat-nota','Riwayat Nota',Receipt]]},
 {group:'Manajemen',items:[['layanan-harga','Layanan & Harga',Shirt],['karyawan','Karyawan',UsersRound],['koreksi-transaksi','Koreksi Transaksi',FilePenLine]]},
 {group:'Keuangan',items:[['pengeluaran','Pengeluaran',WalletCards],['laporan-keuangan','Laporan & Analitik',FileBarChart2]]},
 {group:'',items:[['pengaturan','Pengaturan',Settings2]]}
];
const ITEMS=[...NAV.flatMap(section=>section.items),['analytics','Analitik',BarChart3],['kasir','Kasir',WalletCards],['pesanan','Pesanan',ClipboardList]];
const DEMO_LOGIN_KEY='syaqil-owner-demo-login-v1';
const demoSignedIn=()=>{try{return localStorage.getItem(DEMO_LOGIN_KEY)==='1'||sessionStorage.getItem(DEMO_LOGIN_KEY)==='1';}catch{return false;}};
const currentPage=()=>location.hash.slice(1)==='login'?'login':ITEMS.some(item=>item[0]===location.hash.slice(1))?location.hash.slice(1):'dashboard';

function OwnerBrand(){return <div className="owner-brand"><div className="owner-brand-mark"><svg viewBox="170 150 950 930" aria-hidden="true" focusable="false"><defs><filter id="owner-brand-white" colorInterpolationFilters="sRGB"><feColorMatrix type="matrix" values="0 0 0 0 1  0 0 0 0 1  0 0 0 0 1  -1 0 0 0 1"/></filter></defs><image href="/assets/logo.png" width="1254" height="1254" filter="url(#owner-brand-white)"/></svg></div><div className="owner-brand-type"><b>SYAQIL<br/>LAUNDRY</b></div></div>;}

function Sidebar({page,compact,mobile,open,navigate,close,toggle}){
 return <><button className={`owner-sidebar-shade ${open?'is-visible':''}`} aria-label="Tutup navigasi" tabIndex={open?0:-1} onClick={close}/><aside className={`owner-sidebar ${compact?'is-compact':''} ${open?'is-open':''}`} aria-label="Sidebar Owner" aria-hidden={mobile&&!open} inert={mobile&&!open}>
 <div className="owner-sidebar-head"><div className="owner-window-dots" aria-hidden="true"><i/><i/><i/></div><button className="ow-sidebar-toggle" onClick={mobile?close:toggle} aria-label={mobile?'Tutup menu':compact?'Perluas sidebar':'Ringkas sidebar'}>{mobile?<X size={19}/>:compact?<PanelLeftOpen size={17}/>:<PanelLeftClose size={17}/>}</button></div><OwnerBrand/>
 <div className="owner-sidebar-scroll"><nav aria-label="Navigasi Owner">{NAV.map((section,i)=><div className="owner-nav-section" key={i}>{section.group&&<p className="owner-nav-group">{section.group}</p>}{section.items.map(([key,label,Icon])=><button key={key} className={`owner-nav-item ${page===key||page==='analytics'&&key==='laporan-keuangan'?'is-active':''}`} onClick={()=>navigate(key)} aria-current={page===key||page==='analytics'&&key==='laporan-keuangan'?'page':undefined} title={compact?label:undefined}><Icon size={21}/><span>{label}</span></button>)}</div>)}</nav></div>
 <div className="owner-sidebar-foot owner-sidebar-note"><strong>SYAQIL LAUNDRY</strong><span>Workspace Owner · Sistem Manajemen</span></div></aside></>;
}

function Topbar({page,mobile,toggle,navigate,onLogout,currentUser,customers,receipts,employees,services,corrections,expenses}){
 const [search,setSearch]=useState(''),[popover,setPopover]=useState('');
 const searchRef=useRef(null),rootRef=useRef(null),query=search.trim().toLowerCase();
 const matches=query?[
  ...ITEMS.filter(item=>item[1].toLowerCase().includes(query)).map(([key,label,Icon])=>({key,label,Icon})),
  ...[['profile','Profil Usaha dan Jam Operasional'],['access','Akun & Akses'],['receipt','Nota & Struk'],['system','Sistem dan Format Tampilan'],['notifications','Notifikasi'],['backup','Backup & Data']].filter(([,label])=>label.toLowerCase().includes(query)).map(([tab,label])=>({key:'pengaturan',label,query:tab,Icon:Settings2})),
  ...customers.filter(row=>row.name.toLowerCase().includes(query)||row.phone.includes(query)||row.address.toLowerCase().includes(query)).slice(0,3).map(row=>({key:'pelanggan',label:row.name,query:row.name,Icon:UsersRound})),
  ...receipts.filter(row=>row.number.toLowerCase().includes(query)||row.customer.toLowerCase().includes(query)).slice(0,2).map(row=>({key:'riwayat-nota',label:row.number,query:row.number,Icon:Receipt})),
  ...employees.filter(row=>[row.name,row.username,row.role].join(' ').toLowerCase().includes(query)).slice(0,2).map(row=>({key:'karyawan',label:row.name+' · '+row.role,query:row.username,Icon:UsersRound})),
  ...services.filter(row=>[row.name,row.price].join(' ').toLowerCase().includes(query)).slice(0,2).map(row=>({key:'layanan-harga',label:row.name,query:row.name,Icon:Shirt})),
  ...corrections.filter(row=>[row.number,row.customer,row.reason].join(' ').toLowerCase().includes(query)).slice(0,2).map(row=>({key:'koreksi-transaksi',label:row.number+' · Koreksi',query:row.number,Icon:FilePenLine})),
  ...expenses.filter(row=>[row.description,row.category].join(' ').toLowerCase().includes(query)).slice(0,2).map(row=>({key:'pengeluaran',label:row.description,query:row.description,Icon:WalletCards}))
 ].slice(0,7):[];
 useEffect(()=>{
  const key=event=>{if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='k'){event.preventDefault();searchRef.current?.focus();}if(event.key==='Escape'){setSearch('');setPopover('');}};
  const outside=event=>{if(!rootRef.current?.contains(event.target)){setPopover('');setSearch('');}};
  document.addEventListener('keydown',key);document.addEventListener('pointerdown',outside);
  return()=>{document.removeEventListener('keydown',key);document.removeEventListener('pointerdown',outside);};
 },[]);
 function choose(match){navigate(match.key,match.query||'');setSearch('');searchRef.current?.blur();}
 const searchHint={pelanggan:'Cari pelanggan, nomor HP, atau alamat...','riwayat-nota':'Cari nomor nota, nama pelanggan, atau menu...',pengeluaran:'Cari pengeluaran, kategori, atau keterangan...',analytics:'Cari menu, layanan, atau karyawan...','laporan-keuangan':'Cari laporan, pelanggan, atau menu...',pengaturan:'Cari pengaturan, profil, atau lainnya...'}[page]||'Cari menu, pelanggan, atau laporan...';
 const displayName = currentUser?.name || 'Owner';
 const initials = displayName.split(' ').map(n=>n[0]).slice(0,2).join('').toUpperCase();
 return <header className="owner-topbar" ref={rootRef}>
  {mobile&&<button className="owner-topbar-icon ow-mobile-menu" onClick={toggle} aria-label="Buka menu Owner"><Menu size={22}/></button>}
  <div className="owner-search"><Search size={20}/><input ref={searchRef} value={search} onChange={e=>setSearch(e.target.value)} onKeyDown={e=>{if(e.key==='Enter'&&matches[0])choose(matches[0]);}} placeholder={searchHint} aria-label="Pencarian Owner"/>{search&&<div className="owner-search-results">{matches.length?matches.map((match,i)=><button key={i} onClick={()=>choose(match)}><match.Icon size={17}/>{match.label}<ArrowRight size={15}/></button>):<p>Tidak ada hasil yang cocok.</p>}</div>}</div>
  <div className="owner-topbar-end"><span className="owner-topbar-date">{new Date().toLocaleDateString('id-ID',{weekday:'short',day:'numeric',month:'short',year:'numeric'})}</span><strong className="ow-clock">{new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}).replace('.',':')}</strong>
   <div className="owner-popover-anchor"><button className="owner-topbar-icon owner-notification-button" onClick={()=>setPopover(popover==='notices'?'':'notices')} aria-label="Notifikasi Owner" aria-expanded={popover==='notices'}><Bell size={20}/><i/></button>{popover==='notices'&&<div className="owner-popover"><b>Selamat datang, {displayName}!</b><p>Workspace Owner aktif terhubung ke basis data operasional.</p></div>}</div>
   <div className="owner-popover-anchor"><button className="owner-profile" onClick={()=>setPopover(popover==='profile'?'':'profile')} aria-expanded={popover==='profile'}><span className="owner-profile-avatar">{initials}</span><span><b>{displayName}</b><small>Owner</small></span><ChevronDown size={17}/></button>{popover==='profile'&&<div className="owner-popover owner-profile-popover"><b>{displayName}</b><p>Sistem manajemen pemilik toko terhubung ke backend Laravel.</p><StatusBadge tone="success">Online</StatusBadge><div className="ow-profile-links">{ITEMS.filter(item=>['kasir','pesanan'].includes(item[0])).map(([key,label,Icon])=><button key={key} onClick={()=>{navigate(key);setPopover('');}}><Icon size={17}/>Workspace {label}<ArrowRight size={14}/></button>)}<button onClick={()=>{setPopover('');onLogout();}}><LogOut size={17}/>Keluar akun<ArrowRight size={14}/></button></div></div>}</div>
  </div></header>;
}

function FuturePlaceholder({page,navigate}){
 const item=ITEMS.find(row=>row[0]===page)||ITEMS[0];
 return <div className="owner-page-inner owner-future"><div className="owner-page-heading"><div><span className="ui-eyebrow">OWNER WORKSPACE</span><h1>{item[1]}</h1><p>Modul ini disiapkan untuk tahap desain berikutnya.</p></div><StatusBadge tone="neutral">Belum tersedia</StatusBadge></div><Card><EmptyState icon={item[2]} title={`${item[1]} belum tersedia di prototipe ini`} description="Gunakan sidebar untuk mencoba halaman operasional, manajemen, dan keuangan yang tersedia." action={<Button onClick={()=>navigate('dashboard')}>Kembali ke Dashboard</Button>}/></Card></div>;
}

import {ownerApi} from './api';

export default function OwnerApp(){
 const [signedIn,setSignedIn]=useState(false);
 const [currentUser,setCurrentUser]=useState(null);
 const [page,setPage]=useState(currentPage),[compact,setCompact]=useState(()=>innerWidth<1200),[mobile,setMobile]=useState(()=>innerWidth<768),[mobileOpen,setMobileOpen]=useState(false);
 const [customers,setCustomers]=useState(initialCustomers),[receipts,setReceipts]=useState(initialReceipts),[activities,setActivities]=useState(initialActivities),[intent,setIntent]=useState(''),[toast,setToast]=useState(''),[loading,setLoading]=useState(true);
 const [employees,setEmployees]=useState(initialEmployees),[accountActivity,setAccountActivity]=useState(initialAccountActivity),[services,setServices]=useState(initialServices),[priceHistory,setPriceHistory]=useState(initialPriceHistory),[corrections,setCorrections]=useState(initialCorrections);
 const [expenses,setExpenses]=useState(initialExpenses);
 const [settings,setSettings]=useState(()=>structuredClone(initialSettings)),[backupAt,setBackupAt]=useState(null);
 const lastWidth=useRef(innerWidth),toastTimer=useRef(null);

 const loadOwnerData = async () => {
  try {
   const boot = await ownerApi('/owner/bootstrap');
   if (boot) {
    if (boot.customers) setCustomers(boot.customers);
    if (boot.receipts) setReceipts(boot.receipts);
    if (boot.expenses) setExpenses(boot.expenses);
    if (boot.services) setServices(boot.services);
    if (boot.priceHistory) setPriceHistory(boot.priceHistory);
    if (boot.employees) setEmployees(boot.employees);
    if (boot.corrections) setCorrections(boot.corrections);
    if (boot.accountActivity) setAccountActivity(boot.accountActivity);
    if (boot.settings) setSettings(boot.settings);
   }
  } catch (e) {
   console.error('Gagal memuat data owner:', e);
  }
 };

 useEffect(()=>{
  ownerApi('/session')
   .then(async data=>{
    if(data.user && data.user.role==='owner'){
     setCurrentUser(data.user);
     setSignedIn(true);
     await loadOwnerData();
    }else{
     setSignedIn(false);
     if(location.hash!=='#login')location.hash='login';
    }
   })
   .catch(()=>{
    setSignedIn(false);
    if(location.hash!=='#login')location.hash='login';
   })
   .finally(()=>{
    setLoading(false);
   });
 },[]);

 useEffect(()=>{if(!signedIn&&location.hash!=='#login')location.hash='login';},[signedIn]);
 useEffect(()=>{
  const hash=()=>{setPage(currentPage());setMobileOpen(false);};
  const resize=()=>{const width=innerWidth;setMobile(width<768);if(width<1200&&lastWidth.current>=1200)setCompact(true);if(width>=1200&&lastWidth.current<1200)setCompact(false);if(width>=768)setMobileOpen(false);lastWidth.current=width;};
  addEventListener('hashchange',hash);addEventListener('resize',resize);return()=>{removeEventListener('hashchange',hash);removeEventListener('resize',resize);clearTimeout(toastTimer.current);};
 },[]);
 useEffect(()=>{
  if(!mobileOpen)return;
  const sidebar=document.querySelector('.owner-sidebar');sidebar.querySelector('button')?.focus();
  const key=e=>{const buttons=[...sidebar.querySelectorAll('button')];if(e.key==='Escape'){setMobileOpen(false);document.querySelector('.ow-mobile-menu')?.focus();}if(e.key==='Tab'){if(e.shiftKey&&document.activeElement===buttons[0]){e.preventDefault();buttons.at(-1)?.focus();}else if(!e.shiftKey&&document.activeElement===buttons.at(-1)){e.preventDefault();buttons[0]?.focus();}}};
  addEventListener('keydown',key);return()=>removeEventListener('keydown',key);
 },[mobileOpen]);
 function navigate(key,query=''){location.hash=key;setPage(key);setIntent(query);setMobileOpen(false);document.querySelector('.owner-content')?.scrollTo({top:0});}
 function notify(message){setToast(message);clearTimeout(toastTimer.current);toastTimer.current=setTimeout(()=>setToast(''),6000);}
 function toggle(){if(mobile)setMobileOpen(value=>!value);else setCompact(value=>!value);}
 async function login(identifier,password,remember){
  const res=await ownerApi('/login',{username:identifier,password,remember});
  if(res.user?.role!=='owner'){
   await ownerApi('/logout',{}).catch(()=>{});
   throw new Error('Akun ini adalah Kasir. Akses Owner hanya untuk peran Owner.');
  }
  setCurrentUser(res.user);
  setSignedIn(true);
  await loadOwnerData();
  navigate('dashboard');
 }
 async function logout(){
  try{await ownerApi('/logout',{});}catch(e){}
  setCurrentUser(null);
  setSignedIn(false);
  setMobileOpen(false);
  setPage('login');
  location.hash='login';
 }
 if(!signedIn||page==='login')return <OwnerLogin onLogin={login}/>;
 let content;
 if(loading)content=<LoadingState label="Menyiapkan workspace Owner..." rows={6}/>;
 else if(page==='dashboard')content=<OwnerDashboard navigate={navigate} corrections={corrections} expenses={expenses} receipts={receipts}/>;
 else if(page==='pelanggan')content=<OwnerCustomers customers={customers} setCustomers={setCustomers} receipts={receipts} notify={notify} initialQuery={intent}/>;
 else if(page==='riwayat-nota')content=<OwnerReceipts receipts={receipts} setReceipts={setReceipts} activities={activities} setActivities={setActivities} notify={notify} initialQuery={intent}/>;
 else if(page==='karyawan')content=<OwnerEmployees employees={employees} setEmployees={setEmployees} activity={accountActivity} setActivity={setAccountActivity} notify={notify} initialQuery={intent}/>;
 else if(page==='layanan-harga')content=<OwnerServices services={services} setServices={setServices} history={priceHistory} setHistory={setPriceHistory} notify={notify} initialQuery={intent}/>;
 else if(page==='koreksi-transaksi')content=<OwnerCorrections corrections={corrections} setCorrections={setCorrections} notify={notify} initialQuery={intent}/>;
 else if(page==='pengeluaran')content=<OwnerExpenses expenses={expenses} setExpenses={setExpenses} notify={notify} initialQuery={intent}/>;
 else if(page==='analytics'||page==='laporan-keuangan')content=<OwnerReports key={page} expenses={expenses} customers={customers} notify={notify} initialTab={page==='analytics'?'analytics':'summary'}/>;
 else if(page==='pengaturan')content=<OwnerSettings settings={settings} setSettings={setSettings} backupAt={backupAt} setBackupAt={setBackupAt} employees={employees} navigate={navigate} notify={notify} initialTab={intent||'profile'}/>;
 else content=<FuturePlaceholder page={page} navigate={navigate}/>;
 return <div className={`owner-app ow-reference-shell ${compact?'is-compact':''}`}><Sidebar page={page} compact={compact} mobile={mobile} open={mobileOpen} navigate={navigate} close={()=>setMobileOpen(false)} toggle={toggle}/><div className="owner-main" inert={mobile&&mobileOpen}><Topbar page={page} mobile={mobile} toggle={toggle} navigate={navigate} onLogout={logout} currentUser={currentUser} customers={customers} receipts={receipts} employees={employees} services={services} corrections={corrections} expenses={expenses}/><main className="owner-content" id="owner-main-content">{content}</main></div>{toast&&<div className="owner-toast" role="status"><Check size={18}/><span>{toast}</span><button onClick={()=>setToast('')} aria-label="Tutup pemberitahuan"><X size={17}/></button></div>}</div>;
}
