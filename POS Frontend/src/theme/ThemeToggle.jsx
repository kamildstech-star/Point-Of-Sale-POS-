import Icon from '../components/Icon';
import { Button } from '../components/ui/primitives';
import { useTheme } from './ThemeProvider';

export default function ThemeToggle() {
  const { theme, setTheme } = useTheme();
  const dark = theme === 'dark';
  const label = dark ? 'Switch to light theme' : 'Switch to dark theme';
  return <Button variant="secondary" size="icon" className="theme-toggle"
    aria-label={label} title={label}
    onClick={() => setTheme(dark ? 'light' : 'dark')}>
    <Icon name={dark ? 'sun' : 'moon'} size={20}/>
  </Button>;
}
