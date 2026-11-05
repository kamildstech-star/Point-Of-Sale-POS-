// Each request owns a completion handle. One completed/canceled request must
// never hide another request's progress or decrement the counter twice.
export function createLoadingActivity() {
  const active = new Set();
  const listeners = new Set();
  const publish = () => listeners.forEach(listener => listener());
  return {
    getSnapshot: () => active.size,
    subscribe(listener) { listeners.add(listener); return () => listeners.delete(listener); },
    begin() {
      const token = Symbol('request');
      active.add(token); publish();
      return () => { if (active.delete(token)) publish(); };
    },
  };
}

export const loadingActivity = createLoadingActivity();
