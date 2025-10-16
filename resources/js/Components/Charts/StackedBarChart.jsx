import React from "react";
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Legend,
    Brush,
} from "recharts";
import BaseChart from "./baseChart";
import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";

const StackedBarChart = ({
    data,
    isLoading,
    bars,
    visibleBars,
    width = 500,
    height = 300,
    margin,
}) => {
    return (
        <BaseChart data={data} isLoading={isLoading}>
            {({ colors, tooltip }) => (
                <BarChart
                    width={width}
                    height={height}
                    data={data}
                    margin={
                        margin || { top: 20, right: 30, left: 20, bottom: 5 }
                    }
                >
                    {tooltip}
                    <defs>
                        {bars.map((bar, index) => {
                            if (
                                Array.isArray(bar.fill) &&
                                bar.fill.length > 1
                            ) {
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
                                                    (i /
                                                        (bar.fill.length - 1)) *
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
                    <YAxis
                        tickFormatter={(value) => formatAbbreviateNumber(value)}
                    />
                    <Legend />
                    <Brush
                        dataKey="Package_Name"
                        height={20}
                        stroke={colors.baseContent}
                        fill={colors.base300}
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
            )}
        </BaseChart>
    );
};

export default StackedBarChart;
