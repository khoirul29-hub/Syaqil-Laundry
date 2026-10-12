import React from 'react';
import { createRoot } from 'react-dom/client';

// Tipografi Font Terpadu
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/inter/800.css';
import '@fontsource/anton/400.css';
import '@fontsource/archivo-black/400.css';
import '@fontsource/caveat/600.css';

// Stylesheet Kasir
import './styles.css';
import './reference.css';
import './orders-reference.css';

// Stylesheet Owner
import './owner/tokens.css';
import './owner/owner.css';
import './owner/screens.css';
import './owner/management.css';
import './owner/finance.css';
import './owner/settings.css';
import './owner/receipt-preview.css';

import App from './App.jsx';

createRoot(document.getElementById('root')).render(<App />);
