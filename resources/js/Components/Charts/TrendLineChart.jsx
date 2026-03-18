import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import clsx from "clsx";
import { memo, useEffect, useMemo, useRef, useState } from "react";
import { LuEye, LuEyeOff, LuTableOfContents } from "react-icons/lu";
import {
	CartesianGrid,
	Legend,
	Line,
	LineChart as ReLineChart,
	XAxis,
	YAxis,
} from "recharts";
import BaseChart from "./BaseChart";
import ChartDataTable from "./ChartDataTable";

const MARGIN = {
	top: 10,
	right: 70,
	left: 150,
	bottom: 0,
};
const MIN_CELL_WIDTH = 40;

const TrendLineChart = memo(function TrendLineChart({
	data,
	xKey = "name",
	isXAxisHidden = false,
	isLoading = false,
	errorMessage = null,
	syncId = null,
	lines = [],
	legendLabels = undefined,
	height = 200,
	leftAxisLabel = null,
	rightAxisLabel = null,
	title = null,
	subtitle = null,
	leftAxisTickFormatter = (value) => formatAbbreviateNumber(value),
	rightAxisTickFormatter = (value) => formatAbbreviateNumber(value),
}) {
	const containerRef = useRef(null);
	const [chartWidth, setChartWidth] = useState(0);
	const [activeIndex, setActiveIndex] = useState(null);
	const [hiddenLines, setHiddenLines] = useState(new Set());
	const [showTable, setShowTable] = useState(true);

	const [lineProps, setLineProps] = useState(
		lines.reduce(
			(a, { dataKey }) => {
				a[dataKey] = false;
				return a;
			},
			{ hover: null },
		),
	);

	const Y_AXIS_WIDTH = 550 / (data?.length + 1 || 0);

	useEffect(() => {
		const el = containerRef.current;
		if (!el) return;
		const ro = new ResizeObserver(([entry]) => {
			setChartWidth(entry.contentRect.width);
		});
		ro.observe(el);
		return () => ro.disconnect();
	}, []);

	const plotLeft = MARGIN.left + Y_AXIS_WIDTH;
	const plotRight = MARGIN.right + Y_AXIS_WIDTH;
	const plotWidth = chartWidth - plotLeft - plotRight;

	const colWidth = plotWidth / data?.length;

	const isOneXAxis = data?.length === 1;

	const dotSpacing = isOneXAxis ? plotWidth : plotWidth / (data?.length - 1);
	// const dotSpacing = plotWidth / data?.length;
	const halfGap = isOneXAxis ? 0 : dotSpacing / 2;
	// const halfGap = 0;
	const N = Math.ceil(MIN_CELL_WIDTH / dotSpacing);

	const handleToggleLine = (dataKey, allKeys) => {
		if (dataKey === "__show_all__") {
			setHiddenLines(new Set());
			return;
		}
		if (dataKey === "__hide_all__") {
			setHiddenLines(new Set(allKeys));
			return;
		}
		setHiddenLines((prev) => {
			const next = new Set(prev);
			next.has(dataKey) ? next.delete(dataKey) : next.add(dataKey);
			return next;
		});
	};

	return (
		<>
			{!(!data || data?.length === 0) && (title || subtitle) && !isLoading && (
				<div className="mb-1 text-center">
					{title && (
						<div className="text-sm font-semibold text-base-content">
							{title}
						</div>
					)}
					{subtitle && (
						<div className="text-xs text-base-content/50">{subtitle}</div>
					)}
				</div>
			)}
			<div ref={containerRef} style={{ width: "100%", height }}>
				<BaseChart data={data} isLoading={isLoading} error={errorMessage}>
					{({ tooltip }) => (
						<ReLineChart
							data={data}
							margin={MARGIN}
							syncId={syncId}
							onMouseMove={(state) => {
								if (state.isTooltipActive)
									setActiveIndex(state.activeTooltipIndex);
								else setActiveIndex(null);
							}}
							onMouseLeave={() => setActiveIndex(null)}
						>
							<CartesianGrid
								vertical={false}
								stroke={"var(--color-base-content-dim)"}
								strokeDasharray="2 3"
							/>
							<XAxis
								dataKey={xKey}
								hide={isXAxisHidden}
								tickLine={false}
								axisLine={false}
							/>

							<YAxis
								width={Y_AXIS_WIDTH}
								tickLine={false}
								tickFormatter={leftAxisTickFormatter}
								yAxisId="left"
								axisLine={false}
								label={
									leftAxisLabel
										? {
												value: leftAxisLabel,
												angle: -90,
												position: "insideLeft",
												offset: -5,
												style: {
													textAnchor: "middle",
													fill: "var(--color-base-content)",
													fontSize: 11,
												},
											}
										: undefined
								}
							/>
							<YAxis
								width={Y_AXIS_WIDTH}
								tickFormatter={rightAxisTickFormatter}
								yAxisId="right"
								axisLine={false}
								tickLine={false}
								orientation="right"
								label={
									rightAxisLabel
										? {
												value: rightAxisLabel,
												angle: 90,
												position: "insideRight",
												offset: 5,
												style: {
													textAnchor: "middle",
													fill: "var(--color-base-content)",
													fontSize: 11,
												},
											}
										: undefined
								}
							/>

							{/* <Legend
								wrapperStyle={{ cursor: "pointer" }}
								onClick={selectLine}
								onMouseOver={handleLegendMouseEnter}
								onMouseOut={handleLegendMouseLeave}
								formatter={legendLabels
									? (value) => legendLabels[value] ?? value.replace(/_/g, " ")
									: (value) => value.replace(/_/g, " ")
								}
							/> */}

							{tooltip}
							{lines.map((line, index) => (
								<Line
									key={index}
									hide={
										lineProps[line.dataKey] === true ||
										hiddenLines.has(line.dataKey)
									}
									animationDuration={1500}
									tickFormatter={(value) =>
										// formatAbbreviateNumber(value)
										22
									}
									activeDot={{ r: 5 }}
									animationEasing="cubic-bezier(0, 0, 0, 1)"
									opacity={Number(
										lineProps.hover === line.dataKey || !lineProps.hover
											? 1
											: 0.1,
									)}
									{...line}
								/>
							))}
						</ReLineChart>
					)}
				</BaseChart>
			</div>

			<ChartDataTable
				data={data}
				lines={lines}
				plotLeft={plotLeft}
				skipN={N}
				colWidth={colWidth}
				dotSpacing={dotSpacing}
				activeIndex={activeIndex}
				legendLabels={legendLabels}
				onToggle={handleToggleLine}
				hidden={hiddenLines}
				halfGap={halfGap}
			/>
		</>
	);
});

export default TrendLineChart;
