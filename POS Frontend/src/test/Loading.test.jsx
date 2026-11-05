import { useState } from 'react';
import { AxiosError, CanceledError } from 'axios';
import { act, cleanup, fireEvent, render, screen, within } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { client } from '../api/client';
import GlobalLoader from '../components/GlobalLoader';
import LoadingState from '../components/LoadingState';
import Modal from '../components/Modal';
import { RemoteTable } from '../features/operations/OperationUI';
import { loadingActivity } from '../loading/activity';

const originalAdapter = client.defaults.adapter;
const pending = new Set();
const requests = [];
const response = (config, data = {}) => ({ config, data, status: 200, statusText: 'OK', headers: {} });

function deferredAdapter(config) {
  let resolve, reject;
  const promise = new Promise((success, failure) => { resolve = success; reject = failure; });
  const request = {
    config,
    complete(data = {}) { resolve(response(config, data)); pending.delete(request); },
    fail(error) { reject(error); pending.delete(request); },
  };
  pending.add(request); requests.push(request);
  return promise;
}

async function request(url = '/api/v1/products', config = {}) {
  let result;
  // Attach rejection handling immediately, before deliberately failing a transport.
  await act(async () => { result = client.get(url, { notifications: false, ...config }).catch(error => error); });
  return { result };
}

async function advance(milliseconds) {
  await act(async () => { await vi.advanceTimersByTimeAsync(milliseconds); });
}

beforeEach(() => {
  vi.useFakeTimers(); requests.length = 0; client.defaults.adapter = deferredAdapter;
});

afterEach(async () => {
  await act(async () => {
    for (const transport of pending) transport.complete();
    await Promise.resolve();
  });
  cleanup();
  client.defaults.adapter = originalAdapter;
  vi.useRealTimers();
});

describe('central request loading indicator', () => {
  it('keeps progress visible until every concurrent request has finished', async () => {
    render(<GlobalLoader />);
    const first = await request('/api/v1/products');
    const second = await request('/api/v1/catalog/categories');
    expect(loadingActivity.getSnapshot()).toBe(2);
    await advance(180);
    expect(screen.getByRole('progressbar', { name: 'Loading data' })).toBeInTheDocument();
    await act(async () => { requests[0].complete(); await first.result; });
    expect(loadingActivity.getSnapshot()).toBe(1);
    await advance(600);
    expect(screen.getByRole('progressbar')).toHaveAttribute('data-pending', 'true');
    await act(async () => { requests[1].complete(); await second.result; });
    await advance(0);
    expect(loadingActivity.getSnapshot()).toBe(0);
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
  });

  it('does not flash for a request that completes within the display delay', async () => {
    render(<GlobalLoader />);
    const call = await request();
    await advance(100);
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
    await act(async () => { requests[0].complete(); await call.result; });
    await advance(600);
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
    expect(loadingActivity.getSnapshot()).toBe(0);
  });

  it('keeps a displayed indicator steady for at least 300ms without showing a completion percentage', async () => {
    render(<GlobalLoader />);
    const call = await request();
    await advance(180);
    const progress = screen.getByRole('progressbar');
    expect(progress).not.toHaveAttribute('aria-valuenow');
    expect(progress).not.toHaveAttribute('aria-valuetext');
    await act(async () => { requests[0].complete(); await call.result; });
    expect(progress).toHaveAttribute('data-pending', 'false');
    await advance(299);
    expect(progress).toBeInTheDocument();
    await advance(1);
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
  });

  it.each(['http', 'network', 'abort'])('cleans up after a %s failure and can show the next request', async kind => {
    render(<GlobalLoader />);
    const call = await request();
    const transport = requests[0];
    await advance(180);
    const failure = kind === 'http'
      ? new AxiosError('Unavailable', 'ERR_BAD_RESPONSE', transport.config, null, { status: 503, data: {} })
      : kind === 'abort' ? new CanceledError('Canceled', transport.config) : new Error('Connection lost');
    await act(async () => { transport.fail(failure); await call.result; });
    expect(loadingActivity.getSnapshot()).toBe(0);
    await advance(300);
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
    const retry = await request();
    await advance(180);
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
    await act(async () => { requests[1].complete(); await retry.result; });
  });

  it('cleans up a live AbortSignal cancellation without decrementing a separate request', async () => {
    client.defaults.adapter = config => {
      const transportPromise = deferredAdapter(config);
      const transport = requests.at(-1);
      config.signal?.addEventListener('abort', () => transport.fail(new CanceledError('Aborted', config)), { once: true });
      return transportPromise;
    };
    render(<GlobalLoader />);
    const controller = new AbortController();
    const canceled = await request('/api/v1/products', { signal: controller.signal });
    const remaining = await request('/api/v1/catalog/units');
    await advance(180);
    await act(async () => { controller.abort(); await canceled.result; });
    expect(loadingActivity.getSnapshot()).toBe(1);
    expect(screen.getByRole('progressbar')).toHaveAttribute('data-pending', 'true');
    await act(async () => { requests[1].complete(); await remaining.result; });
    expect(loadingActivity.getSnapshot()).toBe(0);
  });

  it('does not start transport or leave a loader behind for an already aborted request', async () => {
    render(<GlobalLoader />);
    const controller = new AbortController(); controller.abort();
    const call = await request('/api/v1/products', { signal: controller.signal });
    expect(await call.result).toBeInstanceOf(CanceledError);
    expect(requests).toHaveLength(0);
    expect(loadingActivity.getSnapshot()).toBe(0);
    await advance(600);
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
  });

  it('does not double count a retry made with the previous Axios request configuration', async () => {
    render(<GlobalLoader />);
    const first = await request();
    await act(async () => { requests[0].complete(); await first.result; });
    let retried;
    await act(async () => { retried = client.request(requests[0].config); });
    expect(loadingActivity.getSnapshot()).toBe(1);
    await advance(180);
    expect(screen.getAllByRole('progressbar')).toHaveLength(1);
    await act(async () => { requests[1].complete(); await retried; });
    expect(loadingActivity.getSnapshot()).toBe(0);
  });

  it('allows opt-out reads and CSRF preflights to run without displaying global progress', async () => {
    render(<GlobalLoader />);
    const silent = await request('/api/v1/notifications', { globalLoader: false });
    const csrf = await request('/sanctum/csrf-cookie?refresh=1');
    expect(requests).toHaveLength(2);
    expect(loadingActivity.getSnapshot()).toBe(0);
    await advance(600);
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
    await act(async () => { requests.forEach(transport => transport.complete()); await Promise.all([silent.result, csrf.result]); });
  });

  it('does not capture keyboard focus or disable navigation while loading', async () => {
    const navigate = vi.fn();
    render(<><GlobalLoader /><input aria-label="Search products" /><button onClick={navigate}>Open sales</button></>);
    const input = screen.getByRole('textbox', { name: 'Search products' }); input.focus();
    const call = await request();
    await advance(180);
    expect(input).toHaveFocus();
    const host = document.getElementById('pos-loader-root');
    expect(host.querySelector('button, a, input, [tabindex]')).toBeNull();
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Open sales' })).toBeEnabled();
    fireEvent.click(screen.getByRole('button', { name: 'Open sales' }));
    expect(navigate).toHaveBeenCalledTimes(1);
    await act(async () => { requests[0].complete(); await call.result; });
  });

  it('moves one accessible progress portal through nested dialogs without restarting its delay', async () => {
    function Dialogs() {
      const [outer, setOuter] = useState(false), [inner, setInner] = useState(false);
      return <><GlobalLoader /><button onClick={() => setOuter(true)}>Open outer</button>{outer &&
        <Modal title="Outer" onClose={() => setOuter(false)}><input aria-label="Record name" />
          <button onClick={() => setInner(true)}>Open inner</button>{inner &&
            <Modal title="Inner" onClose={() => setInner(false)}><input aria-label="Nested name" /></Modal>}
        </Modal>}</>;
    }
    render(<Dialogs />);
    const call = await request();
    const host = document.getElementById('pos-loader-root');
    await advance(100);
    fireEvent.click(screen.getByRole('button', { name: 'Open outer' }));
    const outer = screen.getByRole('dialog', { name: 'Outer' });
    expect(outer).toContainElement(host);
    await advance(80);
    const progress = screen.getByRole('progressbar');
    expect(host.closest('[inert], [aria-hidden="true"]')).toBeNull();
    expect(screen.getByRole('textbox', { name: 'Record name' })).toHaveFocus();
    fireEvent.click(screen.getByRole('button', { name: 'Open inner' }));
    const inner = screen.getByRole('dialog', { name: 'Inner' });
    expect(inner).toContainElement(host);
    expect(screen.getByRole('progressbar')).toBe(progress);
    expect(host.closest('[inert], [aria-hidden="true"]')).toBeNull();
    fireEvent.click(within(inner).getByRole('button', { name: 'Close dialog' }));
    expect(outer).toContainElement(host);
    fireEvent.click(within(outer).getByRole('button', { name: 'Close dialog' }));
    expect(host.parentElement).toBe(document.body);
    expect(screen.getByRole('progressbar')).toBe(progress);
    expect(host.closest('[inert], [aria-hidden="true"]')).toBeNull();
    await act(async () => { requests[0].complete(); await call.result; });
  });
});

describe('shared loading states for data regions', () => {
  it.each(['table', 'form', 'cards', 'inline'])('announces one %s region and keeps skeletons decorative', async variant => {
    const view = render(<LoadingState label="Loading products" variant={variant} rows={3} columns={2} />);
    expect(screen.queryByRole('status')).not.toBeInTheDocument();
    await advance(180);
    const status = screen.getByRole('status', { name: 'Loading products' });
    expect(status).toHaveAttribute('aria-live', 'polite');
    expect(status).toHaveAttribute('aria-atomic', 'true');
    expect(screen.getAllByText('Loading products')).toHaveLength(1);
    expect(status.querySelector('.loading-state__spinner')).toHaveAttribute('aria-hidden', 'true');
    const skeletons = status.querySelector('.loading-state__skeletons');
    if (variant === 'inline') expect(skeletons).toBeNull();
    else expect(skeletons).toHaveAttribute('aria-hidden', 'true');
    expect(status.querySelectorAll('button, input, [tabindex]')).toHaveLength(0);
    view.rerender(<LoadingState busy={false} label="Loading products" variant={variant} />);
    await advance(299);
    expect(status).toBeInTheDocument();
    await advance(1);
    expect(screen.queryByRole('status')).not.toBeInTheDocument();
  });

  it('keeps loaded rows visible during a refresh and after a failed refresh', async () => {
    const columns = [['name', 'Product']];
    const view = render(<RemoteTable path="products" columns={columns} />);
    await act(async () => { await Promise.resolve(); });
    await act(async () => { requests[0].complete({ data: [{ id: 1, name: 'Existing shirt' }], last_page: 1 }); });
    expect(screen.getByRole('cell', { name: 'Existing shirt' })).toBeInTheDocument();
    expect(view.container.querySelector('.table-scroll')).toHaveAttribute('aria-busy', 'false');
    view.rerender(<RemoteTable path="products" columns={columns} revision={1} />);
    await act(async () => { await Promise.resolve(); });
    expect(view.container.querySelector('.table-scroll')).toHaveAttribute('aria-busy', 'true');
    await advance(180);
    expect(screen.getByRole('status', { name: 'Loading records…' })).toHaveClass('loading-state--inline');
    expect(screen.getByRole('cell', { name: 'Existing shirt' })).toBeInTheDocument();
    await act(async () => { requests[1].fail(new AxiosError('Unable to refresh', 'ERR_BAD_RESPONSE', requests[1].config, null,
      { status: 503, data: { message: 'Please retry the refresh.' } })); });
    expect(screen.getByRole('cell', { name: 'Existing shirt' })).toBeInTheDocument();
    expect(screen.getByText('Please retry the refresh.')).toBeInTheDocument();
    expect(screen.queryByText('No records yet.')).not.toBeInTheDocument();
    expect(view.container.querySelector('.table-scroll')).toHaveAttribute('aria-busy', 'false');
    await advance(300);
    expect(screen.queryByRole('status', { name: 'Loading records…' })).not.toBeInTheDocument();
    expect(loadingActivity.getSnapshot()).toBe(0);
  });
});
