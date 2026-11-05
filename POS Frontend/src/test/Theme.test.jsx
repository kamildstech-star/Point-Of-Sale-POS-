import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ThemeProvider, useTheme } from '../theme/ThemeProvider';
import ThemeToggle from '../theme/ThemeToggle';
import { initializeTheme, themeStorageKey } from '../theme/preference';
import { Input, SaveForm, Select } from '../components/WorkspaceForm';
import { Alert, Badge, Button, Card, Skeleton, Table } from '../components/ui/primitives';

beforeEach(() => { localStorage.clear(); document.documentElement.classList.remove('dark'); });
afterEach(() => { localStorage.clear(); document.documentElement.classList.remove('dark'); delete document.documentElement.dataset.theme; document.documentElement.style.removeProperty('color-scheme'); });

function Choice() {
  const { theme, setTheme } = useTheme();
  return <><output aria-label="Current theme">{theme}</output><ThemeToggle/><button onClick={() => setTheme('invalid')}>Invalid preference</button></>;
}

describe('presentation theme preferences', () => {
  it('starts light for a new user even when the system requests dark', () => {
    vi.stubGlobal('matchMedia', vi.fn(() => ({ matches: true })));
    initializeTheme();
    render(<ThemeProvider userId={1}><Choice/></ThemeProvider>);
    expect(document.documentElement).not.toHaveClass('dark');
    expect(screen.getByLabelText('Current theme')).toHaveTextContent('light');
    expect(screen.getByRole('button', { name: 'Switch to dark theme' })).toHaveAttribute('type', 'button');
    vi.unstubAllGlobals();
  });

  it('toggles with the keyboard and restores the explicit choice on remount', async () => {
    const user = userEvent.setup();
    const view = render(<ThemeProvider userId={7}><Choice/></ThemeProvider>);
    const toggle = screen.getByRole('button', { name: 'Switch to dark theme' });
    toggle.focus(); await user.keyboard(' ');
    expect(document.documentElement).toHaveClass('dark');
    expect(document.documentElement.style.colorScheme).toBe('dark');
    expect(localStorage.getItem(themeStorageKey(7))).toBe('dark');
    expect(screen.getByRole('button', { name: 'Switch to light theme' })).toHaveFocus();
    expect(toggle.querySelector('svg')).toHaveAttribute('data-remix-icon', 'sun');
    view.unmount();
    render(<ThemeProvider userId={7}><Choice/></ThemeProvider>);
    expect(document.documentElement).toHaveClass('dark');
  });

  it('keeps preferences separate when users switch or sign out', async () => {
    const view = render(<ThemeProvider userId={1}><Choice/></ThemeProvider>);
    await userEvent.click(screen.getByRole('button', { name: 'Switch to dark theme' }));
    view.rerender(<ThemeProvider userId={2}><Choice/></ThemeProvider>);
    expect(document.documentElement).not.toHaveClass('dark');
    expect(localStorage.getItem(themeStorageKey(2))).toBeNull();
    view.rerender(<ThemeProvider><Choice/></ThemeProvider>);
    expect(document.documentElement).not.toHaveClass('dark');
    expect(localStorage.getItem(themeStorageKey(null))).toBeNull();
    view.rerender(<ThemeProvider userId={1}><Choice/></ThemeProvider>);
    expect(document.documentElement).toHaveClass('dark');
  });

  it('retains theme and unsaved fields across presentation rerenders', async () => {
    function Presentation({ revision }) {
      return <ThemeProvider userId={1}><Choice/><Input name="reference" label="Reference"/><p>Revision {revision}</p></ThemeProvider>;
    }
    const view = render(<Presentation revision={1}/>);
    await userEvent.type(screen.getByLabelText('Reference'), 'Draft invoice');
    await userEvent.click(screen.getByRole('button', { name: 'Switch to dark theme' }));
    view.rerender(<Presentation revision={2}/>);
    expect(document.documentElement).toHaveClass('dark');
    expect(screen.getByLabelText('Reference')).toHaveValue('Draft invoice');
  });

  it('synchronizes only the active user across tabs and falls back when cleared', () => {
    render(<ThemeProvider userId={4}><Choice/></ThemeProvider>);
    localStorage.setItem(themeStorageKey(5), 'dark');
    fireEvent(window, new StorageEvent('storage', { key: themeStorageKey(5), newValue: 'dark' }));
    expect(document.documentElement).not.toHaveClass('dark');
    localStorage.setItem(themeStorageKey(4), 'dark');
    fireEvent(window, new StorageEvent('storage', { key: themeStorageKey(4), newValue: 'dark' }));
    expect(document.documentElement).toHaveClass('dark');
    localStorage.clear(); fireEvent(window, new StorageEvent('storage', { key: null }));
    expect(document.documentElement).not.toHaveClass('dark');
  });

  it('ignores corrupt/unsupported preferences', async () => {
    localStorage.setItem(themeStorageKey(1), 'system');
    render(<ThemeProvider userId={1}><Choice/></ThemeProvider>);
    await userEvent.click(screen.getByRole('button', { name: 'Invalid preference' }));
    expect(screen.getByLabelText('Current theme')).toHaveTextContent('light');
    expect(localStorage.getItem(themeStorageKey(1))).toBe('system');
  });

  it('works in memory when browser storage is unavailable', async () => {
    vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('Blocked'); });
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('Blocked'); });
    initializeTheme();
    render(<ThemeProvider userId={1}><Choice/></ThemeProvider>);
    await userEvent.click(screen.getByRole('button', { name: 'Switch to dark theme' }));
    expect(document.documentElement).toHaveClass('dark');
    await userEvent.click(screen.getByRole('button', { name: 'Switch to light theme' }));
    expect(document.documentElement).not.toHaveClass('dark');
  });

  it('does not reset fields or submit a form when switching themes', async () => {
    const saved = vi.fn();
    render(<ThemeProvider userId={1}><SaveForm onSave={saved} label="Save draft">
      <Input name="name" label="Product name"/>
      <Input name="default_price" label="Catalog price" inputMode="decimal"/>
      <Select name="unit" label="Unit" options={[{ id: 1, name: 'Each' }]}/>
      <ThemeToggle/>
    </SaveForm></ThemeProvider>);
    const user = userEvent.setup();
    await user.type(screen.getByLabelText('Product name'), 'School shirt');
    await user.type(screen.getByLabelText('Catalog price'), '12.3456');
    await user.selectOptions(screen.getByLabelText('Unit'), '1');
    await user.click(screen.getByRole('button', { name: 'Switch to dark theme' }));
    expect(saved).not.toHaveBeenCalled();
    expect(screen.getByLabelText('Product name')).toHaveValue('School shirt');
    expect(screen.getByLabelText('Catalog price')).toHaveValue('12.3456');
    await user.click(screen.getByRole('button', { name: 'Save draft' }));
    await waitFor(() => expect(saved).toHaveBeenCalledTimes(1));
    const data = saved.mock.calls[0][0];
    expect(Object.fromEntries(data)).toEqual({ name: 'School shirt', default_price: '12.3456', unit: '1' });
  });
});

describe('JavaScript UI foundations', () => {
  it('retains native semantics, labels, disabled controls and non-color status cues', async () => {
    const action = vi.fn();
    render(<Card aria-label="Stock status"><Badge variant="warning">Low stock</Badge>
      <Alert variant="danger" role="alert">Cannot save this record.</Alert>
      <Skeleton label="Loading inventory"/>
      <Button disabled onClick={action}>Post adjustment</Button>
      <Table><caption>Available stock</caption><thead><tr><th scope="col">SKU</th></tr></thead><tbody><tr><td>SHIRT-1</td></tr></tbody></Table>
    </Card>);
    expect(screen.getByRole('region', { name: 'Stock status' })).toBeInTheDocument();
    expect(screen.getByText('Low stock').querySelector('svg')).toHaveAttribute('data-remix-icon', 'warning');
    expect(screen.getByRole('alert')).toHaveTextContent('Cannot save');
    expect(screen.getByRole('status', { name: 'Loading inventory' })).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'SKU' })).toHaveAttribute('scope', 'col');
    await userEvent.click(screen.getByRole('button', { name: 'Post adjustment' }));
    expect(action).not.toHaveBeenCalled();
  });
});
