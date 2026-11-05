import { useEffect, useRef, useState } from 'react';

export const LOADING_DELAY_MS = 180;
export const LOADING_MIN_VISIBLE_MS = 300;

export function useDelayedBusy(busy, { delay = LOADING_DELAY_MS, minimum = LOADING_MIN_VISIBLE_MS } = {}) {
  const [visible, setVisible] = useState(false);
  const shownAt = useRef(0);
  useEffect(() => {
    let timer;
    if (busy && !visible) {
      timer = setTimeout(() => { shownAt.current = Date.now(); setVisible(true); }, delay);
    } else if (!busy && visible) {
      timer = setTimeout(() => setVisible(false), Math.max(0, minimum - (Date.now() - shownAt.current)));
    }
    return () => clearTimeout(timer);
  }, [Boolean(busy), visible, delay, minimum]);
  return visible;
}
