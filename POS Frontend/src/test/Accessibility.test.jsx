import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { Profiler } from 'react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { Link, MemoryRouter, Route, Routes, useNavigate } from 'react-router-dom';
import RouteAccessibility from '../components/layout/RouteAccessibility';
import { FormDialog } from '../components/Modal';
import { FormDetails, Input } from '../components/WorkspaceForm';
import { ListPagination } from '../components/ui/ListPatterns';
import TableViewport from '../components/ui/TableViewport';

function Page({ title, pending = false }) {
  const navigate = useNavigate();
  return <main aria-busy={pending || undefined}><h1>{pending ? 'Loading page' : title}</h1><button onClick={() => navigate('?page=2')}>Next server page</button></main>;
}
function Harness({ pending = false, businessId = 1, name = 'Products' }) {
  return <><input aria-label="Existing field" autoFocus/><nav><Link to="/catalog">Products</Link></nav>
    <RouteAccessibility businessId={businessId} pageLabel="Workspace"/><div id="workspace-content" tabIndex={-1}><Routes>
      <Route path="/" element={<Page title="Overview"/>}/><Route path="/catalog" element={<Page title={name} pending={pending}/>}/>
    </Routes></div></>;
}
const open = (props = {}, path = '/') => render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><Harness {...props}/></MemoryRouter>);
const rerender = (view, props) => view.rerender(<MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><Harness {...props}/></MemoryRouter>);
beforeEach(() => { vi.spyOn(window, 'scrollTo').mockImplementation(() => {}); });

describe('accessible client navigation', () => {
  it('sets a descriptive title on initial entry without stealing input/scanner focus', () => {
    open(); expect(document.title).toBe('Overview · Universal POS'); expect(screen.getByLabelText('Existing field')).toHaveFocus();
    expect(window.scrollTo).not.toHaveBeenCalled();
  });
  it('focuses the actual page heading after navigation and resets page scroll', async () => {
    open(); await userEvent.click(screen.getByRole('link', { name: 'Products' }));
    expect(screen.getByRole('heading', { name: 'Products' })).toHaveFocus(); expect(document.title).toBe('Products · Universal POS');
    expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, left: 0, behavior: 'instant' });
  });
  it('waits for the real asynchronous page rather than focusing a loading heading', async () => {
    const view = open({ pending: true }); await userEvent.click(screen.getByRole('link', { name: 'Products' }));
    expect(screen.getByRole('heading', { name: 'Loading page' })).not.toHaveFocus();
    rerender(view, { pending: false }); await waitFor(() => expect(screen.getByRole('heading', { name: 'Products' })).toHaveFocus());
  });
  it('keeps focus with someone who starts editing during an asynchronous load', async () => {
    const view = open({ pending: true }); const user = userEvent.setup(); await user.click(screen.getByRole('link', { name: 'Products' }));
    await user.type(screen.getByLabelText('Existing field'), 'keep typing');
    rerender(view, { pending: false }); await waitFor(() => expect(document.title).toBe('Products · Universal POS'));
    expect(screen.getByLabelText('Existing field')).toHaveFocus(); expect(window.scrollTo).not.toHaveBeenCalled();
  });
  it('leaves pagination/filter query changes in the current reading position', async () => {
    open({}, '/catalog'); const button = screen.getByRole('button', { name: 'Next server page' }); await userEvent.click(button);
    expect(button).toHaveFocus(); expect(window.scrollTo).not.toHaveBeenCalled();
  });
  it('announces a new business by focusing its current page heading', async () => {
    const view = open({}, '/catalog'); rerender(view, { businessId: 2, name: 'Second business products' });
    expect(screen.getByRole('heading', { name: 'Second business products' })).toHaveFocus();
    expect(document.title).toBe('Second business products · Universal POS');
  });
  it('does not take focus from an active dialog while the page finishes loading', async () => {
    const view = open({ pending: true }); await userEvent.click(screen.getByRole('link', { name: 'Products' }));
    const modal = document.createElement('div'); modal.setAttribute('role', 'dialog'); modal.setAttribute('aria-modal', 'true'); document.body.appendChild(modal);
    try { rerender(view, { pending: false }); await act(async () => {}); expect(screen.getByRole('heading', { name: 'Products' })).not.toHaveFocus(); }
    finally { modal.remove(); }
  });
});

describe('keyboard disclosures and list announcements', () => {
  it('focuses the first field when an asynchronous dialog form becomes ready', async () => {
    const view = render(<FormDialog title="Async editor" trigger="Open editor"><p>Loading form</p></FormDialog>);
    await userEvent.click(screen.getByRole('button', { name: 'Open editor' })); expect(screen.getByRole('dialog')).toHaveFocus();
    view.rerender(<FormDialog title="Async editor" trigger="Open editor"><Input name="name" label="Ready field"/></FormDialog>);
    await waitFor(() => expect(screen.getByLabelText('Ready field')).toHaveFocus());
  });
  it('keeps focus with someone who has already navigated inside a loading dialog', async () => {
    const view = render(<FormDialog title="Async editor" trigger="Open editor"><p>Loading form</p></FormDialog>);
    const user = userEvent.setup(); await user.click(screen.getByRole('button', { name: 'Open editor' })); await user.tab();
    expect(screen.getByRole('button', { name: 'Close dialog' })).toHaveFocus();
    view.rerender(<FormDialog title="Async editor" trigger="Open editor"><Input name="name" label="Ready field"/></FormDialog>);
    await act(async () => {}); expect(screen.getByRole('button', { name: 'Close dialog' })).toHaveFocus();
  });
  it('excludes summaries inside a closed ancestor disclosure from the focus loop', async () => {
    render(<FormDialog title="Nested details" trigger="Open details"><FormDetails title="Outer options"><FormDetails title="Inner options"><Input name="hidden" label="Hidden field"/></FormDetails></FormDetails></FormDialog>);
    const user = userEvent.setup(); await user.click(screen.getByRole('button', { name: 'Open details' }));
    screen.getByRole('button', { name: 'Close dialog' }).focus(); await user.tab({ shift: true });
    expect(screen.getByText('Outer options')).toHaveFocus(); expect(screen.getByText('Inner options')).not.toHaveFocus();
  });
  it('excludes CSS-hidden controls from modal wraparound', async () => {
    render(<FormDialog title="Hidden actions" trigger="Open actions"><button>Visible action</button><button style={{ display: 'none' }}>Hidden action</button></FormDialog>);
    const user = userEvent.setup(); await user.click(screen.getByRole('button', { name: 'Open actions' }));
    screen.getByRole('button', { name: 'Close dialog' }).focus(); await user.tab({ shift: true }); expect(screen.getByRole('button', { name: 'Visible action' })).toHaveFocus();
  });
  it('exposes atomic, polite server-page summaries while preventing duplicate paging', async () => {
    const data = { current_page: 1, last_page: 2, total: 21, per_page: 20, from: 1, to: 20, data: Array(20).fill({ id: 1 }) };
    const change = vi.fn(); const view = render(<ListPagination label="Products" data={data} onPageChange={change}/>);
    expect(screen.getByRole('status', { name: 'Products results' })).toHaveTextContent('Showing 1–20 · 21 total');
    expect(screen.getByRole('status')).toHaveAttribute('aria-atomic', 'true');
    view.rerender(<ListPagination label="Products" data={data} busy onPageChange={change}/>);
    expect(screen.getByRole('status')).toHaveAttribute('aria-busy', 'true');
    expect(screen.getByRole('status')).toHaveTextContent('Updating records'); fireEvent.click(screen.getByRole('button', { name: 'Next' })); expect(change).not.toHaveBeenCalled();
  });
  it('updates table keyboard overflow only when measurements actually change', () => {
    const commits = vi.fn(); let wide = false;
    render(<Profiler id="table" onRender={commits}><TableViewport label="Wide records"><table><tbody><tr><td>Exact amount</td></tr></tbody></table></TableViewport></Profiler>);
    const viewport = screen.getByRole('region', { name: 'Wide records' });
    Object.defineProperties(viewport, { clientWidth: { get: () => 100 }, scrollWidth: { get: () => wide ? 300 : 100 } });
    const before = commits.mock.calls.length; fireEvent(window, new Event('resize'));
    expect(commits).toHaveBeenCalledTimes(before);
    wide = true; fireEvent(window, new Event('resize'));
    expect(viewport).toHaveAttribute('tabindex', '0'); expect(screen.getByText(/Scroll horizontally/)).toBeInTheDocument();
    const changed = commits.mock.calls.length; fireEvent(window, new Event('resize')); expect(commits).toHaveBeenCalledTimes(changed);
  });
});
