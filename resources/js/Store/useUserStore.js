import { create } from "zustand";

const useUserStore = create((set) => ({
	empData: null,

	setUser: (data) => set({ empData: data }),

	clearUserData: () => set({ empData: null }),

	getJobTitle: () => {
		const empRoles = get().empData?.emp_jobtitle;
		return empRoles;
	},
}));

export default useUserStore;
