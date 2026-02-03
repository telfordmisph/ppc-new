import { WIP_OUT_CAPACITY } from "@/Constants/colors";

export const visibleLines = (options = {}) => {
	const {
		showQuantities = true,
		showLots = true,
		showOuts = false,
		showCapacities = false,
		showUtilization = false,
		showFactories = { f1: true, f2: true, f3: true, overall: true },
		keyLines = WIP_OUT_CAPACITY,
	} = options;

	const result = [];

	keyLines.forEach(({ key, colorVar, strokeWidth, r, className }) => {
		if (showFactories[key]) {
			// TODO have better distinction of overall lines
			// TODO jan 1 huh?

			if (showUtilization) {
				result.push({
					dataKey: `${key}_utilization`,
					yAxisId: "right",
					stroke: "var(--color-utilization)",
					fill: "var(--color-utilization)",
					strokeWidth: 2,
					className: "",
					r: r,
					connectNulls: true,
				});
			}

			if (showQuantities) {
				result.push({
					dataKey: `${key}_total_wip`,
					yAxisId: "left",
					stroke: colorVar.wip,
					fill: colorVar.wip,
					strokeWidth: strokeWidth,
					className: className,
					r: r,
					connectNulls: true,
				});
			}
			if (showLots) {
				result.push({
					dataKey: `${key}_total_lots`,
					yAxisId: "right",
					stroke: colorVar.lots,
					fill: colorVar.lots,
					strokeWidth: strokeWidth,
					className: className,
					r: r,
					connectNulls: true,
					strokeDasharray: "5 6",
				});
			}
			if (showOuts) {
				result.push({
					dataKey: `${key}_total_outs`,
					yAxisId: "left",
					stroke: colorVar.out,
					fill: colorVar.out,
					strokeWidth: strokeWidth,
					className: className,
					r: r,
					connectNulls: true,
					// strokeOpacity: 0.75,
				});
			}
			if (showCapacities) {
				result.push({
					strokeDasharray: "70 5",
					dataKey: `${key}_capacity`,
					yAxisId: "left",
					stroke: colorVar.capacity,
					fill: colorVar.capacity,
					strokeWidth: strokeWidth - 5,
					className: `${className} opacity-50 drop-shadow-sm drop-shadow-accent`,
					r: 0,
					// 'basis' | 'basisClosed' | 'basisOpen' | 'bumpX' | 'bumpY' | 'bump' | 'linear' | 'linearClosed' | 'natural' | 'monotoneX' | 'monotoneY' | 'monotone' | 'step' | 'stepBefore' | 'stepAfter' | CurveFactory;
					type: "step",
					connectNulls: true,
				});
			}
		}
	});

	return result;
};
