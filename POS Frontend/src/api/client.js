import axios from 'axios';
import { dismissOperationErrors, dismissValidationNotification, ignoreNotificationError, notificationOperation, notificationScope, notify } from '../notifications';
import { apiPath, successNotification } from '../notifications/apiMessages';
import { trackLoadingAdapter } from '../loading/trackAdapter';
export { errorMessage } from './errors';

export const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
});

client.interceptors.request.use(config => {
  config.adapter = trackLoadingAdapter(axios.getAdapter(config.adapter || client.defaults.adapter, config));
  config.posNotificationScope = notificationScope();
  if (['post', 'put', 'patch', 'delete'].includes(config.method)) dismissValidationNotification();
  return config;
});
const currentScope = config => config?.posNotificationScope === undefined || config.posNotificationScope === notificationScope();
client.interceptors.response.use(response => {
  if (currentScope(response.config)) {
    const notification = successNotification(response.config, response.data);
    if (notification) {
      if (notification.type !== 'warning') { dismissOperationErrors(response.config); dismissValidationNotification(); }
      notify[notification.type](notification.message, notification.type === 'warning' ? { operationKey: notificationOperation(response.config) } : undefined);
    }
  }
  return response;
}, error => {
  const path = apiPath(error.config?.url), status = error.response?.status;
  if (!currentScope(error.config)) { ignoreNotificationError(error); return Promise.reject(error); }
  const expected = (path === 'auth/me' && status === 401) || (path === 'auth/logout' && [401, 419].includes(status));
  const expired = [401, 419].includes(status) && ! /\/auth\/(login|register|me|logout)$/.test(error.config?.url || '');
  if (expected || expired || error.config?.notifications === false || axios.isCancel(error)) ignoreNotificationError(error);
  else notify.error(error);
  if (expired && typeof window !== 'undefined') {
    window.dispatchEvent(new Event('pos:session-expired'));
  }
  return Promise.reject(error);
});

export const authApi = {
  me: async () => (await client.get('/api/v1/auth/me')).data.data,
  login: async credentials => {
    await client.get('/sanctum/csrf-cookie');
    return (await client.post('/api/v1/auth/login', credentials)).data.data;
  },
  logout: async () => {
    await client.get('/sanctum/csrf-cookie');
    await client.post('/api/v1/auth/logout');
  },
  tenants: async () => (await client.get('/api/v1/tenants')).data,
  activate: async id => (await client.post(`/api/v1/tenants/${id}/activate`)).data.data,
  createBusiness: async values => (await client.post('/api/v1/tenants', values)).data.data,
  register: async values => {
    await client.get('/sanctum/csrf-cookie');
    return (await client.post('/api/v1/auth/register', values)).data.data;
  },
};

