import React, { useState, useEffect, useCallback } from 'react';
import { api } from './lib';
import CashierApp from './CashierApp';
import OwnerApp from './owner/OwnerApp';
import UnifiedLogin from './UnifiedLogin';
import PublicReceipt from './PublicReceipt';

function getPublicTokenFromUrl() {
  if (typeof window === 'undefined') return null;
  const pathname = window.location.pathname || '';
  if (pathname.startsWith('/nota/')) {
    const segment = pathname.replace(/^\/nota\//, '').split('/')[0].split('?')[0];
    if (segment) return segment;
  }
  const hash = window.location.hash || '';
  if (hash.startsWith('#/nota/') || hash.startsWith('#nota/')) {
    const segment = hash.replace(/^#(|\/)nota\//, '').split('/')[0].split('?')[0];
    if (segment) return segment;
  }
  return null;
}

const OWNER_PAGES = [
  'dashboard',
  'pelanggan',
  'riwayat-nota',
  'layanan-harga',
  'karyawan',
  'koreksi-transaksi',
  'pengeluaran',
  'laporan-keuangan',
  'pengaturan',
  'analytics',
];

const CASHIER_PAGES = [
  'kasir',
  'dashboard',
  'pesanan',
  'pelanggan',
  'riwayat',
  'laporan',
];

export default function App() {
  const [session, setSession] = useState(null);
  const [loading, setLoading] = useState(true);
  const [currentHash, setCurrentHash] = useState(() => location.hash.slice(1));
  const [publicToken, setPublicToken] = useState(() => getPublicTokenFromUrl());

  useEffect(() => {
    const handleUrlChange = () => {
      setPublicToken(getPublicTokenFromUrl());
    };
    window.addEventListener('hashchange', handleUrlChange);
    window.addEventListener('popstate', handleUrlChange);
    return () => {
      window.removeEventListener('hashchange', handleUrlChange);
      window.removeEventListener('popstate', handleUrlChange);
    };
  }, []);

  const checkSession = useCallback(async () => {
    try {
      const data = await api('/session');
      setSession(data);
      return data;
    } catch (err) {
      setSession({ user: null });
      return { user: null };
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    checkSession();
  }, [checkSession]);

  // Pantau hashchange dan lakukan role guard
  useEffect(() => {
    const onHashChange = () => {
      const hash = location.hash.slice(1);
      setCurrentHash(hash);

      if (session?.user) {
        const role = session.user.role;
        // Jika kasir mencoba mengakses halaman owner khusus
        if (role === 'cashier' && ['karyawan', 'pengeluaran', 'layanan-harga', 'koreksi-transaksi', 'pengaturan', 'riwayat-nota'].includes(hash)) {
          location.hash = 'kasir';
        }
      }
    };

    window.addEventListener('hashchange', onHashChange);
    return () => window.removeEventListener('hashchange', onHashChange);
  }, [session]);

  const handleLoginSuccess = async (loginData) => {
    if (loginData?.user) {
      setSession(prev => ({
        ...(prev || {}),
        user: loginData.user,
        settings: loginData.settings || prev?.settings,
      }));

      // Arahkan ke dashboard yang sesuai
      if (loginData.user.role === 'owner') {
        location.hash = 'dashboard';
      } else {
        location.hash = 'kasir';
      }
    } else {
      await checkSession();
    }
  };

  const handleLogout = async () => {
    try {
      await api('/logout', {});
    } catch (e) {
      console.warn('Logout failed:', e);
    }
    setSession({ user: null });
    location.hash = 'login';
  };

  // Akses Publik Nota Digital: Dapat dibuka siapa saja melalui internet tanpa login kasir
  if (publicToken) {
    return <PublicReceipt token={publicToken} />;
  }

  if (loading) {
    return (
      <div className="loading-screen" style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', background: '#f6f9ff' }}>
        <div className="loader" />
        <div className="brand" style={{ display: 'flex', alignItems: 'center', gap: '10px', marginTop: '16px' }}>
          <img src="/assets/logo.png" alt="Syaqil Laundry" width="44" height="44" style={{ borderRadius: '50%' }} />
          <b style={{ fontSize: '20px', color: '#0755ee' }}>Syaqil Laundry</b>
        </div>
        <p style={{ color: '#7a8ba5', fontSize: '12px', marginTop: '8px' }}>Memuat sistem web terpadu...</p>
      </div>
    );
  }

  // Jika belum login, tampilkan halaman Login Terpadu
  if (!session?.user) {
    return <UnifiedLogin settings={session?.settings} onLoginSuccess={handleLoginSuccess} />;
  }

  // Routing Berdasarkan Role:
  // 1. Role Owner -> Tampilkan Owner Workspace
  if (session.user.role === 'owner') {
    return (
      <OwnerApp
        currentUser={session.user}
        onLogout={handleLogout}
      />
    );
  }

  // 2. Role Kasir -> Tampilkan Kasir Workspace
  return (
    <CashierApp
      session={session}
      onLogout={handleLogout}
      onSessionUpdate={checkSession}
    />
  );
}
