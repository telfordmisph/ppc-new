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

const MARGIN = { 
	top: 10, 
	right: 70, 
	left: 150, 
	bottom: 0, 
};
const MIN_CELL_WIDTH = 50;

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

	const toggleLine = (dataKey) => {
			setHiddenLines(prev => {
					const next = new Set(prev);
					next.has(dataKey) ? next.delete(dataKey) : next.add(dataKey);
					return next;
			});
	};

	const visibleLineKeys = useMemo(
			() => lines.filter(line => !hiddenLines.has(line.dataKey)),
			[lines, hiddenLines]
	);

	const [lineProps, setLineProps] = useState(
		lines.reduce(
			(a, { dataKey }) => {
				a[dataKey] = false;
				return a;
			},
			{ hover: null },
		),
	);

	const Y_AXIS_WIDTH = 550 / (data.length || 0);

	const handleLegendMouseEnter = (e) => {
		if (!lineProps[e.dataKey]) {
			setLineProps({ ...lineProps, hover: e.dataKey });
		}
	};

	const handleLegendMouseLeave = (e) => {
		setLineProps({ ...lineProps, hover: null });
	};

	const selectLine = (e) => {
		setLineProps({
			...lineProps,
			[e.dataKey]: !lineProps[e.dataKey],
			hover: null,
		});
	};

	useEffect(() => {
    const el = containerRef.current;
    if (!el) return;
    const ro = new ResizeObserver(([entry]) => {
      setChartWidth(entry.contentRect.width);
    });
    ro.observe(el);
    return () => ro.disconnect();
  }, []);

	const plotLeft  = MARGIN.left  + Y_AXIS_WIDTH;
	const plotRight = MARGIN.right + Y_AXIS_WIDTH;
	const plotWidth = (chartWidth - plotLeft - plotRight);
  
	const colWidth  = plotWidth / data.length;

	const dotSpacing = data.length > 1 ? plotWidth / (data.length - 1) : plotWidth;
	const halfGap    = dotSpacing / 2;
	const N = Math.ceil(MIN_CELL_WIDTH / dotSpacing);

	return (
		<>
		{!(!data || data.length === 0) && (title || subtitle) && !isLoading && (
        <div className="mb-1 text-center">
            {title && <div className="text-sm font-semibold text-base-content">{title}</div>}
            {subtitle && <div className="text-xs text-base-content/50">{subtitle}</div>}
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
								if (state.isTooltipActive) setActiveIndex(state.activeTooltipIndex);
								else setActiveIndex(null);
							}}
							onMouseLeave={() => setActiveIndex(null)}
						>
							<CartesianGrid
								vertical={false}
								stroke={"var(--color-base-content-dim)"}
								strokeDasharray="2 3"
							/>
							<XAxis dataKey={xKey} hide={isXAxisHidden} tickLine={false} axisLine={false} />

							<YAxis
									width={Y_AXIS_WIDTH}
									tickLine={false}
									tickFormatter={leftAxisTickFormatter}
									yAxisId="left"
									axisLine={false}
									label={leftAxisLabel ? {
											value: leftAxisLabel,
											angle: -90,
											position: "insideLeft",
											offset: -5,
											style: { textAnchor: "middle", fill: "var(--color-base-content)", fontSize: 11 },
									} : undefined}
							/>
							<YAxis
									width={Y_AXIS_WIDTH}
									tickFormatter={rightAxisTickFormatter}
									yAxisId="right"
									axisLine={false}
									tickLine={false}
									orientation="right"
									label={rightAxisLabel ? {
											value: rightAxisLabel,
											angle: 90,
											position: "insideRight",
											offset: 5,
											style: { textAnchor: "middle", fill: "var(--color-base-content)", fontSize: 11 },
									} : undefined}
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
									hide={lineProps[line.dataKey] === true || hiddenLines.has(line.dataKey)}
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

		{!(!data || data.length === 0) && !isLoading && (
    <div className="overflow-x-hidden">
        {/* Header row */}
				<div className="flex justify-start mb-1 pr-1">
            <button
                type="button"
                className="btn btn-xs flex items-center cursor-pointer gap-1 text-[0.65rem] opacity-50 hover:opacity-100 transition-opacity"
                onClick={() => {
                    const allHidden = lines.every(line => hiddenLines.has(line.dataKey));
                    setHiddenLines(allHidden
                        ? new Set()
                        : new Set(lines.map(l => l.dataKey))
                    );
                }}
            >
                {lines.every(l => hiddenLines.has(l.dataKey))
                    ? <><LuEyeOff size={12} /> Show all</>
                    : <><LuEye size={12} /> Hide all</>
                }
            </button>
						<button
							type="button"
							className={clsx(
								"btn btn-xs flex items-center cursor-pointer gap-1 text-[0.65rem] transition-opacity",
								showTable ? "opacity-50" : "opacity-100"
							)}
							onClick={() => {setShowTable(!showTable)}}
						>
							<LuTableOfContents size={12} /> {showTable ? "Hide table" : "Show table"}
						</button>
        </div>

        <div className="flex" style={{ paddingLeft: plotLeft }}>
            {data.map((d, i) => (
                <div key={i} style={{ width: colWidth }} className="text-center text-[0.65rem] tracking-widest pb-1.5" />
            ))}
        </div>

        {/* One row per series */}
        {showTable && lines.map((line, li) => (
            <div
                key={line.dataKey}
                className={clsx("flex items-center", { "bg-base-content/5": li % 2 === 0 })}
            >
                {/* Series label */}
                <button
										type="button"
                    className="flex items-center gap-1.5 cursor-pointer hover:bg-base-content/10 shrink-0"
                    style={{ width: plotLeft - halfGap }}
										onClick={() => toggleLine(line.dataKey)}
                >
									{hiddenLines.has(line.dataKey) ? (
										<span>
											<LuEyeOff size={12} />
										</span>
									) : (
										<span
												className="w-2 h-2 rounded-full shrink-0"
												style={{ background: line.stroke }}
										/>
									)}
										<span
                        className="text-base-content text-[0.65rem] tracking-[0.05em] whitespace-nowrap">
                        {legendLabels?.[line.dataKey] ?? line.dataKey.replace(/_/g, " ")}
                    </span>
                </button>

                {data.map((d, i) => {
                    const isActive = i === Number(activeIndex);
                    return (
                        <div
                            key={i}
                            className={clsx(
                                "tabular-nums h-5 text-[10px] text-center transition-colors duration-150 py-[5px]",
                                { "font-extrabold": isActive }
                            )}
                            style={{
                                width: dotSpacing,
                                fontFeatureSettings: '"tnum"',
                                color: isActive ? line.stroke : undefined,
                            }}
                        >
                            {i % N === 0 && d[line.dataKey] != null
                                ? Number(d[line.dataKey] || 0).toLocaleString()
                                : ""}
                        </div>
                    );
                })}
            </div>
        ))}
    </div>
		)}
		</>
	);
});

export default TrendLineChart;
