import { WIP_LOTS } from "@/Constants/colors";

export const getBarConfigs = (metric = "wip", stackId = "a") => {
  return WIP_LOTS.map(({ key, colorVar }) => ({
    visibilityKey: key,
    dataKey: `${key}_total_${metric}`,
    stackId,
    fill: colorVar[metric],
  }));
};

// Optional helpers for clarity
export const summaryWipPLBarswip = getBarConfigs("wip");
export const summaryWipPLBarsLots = getBarConfigs("lots");
