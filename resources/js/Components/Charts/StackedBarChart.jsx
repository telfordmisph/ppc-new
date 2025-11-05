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
import HoveredBar from "./HoverBar";

const StackedBarChart = ({
    data,
    isLoading,
    errorMessage,
    bars,
    visibleBars,
    onBarClick = () => {},
    width = 500,
    height = 300,
    margin,
}) => {
    return (
        <BaseChart data={data} isLoading={isLoading} error={errorMessage}>
            {({ tooltip }) => (
                <BarChart
                    width={width}
                    height={height}
                    data={data}
                    barCategoryGap="3%"
                    margin={
                        margin || { top: 20, right: 20, left: 20, bottom: 20 }
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

                    <CartesianGrid
                        stroke={"var(--color-base-content-dim)"}
                        strokeDasharray="2 3"
                    />
                    <XAxis
                        dataKey="Package_Name"
                        tick={{ fontSize: 10 }}
                        angle={-45}
                        textAnchor="end"
                        interval={0}
                        height={80}
                    />
                    <YAxis
                        tickFormatter={(value) => formatAbbreviateNumber(value)}
                    />
                    <Legend />
                    {/* <Brush
                        dataKey="Package_Name"
                        height={20}
                        stroke={colors.baseContent}
                        fill={colors.base300}
                    /> */}
                    <Bar
                        dataKey="total_quantity"
                        hide
                        className="hidden"
                        fill={"var(--color-neutral-content)"}
                    />
                    {bars.map((bar, index) =>
                        visibleBars?.[bar.visibilityKey] ? (
                            <Bar
                                key={index}
                                radius={4}
                                dataKey={bar.dataKey}
                                stackId={bar.stackId || null}
                                fill={
                                    Array.isArray(bar.fill)
                                        ? `url(#grad-${bar.dataKey})`
                                        : bar.fill
                                }
                                activeBar={(props) => (
                                    <HoveredBar
                                        barProps={props}
                                        onClick={({ data, dataKey }) => {
                                            if (onBarClick) {
                                                onBarClick({ data, dataKey });
                                            }
                                        }}
                                    />
                                )}
                            />
                        ) : null
                    )}
                </BarChart>
            )}
        </BaseChart>
    );
};
export default StackedBarChart;
