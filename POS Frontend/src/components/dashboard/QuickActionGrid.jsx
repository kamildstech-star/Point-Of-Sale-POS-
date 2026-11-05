import { Link } from 'react-router-dom';
import Icon from '../Icon';

export default function QuickActionGrid({ title = 'Quick actions', actions = [], permissions = [] }) {
  const allowed = actions.filter(action => (!action.permission || permissions.includes(action.permission))
    && (!action.permissions || action.permissions.every(permission => permissions.includes(permission)))
    && (action.to || typeof action.onClick === 'function'));
  if (!allowed.length) return null;
  return <section className="quick-actions"><h2>{title}</h2><div className="quick-action-grid">{allowed.map(action => {
    const contents = <><span className="tile-icon"><Icon name={action.icon || 'forward'}/></span><strong>{action.label}</strong>{action.description && <span>{action.description}</span>}</>;
    return action.to ? <Link key={action.label} to={action.to} className="quick-action">{contents}</Link>
      : <button key={action.label} type="button" className="quick-action" onClick={action.onClick}>{contents}</button>;
  })}</div></section>;
}
