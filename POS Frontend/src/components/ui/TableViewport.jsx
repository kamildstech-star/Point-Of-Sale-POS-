import { useEffect, useId, useRef, useState } from 'react';

// A scrollable table remains reachable with the keyboard. Critical columns stay
// present; a measured hint explains horizontal overflow instead of hiding data.
export default function TableViewport({ label = 'Records', className = 'table-scroll', stickyHeader = false, children, ...props }) {
  const viewport = useRef(null), hintId = useId();
  const [overflow, setOverflow] = useState({ horizontal: false, vertical: false });
  useEffect(() => {
    const node = viewport.current;
    const update = () => {
      const horizontal = node.scrollWidth > node.clientWidth + 1, vertical = node.scrollHeight > node.clientHeight + 1;
      setOverflow(previous => previous.horizontal === horizontal && previous.vertical === vertical ? previous : { horizontal, vertical });
    };
    update();
    const observer = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(update) : null;
    observer?.observe(node);
    if (node.firstElementChild) observer?.observe(node.firstElementChild);
    window.addEventListener('resize', update);
    return () => { observer?.disconnect(); window.removeEventListener('resize', update); };
  }, [children]);
  return <>
    {(overflow.horizontal || overflow.vertical) && <p className="table-scroll-hint" id={hintId}>{overflow.horizontal ? 'Scroll horizontally to view all columns.' : 'Scroll within the table to view more rows.'}{overflow.horizontal && overflow.vertical ? ' Scroll within the table for more rows.' : ''}</p>}
    <div {...props} className={`${className}${stickyHeader ? ' table-viewport-sticky' : ''}`} ref={viewport} role="region" aria-label={label} tabIndex={overflow.horizontal || overflow.vertical ? 0 : undefined} aria-describedby={overflow.horizontal || overflow.vertical ? hintId : undefined}>{children}</div>
  </>;
}
