import React, {useMemo, useState} from 'react';
import {Users, Star, Search, Plus, Download, Upload, MoreVertical, Pencil, Phone, CalendarDays, ReceiptText, ShoppingCart, FileText, Copy, Check, ChevronRight, ArrowRight} from 'lucide-react';
import {api, rupiah, localPhone, dateText, quantity} from './lib';
import {Modal, Empty, ErrorBox} from './components';
import {CustomerForm} from './Cashier';
import {ReferenceBanner, StatCard, Pagination} from './ReferenceUI';
import {exportExcel, initials} from './referenceUtils';
import {jakartaDate} from './insights';
import './customer-reference.css';

const PAGE_SIZE = 10;
const tabs = ['Riwayat Transaksi', 'Catatan'];
const normalizedPhone = value => String(value || '').replace(/[\s()+-]/g, '').replace(/^0/, '62');

function summaries(customers, orders) {
 return customers.map(customer => {
  const history = orders.filter(order => Number(order.customer_id) === Number(customer.id)).sort((a, b) => b.created_at.localeCompare(a.created_at) || Number(b.id) - Number(a.id));
  return {...customer, history, visits: history.length, spend: history.reduce((total, order) => total + Number(order.total), 0)};
 });
}

function CustomerEditor({customer, onClose, onSaved, focusNotes = false}) {
 const [form, setForm] = useState({name: customer.name, phone: localPhone(customer.phone), notes: customer.notes || ''});
 const [busy, setBusy] = useState(false), [error, setError] = useState('');
 async function save(event) {
  event.preventDefault(); setBusy(true); setError('');
  try { const result = await api('/customers/' + customer.id, form); onSaved(result.customer); }
  catch (failure) { setError(failure.message); }
  finally { setBusy(false); }
 }
 return <Modal title="Edit pelanggan" onClose={() => !busy && onClose()}>
  <form className="customer-form" onSubmit={save}>
   <label htmlFor="edit-customer-name">Nama pelanggan *</label><input id="edit-customer-name" autoFocus={!focusNotes} required minLength="2" maxLength="100" value={form.name} onChange={event => setForm({...form, name: event.target.value})}/>
   <label htmlFor="edit-customer-phone">No. WhatsApp *</label><input id="edit-customer-phone" type="tel" required value={form.phone} onChange={event => setForm({...form, phone: event.target.value})}/>
   <label htmlFor="edit-customer-notes">Catatan pelanggan</label><textarea id="edit-customer-notes" autoFocus={focusNotes} maxLength="1000" placeholder="Contoh: pisahkan pakaian putih." value={form.notes} onChange={event => setForm({...form, notes: event.target.value})}/>
   <ErrorBox message={error}/><div className="modal-actions"><button type="button" className="secondary" disabled={busy} onClick={onClose}>Batal</button><button className="primary" disabled={busy}>{busy ? 'Menyimpan...' : 'Simpan perubahan'}</button></div>
  </form>
 </Modal>;
}

function csvRows(source) {
 const text = source.replace(/^\uFEFF/, '');
 const delimiter = text.split(/\r?\n/, 1)[0].includes(';') ? ';' : ',';
 const rows = []; let row = [], cell = '', quoted = false;
 for (let index = 0; index < text.length; index++) {
  const char = text[index];
  if (char === '"') {
   if (quoted && text[index + 1] === '"') { cell += '"'; index++; } else quoted = !quoted;
  } else if (!quoted && char === delimiter) { row.push(cell.trim()); cell = ''; }
  else if (!quoted && (char === '\n' || char === '\r')) {
   if (char === '\r' && text[index + 1] === '\n') index++;
   row.push(cell.trim()); if (row.some(Boolean)) rows.push(row); row = []; cell = '';
  } else cell += char;
 }
 if (quoted) throw new Error('Tanda kutip pada CSV belum ditutup.');
 row.push(cell.trim()); if (row.some(Boolean)) rows.push(row);
 return rows;
}

function ImportCustomers({customers, onCustomer, onClose}) {
 const [entries, setEntries] = useState([]), [error, setError] = useState(''), [busy, setBusy] = useState(false), [finished, setFinished] = useState(false);
 async function read(event) {
  setError(''); setEntries([]); setFinished(false);
  const file = event.target.files?.[0]; if (!file) return;
  try {
   if (file.size > 100000) throw new Error('Ukuran CSV maksimal 100 KB.');
   const parsed = csvRows(await file.text());
   const headings = (parsed.shift() || []).map(heading => heading.toLowerCase().trim());
   const nameIndex = headings.findIndex(heading => ['nama', 'name'].includes(heading));
   const phoneIndex = headings.findIndex(heading => ['telepon', 'phone', 'whatsapp', 'no hp', 'no. hp'].includes(heading));
   if (nameIndex < 0 || phoneIndex < 0) throw new Error('Baris pertama harus memiliki kolom nama dan whatsapp.');
   if (!parsed.length || parsed.length > 100) throw new Error('Impor 1–100 pelanggan dalam satu file.');
   const known = new Set(customers.map(customer => normalizedPhone(customer.phone)));
   setEntries(parsed.map((values, index) => {
    const name = values[nameIndex] || '', phone = normalizedPhone(values[phoneIndex]);
    let issue = '';
    if (name.length < 2 || name.length > 100) issue = 'Nama harus 2–100 karakter';
    else if (!/^628[1-9]\d{7,10}$/.test(phone)) issue = 'Nomor WhatsApp tidak valid';
    else if (known.has(phone)) issue = 'Nomor sudah terdaftar / duplikat';
    if (!issue) known.add(phone);
    return {name, phone, row: index + 2, issue, status: issue ? 'skip' : 'ready'};
   }));
  } catch (failure) { setError(failure.message); }
 }
 async function save() {
  setBusy(true); setError('');
  for (let index = 0; index < entries.length; index++) {
   const entry = entries[index]; if (entry.status !== 'ready') continue;
   try {
    const result = await api('/customers', {name: entry.name, phone: entry.phone}); onCustomer(result.customer);
    setEntries(current => current.map((item, at) => at === index ? {...item, status: 'saved'} : item));
   } catch (failure) {
    setEntries(current => current.map((item, at) => at === index ? {...item, status: 'failed', issue: failure.message} : item));
   }
  }
  setBusy(false); setFinished(true);
 }
 const ready = entries.filter(entry => entry.status === 'ready').length, saved = entries.filter(entry => entry.status === 'saved').length;
 return <Modal title="Import data pelanggan" onClose={() => !busy && onClose()} wide>
  <p className="modal-description">Unggah CSV dengan kolom <b>nama, whatsapp</b>. Nomor yang sudah terdaftar akan dilewati.</p>
  <label className="ref-customer-upload"><Upload size={22}/><span>Pilih file CSV <small>Maksimal 100 pelanggan · 100 KB</small></span><input type="file" accept=".csv,text/csv" aria-label="File CSV pelanggan" disabled={busy} onChange={read}/></label>
  <ErrorBox message={error}/>
  {!!entries.length && <><div className="ref-import-summary">{finished ? `${saved} pelanggan berhasil disimpan.` : `${ready} pelanggan siap diimpor.`} {entries.length - ready - saved > 0 && `${entries.length - ready - saved} baris dilewati atau gagal.`}</div><div className="ref-import-preview"><table><thead><tr><th>Baris</th><th>Nama</th><th>WhatsApp</th><th>Status</th></tr></thead><tbody>{entries.map(entry => <tr key={entry.row}><td>{entry.row}</td><td>{entry.name || '—'}</td><td>{localPhone(entry.phone)}</td><td className={entry.issue ? 'ref-import-error' : ''}>{entry.status === 'saved' ? 'Tersimpan' : entry.issue || 'Siap diimpor'}</td></tr>)}</tbody></table></div></>}
  <div className="modal-actions"><button className="secondary" disabled={busy} onClick={onClose}>{finished ? 'Selesai' : 'Batal'}</button>{!finished && <button className="primary" disabled={!ready || busy} onClick={save}>{busy ? `Menyimpan ${saved}/${ready + saved}...` : `Import ${ready || ''} pelanggan`}</button>}</div>
 </Modal>;
}

export default function ReferenceCustomers({data, onCustomer, onSelect, go}) {
 const [search, setSearch] = useState(''), [sort, setSort] = useState('latest'), [page, setPage] = useState(1);
 const [selectedId, setSelectedId] = useState(null), [tab, setTab] = useState(tabs[0]), [checked, setChecked] = useState(new Set());
 const [add, setAdd] = useState(false), [edit, setEdit] = useState(null), [importing, setImporting] = useState(false), [copied, setCopied] = useState(false), [feedback, setFeedback] = useState('');
 const customers = useMemo(() => summaries(data.customers, data.orders), [data.customers, data.orders]);
 const month = jakartaDate().slice(0, 7);
 const filtered = customers.filter(customer => [customer.name, customer.phone, localPhone(customer.phone)].join(' ').toLowerCase().includes(search.toLowerCase())).sort((a, b) => sort === 'name' ? a.name.localeCompare(b.name, 'id') : sort === 'visits' ? b.visits - a.visits || a.name.localeCompare(b.name) : String(b.created_at).localeCompare(String(a.created_at)) || Number(b.id) - Number(a.id));
 const currentPage = Math.min(page, Math.max(1, Math.ceil(filtered.length / PAGE_SIZE))), visible = filtered.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE);
 const selected = filtered.find(customer => Number(customer.id) === Number(selectedId)) || filtered[0];
 const selectedOrders = selected?.history || [];
 const selectCustomer = customer => { setSelectedId(customer.id); setTab(tabs[0]); setCopied(false); };
 const changeFilter = setter => event => { setter(event.target.value); setPage(1); };
 function check(id) { setChecked(current => { const next = new Set(current); next.has(id) ? next.delete(id) : next.add(id); return next; }); }
 function checkPage() { setChecked(current => { const next = new Set(current), remove = visible.every(customer => next.has(customer.id)); visible.forEach(customer => remove ? next.delete(customer.id) : next.add(customer.id)); return next; }); }
 function exportCustomers() {
  const rows = checked.size ? customers.filter(customer => checked.has(customer.id)) : filtered;
  exportExcel('Pelanggan-Syaqil-Laundry', ['Nama Pelanggan', 'WhatsApp', 'Total Transaksi', 'Total Belanja', 'Catatan'], rows.map(customer => [customer.name, localPhone(customer.phone), customer.visits, customer.spend, customer.notes || '']));
 }
 async function copyPhone() {
  try { await navigator.clipboard.writeText(localPhone(selected.phone)); setCopied(true); setFeedback('Nomor WhatsApp disalin.'); }
  catch { setFeedback('Nomor belum dapat disalin. Pilih nomor WhatsApp untuk menyalinnya secara manual.'); }
 }
 return <div className="ref-customers reference-page">
  <ReferenceBanner variant="customers"/>
  <div className="ref-customer-heading"><div><h1>Pelanggan</h1><p>Kelola data pelanggan Syaqil Laundry</p></div><div className="ref-customer-actions"><button className="secondary" onClick={() => setImporting(true)}><Upload size={17}/> Import Data</button><button className="secondary" disabled={!filtered.length && !checked.size} onClick={exportCustomers}><Download size={17}/> Export Excel</button><button className="primary" onClick={() => setAdd(true)}><Plus size={19}/> Tambah Pelanggan</button></div></div>
  <div className="ref-customer-metrics">
   <StatCard icon={Users} label="Total Pelanggan" value={customers.length} tone="blue" note="pelanggan terdaftar"/>
   <StatCard icon={Star} label="Pelanggan Baru" value={customers.filter(customer => customer.created_at?.slice(0, 7) === month).length} tone="yellow" note="bulan ini"/>
   <StatCard icon={ReceiptText} label="Total Transaksi" value={customers.reduce((sum, c) => sum + c.visits, 0)} tone="purple" note="riwayat pesanan"/>
  </div>
  <div className="ref-customer-layout">
   <section className="ref-customer-list" aria-label="Daftar pelanggan">
    <div className="ref-customer-filters"><label className="ref-customer-search"><Search size={17}/><input aria-label="Cari pelanggan" placeholder="Cari nama atau nomor HP..." value={search} onChange={changeFilter(setSearch)}/></label><select aria-label="Urutkan pelanggan" value={sort} onChange={changeFilter(setSort)}><option value="latest">Urutkan: Terbaru</option><option value="name">Nama A–Z</option><option value="visits">Transaksi terbanyak</option></select></div>
    {checked.size > 0 && <div className="ref-customer-selection"><b>{checked.size} pelanggan dipilih</b><button onClick={exportCustomers}>Export pilihan <Download size={13}/></button><button onClick={() => setChecked(new Set())}>Batal</button></div>}
    <div className="ref-customer-table-wrap"><table className="ref-customer-table"><thead><tr><th><input type="checkbox" aria-label="Pilih semua pelanggan di halaman ini" checked={visible.length > 0 && visible.every(customer => checked.has(customer.id))} onChange={checkPage}/></th><th>No.</th><th>Nama Pelanggan</th><th>No. HP / WhatsApp</th><th>Total Transaksi</th><th>Total Belanja</th><th>Aksi</th></tr></thead><tbody>{visible.map((customer, index) => <tr key={customer.id} className={selected?.id === customer.id ? 'is-selected' : ''}>
     <td><input type="checkbox" aria-label={'Pilih ' + customer.name} checked={checked.has(customer.id)} onChange={() => check(customer.id)}/></td><td>{(currentPage - 1) * PAGE_SIZE + index + 1}</td><td><button className="ref-customer-name" onClick={() => selectCustomer(customer)}><span className={'ref-customer-avatar avatar-tone-' + Number(customer.id) % 6}>{initials(customer.name)}</span><b>{customer.name}</b></button></td><td>{localPhone(customer.phone)}</td><td>{customer.visits}</td><td>{rupiah(customer.spend)}</td><td><button className="ref-customer-more" aria-label={'Detail ' + customer.name} onClick={() => selectCustomer(customer)}><MoreVertical size={17}/></button></td>
    </tr>)}</tbody></table>{!visible.length && <Empty title="Pelanggan belum ditemukan" description="Coba kata kunci lain, atau tambahkan pelanggan baru."/>}</div>
    <Pagination page={currentPage} total={filtered.length} pageSize={PAGE_SIZE} onPage={setPage}/>
   </section>
   <aside className="ref-customer-detail" aria-label="Detail pelanggan">
    <div className="ref-customer-detail-heading"><h2>Detail Pelanggan</h2>{selected && <button className="secondary" onClick={() => setEdit({customer: selected})}><Pencil size={14}/> Edit</button>}</div>
    {selected ? <><div className="ref-customer-overview"><div className="ref-customer-bio"><div className="ref-customer-identity"><span className="ref-customer-large-avatar">{initials(selected.name)}</span><div><h3>{selected.name}</h3></div></div><div className="ref-customer-contacts"><div><Phone size={16}/><span>{localPhone(selected.phone)}</span><button aria-label="Salin nomor WhatsApp" onClick={copyPhone}>{copied ? <Check size={14}/> : <Copy size={14}/>}</button></div></div></div><div className="ref-customer-summary"><div><span><CalendarDays size={17}/></span><p>Terdaftar Sejak<b>{dateText(selected.created_at)}</b></p></div><div><span><ReceiptText size={17}/></span><p>Total Transaksi<b>{selected.visits} kali</b></p></div><div><span><ShoppingCart size={17}/></span><p>Total Belanja<b>{rupiah(selected.spend)}</b></p></div></div></div>
    <div className="ref-customer-tabs" role="tablist" aria-label="Informasi pelanggan">{tabs.map(label => <button key={label} role="tab" aria-selected={tab === label} aria-controls="customer-tab-panel" className={tab === label ? 'active' : ''} onClick={() => setTab(label)}>{label}</button>)}</div>
    <div id="customer-tab-panel" role="tabpanel" aria-label={tab} className="ref-customer-tab-content">
     {tab === tabs[0] && (selectedOrders.length ? <div className="ref-customer-history-wrap"><table className="ref-customer-history"><thead><tr><th>No. Nota</th><th>Tanggal</th><th>Layanan</th><th>Berat</th><th>Total</th><th>Status</th></tr></thead><tbody>{selectedOrders.map(order => <tr key={order.id}><td><button onClick={() => onSelect(order)}>{order.number}</button></td><td>{dateText(order.created_at)}</td><td>{[...new Set(order.items.map(item => item.service_name))].join(', ')}</td><td>{quantity(order.items.reduce((total, item) => total + Number(item.quantity), 0))} kg</td><td>{rupiah(order.total)}</td><td><span className="ref-customer-status active">Lunas</span></td></tr>)}</tbody></table></div> : <div className="ref-customer-empty"><ReceiptText size={27}/><b>Belum ada transaksi</b><p>Nota pelanggan akan muncul setelah pembayaran disimpan.</p><button onClick={() => go('kasir')}>Buat transaksi <ArrowRight size={14}/></button></div>)}
     {tab === tabs[1] && <div className="ref-profile-notes"><h3>Catatan pelanggan</h3><p>{selected.notes || 'Belum ada catatan khusus untuk pelanggan ini.'}</p><button className="text-button" onClick={() => setEdit({customer: selected, focusNotes: true})}><Pencil size={14}/> Edit catatan</button>{selectedOrders.filter(order => order.notes).length > 0 && <><h3>Catatan dari pesanan</h3>{selectedOrders.filter(order => order.notes).slice(0, 5).map(order => <div className="ref-order-note" key={order.id}><button onClick={() => onSelect(order)}>{order.number}<ChevronRight size={13}/></button><p>{order.notes}</p></div>)}</>}</div>}
    </div>
    <div className="ref-customer-note-card"><FileText size={25}/><div><b>Catatan Pelanggan</b><p>{selected.notes || 'Tambahkan preferensi atau perhatian khusus pelanggan.'}</p></div><button aria-label="Edit catatan pelanggan" onClick={() => setEdit({customer: selected, focusNotes: true})}><Pencil size={13}/> Edit</button></div>
    </> : <div className="ref-customer-empty"><Users size={30}/><b>Pilih pelanggan</b><p>Detail dan riwayat transaksi tampil di sini.</p></div>}
   </aside>
  </div>
  {feedback && <p className="ref-customer-feedback" role="status">{feedback}</p>}
  {add && <CustomerForm onClose={() => setAdd(false)} onSaved={customer => {onCustomer(customer); setSelectedId(customer.id); setSearch(''); setSort('latest'); setPage(1); setAdd(false);}}/>}
  {edit && <CustomerEditor {...edit} onClose={() => setEdit(null)} onSaved={customer => {onCustomer(customer); setSelectedId(customer.id); setEdit(null); setFeedback('Data pelanggan berhasil diperbarui.');}}/>}
  {importing && <ImportCustomers customers={data.customers} onCustomer={onCustomer} onClose={() => setImporting(false)}/>}
 </div>;
}
