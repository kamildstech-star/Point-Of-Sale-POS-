import { Input, SaveForm } from '../WorkspaceForm';

export default function DateRangeFilter({ value = {}, timezone, onApply, label = 'Apply period' }) {
  if (typeof onApply !== 'function') return null;
  return <section className="dashboard-date-filter" aria-label="Reporting period">
    <SaveForm key={`${value.from || ''}:${value.to || ''}`} label={label} validate={form => (Date.parse(form.get('to')) - Date.parse(form.get('from'))) / 86400000 >= 366 ? { to: 'Choose at most 366 calendar days, including both dates.' } : {}}
      onSave={form => onApply({ from: form.get('from'), to: form.get('to') })}>
      <div className="date-filter-fields"><Input type="date" name="from" label="From date" value={value.from}/><Input type="date" name="to" label="Through date" value={value.to}/></div>
    </SaveForm>{timezone && <p className="dashboard-metadata">Dates use {timezone}.</p>}
  </section>;
}
