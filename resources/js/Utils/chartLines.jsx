import { WIP_OUT_CAPACITY } from "@/Constants/colors";

const SIZE_OPACITY = { big: 1, medium: 0.95, small: 1 };
const SIZE_PATTERNS = {
	big: "dots-big",
	medium: "diagonal_stripes",
	small: "dots-small",
};

export const visibleLines = (options = {}) => {
	const {
		showQuantities = true,
		showLots = true,
		showPLWip = false,
		showPLOut = false,
		showOuts = false,
		showCapacities = false,
		showPL6 = true,
		showPL1 = true,
		showBuckets = false,
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

			if (showBuckets) {
				const sizes = ["big", "medium", "small"];
				const metrics = [
					showQuantities && "total_wip",
					showOuts && "total_outs",
					showLots && "total_lots",
				].filter(Boolean);

				if (showPLWip || showPLOut) {
					["pl1", "pl6"]
						.filter((pl) => (pl === "pl1" ? showPL1 : showPL6))
						.forEach((pl) => {
							metrics.forEach((metric) => {
								sizes.forEach((size) => {
									result.push({
										dataKey: `${key}_${pl}_${size}_${metric}`,
										stackId: `${key}_${pl}_${metric}_stack`,
										fill: colorVar[pl],
										fillOpacity: SIZE_OPACITY[size],
										yAxisId: metric === "total_lots" ? "right" : "left",
										pattern: SIZE_PATTERNS[size],
										patternId:
											SIZE_PATTERNS[size] !== "solid"
												? `pat_${key}_${pl}_${size}_${metric}`
												: null,
										visibilityKey: `${key}_${pl}_${size}_${metric}`,
									});
								});
							});
						});
				} else {
					metrics.forEach((metric) => {
						sizes.forEach((size) => {
							result.push({
								dataKey: `${key}_${size}_${metric}`,
								stackId: `${key}_${metric}_stack`,
								fill: colorVar[metric],
								yAxisId: metric === "total_lots" ? "right" : "left",
								fillOpacity: SIZE_OPACITY[size],
								pattern: SIZE_PATTERNS[size],
								patternId:
									SIZE_PATTERNS[size] !== "solid"
										? `pat_${key}_${size}_${metric}`
										: null,
								visibilityKey: `${key}_${size}_${metric}`,
							});
						});
					});
				}
			}

			if (!showBuckets && (showPLWip || showPLOut)) {
				["pl1", "pl6"].forEach((pl) => {
					if (showPLWip) {
						result.push({
							dataKey: `${key}_${pl}_total_wip`,
							yAxisId: "left",
							stroke: colorVar[pl] ?? colorVar.wip,
							fill: colorVar.wip,
							legendType: "circle",
							dot: { r: 3 },
							activeDot: { r: 5 },
							connectNulls: true,
						});
					}

					if (showPLOut) {
						result.push({
							dataKey: `${key}_${pl}_total_outs`,
							yAxisId: "right",
							stroke: colorVar[pl] ?? colorVar.out,
							fill: colorVar[pl] ?? colorVar.out,
							strokeDasharray: pl === "pl6" ? "2 5" : "6 3",
							strokeOpacity: 0.75,
							legendType: "triangle",
							dot: (props) => {
								const { cx, cy, fill } = props;
								const size = 8;
								return (
									<svg
										x={cx - size / 2}
										y={cy - size / 2}
										width={size}
										height={size}
										viewBox="0 0 10 10"
										xmlns="http://www.w3.org/2000/svg"
									>
										<polygon points="5,0 10,10 0,10" fill={fill} />
									</svg>
								);
							},
							activeDot: (props) => {
								const { cx, cy, fill } = props;
								return (
									<svg
										key={`${cx}-${cy}`}
										x={cx - 6}
										y={cy - 6}
										width={12}
										height={12}
										viewBox="0 0 8 8"
									>
										<polygon points="4,0 8,8 0,8" fill={fill} />
									</svg>
								);
							},
							connectNulls: true,
						});
					}
				});
			}

			if (!showBuckets && showQuantities) {
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
			if (!showBuckets && showLots) {
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
			if (!showBuckets && showOuts) {
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
