import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { AuthProvider } from './auth/AuthProvider';
import App from './app/App';
import GlobalToaster from './components/GlobalToaster';
import GlobalLoader from './components/GlobalLoader';
import { initializeTheme } from './theme/preference';
import '@fontsource/inter/latin-400.css';
import '@fontsource/inter/latin-500.css';
import '@fontsource/inter/latin-600.css';
import '@fontsource/inter/latin-700.css';
import './styles/tailwind.css';
import './styles/tokens.css';
import './styles/app.css';
import './styles/modal.css';
import './styles/notifications.css';
import './styles/icons.css';
import './styles/foundations.css';
import './styles/layout.css';
import './styles/loading.css';
import './styles/shell.css';
import './styles/dashboard.css';
import './styles/pos.css';

initializeTheme();

createRoot(document.getElementById('root')).render(
  <React.StrictMode><GlobalToaster/><GlobalLoader/><BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><AuthProvider><App /></AuthProvider></BrowserRouter></React.StrictMode>,
);
