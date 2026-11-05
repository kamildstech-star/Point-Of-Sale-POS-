import { toast } from 'react-toastify';
import { errorMessage } from '../api/errors';
import { apiPath } from './apiMessages';

let scope = 0;
const reportedErrors = new WeakSet();
const recent = new Map();
const operationErrors = new Map();
export const notificationScope = () => scope;
export const notificationOperation = config => `${config?.method || 'get'}:${apiPath(config?.url)}`;
export function dismissValidationNotification() { toast.dismiss(`pos-${scope}-validation`); }
export function dismissOperationErrors(config) {
  const key = notificationOperation(config);
  for (const id of operationErrors.get(key) || []) toast.dismiss(id);
  operationErrors.delete(key);
}
export function ignoreNotificationError(error) {
  if (error && typeof error === 'object') reportedErrors.add(error);
}

function messageId(type, message) {
  let hash = 2166136261;
  for (const character of `${type}:${message}`) hash = Math.imul(hash ^ character.charCodeAt(0), 16777619);
  return `pos-${scope}-${type}-${hash >>> 0}`;
}
function show(type, message, options = {}) {
  if (typeof message !== 'string' || !message.trim()) return;
  const text = message.trim().slice(0, 350);
  const { id, operationKey, ...settings } = options;
  const toastId = id || messageId(type, text);
  if (operationKey) {
    if (!operationErrors.has(operationKey)) operationErrors.set(operationKey, new Set());
    operationErrors.get(operationKey).add(toastId);
    if (operationErrors.size > 100) operationErrors.delete(operationErrors.keys().next().value);
  }
  // Suppress concurrent failures even before Toastify commits the first toast.
  if ((type === 'error' || type === 'warning') && Date.now() - (recent.get(toastId) ?? -Infinity) < 2500) return;
  if (toast.isActive(toastId)) return;
  recent.set(toastId, Date.now());
  if (recent.size > 100) recent.delete(recent.keys().next().value);
  return toast[type](text, { toastId, role: type === 'error' ? 'alert' : 'status', autoClose: type === 'error' ? 7000 : 4500, ...settings });
}
export const notify = {
  success: (message, options) => show('success', message, options),
  info: (message, options) => show('info', message, options),
  warning: (message, options) => show('warning', message, options),
  error(error, options = {}) {
    if (!error || error.code === 'ERR_CANCELED' || error.name === 'CanceledError') return;
    if (typeof error === 'object') {
      if (reportedErrors.has(error)) return;
      reportedErrors.add(error);
    }
    if (error.response?.status === 422 && error.response?.data?.message === 'Please correct the highlighted fields.') return notify.validation();
    return show('error', typeof error === 'string' ? error : errorMessage(error), { ...(error.config ? { operationKey: notificationOperation(error.config) } : {}), ...options });
  },
  validation: () => show('error', 'Please correct the highlighted fields.', { id: `pos-${scope}-validation`, role: 'status' }),
};

// Used on sign-in, sign-out, session expiry and business switches. Requests
// already in flight carry the previous scope and cannot create stale toasts.
export function clearNotifications() {
  scope++;
  recent.clear();
  operationErrors.clear();
  toast.clearWaitingQueue();
  toast.dismiss();
}
