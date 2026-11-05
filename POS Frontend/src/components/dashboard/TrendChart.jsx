import TableViewport from '../ui/TableViewport';
import { useEffect, useId, useRef, useState } from 'react';
import EmptyState from './EmptyState';
import { formatMoney, formatNumber } from './format';

// A small SVG chart avoids a new dependency. Callers supply server-computed
// series. Numbers below position marks only; the data table uses original strings.
export default function TrendChart({ data, title, unit, currency, locale, kind = 'line' }) {
  const id = useId();
  const figure = useRef(null);
  const [width, setWidth] = useState(640);
  useEffect(() => {
    const node = figure.current;
    if (!node) return;
    const measure = () => { if (node.clientWidth) setWidth(Math.max(260, Math.floor(node.clientWidth))); };
    measure();
    const observer = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(measure);
    observer?.observe(node); window.addEventListener('resize', measure);
    return () => { observer?.disconnect(); window.removeEventListener('resize', measure); };
  }, [data]);
  if (!Array.isArray(data)) return <EmptyState title="Chart unavailable" description="No verified data source has been supplied."/>;
  if (!data.length) return <EmptyState/>;
  const number = value => value !== null && value !== undefined && /^-?\d+(?:\.\d+)?$/.test(String(value)) && Number.isFinite(Number(value)) ? Number(value) : null;
  const values = data.map(point => number(point.value));
  const known = values.filter(value => value !== null);
  if (!known.length) return <EmptyState title="Chart unavailable" description="Values for this series are unavailable."/>;
  const low = Math.min(0, ...known), high = Math.max(0, ...known);
  const range = high - low || 1;
  const right = width - 26, plotWidth = right - 54;
  const x = index => data.length === 1 ? 54 + plotWidth / 2 : 54 + index * (plotWidth / (data.length - 1));
  const y = value => 196 - (value - low) / range * 164;
  const zero = y(0);
  let continuing = false;
  const path = values.map((value, index) => {
    if (value === null) { continuing = false; return ''; }
    const command = continuing ? 'L' : 'M'; continuing = true;
    return `${command}${x(index)},${y(value)}`;
  }).join(' ');
  const display = value => currency ? formatMoney(value, currency, locale) : formatNumber(value, locale);
  const tick = value => {
    try { return new Intl.NumberFormat(locale, { notation: 'compact', maximumFractionDigits: 1 }).format(value); }
    catch { return formatNumber(value) ?? String(value); }
  };
  const ticks = [high, (high + low) / 2, low].filter((value, index, list) => list.indexOf(value) === index);
  return <figure className="trend-chart" ref={figure}>
    <svg viewBox={`0 0 ${width} 236`} role="img" aria-labelledby={`${id}-title ${id}-description`}>
      <title id={`${id}-title`}>{title}</title><desc id={`${id}-description`}>{unit ? `Values in ${unit}. ` : ''}Exact values are available in the data table below. Missing values are not treated as zero.</desc>
      {ticks.map(value => <g key={value}><line x1="54" x2={right} y1={y(value)} y2={y(value)} className="trend-chart__grid"/><text x="44" y={y(value) + 4} textAnchor="end">{tick(value)}</text></g>)}
      {kind === 'bar' ? values.map((value, index) => value === null ? null : <rect key={index} x={x(index) - Math.min(22, 180 / data.length) / 2} y={Math.min(zero, y(value))} width={Math.min(22, 180 / data.length)} height={Math.max(1, Math.abs(zero - y(value)))} className="trend-chart__bar"/>)
        : <><path d={path} className="trend-chart__line"/>{values.map((value, index) => value === null ? null : <circle key={index} cx={x(index)} cy={y(value)} r="4" className="trend-chart__point"/>)}</>}
      {[...new Set([0, Math.floor((data.length - 1) / 2), data.length - 1])].map(index => <text key={index} x={x(index)} y="222" textAnchor={index === 0 ? 'start' : index === data.length - 1 ? 'end' : 'middle'}>{data[index].label}</text>)}
    </svg>
    <details><summary>View chart data</summary><TableViewport className="table-scroll"><table><caption>{title}{unit && ` (${unit})`}</caption><thead><tr><th scope="col">Period</th><th scope="col">Value</th></tr></thead>
      <tbody>{data.map((point, index) => <tr key={index}><th scope="row">{point.label}</th><td>{display(point.value) ?? 'Unavailable'}</td></tr>)}</tbody></table></TableViewport></details>
  </figure>;
}
