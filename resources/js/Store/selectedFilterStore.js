import { create } from "zustand";

export const useSelectedFilteredStore = create((set) => {
  const packageStorageName = "selectedPackage";
  const lookBackStorageName = "selectedLookBackPeriod";
  const periodStorageName = "selectedPeriod";
  const factoryStorageName = "selectedFactory";
  const offsetStorageName = "selectedOffset";

  const getNumber = (key, defaultValue) => {
    const val = localStorage.getItem(key);
    const num = Number(val);
    return isNaN(num) ? defaultValue : num;
  };

  return {
    packageName: localStorage.getItem(packageStorageName) || "",
    lookBack: getNumber(lookBackStorageName, 4),
    period: localStorage.getItem(periodStorageName) || "weekly",
    factory: localStorage.getItem(factoryStorageName) || "All",
    offset: getNumber(offsetStorageName, 0),
  
    setSelectedPackage: (packageName) => {
      localStorage.setItem(packageStorageName, packageName);
      set({ packageName: packageName });
    },

    setSelectedLookBack: (lookBack) => {
      localStorage.setItem(lookBackStorageName, lookBack);
      set({ lookBack: lookBack });
    },

    setSelectedPeriod: (period) => {
      localStorage.setItem(periodStorageName, period);
      set({ period: period });
    },

    setSelectedFactory: (factory) => {
      localStorage.setItem(factoryStorageName, factory);
      set({ factory: factory });
    },

    setSelectedOffset: (offset) => {
      localStorage.setItem(offsetStorageName, offset);
      set({ offset: offset });
    }
  }
});
