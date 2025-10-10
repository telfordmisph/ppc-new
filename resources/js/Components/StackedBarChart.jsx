import React from "react";
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    Brush,
    ResponsiveContainer,
} from "recharts";

const StackedBarChart = ({
    data,
    bars,
    visibleBars,
    width = 500,
    height = 300,
    margin,
}) => {
    console.log("data", data);
    console.log("visibility bars", visibleBars);

    const isDark = localStorage.getItem("theme") === "dark";

    const brushStroke = isDark ? "#8884d8" : "#333";
    const brushFill = isDark ? "#222" : "#eee";

    return (
        <ResponsiveContainer width="100%" height="100%">
            <BarChart
                width={width}
                height={height}
                data={data}
                margin={margin || { top: 20, right: 30, left: 20, bottom: 5 }}
            >
                <defs>
                    {bars.map((bar, index) => {
                        if (Array.isArray(bar.fill) && bar.fill.length > 1) {
                            return (
                                <linearGradient
                                    key={index}
                                    id={`grad-${bar.dataKey}`}
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    {bar.fill.map((color, i) => (
                                        <stop
                                            key={i}
                                            offset={`${
                                                (i / (bar.fill.length - 1)) *
                                                100
                                            }%`}
                                            stopColor={color}
                                            stopOpacity={0.9}
                                        />
                                    ))}
                                </linearGradient>
                            );
                        }
                        return null;
                    })}
                </defs>

                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="Package_Name" />
                <YAxis />
                <Tooltip
                    labelStyle={{
                        color: isDark ? "#aaa" : "#333",
                    }}
                />
                <Legend />
                <Brush
                    dataKey="Package_Name"
                    height={30}
                    stroke={brushStroke}
                    fill={brushFill}
                />
                {bars.map((bar, index) =>
                    visibleBars?.[bar.visibilityKey] ? (
                        <Bar
                            key={index}
                            dataKey={bar.dataKey}
                            stackId={bar.stackId || null}
                            fill={
                                Array.isArray(bar.fill)
                                    ? `url(#grad-${bar.dataKey})`
                                    : bar.fill
                            }
                        />
                    ) : null
                )}
            </BarChart>
        </ResponsiveContainer>
    );
};

export default StackedBarChart;
