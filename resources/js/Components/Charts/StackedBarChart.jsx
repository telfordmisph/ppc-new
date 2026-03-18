import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import { memo, useEffect, useRef, useState } from "react";
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from "recharts";
import BaseChart from "./baseChart";
import ChartDataTable from "./ChartDataTable";
import { BarPatternDefs } from "./ChartPatterns";
import HoveredBar from "./HoverBar";

const HoverableBar = memo(({ bar, isHidden, visible, onBarClick, yAxisId }) => {
	if (!visible) return null;
	if (!visible) return null;

	return (
		<Bar
			key={bar.dataKey}
			radius={4}
			unit={10}
			hide={isHidden}
			dataKey={bar.dataKey}
			stackId={bar.stackId || null}
			yAxisId={yAxisId}
			fillOpacity={bar.fillOpacity ?? 1}
			fill={bar.patternId ? `url(#${bar.patternId})` : bar.fill}
			// TODO this causes unnecessary re-renders, but have beautiful hover effects
			shape={(props) => (
				<HoveredBar
					barProps={{ ...props, patternId: bar.patternId }}
					onClick={({ data, dataKey }) => onBarClick?.({ data, dataKey })}
				/>
			)}
		/>
	);
});

const MARGIN = {
	top: 0,
	right: 70,
	left: 150,
	bottom: 0,
};

const StackedBarChart = memo(function StackedBarChart({
	data = [],
	isLoading,
	errorMessage,
	bars,
	syncId = null,
	visibleBars,
	onBarClick = () => {},
	xAxisDataKey = "Package_Name",
	defaultAngle = null,
	yAxisWidth = 50,
	width = 500,
	height = 300,
	margin,
	children,
}) {
	const totalBarCount = data?.length || 0;
	const fontSize = totalBarCount > 25 ? 10 : 14;
	const angle = defaultAngle || totalBarCount > 25 ? -45 : 0;
	const [activeIndex, setActiveIndex] = useState(null);
	const containerRef = useRef(null);
	const [chartWidth, setChartWidth] = useState(0);
	const [hiddenBars, setHiddenBars] = useState(new Set());

	const Y_AXIS_WIDTH = yAxisWidth / (data?.length + 1 || 0);

	const [barProps, setBarProps] = useState(
		bars.reduce(
			(a, { dataKey }) => {
				a[dataKey] = false;
				return a;
			},
			{ hover: null },
		),
	);

	useEffect(() => {
		const el = containerRef.current;
		if (!el) return;
		const ro = new ResizeObserver(([entry]) =>
			setChartWidth(entry.contentRect.width),
		);
		ro.observe(el);
		return () => ro.disconnect();
	}, []);

	const plotLeft = MARGIN?.left + Y_AXIS_WIDTH;
	const plotRight = MARGIN?.right + Y_AXIS_WIDTH;

	const plotWidth = chartWidth - plotLeft - plotRight;
	const colWidth = plotWidth / (data?.length || 1);

	const handleToggleLine = (dataKey, allKeys) => {
		if (dataKey === "__show_all__") {
			setHiddenBars(new Set());
			return;
		}
		if (dataKey === "__hide_all__") {
			setHiddenBars(new Set(allKeys));
			return;
		}
		setHiddenBars((prev) => {
			const next = new Set(prev);
			next.has(dataKey) ? next.delete(dataKey) : next.add(dataKey);
			return next;
		});
	};

	return (
		<div className="flex flex-col w-full">
			<div ref={containerRef} style={{ width: "100%", height }}>
				<BaseChart data={data} isLoading={isLoading} error={errorMessage}>
					{({ tooltip }) => (
						<BarChart
							// width={chartWidth}
							data={data}
							margin={MARGIN}
							height={height}
							syncId={syncId}
							barCategoryGap="3%"
							onMouseMove={(state) => {
								if (state.isTooltipActive)
									setActiveIndex(state.activeTooltipIndex);
								else setActiveIndex(null);
							}}
							onMouseLeave={() => setActiveIndex(null)}
							// margin={margin || { top: 20, right: 20, left: 20, bottom: 20 }}
						>
							<defs>
								<BarPatternDefs bars={bars} />
							</defs>

							{tooltip}

							<CartesianGrid
								stroke={"var(--color-base-content-dim)"}
								strokeDasharray="2 3"
							/>
							<XAxis
								dataKey={xAxisDataKey}
								tick={{
									fontSize: fontSize,
								}}
								angle={angle}
								textAnchor="end"
								interval={0}
								height={80}
							/>
							<YAxis
								width={Y_AXIS_WIDTH}
								yAxisId="left"
								tickFormatter={(value) => formatAbbreviateNumber(value)}
							/>
							<YAxis
								width={Y_AXIS_WIDTH}
								yAxisId="right"
								orientation="right"
								tickFormatter={(value) => formatAbbreviateNumber(value)}
							/>
							{/* <Legend /> */}
							{bars.map((bar) => (
								<HoverableBar
									key={bar.dataKey}
									bar={bar}
									isHidden={
										barProps[bar.dataKey] === true ||
										hiddenBars.has(bar.dataKey)
									}
									visible={visibleBars?.[bar.visibilityKey]}
									onBarClick={onBarClick}
									yAxisId={bar.yAxisId || "left"}
								/>
							))}
							{children}
						</BarChart>
					)}
				</BaseChart>
			</div>

			<ChartDataTable
				data={data}
				lines={bars.filter((b) => visibleBars?.[b.visibilityKey])}
				plotLeft={plotLeft}
				colWidth={colWidth}
				dotSpacing={null}
				activeIndex={activeIndex}
				onToggle={handleToggleLine}
				hidden={hiddenBars}
				halfGap={0}
			/>
		</div>
	);
});
StackedBarChart.name = "StackedBarChart";
export default StackedBarChart;
