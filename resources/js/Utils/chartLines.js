import { FACTORIES_COLOR } from "@/Constants/colors";

export const visibleLines = (options = {}) => {
    const { 
        showQuantities = true, 
        showLots = true,
        showOuts = false,
        showCapacities = false,
        showFactories = { f1: true, f2: true, f3: true, overall: true },
        keyLines = FACTORIES_COLOR
    } = options;

    const result = [];

    keyLines.forEach(({ key, colorVar }) => {
        if (showFactories[key]) {
            if (showQuantities) {
                result.push({
                    dataKey: `${key}_total_quantity`,
                    yAxisId: "left",
                    stroke: colorVar.quantity,
                    strokeWidth: 2,
                    connectNulls: true,
                });
            }
            if (showLots) {
                result.push({
                    dataKey: `${key}_total_lots`,
                    yAxisId: "right",
                    stroke: colorVar.lots,
                    strokeWidth: 2,
                    connectNulls: true,
                    strokeDasharray: "5 6",
                });
            }
            if (showOuts) {
                result.push({
                    dataKey: `${key}_total_outs`,
                    yAxisId: "right",
                    stroke: colorVar.out,
                    strokeWidth: 2,
                    connectNulls: true,
                    strokeDasharray: "10 5",
                    // strokeOpacity: 0.75,
                });
            }
            if (showCapacities) {
                result.push({
                    dataKey: `${key}_capacity`,
                    yAxisId: "right",
                    stroke: colorVar.capacity,
                    strokeWidth: 2,
                    connectNulls: true,
                });
            }
        }
    });

    return result;
};
