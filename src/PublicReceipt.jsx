import React, { useState, useEffect } from 'react';
import { 
  Check, 
  Clock, 
  Printer, 
  MessageCircle, 
  AlertCircle, 
  ShieldCheck, 
  Package, 
  Sparkles, 
  ChevronRight,
  ExternalLink
} from 'lucide-react';
import { rupiah, quantity, dateText } from './lib';

export default function PublicReceipt({ token }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!token) {
      setError('Token nota digital tidak ditemukan.');
      setLoading(false);
      return;
    }

    let isMounted = true;
    const fetchReceipt = async () => {
      try {
        const apiBase = (import.meta.env?.VITE_API_BASE_URL || '/api').replace(/\/$/, '');
        const res = await fetch(`${apiBase}/public/receipt/${encodeURIComponent(token)}`, {
          headers: {
            'Accept': 'application/json',
          },
        });

        if (!res.ok) {
          if (res.status === 404) {
            throw new Error('Nota digital tidak ditemukan atau tautan telah kedaluwarsa.');
          }
          throw new Error(`Gagal memuat nota (HTTP ${res.status}).`);
        }

        const json = await res.json();
        if (isMounted) {
          setData(json);
        }
      } catch (err) {
        if (isMounted) {
          setError(err.message || 'Terjadi kesalahan saat memuat nota digital.');
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    };

    fetchReceipt();
    return () => {
      isMounted = false;
    };
  }, [token]);

  if (loading) {
    return (
      <div style={styles.page}>
        <div style={styles.card}>
          <div style={styles.loadingContainer}>
            <div className="loader" style={{ width: '36px', height: '36px', borderWidth: '3px' }} />
            <p style={{ marginTop: '16px', color: '#64748b', fontSize: '14px', fontWeight: '500' }}>
              Memuat nota digital pelanggan...
            </p>
          </div>
        </div>
      </div>
    );
  }

  if (error || !data?.order) {
    return (
      <div style={styles.page}>
        <div style={styles.card}>
          <div style={styles.errorContainer}>
            <div style={styles.errorIconBadge}>
              <AlertCircle size={32} color="#dc2626" />
            </div>
            <h2 style={styles.errorTitle}>Nota Tidak Ditemukan</h2>
            <p style={styles.errorDesc}>
              {error || 'Tautan nota digital yang Anda buka tidak terdaftar dalam sistem.'}
            </p>
            <div style={styles.errorHelp}>
              <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>
                Pastikan tautan yang Anda klik dari pesan WhatsApp sudah lengkap dan benar.
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const { order, store = {} } = data;
  const statuses = ['Diterima', 'Diproses', 'Selesai', 'Diambil'];
  const currentIndex = statuses.indexOf(order.status);
  const activeStep = currentIndex === -1 ? 0 : currentIndex;

  const storePhoneClean = (store.phone || '').replace(/[^0-9]/g, '');
  const storePhoneFormatted = storePhoneClean.startsWith('0') 
    ? '62' + storePhoneClean.slice(1) 
    : storePhoneClean;
  const waContactUrl = storePhoneFormatted 
    ? `https://wa.me/${storePhoneFormatted}?text=${encodeURIComponent(`Halo ${store.name || 'Laundry'}, saya ingin menanyakan pesanan #${order.number}.`)}`
    : null;

  return (
    <div style={styles.page}>
      <div style={styles.card} className="public-receipt-card">
        {/* Header Toko */}
        <header style={styles.header}>
          <div style={styles.badge}>
            <ShieldCheck size={14} />
            <span>Nota Resmi Pelanggan</span>
          </div>
          <h1 style={styles.storeName}>{store.name || 'Syaqil Laundry'}</h1>
          <p style={styles.storeInfo}>
            {store.address || 'Jl. Layanan Laundry'}{store.phone ? ` • ${store.phone}` : ''}
          </p>
        </header>

        {/* Progress Tracker Cucian */}
        <div style={styles.tracker}>
          <div style={styles.trackerHeader}>
            <span style={styles.trackerLabel}>Status Pengerjaan</span>
            <span style={{
              ...styles.statusPill,
              ...(order.status === 'Selesai' ? styles.statusPillReady : (order.status === 'Diproses' ? styles.statusPillProcess : styles.statusPillDefault))
            }}>
              {order.status === 'Selesai' ? 'Siap Diambil' : order.status}
            </span>
          </div>
          <div style={styles.stepGrid}>
            {statuses.map((st, idx) => {
              const isCompleted = idx < activeStep;
              const isActive = idx === activeStep;
              return (
                <div key={st} style={styles.stepItem}>
                  <div style={{
                    ...styles.stepBar,
                    backgroundColor: isCompleted ? '#10b981' : (isActive ? '#0755ee' : '#e2e8f0')
                  }} />
                  <span style={{
                    ...styles.stepText,
                    color: (isActive || isCompleted) ? '#0f172a' : '#94a3b8',
                    fontWeight: (isActive || isCompleted) ? '700' : '500'
                  }}>
                    {st === 'Selesai' ? 'Siap' : st}
                  </span>
                </div>
              );
            })}
          </div>
        </div>

        {/* Isi Rincian Nota */}
        <div style={styles.body}>
          {/* Metadata Grid */}
          <div style={styles.metaGrid}>
            <div style={styles.metaCol}>
              <span style={styles.metaLabel}>No. Nota</span>
              <span style={styles.metaNumber}>{order.number}</span>
            </div>
            <div style={styles.metaCol}>
              <span style={styles.metaLabel}>Nama Pelanggan</span>
              <span style={styles.metaValue}>{order.customer_name}</span>
            </div>
            <div style={styles.metaCol}>
              <span style={styles.metaLabel}>Tanggal Masuk</span>
              <span style={styles.metaValue}>{dateText(order.created_at, true)}</span>
            </div>
            <div style={styles.metaCol}>
              <span style={styles.metaLabel}>Estimasi Selesai</span>
              <span style={styles.metaValue}>{dateText(order.due_at, true)}</span>
            </div>
          </div>

          {/* Rincian Layanan */}
          <div style={styles.sectionHeader}>Rincian Layanan Cucian</div>
          <div style={styles.itemsList}>
            {order.items?.map((item, idx) => (
              <div key={idx} style={styles.itemRow}>
                <div style={{ flex: 1, paddingRight: '12px' }}>
                  <div style={styles.itemName}>{item.service_name}</div>
                  <div style={styles.itemSub}>
                    {item.category} • {quantity(item.quantity)} {item.unit} × {rupiah(item.price)}
                  </div>
                </div>
                <div style={styles.itemSubtotal}>{rupiah(item.subtotal)}</div>
              </div>
            ))}
          </div>

          {/* Catatan Khusus */}
          {order.notes && (
            <div style={styles.notesBox}>
              <span style={{ fontWeight: '700', color: '#475569' }}>Catatan Khusus:</span>
              <p style={{ margin: '4px 0 0', color: '#334155', fontSize: '13px' }}>{order.notes}</p>
            </div>
          )}

          {/* Rincian Pembayaran */}
          <div style={styles.sectionHeader}>Rincian Pembayaran</div>
          <div style={styles.paymentBox}>
            <div style={styles.payRowTotal}>
              <span>Total Pembayaran</span>
              <span style={styles.totalValue}>{rupiah(order.total)}</span>
            </div>
            {((order.payment_status === 'unpaid') || (order.method === 'later')) ? (
              <div style={{ ...styles.paidBadge, backgroundColor: '#fffbeb', borderColor: '#fde68a', color: '#b45309' }}>
                <span>Metode: Bayar Nanti</span>
                <span style={{ fontWeight: '800' }}>⚠ BELUM BAYAR</span>
              </div>
            ) : (
              <div style={styles.paidBadge}>
                <span>Metode: {order.method ? order.method.toUpperCase() : 'TUNAI'}</span>
                <span style={{ fontWeight: '800' }}>✓ LUNAS</span>
              </div>
            )}
          </div>

          {/* Tombol Aksi */}
          <div style={styles.actions} className="no-print">
            <button 
              type="button" 
              style={styles.btnPrint}
              onClick={() => window.print()}
            >
              <Printer size={16} />
              <span>Cetak / Simpan Nota (PDF)</span>
            </button>

            {waContactUrl && (
              <a 
                href={waContactUrl} 
                target="_blank" 
                rel="noopener noreferrer" 
                style={styles.btnWa}
              >
                <MessageCircle size={16} />
                <span>Hubungi Laundry via WhatsApp</span>
              </a>
            )}
          </div>

          {/* Footer Verifikasi */}
          <footer style={styles.footer}>
            <p style={{ margin: '0 0 6px' }}>
              Terima kasih telah mempercayakan pakaian Anda kepada <b>{store.name || 'Syaqil Laundry'}</b>.
            </p>
            <p style={{ margin: '0 0 10px', fontSize: '12px' }}>
              Tunjukkan nota digital ini saat pengambilan pakaian di outlet kami.
            </p>
            <div style={styles.verifiedStamp}>
              <ShieldCheck size={14} />
              <span>Tautan Nota Terverifikasi Resmi</span>
            </div>
          </footer>
        </div>
      </div>
    </div>
  );
}

const styles = {
  page: {
    minHeight: '100vh',
    backgroundColor: '#f1f5f9',
    padding: '24px 16px',
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'flex-start',
    fontFamily: "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif",
  },
  card: {
    width: '100%',
    maxWidth: '520px',
    backgroundColor: '#ffffff',
    borderRadius: '20px',
    boxShadow: '0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04)',
    border: '1px solid #e2e8f0',
    overflow: 'hidden',
  },
  loadingContainer: {
    padding: '60px 20px',
    textAlign: 'center',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
  },
  errorContainer: {
    padding: '48px 24px',
    textAlign: 'center',
  },
  errorIconBadge: {
    width: '64px',
    height: '64px',
    borderRadius: '50%',
    backgroundColor: '#fee2e2',
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: '16px',
  },
  errorTitle: {
    fontSize: '20px',
    fontWeight: '800',
    color: '#0f172a',
    margin: '0 0 8px',
  },
  errorDesc: {
    fontSize: '14px',
    color: '#64748b',
    margin: '0 0 20px',
    lineHeight: '1.5',
  },
  errorHelp: {
    padding: '14px',
    backgroundColor: '#f8fafc',
    borderRadius: '12px',
    border: '1px solid #e2e8f0',
  },
  header: {
    background: 'linear-gradient(135deg, #0755ee 0%, #003db3 100%)',
    color: '#ffffff',
    padding: '28px 24px 22px',
    textAlign: 'center',
  },
  badge: {
    display: 'inline-flex',
    alignItems: 'center',
    gap: '6px',
    backgroundColor: 'rgba(255, 255, 255, 0.16)',
    padding: '5px 12px',
    borderRadius: '9999px',
    fontSize: '11px',
    fontWeight: '700',
    letterSpacing: '0.04em',
    textTransform: 'uppercase',
    marginBottom: '10px',
    border: '1px solid rgba(255, 255, 255, 0.25)',
  },
  storeName: {
    fontSize: '22px',
    fontWeight: '800',
    margin: '0 0 4px',
    letterSpacing: '-0.02em',
  },
  storeInfo: {
    fontSize: '13px',
    color: 'rgba(255, 255, 255, 0.85)',
    margin: 0,
    lineHeight: '1.4',
  },
  tracker: {
    backgroundColor: '#ffffff',
    margin: '-12px 18px 16px',
    borderRadius: '14px',
    padding: '14px 16px',
    boxShadow: '0 4px 12px rgba(15, 23, 42, 0.08)',
    border: '1px solid #e2e8f0',
  },
  trackerHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: '12px',
  },
  trackerLabel: {
    fontSize: '12px',
    fontWeight: '700',
    color: '#64748b',
    textTransform: 'uppercase',
    letterSpacing: '0.05em',
  },
  statusPill: {
    padding: '4px 10px',
    borderRadius: '9999px',
    fontSize: '12px',
    fontWeight: '700',
  },
  statusPillReady: {
    backgroundColor: '#ecfdf5',
    color: '#10b981',
  },
  statusPillProcess: {
    backgroundColor: '#fffbeb',
    color: '#d97706',
  },
  statusPillDefault: {
    backgroundColor: '#f0f9ff',
    color: '#0284c7',
  },
  stepGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(4, 1fr)',
    gap: '6px',
    textAlign: 'center',
  },
  stepItem: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    gap: '5px',
  },
  stepBar: {
    width: '100%',
    height: '5px',
    borderRadius: '9999px',
  },
  stepText: {
    fontSize: '11px',
  },
  body: {
    padding: '0 20px 24px',
  },
  metaGrid: {
    display: 'grid',
    gridTemplateColumns: '1fr 1fr',
    gap: '12px',
    backgroundColor: '#f8fafc',
    borderRadius: '12px',
    padding: '12px 14px',
    marginBottom: '18px',
    border: '1px solid #edf2f7',
  },
  metaCol: {
    display: 'flex',
    flexDirection: 'column',
    gap: '2px',
  },
  metaLabel: {
    fontSize: '11px',
    fontWeight: '600',
    color: '#64748b',
    textTransform: 'uppercase',
  },
  metaNumber: {
    fontFamily: 'monospace',
    fontWeight: '700',
    fontSize: '13px',
    color: '#0755ee',
  },
  metaValue: {
    fontSize: '13px',
    fontWeight: '700',
    color: '#0f172a',
  },
  sectionHeader: {
    fontSize: '12px',
    fontWeight: '700',
    color: '#475569',
    textTransform: 'uppercase',
    letterSpacing: '0.05em',
    marginBottom: '10px',
  },
  itemsList: {
    display: 'flex',
    flexDirection: 'column',
    gap: '8px',
    marginBottom: '16px',
  },
  itemRow: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    padding: '10px 12px',
    backgroundColor: '#ffffff',
    borderRadius: '10px',
    border: '1px solid #eef2f6',
  },
  itemName: {
    fontSize: '13px',
    fontWeight: '700',
    color: '#0f172a',
  },
  itemSub: {
    fontSize: '12px',
    color: '#64748b',
    marginTop: '2px',
  },
  itemSubtotal: {
    fontSize: '13px',
    fontWeight: '700',
    color: '#0f172a',
    whiteSpace: 'nowrap',
  },
  notesBox: {
    backgroundColor: '#f8fafc',
    borderLeft: '3px solid #94a3b8',
    padding: '10px 12px',
    borderRadius: '0 8px 8px 0',
    fontSize: '12px',
    marginBottom: '16px',
  },
  paymentBox: {
    backgroundColor: '#f8fafc',
    borderRadius: '12px',
    padding: '14px',
    border: '1px solid #e2e8f0',
    marginBottom: '20px',
  },
  payRow: {
    display: 'flex',
    justifyContent: 'space-between',
    fontSize: '13px',
    color: '#64748b',
    marginBottom: '6px',
  },
  payDivider: {
    height: '1px',
    backgroundColor: '#e2e8f0',
    margin: '8px 0',
  },
  payRowTotal: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    fontWeight: '800',
    fontSize: '15px',
    color: '#0f172a',
  },
  totalValue: {
    color: '#0755ee',
    fontSize: '17px',
  },
  paidBadge: {
    marginTop: '10px',
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#ecfdf5',
    border: '1px solid #a7f3d0',
    color: '#065f46',
    padding: '8px 10px',
    borderRadius: '8px',
    fontSize: '12px',
  },
  actions: {
    display: 'flex',
    flexDirection: 'column',
    gap: '10px',
    marginBottom: '20px',
  },
  btnPrint: {
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: '8px',
    width: '100%',
    padding: '12px',
    borderRadius: '12px',
    fontSize: '14px',
    fontWeight: '700',
    cursor: 'pointer',
    backgroundColor: '#0755ee',
    color: '#ffffff',
    border: 'none',
  },
  btnWa: {
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: '8px',
    width: '100%',
    padding: '12px',
    borderRadius: '12px',
    fontSize: '14px',
    fontWeight: '700',
    cursor: 'pointer',
    backgroundColor: '#25d366',
    color: '#ffffff',
    textDecoration: 'none',
    border: 'none',
  },
  footer: {
    textAlign: 'center',
    paddingTop: '16px',
    borderTop: '1px dashed #e2e8f0',
    fontSize: '12px',
    color: '#64748b',
    lineHeight: '1.5',
  },
  verifiedStamp: {
    display: 'inline-flex',
    alignItems: 'center',
    gap: '4px',
    color: '#10b981',
    fontSize: '11px',
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: '0.04em',
  },
};
