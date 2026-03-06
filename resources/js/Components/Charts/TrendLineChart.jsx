import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import { memo, useState } from "react";
import {
	CartesianGrid,
	Legend,
	Line,
	LineChart as ReLineChart,
	XAxis,
	YAxis,
} from "recharts";
import BaseChart from "./BaseChart";

const TrendLineChart = memo(function TrendLineChart({
	data,
	xKey = "name",
	isLoading = false,
	errorMessage = null,
	syncId = null,
	lines = [],
	height = 300,
	leftAxisLabel = null,
	rightAxisLabel = null,
	leftAxisTickFormatter = (value) => formatAbbreviateNumber(value),
	rightAxisTickFormatter = (value) => formatAbbreviateNumber(value),
}) {
	const [lineProps, setLineProps] = useState(
		lines.reduce(
			(a, { dataKey }) => {
				a[dataKey] = false;
				return a;
			},
			{ hover: null },
		),
	);

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

	return (
		<div style={{ width: "100%", height }}>
			<BaseChart data={data} isLoading={isLoading} error={errorMessage}>
				{({ tooltip }) => (
					<ReLineChart
						data={data}
						margin={{
							top: 20,
							right: 20,
							left: 20,
							bottom: 20,
						}}
						syncId={syncId}
					>
						<CartesianGrid
							stroke={"var(--color-base-content-dim)"}
							strokeDasharray="2 3"
						/>
						<XAxis dataKey={xKey} />

						<YAxis
								tickFormatter={leftAxisTickFormatter}
								yAxisId="left"
								label={leftAxisLabel ? {
										value: leftAxisLabel,
										angle: -90,
										position: "insideLeft",
										offset: -5,
										style: { textAnchor: "middle", fill: "var(--color-base-content)", fontSize: 11 },
								} : undefined}
						/>
						<YAxis
								tickFormatter={rightAxisTickFormatter}
								yAxisId="right"
								orientation="right"
								label={rightAxisLabel ? {
										value: rightAxisLabel,
										angle: 90,
										position: "insideRight",
										offset: 5,
										style: { textAnchor: "middle", fill: "var(--color-base-content)", fontSize: 11 },
								} : undefined}
						/>

						<Legend
							wrapperStyle={{ cursor: "pointer" }}
							onClick={selectLine}
							onMouseOver={handleLegendMouseEnter}
							onMouseOut={handleLegendMouseLeave}
						/>

						{tooltip}
						{lines.map((line, index) => (
							<Line
								key={index}
								hide={lineProps[line.dataKey] === true}
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
	);
});

export default TrendLineChart;
