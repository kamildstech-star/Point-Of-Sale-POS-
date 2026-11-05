// Only mount with a verified allowed-branch list and a consumer of the filter.
// This component never grants branch access or persists a global POS branch.
export default function BranchSwitcher({ branches = [], value = '', onChange, includeAll = false, disabled = false }) {
  if (typeof onChange !== 'function') return null;
  return <label className="dashboard-branch-filter">Branch<select aria-label="Branch" value={value} disabled={disabled || !branches.length} onChange={event => {
    const selected = event.target.value;
    if ((includeAll && selected === '') || branches.some(branch => String(branch.id) === selected)) onChange(selected);
  }}>
    <option value="" disabled={!includeAll}>{includeAll ? 'All permitted branches' : 'Choose a branch'}</option>
    {branches.map(branch => <option key={branch.id} value={branch.id}>{branch.name}</option>)}
  </select></label>;
}
