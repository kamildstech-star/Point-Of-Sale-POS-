import { Link } from 'react-router-dom';
import Icon from '../Icon';

export default function EmptyState({ title = 'No data for this period', description, icon = 'inbox', action }) {
  return <div className="dashboard-empty-state">
    <span className="tile-icon"><Icon name={icon}/></span><h3>{title}</h3>{description && <p>{description}</p>}
    {action?.to && <Link className="button-link" to={action.to}>{action.label}</Link>}
  </div>;
}
