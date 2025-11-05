import React, { useState, useMemo } from "react";
import {
    BarChart as ReBarChart,
    Bar,
    Brush,
    XAxis,
    YAxis,
    CartesianGrid,
    Legend,
    Line,
} from "recharts";
import TogglerButton from "../TogglerButton";
import BaseChart from "./BaseChart";
import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import { TOGGLE_BUTTONS } from "@/Constants/toggleButtons";

const BarChart = ({ data, isLoading, windowSize, setWindowSize }) => {
    console.log("🚀 ~ BarChart ~ data:", data);
    const [visibleBars, setVisibleBars] = useState({
        f1: true,
        f2: true,
        f3: true,
    });

    const toggleBar = (key) => {
        setVisibleBars((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    const toggleAll = () => {
        const allVisible = Object.values(visibleBars).every(Boolean);
        setVisibleBars({
            f1: !allVisible,
            f2: !allVisible,
            f3: !allVisible,
        });
    };

    const chartData = useMemo(() => {
        const sorted = Object.values(data).sort(
            (a, b) => new Date(a.date) - new Date(b.date)
        );

        if (!windowSize || windowSize <= 1) return sorted;

        // Compute moving average
        return sorted.map((item, index, arr) => {
            const start = Math.max(0, index - windowSize + 1);
            const subset = arr.slice(start, index + 1);
            const avgTrend =
                subset.reduce((sum, cur) => sum + (cur.trend || 0), 0) /
                subset.length;

            return { ...item, trendSmoothed: avgTrend };
        });
    }, [data, windowSize]);

    return (
        <div className="w-full flex flex-col gap-4 h-full">
            <div className="items-center justify-between block w-full lg:space-x-2 lg:flex">
                <div className="flex items-center content-center gap-2">
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
                </div>

                <div className="mt-4 lg:mt-0">
                    <TogglerButton
                        toggleButtons={TOGGLE_BUTTONS}
                        visibleBars={visibleBars}
                        toggleBar={toggleBar}
                        toggleAll={toggleAll}
                    />
                </div>
            </div>

            <BaseChart data={data} isLoading={isLoading}>
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
                            tickFormatter={(value) =>
                                formatAbbreviateNumber(value)
                            }
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
                            className="hidden"
                            fill={"var(--color-neutral-content)"}
                        />

                        {visibleBars.f1 && (
                            <Bar
                                yAxisId="left"
                                dataKey="f1"
                                fill={"var(--color-f1color)"}
                            />
                        )}
                        {visibleBars.f2 && (
                            <Bar
                                yAxisId="left"
                                dataKey="f2"
                                fill={"var(--color-f2color)"}
                            />
                        )}
                        {visibleBars.f3 && (
                            <Bar
                                yAxisId="left"
                                dataKey="f3"
                                fill={"var(--color-f3color)"}
                            />
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
                        <Brush
                            dataKey="date"
                            height={20}
                            stroke={"var(--color-base-content)"}
                            fill={"var(--color-base-300)"}
                        />
                    </ReBarChart>
                )}
            </BaseChart>
        </div>
    );
};

export default BarChart;
