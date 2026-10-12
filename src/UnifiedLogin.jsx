import React, { useState } from 'react';
import { ArrowRight, Eye, EyeOff, LockKeyhole, UserRound } from 'lucide-react';
import { api } from './lib';
import { Logo, ErrorBox } from './components';

export default function UnifiedLogin({ settings, onLoginSuccess }) {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [show, setShow] = useState(false);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function handleSubmit(e, presetUser = '', presetPass = '') {
    if (e) e.preventDefault();
    const u = presetUser || username;
    const p = presetPass || password;
    if (!u.trim() || !p.trim() || busy) return;

    setBusy(true);
    setError('');

    try {
      const res = await api('/login', { username: u, password: p });
      if (onLoginSuccess) {
        onLoginSuccess(res);
      }
    } catch (err) {
      setError(err.message || 'Gagal masuk. Periksa kembali username dan kata sandi Anda.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <main className="login-shell">
      <section className="login-form-panel">
        <Logo />
        <div className="login-form-content">
          <span className="eyebrow">SELAMAT DATANG DI SISTEM TERPADU</span>
          <h1>Masuk ke<br />Syaqil Laundry.</h1>
          <p>Satu akses untuk Meja Kasir dan Dashboard Manajemen Owner.</p>

          <form onSubmit={handleSubmit}>
            <label htmlFor="username">Username</label>
            <div className="input-icon">
              <UserRound size={18} />
              <input
                id="username"
                value={username}
                autoComplete="username"
                onChange={e => setUsername(e.target.value)}
                required
                placeholder="Masukkan username (kasir / owner)"
              />
            </div>

            <label htmlFor="password">Kata Sandi</label>
            <div className="input-icon">
              <LockKeyhole size={18} />
              <input
                id="password"
                type={show ? 'text' : 'password'}
                value={password}
                onChange={e => setPassword(e.target.value)}
                autoComplete="current-password"
                required
                placeholder="Masukkan kata sandi"
              />
              <button
                type="button"
                className="icon-button"
                aria-label={show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                onClick={() => setShow(!show)}
              >
                {show ? <EyeOff size={18} /> : <Eye size={18} />}
              </button>
            </div>

            <ErrorBox message={error} />

            <button type="submit" className="primary" disabled={busy}>
              {busy ? 'Memverifikasi akun...' : 'Masuk ke Sistem'}
              <ArrowRight size={18} />
            </button>
          </form>

          <div className="demo-login" style={{ marginTop: '22px' }}>
            <span>Masuk Cepat:</span>
            <div style={{ display: 'flex', gap: '8px' }}>
              <button
                type="button"
                className="secondary"
                disabled={busy}
                onClick={() => handleSubmit(null, 'kasir', 'KasirSyaqil!2026')}
              >
                Kasir
              </button>
              <button
                type="button"
                className="secondary"
                disabled={busy}
                onClick={() => handleSubmit(null, 'owner', 'OwnerSyaqil!2026')}
              >
                Owner
              </button>
            </div>
            <small>Sistem secara otomatis mengarahkan ke dashboard sesuai role akun Anda.</small>
          </div>

          <p className="login-help">
            Sistem terintegrasi Syaqil Laundry Ekspres • Single Origin Architecture
          </p>
        </div>
        <small>© {new Date().getFullYear()} Syaqil Laundry • Bersih. Cepat. Beres.</small>
      </section>

      <section className="login-poster">
        <div className="poster-top">
          <span>SYAQIL LAUNDRY</span>
          <span>EST. CLEAN</span>
        </div>
        <h2>
          BERSIH.<br />
          CEPAT.<br />
          <span>BERES.</span>
        </h2>
        <div className="login-logo">
          <img src="/assets/logo.png" alt="Logo Syaqil Laundry" />
        </div>
        <span className="login-price">
          MULAI<br />
          <strong>6K</strong> / KG
        </span>
        <span className="login-tag">
          Cucian beres,<br />
          hari lebih ringan!
        </span>
        <div className="poster-bottom">
          <b>CUCI • BERSIH • PRAKTIS</b>
          <span>KASIR &amp; OWNER TERPADU</span>
        </div>
      </section>
    </main>
  );
}
