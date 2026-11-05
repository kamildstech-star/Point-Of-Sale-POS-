import { useLayoutEffect, useState, useSyncExternalStore } from 'react';
import { createPortal } from 'react-dom';
import { loadingActivity } from '../loading/activity';
import { registerLoadingHost } from '../loading/host';
import { useDelayedBusy } from '../loading/useDelayedBusy';

export default function GlobalLoader() {
  const pending = useSyncExternalStore(loadingActivity.subscribe, loadingActivity.getSnapshot, () => 0);
  const visible = useDelayedBusy(pending > 0);
  const [host] = useState(() => {
    const element = document.createElement('div');
    element.id = 'pos-loader-root';
    return element;
  });
  useLayoutEffect(() => registerLoadingHost(host), [host]);
  return createPortal(visible ? <div className="global-loader" role="progressbar"
    aria-label="Loading data" data-pending={pending > 0 ? 'true' : 'false'}>
    <span className="global-loader__bar"/>
  </div> : null, host);
}
