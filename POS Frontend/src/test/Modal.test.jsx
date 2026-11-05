import { act, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { FormDialog } from '../components/Modal';
import { FormDetails, Input, SaveForm } from '../components/WorkspaceForm';

function Editor({ onSave = vi.fn().mockResolvedValue({}) }) {
  return <><button>Outside action</button><FormDialog title="Add record" trigger="Add record">
    <SaveForm onSave={onSave}><Input name="name" label="Record name" value="Sample"/></SaveForm>
  </FormDialog></>;
}

describe('popup form behavior', () => {
  it('lets the keyboard expand optional fields and continue through the dialog', async () => {
    const user = userEvent.setup();
    render(<FormDialog title="Details editor" trigger="Open details"><SaveForm onSave={vi.fn()}><Input name="name" label="Name" /><FormDetails title="Optional information"><Input name="note" label="Note" required={false} /></FormDetails></SaveForm></FormDialog>);
    await user.click(screen.getByRole('button', { name: 'Open details' }));
    await user.tab();
    const summary = screen.getByText('Optional information');
    expect(summary).toHaveFocus();
    // jsdom does not synthesize the browser's Enter activation for summary.
    await user.click(summary);
    expect(summary.closest('details').open).toBe(true);
    await user.tab(); expect(screen.getByLabelText('Note')).toHaveFocus();
    await user.tab(); expect(screen.getByRole('button', { name: 'Save changes' })).toHaveFocus();
  });
  it('isolates the background, focuses the form and restores focus and scrolling on Escape', async () => {
    const user = userEvent.setup(); const { container } = render(<Editor/>);
    const trigger = screen.getByRole('button', { name: 'Add record' });
    expect(screen.queryByLabelText('Record name')).not.toBeInTheDocument();
    await user.click(trigger);
    expect(screen.getByRole('dialog', { name: 'Add record' })).toHaveAttribute('aria-modal', 'true');
    expect(screen.getByLabelText('Record name')).toHaveFocus();
    expect(container).toHaveAttribute('inert'); expect(container).toHaveAttribute('aria-hidden', 'true');
    expect(document.body.style.overflow).toBe('hidden');
    await user.keyboard('{Escape}');
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    expect(container).not.toHaveAttribute('inert'); expect(container).not.toHaveAttribute('aria-hidden');
    expect(document.body.style.overflow).toBe(''); expect(trigger).toHaveFocus();
  });

  it('keeps keyboard focus inside the popup and cancels without saving', async () => {
    const onSave = vi.fn(); const user = userEvent.setup(); render(<Editor onSave={onSave}/>);
    await user.click(screen.getByRole('button', { name: 'Add record' }));
    const close = screen.getByRole('button', { name: 'Close dialog' });
    const cancel = screen.getByRole('button', { name: 'Cancel' });
    close.focus(); await user.tab({ shift: true }); expect(cancel).toHaveFocus();
    await user.tab(); expect(close).toHaveFocus();
    await user.click(cancel); expect(onSave).not.toHaveBeenCalled();
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('retains entered data and validation errors until a successful retry', async () => {
    const onSave = vi.fn().mockRejectedValueOnce({ response: { data: { message: 'Name is already used.', errors: { name: ['Name is already used.'] } } } }).mockResolvedValueOnce({});
    const user = userEvent.setup(); render(<Editor onSave={onSave}/>);
    await user.click(screen.getByRole('button', { name: 'Add record' }));
    await user.click(screen.getByRole('button', { name: 'Save changes' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('Name is already used.');
    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByLabelText('Record name')).toHaveValue('Sample');
    await user.clear(screen.getByLabelText('Record name')); await user.type(screen.getByLabelText('Record name'), 'Different');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(onSave.mock.calls[1][0].get('name')).toBe('Different');
  });

  it('blocks close and duplicate submission during an in-flight save and captures form values', async () => {
    let finish; const onSave = vi.fn(() => new Promise(resolve => { finish = resolve; }));
    const user = userEvent.setup(); render(<Editor onSave={onSave}/>);
    await user.click(screen.getByRole('button', { name: 'Add record' }));
    const form = screen.getByLabelText('Record name').closest('form');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));
    expect(screen.getByRole('button', { name: 'Close dialog' })).toBeDisabled();
    await user.keyboard('{Escape}'); fireEvent.submit(form);
    expect(screen.getByRole('dialog')).toBeInTheDocument(); expect(onSave).toHaveBeenCalledTimes(1);
    expect(onSave.mock.calls[0][0].get('name')).toBe('Sample');
    await act(async () => { finish({}); });
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
  });

  it('restores the previous popup when a nested dialog closes', async () => {
    const user = userEvent.setup(); const { container } = render(<FormDialog title="Outer" trigger="Open outer">
      <FormDialog title="Inner" trigger="Open inner"><Input name="nested" label="Nested field"/></FormDialog>
    </FormDialog>);
    await user.click(screen.getByRole('button', { name: 'Open outer' }));
    const innerTrigger = screen.getByRole('button', { name: 'Open inner' }); await user.click(innerTrigger);
    expect(screen.getAllByRole('dialog', { hidden: true })).toHaveLength(2);
    expect(screen.getByRole('dialog', { name: 'Inner' })).toBeInTheDocument();
    await user.keyboard('{Escape}');
    expect(screen.getByRole('dialog', { name: 'Outer' })).toBeInTheDocument(); expect(innerTrigger).toHaveFocus();
    expect(container).toHaveAttribute('inert'); expect(document.body.style.overflow).toBe('hidden');
    await user.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Close dialog' }));
    expect(container).not.toHaveAttribute('inert'); expect(document.body.style.overflow).toBe('');
  });
});
