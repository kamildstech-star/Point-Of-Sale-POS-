import { icons } from '../icons/catalog';

// Icons are decorative. The surrounding button/link keeps its visible text
// or accessible label, including icon-only controls such as edit and close.
export default function Icon({ name, size = 18, className = '', spin = false }) {
  const Symbol = icons[name];
  if (!Symbol) throw new Error(`Unknown application icon: ${name}`);
  return <Symbol size={size} className={`remix-icon${spin ? ' remix-icon-spin' : ''}${className ? ` ${className}` : ''}`}
    aria-hidden="true" focusable="false" data-remix-icon={name} />;
}
