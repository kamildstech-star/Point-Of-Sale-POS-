import { formatUpdatedAt } from './format';

export default function PageHeader({ eyebrow, title, description, actions, updatedAt, timezone, context }) {
  const updated = formatUpdatedAt(updatedAt, timezone);
  return <header className="dashboard-page-header">
    <div>{eyebrow && <p className="eyebrow">{eyebrow}</p>}<h1>{title}</h1>
      {description && <p className="muted">{description}</p>}
      {context}
      {updated && <p className="dashboard-metadata">Updated {updated} · {timezone}</p>}
    </div>{actions && <div className="dashboard-header-actions">{actions}</div>}
  </header>;
}
