import { useState } from 'react';
import { AxiosError, CanceledError } from 'axios';
import { act, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it } from 'vitest';
import { toast } from 'react-toastify';
import GlobalToaster from '../components/GlobalToaster';
import Modal, { FormDialog } from '../components/Modal';
import { ErrorNotice, Input, SaveForm } from '../components/WorkspaceForm';
import { client, authApi } from '../api/client';
import { workspaceApi } from '../api/workspace';
import { AuthProvider, useAuth } from '../auth/AuthProvider';
import { clearNotifications, notify } from '../notifications';
import { successNotification } from '../notifications/apiMessages';

const originalAdapter = client.defaults.adapter;
afterEach(() => { client.defaults.adapter = originalAdapter; });
const response = (config, data = {}, status = 200) => ({ config, data, status, statusText: 'OK', headers: {} });
const reject = (config, status = 422, data = { message: 'The request needs correction.' }) => {
  throw new AxiosError('Request failed', AxiosError.ERR_BAD_REQUEST, config, null, response(config, data, status));
};
const notifications = () => within(document.getElementById('pos-toast-root'));
const open = children => render(<><GlobalToaster/>{children}</>);

describe('global application notifications', () => {
  it('announces a saved record once and persists the toast when the page content changes', async () => {
    client.defaults.adapter = async config => response(config, { data: { id: 4 } }, 201);
    const view = open(<p>Products</p>);
    await act(async () => { await workspaceApi.save('products', { name: 'Shirt' }); });
    expect(await notifications().findByText('Product created successfully.')).toBeInTheDocument();
    expect(document.querySelectorAll('.Toastify__toast')).toHaveLength(1);
    view.rerender(<><GlobalToaster/><p>Another page</p></>);
    expect(notifications().getByText('Product created successfully.')).toBeInTheDocument();
  });
  it('keeps normal reads silent and allows an explicit report completion message', async () => {
    client.defaults.adapter = async config => response(config, { data: [] }); open();
    await act(async () => { await workspaceApi.get('products'); });
    expect(document.querySelector('.Toastify__toast')).not.toBeInTheDocument();
    await act(async () => { await workspaceApi.get('reports/summary', { notifications: { success: 'Report generated successfully.' } }); });
    expect(await notifications().findByText('Report generated successfully.')).toBeInTheDocument();
  });
  it('toasts client validation while keeping the form summary above its fields', async () => {
    let saves = 0;
    open(<SaveForm onSave={() => { saves++; }}><Input name="name" label="Name"/></SaveForm>);
    fireEvent.submit(screen.getByLabelText('Name').closest('form'));
    expect(await notifications().findByText('Please correct the highlighted fields.')).toBeInTheDocument();
    expect(saves).toBe(0); expect(screen.getByLabelText('Name')).toHaveAttribute('aria-invalid', 'true');
    const form = screen.getByLabelText('Name').closest('form');
    expect(form.querySelector('fieldset').firstElementChild).toHaveClass('validation-summary');
  });
  it('deduplicates interceptor, form and inline error feedback for the same failure', async () => {
    let failure;
    client.defaults.adapter = async config => {
      try { reject(config, 422, { message: 'Name already exists.', errors: { name: ['Name already exists.'] } }); }
      catch (error) { failure = error; throw error; }
    };
    open(<SaveForm onSave={() => workspaceApi.save('catalog/brands', {name:'Existing',code:'EX'})}><Input name="name" label="Name" value="Existing"/></SaveForm>);
    fireEvent.submit(screen.getByLabelText('Name').closest('form'));
    expect(await notifications().findByText('Name already exists.')).toBeInTheDocument();
    await act(async () => { notify.error(failure); });
    expect(notifications().getAllByText('Name already exists.')).toHaveLength(1);
    expect(screen.getByLabelText('Name')).toHaveAccessibleDescription('Name already exists.');
  });
  it('clears a failed action toast when retrying that action succeeds', async () => {
    open(); let attempts=0;
    client.defaults.adapter=async config=>{if(++attempts===1)reject(config,409,{message:'Please refresh and retry.'});return response(config,{data:{id:1}},201);};
    await act(async()=>{await workspaceApi.save('products',{name:'Shirt'}).catch(()=>{});});
    const previous=await notifications().findByText('Please refresh and retry.'); const id=previous.closest('.Toastify__toast').id;
    await act(async()=>{await workspaceApi.save('products',{name:'Shirt'});});
    expect(await notifications().findByText('Product created successfully.')).toBeInTheDocument(); expect(toast.isActive(id)).toBe(false);
  });
  it('dismisses a validation toast when the corrected form is submitted', async () => {
    open(<SaveForm onSave={()=>{}}><Input name="name" label="Name"/></SaveForm>);
    const input=screen.getByLabelText('Name'); fireEvent.submit(input.closest('form'));
    const message=await notifications().findByText('Please correct the highlighted fields.'); const id=message.closest('.Toastify__toast').id;
    fireEvent.change(input,{target:{value:'Corrected'}});fireEvent.submit(input.closest('form'));
    await waitFor(()=>expect(toast.isActive(id)).toBe(false));
  });
  it.each([
    [403, 'Access denied.'], [409, 'Stock changed. Refresh the quote.'],
    [429, 'Too many attempts. Please wait a minute and try again.'],
    [500, 'Unable to process your request.'],
  ])('announces HTTP %i errors without reporting success', async (status, expected) => {
    client.defaults.adapter = async config => reject(config, status, {message:expected}); open();
    await act(async () => { await workspaceApi.save('pos/checkout', {}).catch(() => {}); });
    expect(await notifications().findByText(expected)).toBeInTheDocument();
    expect(document.querySelector('.Toastify__toast--success')).not.toBeInTheDocument();
  });
  it('announces network failures without assuming the operation succeeded', async () => {
    client.defaults.adapter = async config => { throw new AxiosError('Network Error', 'ERR_NETWORK', config); }; open();
    await act(async () => { await workspaceApi.save('pos/checkout', {}).catch(() => {}); });
    expect(await notifications().findByText('Unable to connect. Please try again.')).toBeInTheDocument();
    expect(document.querySelector('.Toastify__toast--success')).not.toBeInTheDocument();
  });
  it('keeps expected guest checks, cancellations and explicitly silent requests quiet', async () => {
    open(); client.defaults.adapter = async config => reject(config, 401);
    await act(async () => { await authApi.me().catch(() => {}); });
    client.defaults.adapter = async config => { throw new CanceledError('Canceled', config); };
    await act(async () => { await workspaceApi.get('products').catch(() => {}); });
    client.defaults.adapter = async config => reject(config, 500);
    await act(async () => { await workspaceApi.get('products', { notifications:false }).catch(() => {}); });
    expect(document.querySelector('.Toastify__toast')).not.toBeInTheDocument();
  });
  it('warns for an HTTP-successful invalid import without claiming products were imported', async () => {
    client.defaults.adapter = async config => response(config, { data: { status: 'invalid', errors: [{row:2,message:'Unknown unit'}] } }, 201); open();
    await act(async () => { await workspaceApi.save('workspace/imports', new FormData()); });
    expect(await notifications().findByText(/import has validation errors/)).toBeInTheDocument();
    expect(document.querySelector('.Toastify__toast--warning')).toBeInTheDocument();
    expect(document.querySelector('.Toastify__toast--success')).not.toBeInTheDocument();
  });
  it('describes Stripe Checkout as a redirect awaiting verified payment', async () => {
    client.defaults.adapter = async config => response(config, { data: { url:'https://checkout.stripe.com/test' } }); open();
    await act(async () => { await workspaceApi.save('billing/checkout', {plan_price_id:1}); });
    expect(await notifications().findByText(/Subscription access awaits verified payment confirmation/)).toBeInTheDocument();
    expect(document.querySelector('.Toastify__toast--info')).toBeInTheDocument();
    expect(document.querySelector('.Toastify__toast--success')).not.toBeInTheDocument();
  });
  it('distinguishes requested exports from files ready to download', () => {
    expect(successNotification({method:'post',url:'/api/v1/workspace/exports'}, {data:{status:'queued'}})).toEqual({type:'info',message:'Export requested.'});
    expect(successNotification({method:'post',url:'/api/v1/workspace/exports'}, {data:{status:'ready'}})).toEqual({type:'success',message:'Export is ready to download.'});
    expect(successNotification({method:'post',url:'/api/v1/inventory/reconcile'}, {data:{reconciled:false}}).type).toBe('warning');
  });
  it('does not show stale success responses after switching notification scope', async () => {
    let finish;
    client.defaults.adapter = config => new Promise(resolve => { finish = () => resolve(response(config,{data:{id:1}},201)); });
    open(); const pending = workspaceApi.save('products',{name:'Previous business'});
    await waitFor(() => expect(finish).toBeTypeOf('function'));
    await act(async () => { clearNotifications(); finish(); await pending; });
    expect(document.querySelector('.Toastify__toast')).not.toBeInTheDocument();
  });
  it('does not resurrect stale request errors through a page error notice', async () => {
    let finish, failure;
    client.defaults.adapter = config => new Promise((resolve,rejectPromise) => { finish = () => { try { reject(config,403); } catch(error) { rejectPromise(error); } }; });
    const view = open(); const pending = workspaceApi.get('products').catch(error => {failure=error;});
    await waitFor(() => expect(finish).toBeTypeOf('function'));
    await act(async () => { clearNotifications(); finish(); await pending; });
    view.rerender(<><GlobalToaster/><ErrorNotice error={failure}/></>);
    expect(document.querySelector('.Toastify__toast')).not.toBeInTheDocument();
  });
  it('keeps toasts accessible inside the active modal and retains them after closing it', async () => {
    const user = userEvent.setup();
    open(<FormDialog title="Edit record" trigger="Edit"><Input name="name" label="Name"/></FormDialog>);
    await user.click(screen.getByRole('button',{name:'Edit'}));
    const dialog = screen.getByRole('dialog');
    await act(async () => { notify.error('Please check this record.'); });
    expect(await notifications().findByText('Please check this record.')).toBeInTheDocument();
    const host = document.getElementById('pos-toast-root');
    expect(dialog).toContainElement(host); expect(host.closest('[inert], [aria-hidden="true"]')).toBeNull();
    const closeToast = notifications().getByRole('button',{name:/close/i}); closeToast.focus(); expect(closeToast).toHaveFocus();
    await user.click(screen.getByRole('button',{name:'Close dialog'}));
    expect(host.parentElement).toBe(document.body); expect(notifications().getByText('Please check this record.')).toBeInTheDocument();
  });
  it('moves the same notification host between nested modals', async () => {
    function Nested(){const [inner,setInner]=useState(false);return <Modal title="Outer" onClose={()=>{}}><button onClick={()=>setInner(true)}>Inner</button>{inner&&<Modal title="Inner" onClose={()=>setInner(false)}><p>Nested content</p></Modal>}</Modal>;}
    open(<Nested/>); const user=userEvent.setup(); await act(async()=>{notify.info('Keep this notification.');});
    const host=document.getElementById('pos-toast-root'); expect(screen.getByRole('dialog',{name:'Outer'})).toContainElement(host);
    await user.click(screen.getByRole('button',{name:'Inner'})); expect(screen.getByRole('dialog',{name:'Inner'})).toContainElement(host);
    await user.click(within(screen.getByRole('dialog',{name:'Inner'})).getByRole('button',{name:'Close dialog'}));
    expect(screen.getByRole('dialog',{name:'Outer'})).toContainElement(host); expect(notifications().getByText('Keep this notification.')).toBeInTheDocument();
  });
  it('shows one session-expiry toast and clears the authenticated view', async () => {
    const owner={id:1,name:'Owner',permissions:[],active_tenant:{id:1,name:'Shop'}};
    client.defaults.adapter = async config => config.url.endsWith('/auth/me') ? response(config,{data:owner}) : reject(config,419);
    function Session(){const {user,loading}=useAuth();return <p>{loading?'Checking':user?'Signed in':'Signed out'}</p>;}
    open(<AuthProvider><Session/></AuthProvider>); await screen.findByText('Signed in');
    await act(async()=>{await workspaceApi.save('products',{}).catch(error=>notify.error(error));});
    expect(await screen.findByText('Signed out')).toBeInTheDocument();
    expect(notifications().getAllByText('Your session expired. Please sign in again.')).toHaveLength(1);
  });
  it('announces sign-in and sign-out after authentication succeeds', async () => {
    const owner={id:1,name:'Owner',permissions:[]};
    client.defaults.adapter=async config=>config.url.endsWith('/auth/me')?reject(config,401):response(config,{data:owner});
    function Session(){const {user,login,logout}=useAuth();return <button onClick={()=>user?logout():login({email:'staff@example.test',password:'test-only-password'})}>{user?'Sign out':'Sign in'}</button>;}
    open(<AuthProvider><Session/></AuthProvider>); const user=userEvent.setup();
    await user.click(await screen.findByRole('button',{name:'Sign in'}));
    expect(await notifications().findByText('Signed in successfully.')).toBeInTheDocument();
    await user.click(screen.getByRole('button',{name:'Sign out'}));
    expect(await notifications().findByText('Signed out successfully.')).toBeInTheDocument();
  });
  it('deduplicates bursts of the same error before render', async () => {
    open(); await act(async()=>{for(let index=0;index<5;index++)notify.error({response:{status:500,data:{message:'Please retry later.'}}});});
    expect(await notifications().findAllByText('Please retry later.')).toHaveLength(1);
    expect(toast.isActive(document.querySelector('.Toastify__toast').id)).toBe(true);
  });
});
