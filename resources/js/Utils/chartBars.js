import { FACTORY_COLORS } from "@/Constants/colors";

export const getBarConfigs = (metric = "wip", stackId = "a") => {
	return FACTORY_COLORS.map(({ key, colorVar }) => ({
		visibilityKey: key,
		dataKey: `${key}_total_${metric}`,
		stackId,
		fill: colorVar[metric],
	}));
};

// Optional helpers for clarity
export const summaryWipPLBarswip = getBarConfigs("wip");
console.log("🚀 ~ summaryWipPLBarswip:", summaryWipPLBarswip);
export const summaryOutPLBars = getBarConfigs("out");
export const summaryLotsPLBars = getBarConfigs("lots");
console.log("🚀 ~ summaryLotsPLBars:", summaryLotsPLBars);
