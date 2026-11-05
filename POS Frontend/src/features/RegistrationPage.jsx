import Icon from '../components/Icon';
import { Link, Navigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthProvider';
import { Input, SaveForm } from '../components/WorkspaceForm';
import { BusinessFields } from './BusinessPage';

export default function RegistrationPage() {
  const { user, register } = useAuth();
  if (user) return <Navigate to="/workspace" replace/>;
  return <main className="registration card"><Link to="/login"><Icon name="back" /> Sign in</Link><h1>Start your retail workspace</h1><p className="muted">Create your owner account and first business.</p><SaveForm label="Create account and business" onSave={form => register(Object.fromEntries(form))}><Input name="name" label="Your name"/><Input name="email" label="Email address" type="email"/><Input name="password" label="Password" type="password" minLength={12} autoComplete="new-password"/><Input name="password_confirmation" label="Confirm password" type="password" minLength={12} autoComplete="new-password"/><BusinessFields registration/></SaveForm></main>;
}
