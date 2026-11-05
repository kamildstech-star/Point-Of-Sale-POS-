import TableViewport from '../../components/ui/TableViewport';
import Icon from '../../components/Icon';
import LoadingState from '../../components/LoadingState';
import { paymentErrors } from "../../validation/forms";
import { FormDialog } from "../../components/Modal";
import { useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import DashboardDrilldown, { drilldownQuery } from './DashboardDrilldown';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, SaveForm, ValidatedControl } from '../../components/WorkspaceForm';
import { PaymentFields, RemoteTable, requestKey, useResources } from './OperationUI';
export default function SalesPage() {
  const {
    saleId
  } = useParams();
  const navigate = useNavigate();
  const [search] = useSearchParams();
  const filters = drilldownQuery(search, ['from', 'to', 'branch_id', 'cashier_membership_id', 'own', 'cash_session_id']);
  const receiptPath = `sales/${saleId}${search.get('own') === '1' ? '?own=1' : ''}`;
  const {
    user
  } = useAuth();
  const [revision, setRevision] = useState(0);
  const [payments, setPayments] = useState([]);
  const [key, setKey] = useState(requestKey);
  const [dueOnly, setDueOnly] = useState(false);
  const canPay = user.permissions.includes('payments.record');
  const refs = useResources(saleId ? [receiptPath, ...(canPay ? ['cash/sessions', 'operations/payment-methods'] : [])] : [], revision);
  const sale = refs.data[receiptPath]?.data;
  async function pay(form) {
    await workspaceApi.save(`sales/${saleId}/payments`, {
      idempotency_key: key,
      cash_session_id: Number(form.get('cash_session_id')),
      payments: payments.map(p => ({
        ...p,
        method_id: Number(p.method_id)
      }))
    });
    setPayments([]);
    setKey(requestKey());
    setRevision(r => r + 1);
  }
  if (!saleId) return <main className="content"><p className="eyebrow">SALES & COLLECTIONS</p><h1>Sales</h1><label className="checkbox"><input type="checkbox" checked={dueOnly} onChange={e => setDueOnly(e.target.checked)} aria-label={"Show balances due only"} />Show balances due only</label><DashboardDrilldown query={filters} clearTo="/sales"/><RemoteTable label="Sales" key={`${dueOnly}:${filters}`} path={`sales?due=${dueOnly ? 1 : 0}${filters ? `&${filters}` : ''}`} columns={[["invoice_no", "Invoice"], ["sold_at", "Sold at"], ["customer_id", "Customer"], ["grand_total", "Total"], ["paid_total", "Paid"], ["payment_status", "Payment status"]]} onSelect={row => navigate(`/sales/${row.id}`)} /></main>;
  const branch = sale && JSON.parse(sale.branch_snapshot_json);
  const customer = sale?.customer_snapshot_json && JSON.parse(sale.customer_snapshot_json);
  return <main className="content"><div className="form-actions no-print"><Link to={search.get('own') === '1' ? '/sales?own=1' : '/sales'}>{search.get('own') === '1' ? 'Your sales' : 'All sales'}</Link>{user.permissions.includes('sales.return') && <Link to={'/returns/new/' + saleId}>Return / cancel sale</Link>}<button className="secondary" onClick={() => window.print()}><Icon name="print" />Print receipt</button></div><ErrorNotice error={refs.error} /><LoadingState busy={Boolean(refs.loading)} label="Loading receipt…" variant={sale ? 'inline' : 'form'} />{sale && <><article className="receipt card" aria-busy={Boolean(refs.loading)}><h1>{branch.receipt?.receipt_name || branch.business}</h1><p>{branch.name}<br />{branch.address}<br />{branch.phone}</p><h2>{sale.invoice_no}</h2><p>{new Date(sale.sold_at).toLocaleString(undefined, {
            timeZone: branch.timezone
          })} · {sale.currency_code}<br />{customer ? `${customer.name}${customer.phone ? ` · ${customer.phone}` : ''}` : 'Walk-in customer'}</p><TableViewport className="table-scroll"><table><thead><tr><th>Item / SKU</th><th className="numeric">Qty</th><th className="numeric">Unit price</th><th className="numeric">Discount</th><th className="numeric">Tax</th><th className="numeric">Total</th></tr></thead><tbody>{sale.items.map(line => <tr key={line.id}><td>{line.name_snapshot}<small className="block">{line.sku_snapshot}</small></td><td className="numeric">{line.qty} {line.unit_snapshot}</td><td className="numeric">{line.unit_price}</td><td className="numeric">{line.discount_total}</td><td className="numeric">{line.tax_total}</td><td className="numeric">{line.line_total}</td></tr>)}</tbody></table></TableViewport><div className="quote-totals"><p>Subtotal <strong>{sale.subtotal}</strong></p><p>Discount <strong>{sale.discount_total}</strong></p><p>Tax <strong>{sale.tax_total}</strong></p><p className="grand-total">Total <strong>{sale.currency_code} {sale.grand_total}</strong></p><p>Payments recorded <strong>{sale.paid_total}</strong></p><p>Balance due <strong>{sale.balance_due}</strong></p><p>Payment status <strong>{sale.payment_status}</strong></p></div><p>Return credits: {sale.returned_total || '0'}; refunds / exchange transfers: {sale.total_refunded}; refund due: {sale.refund_due || '0'}</p><p>{branch.receipt?.tax_registration && 'Tax registration: ' + branch.receipt.tax_registration}</p><p>{branch.receipt?.receipt_footer}</p><h3>Payment records</h3>{sale.payments.map(p => <p key={p.id}>{p.method_name_snapshot}: {p.amount} {p.reference && ` · ${p.reference}`}</p>)}</article>{canPay && sale.payment_status !== 'paid' && <FormDialog title={<>Collect balance</>} trigger="Collect balance"><SaveForm onSave={pay} label="Record confirmed payment" validate={() => paymentErrors(payments, refs.data['operations/payment-methods']?.data || [], {
          required: true,
          due: sale.balance_due
        })}><label>Open shift<ValidatedControl><select name="cash_session_id" required aria-label={"Open shift"}><option value="">Choose shift in original sale branch</option>{refs.data['cash/sessions']?.data.map(s => <option key={s.id} value={s.id}>Shift #{s.id} · register {s.register_id}</option>)}</select></ValidatedControl></label><PaymentFields methods={refs.data['operations/payment-methods']?.data || []} payments={payments} setPayments={setPayments} /></SaveForm></FormDialog>}</>}</main>;
}
