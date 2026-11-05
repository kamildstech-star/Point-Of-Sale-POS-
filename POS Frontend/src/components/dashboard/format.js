import { currencyPrecision } from '../../validation/forms';

const decimal = /^-?\d+(?:\.\d+)?$/;
const validValue = value => (typeof value === 'string' || typeof value === 'bigint' || typeof value === 'number') && decimal.test(String(value));

// Pass decimal strings directly to Intl. Never coerce financial display values
// through Number; chart geometry is the only place approximate numbers belong.
export function formatNumber(value, locale) {
  if (!validValue(value)) return null;
  try { return new Intl.NumberFormat(locale, { maximumFractionDigits: 4 }).format(value); }
  catch { return null; }
}
export function formatMoney(value, currency, locale) {
  if (!validValue(value) || !/^[A-Z]{3}$/.test(currency || '')) return null;
  const scale = currencyPrecision(currency);
  try { return new Intl.NumberFormat(locale, { style: 'currency', currency, currencyDisplay: 'code', minimumFractionDigits: scale, maximumFractionDigits: scale }).format(value); }
  catch { return null; }
}
export function formatUpdatedAt(value, timezone, locale) {
  if (!value || !timezone || Number.isNaN(new Date(value).getTime())) return null;
  try { return new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short', timeZone: timezone }).format(new Date(value)); }
  catch { return null; }
}
