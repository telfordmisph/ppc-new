import React, { useState, useMemo } from "react";
import {
    BarChart as ReBarChart,
    Bar,
    Rectangle,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
    Line,
} from "recharts";
import TogglerButton from "./TogglerButton";

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

const BarChart = ({ data }) => {
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

    const isDark = localStorage.getItem("theme") === "dark";

    const chartData = useMemo(() => {
        return Object.values(data).sort(
            (a, b) => new Date(a.date) - new Date(b.date)
        );
    }, [data]);

    return (
        <div className="flex flex-col w-full h-full p-6 rounded-lg shadow-md bg-base-200">
            <div className="mb-4 space-x-2">
                <TogglerButton
                    toggleButtons={toggleButtons}
                    visibleBars={visibleBars}
                    toggleBar={toggleBar}
                    toggleAll={toggleAll}
                />
            </div>

            <ResponsiveContainer width="100%">
                <ReBarChart
                    width={700}
                    height={300}
                    data={chartData}
                    margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
                >
                    <CartesianGrid strokeDasharray="10 2" />
                    <XAxis dataKey="date" />
                    <YAxis yAxisId="left" />
                    <YAxis
                        yAxisId="right"
                        orientation="right"
                        tickFormatter={(v) => `${v.toFixed(1)}%`}
                    />
                    <Tooltip
                        labelStyle={{
                            color: isDark ? "#aaa" : "#333",
                        }}
                    />
                    <Legend />
                    {visibleBars.f1 && (
                        <Bar
                            yAxisId="left"
                            dataKey="f1"
                            fill="#422ad5"
                            activeBar={<Rectangle fill="pink" stroke="blue" />}
                        />
                    )}
                    {visibleBars.f2 && (
                        <Bar
                            yAxisId="left"
                            dataKey="f2"
                            fill="#00d3bb"
                            activeBar={
                                <Rectangle fill="gold" stroke="purple" />
                            }
                        />
                    )}
                    {visibleBars.f3 && (
                        <Bar
                            yAxisId="left"
                            dataKey="f3"
                            fill="#f43098"
                            activeBar={<Rectangle fill="gold" stroke="pink" />}
                        />
                    )}
                    <Line
                        yAxisId="right"
                        type="monotone"
                        dataKey="trend"
                        stroke="rgba(0, 255, 255)"
                        strokeWidth={1}
                        dot={{ r: 4 }}
                        activeDot={{ r: 6 }}
                    />
                </ReBarChart>
            </ResponsiveContainer>
        </div>
    );
};

export default BarChart;
