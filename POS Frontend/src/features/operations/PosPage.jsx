import Icon from '../../components/Icon';
import LoadingState from '../../components/LoadingState';
import { useEffect, useRef, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import LookupSelect from '../../components/LookupSelect';
import { ErrorNotice, ValidatedControl, ValidationScope } from '../../components/WorkspaceForm';
import { currencyPrecision, paymentErrors, requireLines, validateValue, validationFailure } from '../../validation/forms';
import { PaymentFields, ProductFinder, requestKey, useResources } from './OperationUI';
import HeldCartControls from './HeldCartLoader';
import { destination } from '../dashboard/dashboardData';
import PosQuoteStatus from './PosQuoteStatus';
import { isQuoteExpired, usePosSearch, useQuoteExpiry, useVisiblePaymentPanel } from './posUsability';
export default function PosPage() {
  const [fieldErrors, setFieldErrors] = useState({});
  function clearField(name) {
    setFieldErrors(previous => {
      const next = {
        ...previous
      };
      delete next[name];
      delete next.payments;
      return next;
    });
    setError(null);
    setRejected(false);
  }
  function displayFailure(failure) {
    setError(failure);
    if (failure.response?.status === 422 && failure.response.data.errors) setFieldErrors(Object.fromEntries(Object.entries(failure.response.data.errors).map(([name, messages]) => [name, Array.isArray(messages) ? messages[0] : messages])));
  }
  const [searchParams, setSearchParams] = useSearchParams();
  const exchangeId = searchParams.get('exchange_return_id');
  const {
    user
  } = useAuth();
  const sessionPath = destination('cash/sessions', { own: searchParams.get('own') === '1' ? 1 : undefined, branch_id: searchParams.get('branch_id') });
  const refs = useResources([sessionPath, 'operations/payment-methods', 'operations/customers', 'catalog/price-lists', 'me/context']);
  const [cart, setCart] = useState([]);
  const [session, setSession] = useState(searchParams.get('cash_session_id') || '');
  const [customer, setCustomer] = useState('');
  const [priceList, setPriceList] = useState('');
  const [discount, setDiscount] = useState('0');
  const [quote, setQuote] = useState(null);
  const [payments, setPayments] = useState([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);
  const [receipt, setReceipt] = useState(null);
  const [operation, setOperation] = useState(null), [rejected, setRejected] = useState(false);
  const [pending, setPending] = useState(false);
  const [heldCart, setHeldCart] = useState(null), [heldBusy, setHeldBusy] = useState(false), [recallRevision, setRecallRevision] = useState(0);
  const heldAvailable = refs.data['me/context']?.data.active_tenant?.dashboard.held_sales;
  const searchRef = useRef(null), checkoutTitle = useRef(null), paymentPanel = useRef(null), outcomeRef = useRef(null);
  useVisiblePaymentPanel(paymentPanel);
  const locked = busy || pending || heldBusy;
  const focusSearch = usePosSearch({ searchRef, ready: !refs.loading && !refs.error, locked, initialRecall: searchParams.get('held') === '1' });
  const quoteExpired = useQuoteExpiry(quote?.expires_at);
  function clearCart() { setCart([]); setQuote(null); setPayments([]); setHeldCart(null); setCustomer(''); setPriceList(''); setDiscount('0'); setReceipt(null); setRejected(false); setError(null); setFieldErrors({}); setRecallRevision(value => value + 1); }
  function recallCart(held) {
    const sessions = refs.data[sessionPath]?.data || [];
    const eligible = item => String(item.branch_id) === String(held.branch_id);
    const chosen = sessions.find(item => String(item.id) === session && eligible(item)) || sessions.find(item => item.id === held.source_session_id && eligible(item)) || sessions.find(eligible);
    setHeldCart(held); setCart(held.cart.items.map(item => ({ variant_id: item.variant_id, qty: item.qty, name: `${item.name} · ${item.sku}` })));
    setSession(chosen ? String(chosen.id) : ''); setCustomer(held.cart.customer_id ? String(held.cart.customer_id) : ''); setPriceList(held.cart.price_list_id ? String(held.cart.price_list_id) : '');
    setDiscount(held.cart.discount_amount); setQuote(null); setPayments([]); setReceipt(null); setError(null); setFieldErrors({}); setRecallRevision(value => value + 1);
  }
  const attempt = useRef(null);
  const inFlight = useRef(false);
  const quoteGeneration = useRef(0);
  useEffect(() => {
    setQuote(null);
    setError(null);
    setRejected(false);
    quoteGeneration.current++;
  }, [cart, session, customer, priceList, discount, heldCart]);
  function add(row) {
    setCart(previous => {
      const found = previous.find(l => l.variant_id === row.id);
      if (!found) return [...previous, {
        variant_id: row.id,
        name: `${row.name} · ${row.sku}`,
        qty: '1'
      }];
      return previous.map(l => l.variant_id === row.id ? {
        ...l,
        qty: /^\d+$/.test(l.qty) ? String(BigInt(l.qty) + 1n) : l.qty
      } : l);
    });
    setReceipt(null);
  }
  async function getQuote() {
    if (inFlight.current || heldBusy || pending) return;
    const errors = requireLines(cart);
    if (!session) errors.cash_session_id = 'Choose an open register shift.';
    const discountError = validateValue(discount, {
      required: true,
      decimal: true,
      min: '0'
    }, 'Order discount');
    if (discountError) errors.discount_amount = discountError;
    cart.forEach((line, index) => {
      const message = validateValue(line.qty, {
        required: true,
        decimal: true,
        positive: true
      }, 'Quantity');
      if (message) errors[`items.${index}.qty`] = message;
    });
    setFieldErrors(errors);
    if (Object.keys(errors).length) {
      setError(validationFailure(errors));
      return;
    }
    inFlight.current = true;
    setOperation('quote'); setRejected(false);
    setBusy(true);
    setError(null);
    const generation = ++quoteGeneration.current;
    try {
      const result = await workspaceApi.save('pos/quotes', {
        ...(heldCart ? { held_cart_id: heldCart.id, held_cart_version: heldCart.version } : {}),
        ...(exchangeId ? {
          exchange_return_id: Number(exchangeId)
        } : {}),
        cash_session_id: Number(session),
        customer_id: customer ? Number(customer) : null,
        price_list_id: priceList ? Number(priceList) : null,
        discount_amount: discount,
        items: cart.map(({
          variant_id,
          qty
        }) => ({
          variant_id,
          qty
        }))
      });
      if (generation === quoteGeneration.current) {
        setQuote(result.data);
        const cash = refs.data['operations/payment-methods']?.data.find(m => m.method_type === 'cash' && m.active);
        setPayments(cash && /[1-9]/.test(result.data.quote.amount_to_collect ?? result.data.quote.grand_total) ? [{
          method_id: String(cash.id),
          amount: result.data.quote.amount_to_collect ?? result.data.quote.grand_total,
          reference: ''
        }] : []);
      }
    } catch (failure) {
      displayFailure(failure);
    } finally {
      inFlight.current = false;
      setBusy(false);
      setOperation(null);
    }
  }
  async function checkout() {
    if (inFlight.current || heldBusy || !quote && !attempt.current) return;
    // An uncertain submission must always resolve with its original request,
    // even if that original quote expires while the response is unknown.
    if (!attempt.current && isQuoteExpired(quote?.expires_at)) return;
    if (!attempt.current) {
      const errors = paymentErrors(payments, refs.data['operations/payment-methods']?.data || [], {
        due: quote.quote.amount_to_collect ?? quote.quote.grand_total,
        fullPayment: !customer || quote.quote.allow_credit === false,
        scale: quote.quote.currency_scale ?? currencyPrecision(user.active_tenant.currency_code)
      });
      setFieldErrors(errors);
      if (Object.keys(errors).length) {
        setError(validationFailure(errors));
        return;
      }
    }
    inFlight.current = true;
    setOperation('checkout'); setRejected(false);
    setBusy(true);
    setError(null);
    if (!attempt.current) attempt.current = {
      idempotency_key: requestKey(),
      quote_id: quote.id,
      payments: payments.map(p => ({
        ...p,
        method_id: Number(p.method_id)
      }))
    };
    setPending(true);
    try {
      const result = await workspaceApi.save(exchangeId ? 'returns/' + exchangeId + '/exchange' : 'pos/checkout', attempt.current);
      setReceipt(result.data);
      setCart([]);
      setHeldCart(null);
      setQuote(null);
      setPayments([]);
      attempt.current = null;
      setPending(false);
    } catch (failure) {
      displayFailure(failure);
      if ([401, 403, 409, 419, 422].includes(failure.response?.status)) {
        attempt.current = null;
        setPending(false);
        setRejected(true);
        if (failure.response.status === 409) setQuote(null);
      }
    } finally {
      inFlight.current = false;
      setBusy(false);
      setOperation(null);
    }
  }
  const q = quote?.quote;
  const methods = refs.data['operations/payment-methods']?.data || [];
  const selectedSession = refs.data[sessionPath]?.data.find(item => String(item.id) === session);
  const timezone = user.active_tenant.timezone || 'UTC';
  function reviewPayment() {
    checkoutTitle.current?.focus({ preventScroll: true });
    checkoutTitle.current?.scrollIntoView?.({ block: 'start' });
  }
  function startNewSale() {
    if (locked) return;
    const next = new URLSearchParams(searchParams); next.delete('exchange_return_id'); next.delete('held');
    if (next.toString() !== searchParams.toString()) setSearchParams(next, { replace: true });
    clearCart(); requestAnimationFrame(focusSearch);
  }
  return <ValidationScope errors={fieldErrors} clearField={clearField}>
    <main className="content pos-page">
      <div className="page-heading"><div><p className="eyebrow">SELLING DESK</p><h1 tabIndex={-1}>Point of sale</h1>
        <p className="pos-context">{selectedSession ? (selectedSession.branch_name ? selectedSession.branch_name + ' · ' : '') + (selectedSession.register_name || 'Register ' + selectedSession.register_id) + ' · Shift #' + selectedSession.id : 'Choose an open register shift to sell.'}</p>
      </div><Link to="/cash">Register shifts</Link></div>
      <LoadingState busy={Boolean(refs.loading)} label="Loading register and payment options…" variant="inline" />
      <ErrorNotice error={error || refs.error} />
      {receipt && <div className="pos-transaction-status is-completed" role="status"><div><strong><Icon name="check" />Sale completed: {receipt.invoice_no}.</strong><p>The sale is confirmed. Open the receipt or start a new sale.</p></div><div className="pos-status-actions"><Link to={'/sales/' + receipt.id}>Open printable receipt</Link><button type="button" className="secondary" onClick={startNewSale}><Icon name="add" />Start new sale</button></div></div>}
      {pending && <div className="pos-transaction-status is-unconfirmed" tabIndex={-1} ref={outcomeRef} role={busy ? 'status' : 'alert'}><div><strong><Icon name={busy ? 'loader' : 'time'} spin={busy} />{busy ? 'Confirming sale…' : 'The checkout result is unconfirmed.'}</strong><p>{busy ? 'Keep this sale open while confirmation is received.' : 'Retry sends the same request key and payment details. The editor stays locked until the result is confirmed.'}</p></div>{!busy && <button type="button" disabled={heldBusy} onClick={checkout}><Icon name="refresh" />Retry same checkout</button>}</div>}
      {rejected && <div className="pos-transaction-status is-rejected" role="status"><strong>Checkout needs attention</strong><p>The checkout was rejected. Review the error and correct the sale before trying again.</p></div>}
      {heldAvailable && <HeldCartControls cart={cart} session={session} customer={customer} priceList={priceList} discount={discount} held={heldCart} disabled={busy || pending} branchId={searchParams.get('branch_id')} timezone={timezone} initialRecall={searchParams.get('held') === '1'} exchange={Boolean(exchangeId)} onLocked={setHeldBusy} onHeld={clearCart} onRecall={recallCart} onClear={clearCart} />}
      <div className="pos-layout">
        <section className="card pos-products" aria-label="Products and cart">
          <fieldset disabled={locked}>
            <label>Open register shift<ValidatedControl><select value={session} onChange={event => setSession(event.target.value)} aria-label="Open register shift" name="cash_session_id"><option value="">Choose a shift</option>{refs.data[sessionPath]?.data.map(item => <option key={item.id} value={item.id}>Shift #{item.id} · register {item.register_id}</option>)}</select></ValidatedControl></label>
            <div className="pos-search-actions"><h2>Find products</h2><button type="button" className="secondary" disabled={locked} aria-keyshortcuts="F2" onClick={focusSearch}><Icon name="search" />Focus product search <kbd>F2</kbd></button></div>
            <ProductFinder key={'products:' + recallRevision} inputRef={searchRef} onAdd={add} disabled={locked} hint="Scan an exact SKU or barcode, then press Enter. Select a result when searching by name." />
            <div className="pos-cart-heading"><h2>Cart</h2><span>{cart.length} {cart.length === 1 ? 'product line' : 'product lines'}</span></div>
            {cart.length === 0 && <div className="pos-cart-empty"><Icon name="sales" /><p>Scan or select a product to begin.</p></div>}
            <div className="pos-cart-lines">{cart.map((line, index) => <div className="operation-line" key={line.variant_id}>
              <strong>{line.name}</strong><label>Quantity<ValidatedControl><input inputMode="decimal" aria-label={'Quantity for ' + line.name} value={line.qty} onChange={event => setCart(cart.map(item => item.variant_id === line.variant_id ? { ...item, qty: event.target.value } : item))} name={'items.' + index + '.qty'} /></ValidatedControl></label>
              <button type="button" className="secondary" onClick={() => setCart(cart.filter(item => item.variant_id !== line.variant_id))}><Icon name="delete" />Remove</button>
            </div>)}</div>
          </fieldset>
        </section>
        <section className="card checkout-panel" ref={paymentPanel} aria-labelledby="pos-checkout-title">
          <h2 id="pos-checkout-title" tabIndex={-1} ref={checkoutTitle}>Payment summary</h2>
          <fieldset disabled={locked}>
            <LookupSelect key={'customer:' + recallRevision} value={customer} name="customer_id" label="Customer (blank for walk-in)" endpoint="operations/customers" options={refs.data['operations/customers']?.data || []} required={false} onChange={setCustomer} />
            <LookupSelect key={'prices:' + recallRevision} value={priceList} name="price_list_id" label="Price list (blank for catalog price)" endpoint="catalog/price-lists" options={refs.data['catalog/price-lists']?.data || []} required={false} onChange={setPriceList} />
            {user.permissions.includes('sales.discount') && <label>Fixed order discount<ValidatedControl><input inputMode="decimal" value={discount} onChange={event => setDiscount(event.target.value)} aria-label="Fixed order discount" name="discount_amount" /></ValidatedControl></label>}
            <button type="button" className="pos-quote-action" onClick={getQuote} disabled={refs.loading}><Icon name="quote" />{operation === 'quote' ? 'Calculating…' : 'Get server quote'}</button>
          </fieldset>
          {q ? <>
            {!pending && <PosQuoteStatus quote={quote} expired={quoteExpired} locked={locked || refs.loading} timezone={timezone} onRefresh={getQuote} />}
            <div className="quote-totals" aria-label="Server quote">
              <p>Subtotal <strong>{q.currency_code} {q.subtotal}</strong></p><p>Discount <strong>{q.discount_total}</strong></p><p>Tax <strong>{q.tax_total}</strong></p>
              <p className="grand-total">Total <strong>{q.currency_code} {q.grand_total}</strong></p>
              <p>Exchange credit <strong>{q.exchange_credit_amount || '0'}</strong></p><p>Amount to collect <strong>{q.currency_code} {q.amount_to_collect ?? q.grand_total}</strong></p>
            </div>
            <p className="pos-payment-policy">Walk-ins require full payment. Named customer credit depends on branch settings.</p>
            <PaymentFields methods={methods} payments={payments} setPayments={setPayments} disabled={locked || quoteExpired} />
            <div className="pos-checkout-actions"><div className="pos-footer-total"><span>{quoteExpired && !pending ? 'Last quoted amount' : 'Amount to collect'}</span><strong>{q.currency_code} {q.amount_to_collect ?? q.grand_total}</strong></div><button type="button" className="checkout-button" disabled={locked || quoteExpired} onClick={checkout}><Icon name={operation === 'checkout' ? 'loader' : 'sales'} spin={operation === 'checkout'} />{operation === 'checkout' ? 'Posting sale…' : 'Complete sale'}</button></div>
          </> : <div className="pos-quote-empty"><strong>Quote required</strong><p>Add items and choose a shift, then request the current prices and tax before payment.</p></div>}
        </section>
      </div>
      <div className="pos-mobile-summary" aria-label="Sale summary">
        <div><span>{receipt ? 'Sale completed' : pending ? busy ? 'Confirming sale…' : 'Checkout unconfirmed' : q ? quoteExpired ? 'Quote expired' : 'Amount to collect' : cart.length + ' product lines · Quote required'}</span>
          {q && <strong>{q.currency_code} {q.amount_to_collect ?? q.grand_total}</strong>}{receipt && <strong>{receipt.invoice_no}</strong>}</div>
        {receipt ? <Link className="button-link" to={'/sales/' + receipt.id}>View receipt</Link> : <button type="button" className="secondary" onClick={pending ? () => { outcomeRef.current?.focus(); outcomeRef.current?.scrollIntoView?.({ block: 'start' }); } : reviewPayment}>{pending ? 'Review checkout' : 'Review payment'}</button>}
      </div>
    </main>
  </ValidationScope>;
}
