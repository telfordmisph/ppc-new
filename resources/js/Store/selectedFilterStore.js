import { debounce } from "lodash";
import { create } from "zustand";

export const useSelectedFilteredStore = create((set, get) => {
	const storageKeys = {
		packageNames: "selectedPackageNames",
		packageName: "selectedPackageName",
		workWeeks: "selectedWorkWeeks",
		lookBack: "selectedLookBackPeriod",
		period: "selectedPeriod",
		factory: "selectedFactory",
		offset: "selectedOffset",
		startDate: "selectedStartDate",
		endDate: "selectedEndDate",
	};

	const getJSON = (key, fallback) => {
		try {
			const raw = localStorage.getItem(key);
			return raw ? JSON.parse(raw) : fallback;
		} catch {
			return fallback;
		}
	};

	const setJSON = (key, value) => {
		localStorage.setItem(key, JSON.stringify(value));
	};

	const getNumber = (key, defaultValue) => {
		const val = localStorage.getItem(key);
		const num = Number(val);
		return Number.isNaN(num) ? defaultValue : num;
	};

	const debouncedSet = {};
	const createDebouncedSetter = (key, delay = 500) => {
		debouncedSet[key] = debounce((value) => setJSON(key, value), delay);
	};

	createDebouncedSetter(storageKeys.packageNames);
	createDebouncedSetter(storageKeys.workWeeks);
	createDebouncedSetter(storageKeys.lookBack);
	createDebouncedSetter(storageKeys.offset);

	return {
		packageNames: getJSON(storageKeys.packageNames, []),
		packageName: localStorage.getItem(storageKeys.packageName) || "",
		workWeeks: getJSON(storageKeys.workWeeks, []),
		lookBack: getNumber(storageKeys.lookBack, 4),
		period: localStorage.getItem(storageKeys.period) || "weekly",
		factory: localStorage.getItem(storageKeys.factory) || "Overall",
		offset: getNumber(storageKeys.offset, 0),
		startDate: localStorage.getItem(storageKeys.startDate)
			? new Date(localStorage.getItem(storageKeys.startDate))
			: new Date(),
		endDate: localStorage.getItem(storageKeys.endDate)
			? new Date(localStorage.getItem(storageKeys.endDate))
			: new Date(),

		setSelectedPackageNames: (packageNames) => {
			set({ packageNames });
			debouncedSet[storageKeys.packageNames](packageNames);
		},

		setSelectedPackageName: (packageName) => {
			set({ packageName });
			localStorage.setItem(storageKeys.packageName, packageName);
		},

		setSelectedWorkWeeks: (workWeeks) => {
			set({ workWeeks });
			debouncedSet[storageKeys.workWeeks](workWeeks);
		},

		setSelectedLookBack: (lookBack) => {
			set({ lookBack });
			debouncedSet[storageKeys.lookBack](lookBack);
		},

		setSelectedPeriod: (period) => {
			set({ period });
			localStorage.setItem(storageKeys.period, period);
		},

		setSelectedFactory: (factory) => {
			set({ factory });
			localStorage.setItem(storageKeys.factory, factory);
		},

		setSelectedOffset: (offset) => {
			set({ offset });
			debouncedSet[storageKeys.offset](offset);
		},

		setSelectedStartDate: (startDate) => {
			set({ startDate });
			localStorage.setItem(storageKeys.startDate, startDate);
		},

		setSelectedEndDate: (endDate) => {
			set({ endDate });
			localStorage.setItem(storageKeys.endDate, endDate);
		},

		flushDebounced: () => {
			Object.values(debouncedSet).forEach((fn) => fn.flush && fn.flush());
		},
	};
});
