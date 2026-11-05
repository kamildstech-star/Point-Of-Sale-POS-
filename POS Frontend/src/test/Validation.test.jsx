import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { Input, SaveForm, Select } from '../components/WorkspaceForm';
import { PaymentFields } from '../features/operations/OperationUI';
import { decimalCompare, decimalDifference, paymentErrors, rulesFor, selectedQuantityErrors, validateValue } from '../validation/forms';

describe('application validation without browser constraint bubbles', () => {
  it.each([
    ['name', '   '], ['email', 'bad@@example.test'], ['phone', 'x'.repeat(41)],
    ['default_price', '-1'], ['default_price', '1e3'], ['default_cost', '1.12345'], ['unit_price', '1,000'],
    ['amount', '0'], ['amount', '-2'], ['amount', '1000000000000'],
    ['min_qty', '0'], ['rate_percent', '100.0001'], ['rate_percent', '-1'],
    ['quantity_precision', '5'], ['quantity_precision', '1.5'], ['return_days', '-1'], ['return_days', '3651'],
    ['payment_terms_days', '366'], ['minutes', '0'], ['sort_order', '2147483648'],
    ['timezone', 'Mars/Olympus'], ['currency_code', 'pkr'], ['body_template', '{{unknown}}'],
  ])('rejects invalid %s values before saving (%s)', async (name, value) => {
    const save = vi.fn();
    render(<SaveForm onSave={save}><Input name={name} label="Value" value={value}/></SaveForm>);
    const input = screen.getByLabelText('Value');
    expect(input.closest('form')).toHaveAttribute('novalidate');
    fireEvent.submit(input.closest('form'));
    expect(save).not.toHaveBeenCalled();
    expect(input).toHaveAttribute('aria-invalid', 'true');
    expect(input).toHaveAccessibleDescription();
    await waitFor(() => expect(input).toHaveFocus());
  });
  it('checks optional fields when supplied and keeps blank optional fields valid', async () => {
    const save = vi.fn();
    render(<SaveForm onSave={save}><Input name="email" label="Email" type="email" required={false}/></SaveForm>);
    fireEvent.change(screen.getByLabelText('Email'), { target: { value: 'invalid' } });
    fireEvent.submit(screen.getByLabelText('Email').closest('form')); expect(save).not.toHaveBeenCalled();
    fireEvent.change(screen.getByLabelText('Email'), { target: { value: '' } });
    fireEvent.submit(screen.getByLabelText('Email').closest('form')); await waitFor(() => expect(save).toHaveBeenCalledTimes(1));
  });
  it('validates all required fields on the first submit click', () => {
    const save = vi.fn();
    render(<SaveForm onSave={save}><Input name="name" label="Name"/><Input name="slug" label="Slug"/></SaveForm>);
    const name = screen.getByLabelText('Name'), button = screen.getByRole('button', {name:'Save changes'});
    fireEvent.blur(name, {relatedTarget:button});
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    fireEvent.click(button);
    expect(save).not.toHaveBeenCalled();
    expect(name).toHaveAttribute('aria-invalid','true'); expect(screen.getByLabelText('Slug')).toHaveAttribute('aria-invalid','true');
  });
  it('checks password confirmation and explicit acceptance even for programmatic submit', () => {
    const save = vi.fn();
    render(<SaveForm onSave={save}><Input name="password" label="Password" type="password" minLength={12} value="long-test-password"/><Input name="password_confirmation" label="Confirmation" type="password" value="different-password"/><Input name="confirmed" label="Reviewed" type="checkbox"/></SaveForm>);
    fireEvent.submit(screen.getByLabelText('Password').closest('form'));
    expect(save).not.toHaveBeenCalled(); expect(screen.getByLabelText('Confirmation')).toHaveAttribute('aria-invalid', 'true');
    expect(screen.getByText(/password confirmation does not match/)).toBeInTheDocument();
  });
  it('checks select choices and integer precision without relying on HTML', () => {
    const save = vi.fn();
    render(<SaveForm onSave={save}><Select name="role_id" label="Role" options={[{ id: 1, name: 'Cashier' }]}/><Input name="quantity_precision" label="Precision" type="number" value="1.5"/></SaveForm>);
    fireEvent.submit(screen.getByLabelText('Role').closest('form'));
    expect(save).not.toHaveBeenCalled(); expect(screen.getByLabelText('Role')).toHaveAttribute('aria-invalid', 'true');
    expect(screen.getByText(/must be a whole number/)).toBeInTheDocument();
  });
  it.each([['2026-02-10','2026-02-01'], ['2024-01-01','2026-01-01']])('validates report date order and range', (from,to) => {
    const save=vi.fn(); render(<SaveForm onSave={save}><Input name="from" label="From" type="date" value={from}/><Input name="to" label="To" type="date" value={to}/></SaveForm>);
    fireEvent.submit(screen.getByLabelText('From').closest('form')); expect(save).not.toHaveBeenCalled(); expect(screen.getByLabelText('To')).toHaveAttribute('aria-invalid','true');
  });
  it('rejects oversized files and wrong formats before an upload', () => {
    const save=vi.fn(); render(<SaveForm onSave={save}><Input name="image" label="Image" type="file" accept="image/png,image/jpeg,image/webp"/></SaveForm>);
    const input=screen.getByLabelText('Image');
    fireEvent.change(input,{target:{files:[new File(['text'],'wrong.txt',{type:'text/plain'})]}}); fireEvent.submit(input.closest('form')); expect(save).not.toHaveBeenCalled(); expect(screen.getByText(/supported format/)).toBeInTheDocument();
    fireEvent.change(input,{target:{files:[new File([new Uint8Array(2097153)],'big.png',{type:'image/png'})]}}); fireEvent.submit(input.closest('form')); expect(save).not.toHaveBeenCalled(); expect(screen.getByText(/2 MB or smaller/)).toBeInTheDocument();
  });
  it('places nested Laravel errors at the correct field and keeps values for retry', async () => {
    const save=vi.fn().mockRejectedValueOnce({response:{status:422,data:{errors:{'variant.sku':['This SKU already exists.']}}}}).mockResolvedValueOnce({});
    render(<SaveForm onSave={save}><Input name="variant_sku" label="SKU" value="SHIRT-1"/></SaveForm>);
    const input=screen.getByLabelText('SKU'); fireEvent.submit(input.closest('form'));
    expect(await screen.findByText('This SKU already exists.')).toBeInTheDocument(); expect(input).toHaveValue('SHIRT-1'); expect(input).toHaveAccessibleDescription('This SKU already exists.');
    fireEvent.blur(input); expect(screen.getByText('This SKU already exists.')).toBeInTheDocument();
    fireEvent.change(input,{target:{value:'SHIRT-2'}}); fireEvent.submit(input.closest('form')); await waitFor(()=>expect(save).toHaveBeenCalledTimes(2)); expect(save.mock.calls[1][0].get('variant_sku')).toBe('SHIRT-2');
  });
  it('validates split payments in the form and displays nested field errors', () => {
    const save=vi.fn(); const methods=[{id:1,name:'Card',method_type:'card',active:true}];
    function Form(){const [payments,setPayments]=useState([{method_id:'1',amount:'0',reference:''}]);return <SaveForm onSave={save} validate={()=>paymentErrors(payments,methods,{required:true})}><PaymentFields methods={methods} payments={payments} setPayments={setPayments}/></SaveForm>;}
    render(<Form/>); fireEvent.submit(screen.getByLabelText('Payment amount 1').closest('form')); expect(save).not.toHaveBeenCalled(); expect(screen.getByLabelText('Payment amount 1')).toHaveAttribute('aria-invalid','true'); expect(screen.getByLabelText('Payment reference 1')).toHaveAttribute('aria-invalid','true');
  });
  it('compares large decimals exactly and preserves valid catalog prices', () => {
    expect(decimalCompare('999999999999999.9999','999999999999999.9998')).toBe(1);
    expect(decimalDifference('10.0001','9.9999')).toBe('0.0002');
    expect(validateValue('999999999999999.9999',rulesFor('default_price'))).toBeNull();
    expect(validateValue('0.0000',rulesFor('counted_cash'))).toBeNull();
    expect(validateValue('1.001', {decimal:true,scale:2})).toMatch(/currency precision/);
    expect(validateValue('1.2300', {decimal:true,scale:2})).toBeNull();
  });
  it('requires selected receipt/return quantities and rejects excess quantities exactly', () => {
    const form=new FormData(); const items=[{id:1}]; form.set('qty-1','0');
    expect(selectedQuantityErrors(form,items,{prefix:'qty',maximum:()=> '1'})).toHaveProperty('items');
    form.set('qty-1','1.0001'); expect(selectedQuantityErrors(form,items,{prefix:'qty',maximum:()=> '1'})).toHaveProperty('qty-1');
  });
  it('checks duplicate methods, full payment and overpayment without floating point', () => {
    const methods=[{id:1,active:true,method_type:'cash'}];
    expect(paymentErrors([{method_id:'1',amount:'0.1'},{method_id:'1',amount:'0.2'}],methods)).toHaveProperty('payments.1.method_id');
    expect(paymentErrors([{method_id:'1',amount:'0.2999'}],methods,{due:'0.3',fullPayment:true})).toHaveProperty('payments');
    expect(paymentErrors([{method_id:'1',amount:'0.3001'}],methods,{due:'0.3'})).toHaveProperty('payments');
    expect(paymentErrors([{method_id:'1',amount:'0.3'}],methods,{due:'0.3',fullPayment:true})).toEqual({});
  });
});
