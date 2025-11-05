import { FACTORIES_COLOR } from "@/Constants/colors";

export const visibleLines = (options) => {
    const { showQuantities = true, showLots = true, showFactories = { f1: true, f2: true, f3: true } } = options;

    const result = [];

    FACTORIES_COLOR.forEach(({ key, colorVar }) => {
        if (showFactories[key]) {
            if (showQuantities) {
                result.push({
                    dataKey: `${key}_total_quantity`,
                    yAxisId: "left",
                    stroke: colorVar,
                    strokeWidth: 3,
                    connectNulls: true,
                });
            }
            if (showLots) {
                result.push({
                    dataKey: `${key}_total_lots`,
                    yAxisId: "right",
                    stroke: colorVar,
                    strokeWidth: 3,
                    connectNulls: true,
                    strokeDasharray: "5 6",
                });
            }
        }
    });

    return result;
};
