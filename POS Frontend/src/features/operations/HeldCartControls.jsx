import { useRef, useState } from 'react';
import Modal from '../../components/Modal';
import Icon from '../../components/Icon';
import { ErrorNotice, Input, SaveForm } from '../../components/WorkspaceForm';
import { requireLines, validateValue, validationFailure } from '../../validation/forms';
import { workspaceApi } from '../../api/workspace';
import { requestKey } from './OperationUI';
import HeldCartsDialog from './HeldCartsDialog';

export default function HeldCartControls({ cart, session, customer, priceList, discount, held, disabled, branchId, timezone, initialRecall, exchange,
  onLocked, onHeld, onRecall, onClear }) {
  const [form, setForm] = useState(null), [recallOpen, setRecallOpen] = useState(initialRecall), [clearOpen, setClearOpen] = useState(false);
  const [unconfirmed, setUnconfirmed] = useState(false), [error, setError] = useState(null);
  const attempt = useRef(null);
  function startHold() { setForm({ key: attempt.current?.idempotency_key || requestKey(), label: attempt.current?.label || held?.label || '' }); }
  function validate() {
    const errors = requireLines(cart);
    if (!session) errors.cash_session_id = 'Choose an open shift before holding the cart.';
    cart.forEach((item, index) => { const message = validateValue(item.qty, { required: true, decimal: true, positive: true }, 'Quantity'); if (message) errors[`items.${index}.qty`] = message; });
    const message = validateValue(discount, { required: true, decimal: true, min: '0' }, 'Discount'); if (message) errors.discount_amount = message;
    return errors;
  }
  async function save(fields) {
    const errors = validate(); if (Object.keys(errors).length) throw validationFailure(errors);
    if (!attempt.current) attempt.current = { idempotency_key: form.key, label: fields.get('label') || null, cash_session_id: Number(session), customer_id: customer ? Number(customer) : null,
      price_list_id: priceList ? Number(priceList) : null, discount_amount: discount, items: cart.map(item => ({ variant_id: item.variant_id, qty: item.qty })),
      ...(held ? { held_cart_id: held.id, held_cart_version: held.version } : {}) };
    onLocked(true); setError(null);
    try {
      await workspaceApi.save('pos/held-carts', attempt.current);
      attempt.current = null; setUnconfirmed(false); onLocked(false); setForm(null); onHeld();
    } catch (failure) {
      if ([401, 403, 409, 419, 422].includes(failure.response?.status)) { attempt.current = null; setUnconfirmed(false); onLocked(false); }
      else setUnconfirmed(true);
      setError(failure); throw failure;
    }
  }
  return <section className="held-cart-controls" aria-label="Saved cart actions">
    {held && <p className="card">Recalled {held.label || `cart #${held.id}`} · original branch #{held.branch_id}. Request a fresh quote before checkout. An open shift in that branch is required.</p>}
    {!form && <ErrorNotice error={error}/>}
    {unconfirmed && <p className="error" role="status">The hold result is unconfirmed. Retry uses exactly the same request key and cart. Checkout stays locked until it is confirmed.</p>}
    <div className="form-actions"><button className="secondary" disabled={disabled || exchange || (!cart.length && !unconfirmed)} onClick={startHold}><Icon name="pause"/>{unconfirmed ? 'Retry same hold' : 'Hold cart'}</button>
      <button className="secondary" disabled={disabled || unconfirmed || exchange} onClick={() => setRecallOpen(true)}><Icon name="play"/>Recall held cart</button>
      {cart.length > 0 && <button className="secondary" disabled={disabled || unconfirmed} onClick={() => setClearOpen(true)}><Icon name="delete"/>Clear current cart</button>}</div>
    {exchange && <p className="muted">Complete or leave the exchange before using held carts. Exchange credits are never saved in a held cart.</p>}
    {form && <Modal title={unconfirmed ? 'Retry unconfirmed hold' : 'Hold current cart'} onClose={() => setForm(null)}><p>This saves items, quantities and selections. Prices and stock are checked again before sale; payments and exchange credits are not saved.</p>
      <SaveForm key={form.key} onSave={save} validate={validate} label={unconfirmed ? 'Retry same hold' : 'Save held cart'}><Input name="label" label="Cart label (optional)" value={form.label} required={false} maxLength={80} readOnly={unconfirmed}/></SaveForm>
    </Modal>}
    {recallOpen && <HeldCartsDialog branchId={branchId} timezone={timezone} canRecall={!cart.length && !disabled && !unconfirmed && !exchange} onRecall={onRecall} onClose={() => setRecallOpen(false)}/>}
    {clearOpen && <Modal title="Clear current cart?" onClose={() => setClearOpen(false)}><p>This clears the unsold editor. A recalled cart stays in your held list until sold or explicitly discarded.</p><div className="form-actions"><button onClick={() => { onClear(); setClearOpen(false); }}><Icon name="delete"/>Clear current cart</button><button className="secondary" onClick={() => setClearOpen(false)}>Keep current cart</button></div></Modal>}
  </section>;
}
