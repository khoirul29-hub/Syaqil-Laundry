import React from 'react';
import {createRoot} from 'react-dom/client';
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/inter/800.css';
import '@fontsource/archivo-black/400.css';
import '@fontsource/caveat/600.css';
import OwnerApp from './OwnerApp.jsx';
import './tokens.css';
import './owner.css';
import './screens.css';
import './management.css';
import './finance.css';
import './settings.css';

createRoot(document.getElementById('root')).render(<OwnerApp/>);
