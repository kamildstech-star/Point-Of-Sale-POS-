import { loadingActivity } from './activity';

const trackedAdapters = new WeakSet();

// Wrap the actual transport rather than relying on error.config: a custom
// adapter can reject with a plain Error, and a pre-canceled request may never
// reach the transport. finally also covers network errors and aborts.
export function trackLoadingAdapter(adapter) {
  if (trackedAdapters.has(adapter)) return adapter;
  const tracked = async config => {
    const silent = config.globalLoader === false || /\/sanctum\/csrf-cookie(?:[?#]|$)/.test(config.url || '');
    const finish = silent ? () => {} : loadingActivity.begin();
    try { return await adapter(config); }
    finally { finish(); }
  };
  trackedAdapters.add(tracked);
  return tracked;
}
