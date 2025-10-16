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

const toggleButtons = [
    {
        key: "f1",
        label: "F1",
        activeClass: "bg-primary border-primary text-white",
        inactiveClass: "border-primary text-primary hover:bg-primary-content",
    },
    {
        key: "f2",
        label: "F2",
        activeClass: "bg-accent border-accent text-white",
        inactiveClass: "border-accent text-accent hover:bg-accent-content",
    },
    {
        key: "f3",
        label: "F3",
        activeClass: "bg-secondary border-secondary text-white",
        inactiveClass:
            "border-secondary text-secondary hover:bg-secondary-content",
    },
];

const BarChart = ({ data, isLoading }) => {
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
        return Object.values(data).sort(
            (a, b) => new Date(a.date) - new Date(b.date)
        );
    }, [data]);

    return (
        <>
            <div className="mb-4 space-x-2">
                <TogglerButton
                    toggleButtons={toggleButtons}
                    visibleBars={visibleBars}
                    toggleBar={toggleBar}
                    toggleAll={toggleAll}
                />
            </div>

            <BaseChart data={data} isLoading={isLoading}>
                {({ colors, tooltip }) => (
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
                        {visibleBars.f1 && (
                            <Bar
                                yAxisId="left"
                                dataKey="f1"
                                fill={colors.primary}
                            />
                        )}
                        {visibleBars.f2 && (
                            <Bar
                                yAxisId="left"
                                dataKey="f2"
                                fill={colors.secondary}
                            />
                        )}
                        {visibleBars.f3 && (
                            <Bar
                                yAxisId="left"
                                dataKey="f3"
                                fill={colors.accent}
                            />
                        )}
                        <Line
                            yAxisId="right"
                            type="monotone"
                            dataKey="trend"
                            stroke={colors.baseContent}
                            strokeWidth={2}
                            dot={{ r: 4 }}
                            activeDot={{ r: 6 }}
                        />
                        <Brush
                            dataKey="date"
                            height={20}
                            stroke={colors.baseContent}
                            fill={colors.base300}
                        />
                    </ReBarChart>
                )}
            </BaseChart>
        </>
    );
};

export default BarChart;
