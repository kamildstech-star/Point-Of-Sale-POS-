import { iconForAction } from '../icons/catalog';
import Icon from './Icon';
import { cloneElement, createContext, useContext, useEffect, useId, useRef, useState } from 'react';
import { errorMessage } from '../api/client';
import { useModalSubmission } from './Modal';
import { currencyPrecision, validateForm } from '../validation/forms';
import { useAuth } from '../auth/AuthProvider';
import { dismissValidationNotification, notify } from '../notifications';
import { TextInput, SelectInput } from './ui/primitives';

const ValidationContext = createContext({ errors: {} });
export function ValidationScope({ errors, clearField, children }) {
  return <ValidationContext.Provider value={{ errors, clearField }}>{children}</ValidationContext.Provider>;
}

export function FormSection({ title, description, children }) {
  const id = useId();
  return <fieldset className="form-section" aria-describedby={description ? id : undefined}><legend>{title}</legend>
    {description && <p id={id} className="form-section-description">{description}</p>}{children}</fieldset>;
}

// Native disclosure keeps controls mounted, including their values and FormData.
// A server or client error reveals every affected optional section.
export function FormDetails({ title = 'Additional details', description, children }) {
  const { errors } = useContext(ValidationContext);
  const details = useRef(null);
  useEffect(() => {
    if ([...details.current.querySelectorAll('[name]')].some(control => errors[control.name])) details.current.open = true;
  }, [errors]);
  return <details ref={details} className="form-details"><summary>{title}<span>Optional</span></summary>
    {description && <p className="form-section-description">{description}</p>}<div>{children}</div></details>;
}

// Also used around controlled inputs, selects, textareas and confirmations.
export function ValidatedControl({ children }) {
  const { errors, clearField } = useContext(ValidationContext);
  const generated = useId();
  const { name, type, required } = children.props;
  const inline = name && (!['checkbox', 'radio'].includes(type) || required);
  const error = inline && errors[name];
  const id = children.props.id || `field-${generated}`;
  const errorId = `${id}-error`;
  return <>{cloneElement(children, { id, 'data-validation-control': inline ? name : undefined,
    onChange: event => { clearField?.(name); children.props.onChange?.(event); },
    'aria-invalid': error ? 'true' : undefined,
    'aria-describedby': [children.props['aria-describedby'], error && errorId].filter(Boolean).join(' ') || undefined,
  })}{error && <small className="field-error" id={errorId}>{error}</small>}</>;
}

export function ErrorNotice({ error, onRetry }) {
  useEffect(() => { if (error) notify.error(error); }, [error]);
  if (!error) return null;
  const fields = error.response?.data?.errors;
  const message = errorMessage(error);
  const details = [...new Set(Object.values(fields || {}).flat())].filter(detail => detail !== message);
  return <div role="alert" className="error">{message}{details.length > 0 && <ul>{details.map(detail => <li key={detail}>{detail}</li>)}</ul>}{onRetry && <button className="secondary" type="button" onClick={onRetry}><Icon name="refresh" />Try again</button>}</div>;
}

export function SaveForm({ children, onSave, label = 'Save changes', onCancel, readOnly = false, rules = {}, validate, ...props }) {
  const [error, setError] = useState(null);
  const [errors, setErrors] = useState({});
  const [busy, setBusy] = useState(false);
  const formRef = useRef(null);
  const summaryRef = useRef(null);
  const focusError = useRef(false);
  const serverFields = useRef(new Set());
  const inFlight = useRef(false);
  const modal = useModalSubmission();
  const currency = useAuth()?.user?.active_tenant?.currency_code;
  const scale = currencyPrecision(currency);
  function fieldRules(name) {
    return { ...(/^(amount|opening_cash|counted_cash)$/.test(name) || /^payments\.\d+\.amount$/.test(name) ? { scale } : {}), ...rules[name] };
  }
  function formRules() {
    return Object.fromEntries([...formRef.current.elements].map(control => [control.name, fieldRules(control.name)]));
  }
  const cancel = onCancel || modal?.close;
  async function submit(event) {
    event.preventDefault();
    if (readOnly || inFlight.current || modal?.busy) return;
    const invalid = validateForm(event.currentTarget, formRules(), validate);
    serverFields.current.clear();
    setError(null); setErrors(invalid);
    if (Object.keys(invalid).length) { notify.validation(); focusError.current = true; return; }
    dismissValidationNotification();
    const values = new FormData(event.currentTarget);
    inFlight.current = true; setError(null); setBusy(true); modal?.setBusy(true);
    try { await onSave(values); modal?.onSuccess(); }
    catch (failure) {
      notify.error(failure);
      const fields = failure.response?.status === 422 && failure.response?.data?.errors;
      if (fields && Object.keys(fields).length) {
        // Forms use the API's dotted paths for nested inputs. Product creation
        // has a flat variant_ prefix, unlike standalone variant editing.
        const names = new Set([...formRef.current.elements].map(control => control.name));
        const mapped = Object.fromEntries(Object.entries(fields).map(([key, messages]) => {
          let name = key;
          const aliases = { confirm_locations: 'confirm', tax_jurisdiction: 'jurisdiction', quantity: 'items.0.quantity' };
          if (!names.has(name) && names.has(aliases[key])) name = aliases[key];
          if (!names.has(name) && names.has(`variant_${key.replace(/^variant\./, '')}`)) name = `variant_${key.replace(/^variant\./, '')}`;
          return [name, Array.isArray(messages) ? messages[0] : messages];
        }));
        serverFields.current = new Set(Object.keys(mapped));
        setErrors(mapped);
      } else setError(failure);
      focusError.current = true;
    }
    finally { inFlight.current = false; setBusy(false); modal?.setBusy(false); }
  }
  useEffect(() => {
    if (!focusError.current || busy) return;
    focusError.current = false;
    const invalid = [...formRef.current.elements].find(control => errors[control.name] && control.type !== 'hidden');
    // Reveal nested disclosures before moving keyboard focus to an invalid field.
    for (let parent = invalid?.parentElement; parent && parent !== formRef.current; parent = parent.parentElement) {
      if (parent.tagName === 'DETAILS') parent.open = true;
    }
    (invalid || summaryRef.current)?.focus();
  }, [errors, error, busy]);
  function blur(event) {
    const control = event.target;
    // A pointer press focuses the button before its click. Rendering an error
    // here could move that button before pointer release. Submit validates all
    // fields; other buttons do not need to trigger blur feedback.
    if (!control.name || event.relatedTarget?.closest?.('button') || serverFields.current.has(control.name)) return;
    const message = validateForm(formRef.current, formRules(), validate)[control.name];
    setErrors(old => { const next = { ...old }; if (message) next[control.name] = message; else delete next[control.name]; return next; });
  }
  function change(event) {
    const name = event.target.name;
    if (!name) return;
    serverFields.current.delete(name);
    setErrors(old => { const next = { ...old }; delete next[name]; return next; });
    setError(null);
  }
  const unplaced = Object.entries(errors).filter(([name]) => !formRef.current?.querySelectorAll('[data-validation-control]').length ||
    ![...formRef.current.querySelectorAll('[data-validation-control]')].some(control => control.dataset.validationControl === name));
  return <ValidationContext.Provider value={{ errors }}><form {...props} ref={formRef} noValidate onSubmit={submit} onBlur={blur} onChange={change} aria-busy={busy}>
    <fieldset disabled={busy || modal?.busy}>
      {(error || Object.keys(errors).length > 0) && <div ref={summaryRef} tabIndex={-1} className="validation-summary">
        {Object.keys(errors).length > 0 && <div role="alert" className="error">Please correct the highlighted fields.{unplaced.length > 0 && <ul>{[...new Set(unplaced.map(([, message]) => message))].map(message => <li key={message}>{message}</li>)}</ul>}</div>}
        <ErrorNotice error={error}/>
      </div>}
      {children}
      {!readOnly && <div className="form-actions"><button type="submit">{busy ? <Icon name="loader" spin /> : <Icon name={iconForAction(label)} />}{busy ? 'Saving…' : label}</button>{cancel && <button type="button" className="secondary" onClick={cancel}><Icon name="close" />Cancel</button>}</div>}
    </fieldset></form></ValidationContext.Provider>;
}

export function Input({ name, label, value = '', type = 'text', required = true, ...props }) {
  return <label>{label}<ValidatedControl><TextInput aria-label={label} data-label={label} name={name} defaultValue={value ?? ''} type={type} required={required} {...props}/></ValidatedControl></label>;
}

export function Select({ name, label, value = '', options, required = true }) {
  return <label>{label}<ValidatedControl><SelectInput aria-label={label} data-label={label} name={name} defaultValue={value ?? ''} required={required}><option value="">Choose {label.toLowerCase()}</option>{options.map(option => <option key={option.id ?? option} value={option.id ?? option}>{option.name ?? option.sku ?? option.value ?? option}</option>)}</SelectInput></ValidatedControl></label>;
}
