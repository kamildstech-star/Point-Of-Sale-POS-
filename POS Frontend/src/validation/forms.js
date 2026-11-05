// Keep these rules aligned with Laravel. Database identity, stock, permissions
// and subscription checks always remain authoritative on the server.
export const decimalPattern = (digits = 12, signed = false) => new RegExp(`^${signed ? '-?' : ''}\\d{1,${digits}}(?:\\.\\d{1,4})?$`);
export function currencyPrecision(code) {
  if (['JPY', 'KRW', 'VND'].includes(code)) return 0;
  if (['BHD', 'JOD', 'KWD', 'OMR', 'TND'].includes(code)) return 3;
  if (['PKR', 'USD', 'EUR', 'GBP', 'INR', 'AED', 'SAR', 'CAD', 'AUD', 'NZD', 'CHF', 'CNY', 'HKD', 'SGD', 'MYR', 'BDT', 'NPR', 'LKR', 'QAR', 'ZAR', 'THB', 'TRY', 'RUB'].includes(code)) return 2;
  return undefined; // Unsupported currencies are blocked by the retail service.
}
export function decimalUnits(value) {
  const [whole, fraction = ''] = String(value).split('.');
  return BigInt(whole + fraction.padEnd(4, '0'));
}
export function decimalCompare(a, b) {
  const left = decimalUnits(a), right = decimalUnits(b);
  return left < right ? -1 : left > right ? 1 : 0;
}
export function decimalDifference(a, b) {
  const value = decimalUnits(a) - decimalUnits(b);
  return `${value < 0n ? '-' : ''}${(value < 0n ? -value : value) / 10000n}.${String((value < 0n ? -value : value) % 10000n).padStart(4, '0')}`;
}
const lengths = { slug: 100, code: 40, sku: 100, barcode: 100, symbology: 30, value: 160,
  phone: 40, address: 2000, notes: 5000, description: 5000, reason: 1000, reference: 150,
  receipt_footer: 1000, tax_registration: 100, body_template: 2000, jurisdiction_code: 80, tax_jurisdiction: 80 };
export function rulesFor(name = '') {
  const key = name.replace(/^variant_/, '');
  if (['default_cost', 'default_price', 'unit_price'].includes(key)) return { decimal: true, digits: 15, min: '0' };
  if (key === 'min_qty') return { decimal: true, digits: 14, positive: true };
  if (['amount', 'opening_cash', 'counted_cash', 'discount_amount', 'low_stock_threshold', 'unit_cost'].includes(key)) return { decimal: true, positive: key === 'amount', min: '0' };
  if (/^(accepted|rejected|return|qty)-/.test(key)) return { decimal: true, min: '0' };
  if (['rate_percent', 'tax_rate'].includes(key)) return { decimal: true, min: '0', max: '100' };
  if (key === 'quantity_precision') return { integer: true, min: 0, max: 4 };
  if (key === 'sort_order') return { integer: true, min: -2147483648, max: 2147483647 };
  if (key === 'payment_terms_days') return { integer: true, min: 0, max: 365 };
  if (key === 'return_days') return { integer: true, min: 0, max: 3650 };
  if (key === 'minutes') return { integer: true, min: 1, max: 1440 };
  if (key === 'timezone') return { timezone: true, maxLength: 255 };
  if (key === 'email') return { email: true, maxLength: 255 };
  if (key === 'currency_code') return { pattern: '[A-Z]{3}', maxLength: 3, patternMessage: 'Use a three-letter uppercase currency code, for example PKR.' };
  if (/^password(?:_confirmation)?$/.test(key)) return { maxLength: 255 };
  return { maxLength: lengths[key] ?? 255, ...(key === 'body_template' ? { template: true } : {}) };
}
const isDate = value => /^\d{4}-\d{2}-\d{2}$/.test(value) && value.slice(0, 4) !== '0000' && !Number.isNaN(Date.parse(`${value}T00:00:00Z`)) && new Date(`${value}T00:00:00Z`).toISOString().slice(0, 10) === value;
export function validateValue(value, rules = {}, label = 'This field') {
  const text = String(value ?? '');
  if (rules.required && !text.trim()) return `${label} is required.`;
  if (!text.trim()) return null;
  if (rules.maxLength && [...text].length > Number(rules.maxLength)) return `${label} must be ${rules.maxLength} characters or fewer.`;
  if (rules.minLength && [...text].length < Number(rules.minLength)) return `${label} must be at least ${rules.minLength} characters.`;
  if (rules.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text.trim())) return 'Enter a valid email address.';
  if (rules.pattern && !new RegExp(`^(?:${rules.pattern})$`).test(text)) return rules.patternMessage || `Enter a valid ${label.toLowerCase()}.`;
  if (rules.integer && !/^-?\d+$/.test(text)) return `${label} must be a whole number.`;
  if (rules.decimal && !decimalPattern(rules.digits ?? 12, rules.signed).test(text)) return `${label} must use up to ${rules.digits ?? 12} whole-number digits and four decimal places${rules.signed ? '' : ', without a minus sign'}.`;
  if (rules.decimal && rules.scale !== undefined && /[1-9]/.test((text.split('.')[1] || '').slice(rules.scale))) return `${label} must match the currency precision (${rules.scale} decimal places).`;
  if (rules.decimal || rules.integer) {
    if (rules.positive && decimalCompare(text, '0') <= 0) return `${label} must be greater than zero.`;
    if (rules.nonzero && decimalCompare(text, '0') === 0) return `${label} cannot be zero.`;
    if (rules.min !== undefined && decimalCompare(text, String(rules.min)) < 0) return `${label} must be at least ${rules.min}.`;
    if (rules.max !== undefined && decimalCompare(text, String(rules.max)) > 0) return `${label} must be at most ${rules.max}.`;
  }
  if (rules.date && !isDate(text)) return 'Enter a valid date.';
  if (rules.timezone) {
    // Intl uses the browser's IANA time-zone database; Laravel verifies again.
    try { new Intl.DateTimeFormat('en', { timeZone: text }); } catch { return 'Enter a valid time zone, for example Asia/Karachi.'; }
    if (!text.includes('/') && text !== 'UTC') return 'Use an IANA time zone, for example Asia/Karachi or UTC.';
  }
  if (rules.template) {
    const allowed = ['sku', 'quantity', 'invoice_no', 'business_name', 'reference'];
    if ([...text.matchAll(/{{\s*([^}]+?)\s*}}/g)].some(match => !allowed.includes(match[1].trim()))) return 'Use only sku, quantity, invoice_no, business_name or reference inside {{ }}.';
  }
  return null;
}
export function controlRules(control, overrides = {}) {
  const rules = { ...rulesFor(control.name), required: control.required, ...overrides };
  for (const attribute of ['minLength', 'maxLength', 'min', 'max', 'pattern']) {
    const value = control.getAttribute(attribute.toLowerCase());
    if (value !== null && value !== '') rules[attribute] = value;
  }
  if (control.type === 'number') rules.integer = true;
  if (control.type === 'email') rules.email = true;
  if (control.type === 'date') rules.date = true;
  if (control.dataset.decimal !== undefined) rules.decimal = true;
  for (const key of ['signed', 'positive', 'nonzero', 'template']) if (control.dataset[key] !== undefined) rules[key] = control.dataset[key] === 'true';
  return rules;
}
export function validateControl(control, overrides) {
  if (!control.name || control.disabled || ['submit', 'button', 'hidden'].includes(control.type)) return null;
  const label = control.dataset.label || control.getAttribute('aria-label') || control.labels?.[0]?.textContent.replace(/\s+/g, ' ').trim() || control.name.replaceAll('_', ' ');
  const rules = controlRules(control, overrides);
  if (['checkbox', 'radio'].includes(control.type)) return control.required && !control.checked ? `Please confirm ${label.toLowerCase()}.` : null;
  if (control.type === 'file') {
    const files = [...control.files];
    if (!files.length) return rules.required ? `${label} is required.` : null;
    for (const file of files) {
      if (!file.size) return 'Choose a non-empty file.';
      if (file.size > (rules.maxBytes ?? 2 * 1024 * 1024)) return 'The file must be 2 MB or smaller.';
      if (control.accept && !control.accept.split(',').some(raw => {
        const accept = raw.trim().toLowerCase();
        return accept.startsWith('.') ? file.name.toLowerCase().endsWith(accept) : accept.endsWith('/*') ? file.type.startsWith(accept.slice(0, -1)) : file.type === accept;
      })) return 'Choose a file in the supported format.';
    }
    return null;
  }
  const error = validateValue(control.value, rules, label);
  if (error) return error;
  if (control.tagName === 'SELECT' && control.value && ![...control.options].some(option => option.value === control.value && !option.disabled)) return `Choose a valid ${label.toLowerCase()}.`;
  return null;
}
export function validateForm(form, rules = {}, validate) {
  const errors = {};
  for (const control of form.elements) {
    const error = validateControl(control, rules[control.name]);
    if (error) errors[control.name] = error;
  }
  const values = new FormData(form);
  if (values.has('password_confirmation') && values.get('password') !== values.get('password_confirmation')) errors.password_confirmation = 'The password confirmation does not match.';
  if (values.has('code') && String(values.get('code')).startsWith('custom-')) {
    const permissions = values.getAll('permissions');
    const dependencies = { 'products.manage': 'products.view', 'team.manage': 'team.view', 'settings.manage': 'products.view', 'legacy.manage': 'legacy.view', 'inventory.adjust': 'inventory.view', 'inventory.approve': 'inventory.view', 'inventory.reserve': 'inventory.view', 'purchases.manage': 'purchases.view', 'purchases.approve': 'purchases.view', 'purchases.settle': 'purchases.view', 'customers.manage': 'customers.view', 'sales.create': 'sales.view', 'sales.discount': 'sales.create', 'payments.record': 'sales.view', 'cash.manage': 'cash.open' };
    for (const permission of permissions) if (dependencies[permission] && !permissions.includes(dependencies[permission])) { errors.permissions = `${permission} also requires ${dependencies[permission]}.`; break; }
  }
  for (const [from, to] of [['valid_from', 'valid_to'], ['from', 'to']]) {
    if (values.get(from) && values.get(to) && !errors[from] && !errors[to]) {
      if (values.get(to) < values.get(from)) errors[to] = 'The end date must be on or after the start date.';
      else if (from === 'from' && (Date.parse(values.get(to)) - Date.parse(values.get(from))) / 86400000 > 366) errors[to] = 'Choose a report period of 366 days or fewer.';
    }
  }
  return { ...errors, ...validate?.(values, errors) };
}
export function validationFailure(errors) {
  return { response: { status: 422, data: { message: 'Please correct the highlighted fields.', errors } } };
}
export function paymentErrors(payments, methods, { required = false, due, fullPayment = false, scale } = {}) {
  const errors = {};
  if (required && !payments.length) errors.payments = 'Add at least one payment.';
  if (payments.length > 10) errors.payments = 'Use at most 10 payment methods.';
  const seen = new Set(); let total = 0n;
  payments.forEach((payment, index) => {
    const method = methods.find(item => String(item.id) === String(payment.method_id) && item.active);
    if (!method) errors[`payments.${index}.method_id`] = 'Choose an active payment method.';
    else if (seen.has(String(method.id))) errors[`payments.${index}.method_id`] = 'Combine amounts for the same payment method.';
    seen.add(String(payment.method_id));
    const error = validateValue(payment.amount, { required: true, decimal: true, positive: true, scale }, 'Payment amount');
    if (error) errors[`payments.${index}.amount`] = error; else total += decimalUnits(payment.amount);
    if (method && method.method_type !== 'cash' && !payment.reference?.trim()) errors[`payments.${index}.reference`] = 'An external payment reference is required.';
    else if ((payment.reference || '').length > 150) errors[`payments.${index}.reference`] = 'The external reference must be 150 characters or fewer.';
  });
  if (due !== undefined && !Object.keys(errors).length) {
    if (total > decimalUnits(due)) errors.payments = 'Payments exceed the amount due. Enter cash applied after change.';
    else if (fullPayment && total < decimalUnits(due)) errors.payments = 'Full payment is required for this sale.';
  }
  return errors;
}
export function requireLines(lines, { exactlyOne = false } = {}) {
  if (exactlyOne && lines.length !== 1) return { items: 'Choose exactly one product variant.' };
  if (!lines.length || lines.length > 100) return { items: 'Choose between 1 and 100 product variants.' };
  return {};
}
export function selectedQuantityErrors(form, items, { prefix, maximum, secondPrefix } = {}) {
  const errors = {}; let selected = 0;
  items.forEach(item => {
    const name = `${prefix}-${item.id}`, value = form.get(name) || '0', other = secondPrefix ? form.get(`${secondPrefix}-${item.id}`) || '0' : '0';
    if (!decimalPattern().test(value) || !decimalPattern().test(other)) return;
    const quantity = decimalUnits(value) + decimalUnits(other);
    if (quantity > 0n) selected++;
    const max = maximum(item);
    if (quantity > decimalUnits(max)) errors[name] = `The quantity exceeds the remaining amount (${max}).`;
  });
  if (!selected) errors.items = 'Enter a quantity greater than zero for at least one line.';
  return errors;
}
export function externalReferenceErrors(form, methods) {
  const method = methods.find(item => String(item.id) === form.get('method_id'));
  return method && method.method_type !== 'cash' && !String(form.get('reference') || '').trim() ? { reference: 'An external payment reference is required.' } : {};
}
export function expenseErrors(form, methods) {
  const errors = externalReferenceErrors(form, methods);
  if (methods.find(item => String(item.id) === form.get('method_id'))?.method_type === 'cash' && !form.get('cash_session_id')) errors.cash_session_id = 'Choose an open register shift for a cash expense.';
  return errors;
}
