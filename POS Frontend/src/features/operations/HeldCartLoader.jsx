import { lazy, Suspense } from 'react';
import LoadingState from '../../components/LoadingState';

const Controls = lazy(() => import('./HeldCartControls'));
export default function HeldCartLoader(props) {
  return <Suspense fallback={<LoadingState busy label="Opening saved cart tools…" variant="inline"/>}><Controls {...props}/></Suspense>;
}
