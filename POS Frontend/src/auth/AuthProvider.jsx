import { createContext, useContext, useEffect, useRef, useState } from 'react';
import { authApi } from '../api/client';
import { clearNotifications, notify } from '../notifications';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const generation = useRef(0);
  async function refresh() {
    const current = ++generation.current;
    setLoading(true);
    setError(null);
    try {
      const staff = await authApi.me();
      if (current === generation.current) setUser(staff);
    }
    catch (failure) {
      if (current === generation.current) {
        setUser(null);
        if (failure.response?.status !== 401) { setError(failure); notify.error(failure); }
      }
    } finally { if (current === generation.current) setLoading(false); }
  }
  useEffect(() => { refresh(); return () => { generation.current++; }; }, []);
  useEffect(() => {
    const expire = () => {
      clearNotifications();
      notify.error('Your session expired. Please sign in again.');
      generation.current++; setUser(null); setError(null); setLoading(false);
    };
    window.addEventListener('pos:session-expired', expire);
    return () => window.removeEventListener('pos:session-expired', expire);
  }, []);
  async function login(credentials) {
    const staff = await authApi.login(credentials);
    clearNotifications();
    generation.current++;
    setUser(staff); setError(null); setLoading(false);
    notify.success('Signed in successfully.');
  }
  async function logout() {
    try { await authApi.logout(); }
    catch (failure) {
      if (![401, 419].includes(failure.response?.status)) { notify.error(failure); throw failure; }
    }
    generation.current++;
    clearNotifications();
    setUser(null); setError(null); setLoading(false);
    notify.success('Signed out successfully.');
  }
  async function changeBusiness(id) {
    const staff = await authApi.activate(id);
    clearNotifications();
    generation.current++;
    setUser(staff);
    notify.success('Business switched successfully.');
  }
  async function createBusiness(values) {
    const staff = await authApi.createBusiness(values);
    clearNotifications(); generation.current++; setUser(staff);
    notify.success('Business created successfully.');
  }
  async function register(values) {
    const staff = await authApi.register(values);
    clearNotifications(); generation.current++; setUser(staff);
    notify.success('Account and business created successfully.');
  }
  return <AuthContext.Provider value={{ user, loading, error, refresh, login, logout, changeBusiness, createBusiness, register }}>{children}</AuthContext.Provider>;
}

export const useAuth = () => useContext(AuthContext);
