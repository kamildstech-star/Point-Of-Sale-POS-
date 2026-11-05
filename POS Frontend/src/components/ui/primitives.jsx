import { forwardRef } from 'react';
import Icon from '../Icon';

const classes = (...values) => values.filter(Boolean).join(' ');

// JavaScript, native HTML semantics and the existing validation/dialog layers.
// These shadcn-style foundations intentionally do not introduce a second
// component framework, icon system or form state implementation.
export const Button = forwardRef(function Button({ variant = 'primary', size = 'default', type = 'button', className, ...props }, ref) {
  return <button ref={ref} type={type} className={classes('ui-button', className)} data-variant={variant} data-size={size} {...props}/>;
});

export const TextInput = forwardRef(function TextInput({ size = 'default', className, ...props }, ref) {
  return <input ref={ref} className={classes('ui-input', className)} data-size={size} {...props}/>;
});

export const SelectInput = forwardRef(function SelectInput({ size = 'default', className, ...props }, ref) {
  return <select ref={ref} className={classes('ui-select', className)} data-size={size} {...props}/>;
});

export function Card({ as: Component = 'section', className, ...props }) {
  return <Component className={classes('card ui-card bg-card text-card-foreground border-border rounded-lg', className)} {...props}/>;
}

const statusIcons = { neutral: 'info', info: 'info', success: 'success', warning: 'warning', danger: 'error' };
export function Badge({ variant = 'neutral', className, children, ...props }) {
  return <span className={classes('ui-badge', className)} data-variant={variant} {...props}>
    <Icon name={statusIcons[variant] || 'info'} size={14}/>{children}
  </span>;
}

export function Alert({ variant = 'info', className, children, ...props }) {
  return <div className={classes('ui-alert', className)} data-variant={variant} {...props}>
    <Icon name={statusIcons[variant] || 'info'} size={18}/><div>{children}</div>
  </div>;
}

export function Skeleton({ label = 'Loading…', className, ...props }) {
  return <div role="status" aria-label={label} className={classes('ui-skeleton', className)} {...props}/>;
}

export function Table({ density, className, ...props }) {
  return <table className={classes('ui-table', className)} data-density={density} {...props}/>;
}
