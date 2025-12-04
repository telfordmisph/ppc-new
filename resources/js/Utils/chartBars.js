import { WIP_LOTS } from "@/Constants/colors";

export const getBarConfigs = (metric = "quantity", stackId = "a") => {
  return WIP_LOTS.map(({ key, colorVar }) => ({
    visibilityKey: key,
    dataKey: `${key}_total_${metric}`,
    stackId,
    fill: colorVar[metric],
  }));
};

// Optional helpers for clarity
export const summaryWipPLBarsQuantity = getBarConfigs("quantity");
export const summaryWipPLBarsLots = getBarConfigs("lots");
