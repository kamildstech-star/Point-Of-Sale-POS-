import { createContext, useContext } from 'react';

// Context also reaches body portals, where CSS inheritance from the shell stops.
const DensityContext = createContext('compact');
export const useDensity = () => useContext(DensityContext);
export default function DensityProvider({ value = 'compact', children }) {
  return <DensityContext.Provider value={value}>{children}</DensityContext.Provider>;
}
