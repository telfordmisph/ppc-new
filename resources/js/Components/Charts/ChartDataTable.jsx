import clsx from "clsx";
import { memo, useMemo, useState } from "react";
import { LuEye, LuEyeOff, LuTableOfContents } from "react-icons/lu";
import { PatternDef } from "./ChartPatterns";

const ChartDataTable = memo(function ChartDataTable({
	data = [],
	lines = [],
	plotLeft = 0,
	colWidth = 0,
	dotSpacing = null, // line chart uses dotSpacing, bar chart uses colWidth
	activeIndex = null,
	skipN = 1,
	legendLabels = undefined,
	onToggle = undefined,
	hidden = new Set(),
	halfGap = 0,
}) {
	const [showTable, setShowTable] = useState(true);

	const cellWidth = dotSpacing ?? colWidth;

	const toggleLine = (dataKey) => {
		onToggle?.(dataKey);
	};

	const maxValueLength = useMemo(() => {
		return lines.reduce((max, line) => {
			return data.reduce((m, d) => {
				const len =
					d[line.dataKey] != null
						? Number(d[line.dataKey] || 0).toLocaleString().length
						: 0;
				return Math.max(m, len);
			}, max);
		}, 0);
	}, [lines, data]);

	const shouldTilt = cellWidth < maxValueLength * 7; // ~7px per character estimate
	const tiltAngle = shouldTilt ? -45 : 0;

	if (!data.length) {
		return null;
	}

	return (
		<div className="overflow-x-hidden py-5">
			<div className="flex" style={{ paddingLeft: plotLeft }}>
				{data.map((d, i) => (
					<div
						key={i}
						style={{ width: colWidth }}
						className="text-center text-[0.65rem] tracking-widest pb-1.5"
					/>
				))}
			</div>

			{showTable &&
				lines.map((line, li) => (
					<div
						key={line.dataKey}
						className={clsx("flex items-center", {
							"bg-base-content/5": li % 2 === 0,
						})}
					>
						<button
							type="button"
							className="flex items-center gap-1.5 cursor-pointer hover:bg-base-content/10 shrink-0"
							style={{ width: plotLeft - halfGap }}
							onClick={() => toggleLine(line.dataKey)}
						>
							{hidden.has(line.dataKey) ? (
								<span>
									<LuEyeOff size={12} />
								</span>
							) : line.patternId ? (
								<svg width="12" height="12" className="shrink-0">
									<defs>
										<PatternDef
											id={`table_${line.patternId}`}
											pattern={line.pattern}
											fill={line.fill}
										/>
									</defs>
									<rect
										width="12"
										height="12"
										fill={`url(#table_${line.patternId})`}
									/>
								</svg>
							) : (
								<span
									className="w-2 h-2 rounded-full shrink-0"
									style={{ background: line.stroke ?? line.fill }}
								/>
							)}
							<span className="text-base-content text-[0.65rem] tracking-[0.05em] whitespace-nowrap">
								{legendLabels?.[line.dataKey] ??
									line.dataKey.replace(/_/g, " ")}
							</span>
						</button>

						{data.map((d, i) => {
							const isActive = i === Number(activeIndex);
							return (
								<div
									key={i}
									className={clsx(
										"tabular-nums h-5 text-[10px] text-center transition-colors duration-150 py-[5px]",
										{ "font-extrabold": isActive },
										shouldTilt && "flex items-start justify-center",
									)}
									style={{
										width: cellWidth,
										fontFeatureSettings: '"tnum"',
										color: isActive ? (line.stroke ?? line.fill) : undefined,
										height: shouldTilt ? 60 : 20,
									}}
								>
									<span
										style={{
											display: "inline-block",
											transform: `rotate(${tiltAngle}deg)`,
											transformOrigin: "top center",
											whiteSpace: "nowrap",
										}}
									>
										{i % skipN === 0 && d[line.dataKey] != null
											? Number(d[line.dataKey] || 0).toLocaleString()
											: ""}
									</span>
								</div>
							);
						})}
					</div>
				))}

			<div className="flex justify-start mb-1 pr-1">
				<button
					type="button"
					className="btn btn-xs flex items-center cursor-pointer gap-1 text-[0.65rem] opacity-50 hover:opacity-100 transition-opacity"
					onClick={() => {
						const allHidden = lines.every((line) => hidden.has(line.dataKey));
						onToggle?.(
							allHidden ? "__show_all__" : "__hide_all__",
							lines.map((l) => l.dataKey),
						);
					}}
				>
					{lines.every((l) => hidden.has(l.dataKey)) ? (
						<>
							<LuEyeOff size={12} /> Show all
						</>
					) : (
						<>
							<LuEye size={12} /> Hide all
						</>
					)}
				</button>
				<button
					type="button"
					className={clsx(
						"btn btn-xs flex items-center cursor-pointer gap-1 text-[0.65rem] transition-opacity",
						showTable ? "opacity-50" : "opacity-100",
					)}
					onClick={() => setShowTable(!showTable)}
				>
					<LuTableOfContents size={12} />
					{showTable ? "Hide table" : "Show table"}
				</button>
			</div>
		</div>
	);
});

export default ChartDataTable;
