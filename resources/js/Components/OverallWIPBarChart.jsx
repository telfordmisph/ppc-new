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

    const accentColor =
        getComputedStyle(document.documentElement)
            .getPropertyValue("--accent") // DaisyUI accent variable
            .trim() || "#f43f5e"; // fallback

    return (
        <div className="flex flex-col w-full h-full p-6 rounded-lg shadow-md bg-base-200">
            <div className="mb-4 space-x-2">
                <button
                    onClick={() => toggleBar("f1")}
                    className="px-3 py-1 border rounded"
                >
                    F1 {visibleBars.f1 ? "✔" : "❌"}
                </button>
                <button
                    onClick={() => toggleBar("f2")}
                    className="px-3 py-1 border rounded"
                >
                    F2 {visibleBars.f2 ? "✔" : "❌"}
                </button>
                <button
                    onClick={() => toggleBar("f3")}
                    className="px-3 py-1 border rounded"
                >
                    F3 {visibleBars.f3 ? "✔" : "❌"}
                </button>
                <button
                    onClick={toggleAll}
                    className="px-3 py-1 border rounded"
                >
                    Toggle All
                </button>
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
