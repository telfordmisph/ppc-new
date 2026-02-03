import { memo, useCallback, useMemo, useState } from "react";
import {
	Bar,
	CartesianGrid,
	Legend,
	Line,
	BarChart as ReBarChart,
	XAxis,
	YAxis,
} from "recharts";
import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import BaseChart from "./BaseChart";

const BarChart = memo(function BarChart({
	data,
	isLoading,
	errorMessage,
	windowSize,
}) {
	const [visibleBars, setVisibleBars] = useState({
		f1: true,
		f2: true,
		f3: true,
	});

	const toggleBar = useCallback((key) => {
		setVisibleBars((prev) => {
			if (prev[key] === undefined) return prev;
			const newState = { ...prev, [key]: !prev[key] };
			if (newState[key] === prev[key]) return prev;
			return newState;
		});
	}, []);

	const toggleAll = useCallback(() => {
		setVisibleBars((prev) => {
			const allVisible = Object.values(prev).every(Boolean);
			const newState = {
				f1: !allVisible,
				f2: !allVisible,
				f3: !allVisible,
			};
			if (Object.keys(prev).every((k) => prev[k] === newState[k])) {
				return prev;
			}
			return newState;
		});
	}, []);

	const chartData = useMemo(() => {
		const sorted = Object.values(data).sort(
			(a, b) => new Date(a.date) - new Date(b.date),
		);

		if (!windowSize || windowSize <= 1) return sorted;

		// Compute moving average
		return sorted.map((item, index, arr) => {
			const start = Math.max(0, index - windowSize + 1);
			const subset = arr.slice(start, index + 1);
			const avgTrend =
				subset.reduce((sum, cur) => sum + (cur.trend || 0), 0) / subset.length;

			return { ...item, trendSmoothed: avgTrend };
		});
	}, [data, windowSize]);

	return (
		<div className="w-full flex flex-col gap-4 h-full">
			<div className="items-center justify-between block w-full lg:space-x-2 lg:flex">
				{/* <div className="flex items-center content-center gap-2">
                    <legend className="">Smooth</legend>
                    <input
                        type="number"
                        className="input validator"
                        required
                        placeholder="Type a number between 1 to 10"
                        value={windowSize}
                        onChange={(e) => setWindowSize(e.target.value)}
                        min="1"
                        max={Object.keys(data).length}
                        title="Must be between be 1 to 10"
                    />
                </div> */}

				{/* <div className="mt-4 lg:mt-0"> */}
				{/* <TogglerButton
                    toggleButtons={TOGGLE_FACTORY_BUTTONS}
                    visibleBars={visibleBars}
                    toggleBar={toggleBar}
                    toggleAll={toggleAll}
                /> */}
				{/* </div> */}
			</div>

			<BaseChart data={data} isLoading={isLoading} error={errorMessage}>
				{({ tooltip }) => (
					<ReBarChart
						width={700}
						height={300}
						data={chartData}
						margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
					>
						<CartesianGrid strokeDasharray="2 8" />
						<XAxis dataKey="date" />
						<YAxis
							yAxisId="left"
							tickFormatter={(value) => formatAbbreviateNumber(value)}
						/>
						<YAxis
							yAxisId="right"
							orientation="right"
							tickFormatter={(v) => `${v.toFixed(1)}%`}
						/>
						{tooltip}
						<Legend />

						<Bar
							dataKey="total"
							hide
							// className="hidden"
							fill={"var(--color-neutral-content)"}
						/>

						{visibleBars.f1 && (
							<Bar yAxisId="left" dataKey="f1" fill={"var(--color-f1color)"} />
						)}
						{visibleBars.f2 && (
							<Bar yAxisId="left" dataKey="f2" fill={"var(--color-f2color)"} />
						)}
						{visibleBars.f3 && (
							<Bar yAxisId="left" dataKey="f3" fill={"var(--color-f3color)"} />
						)}
						<Line
							yAxisId="right"
							type="basis"
							dataKey={windowSize > 1 ? "trendSmoothed" : "trend"}
							stroke={"var(--color-accent)"}
							strokeWidth={2}
							dot={{ r: 4 }}
							activeDot={{ r: 12 }}
						/>
						{/* <Brush
                            dataKey="date"
                            height={20}
                            stroke={"var(--color-base-content)"}
                            fill={"var(--color-base-300)"}
                        /> */}
					</ReBarChart>
				)}
			</BaseChart>
		</div>
	);
});

export default BarChart;
