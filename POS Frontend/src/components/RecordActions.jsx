import Modal from './Modal';
import { SaveForm } from './WorkspaceForm';
import Icon from './Icon';

export function ActionButton({ action, label, title = label, onClick, disabled = false }) {
  return <button type="button" className={`secondary icon-action ${action === 'delete' ? 'danger-action' : ''}`} aria-label={label} title={title} onClick={onClick} disabled={disabled}>
    <Icon name={action === 'delete' ? 'delete' : 'edit'} />
  </button>;
}

export default function RecordActions({ name, onEdit, onDelete, editLabel = 'Edit', deleteLabel = 'Delete', deletingDisabled = false, deleteHint }) {
  return <span className="record-actions">
    {onEdit && <ActionButton action="edit" label={editLabel} title={`${editLabel}: ${name}`} onClick={onEdit} />}
    {onDelete && <ActionButton action="delete" label={deleteLabel} title={deleteHint || `${deleteLabel}: ${name}`} onClick={onDelete} disabled={deletingDisabled} />}
  </span>;
}

export function DeleteDialog({ name, title = 'Delete record', question = 'Delete', description, confirmLabel = 'Delete record', onDelete, onClose }) {
  return <Modal title={title} size="small" onClose={onClose}>
    <SaveForm className="delete-confirmation" label={confirmLabel} onCancel={onClose} onSave={async () => { await onDelete(); onClose(); }}>
      <p>{question} <strong>{name}</strong>?</p><p className="muted">{description}</p>
    </SaveForm>
  </Modal>;
}
