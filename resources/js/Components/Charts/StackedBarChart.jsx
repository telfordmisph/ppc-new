import React, { memo } from "react";
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Rectangle,
    Legend,
    Brush,
} from "recharts";
import BaseChart from "./baseChart";
import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import HoveredBar from "./HoverBar";

const HoverableBar = memo(({ bar, visible, onBarClick, yAxisId }) => {
    if (!visible) return null;

    return (
        <Bar
            key={bar.dataKey}
            radius={4}
            unit={10}
            dataKey={bar.dataKey}
            stackId={bar.stackId || null}
            yAxisId={yAxisId}
            fill={
                Array.isArray(bar.fill) ? `url(#grad-${bar.dataKey})` : bar.fill
            }
            // TODO this causes unnecessary re-renders, but have beautiful hover effects
            shape={(props) => (
                <HoveredBar
                    barProps={props}
                    onClick={({ data, dataKey }) =>
                        onBarClick?.({ data, dataKey })
                    }
                />
            )}
            // shape={(props) => (
            //     <g
            //         onClick={() =>
            //             onBarClick?.({
            //                 data: props.payload,
            //                 dateKey: bar.dataKey,
            //             })
            //         }
            //         className="group cursor-pointer"
            //     >
            //         <Rectangle
            //             width={props.width}
            //             height={999}
            //             x={props.x}
            //             fill="transparent"
            //             y={0}
            //         />
            //         <Rectangle
            //             stroke={props.stroke}
            //             width={props.width}
            //             height={props.height}
            //             x={props.x}
            //             y={props.y}
            //             radius={props.radius}
            //             fill={props.fill}
            //             strokeWidth={props.strokeWidth}
            //         />
            //     </g>
            // )}
        />
    );
});

const StackedBarChart = memo(function StackedBarChart({
    data,
    isLoading,
    errorMessage,
    bars,
    visibleBars,
    onBarClick = () => {},
    xAxisDataKey = "Package_Name",
    defaultAngle = null,
    width = 500,
    height = 300,
    margin,
}) {
    const totalBarCount = data?.length || 0;
    const fontSize = totalBarCount > 25 ? 10 : 14;
    const angle = defaultAngle || totalBarCount > 25 ? -45 : 0;

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

                    <CartesianGrid
                        stroke={"var(--color-base-content-dim)"}
                        strokeDasharray="2 3"
                    />
                    <XAxis
                        dataKey={xAxisDataKey}
                        tick={{
                            fontSize: fontSize,
                        }}
                        angle={angle}
                        textAnchor="end"
                        interval={0}
                        height={80}
                    />
                    <YAxis
                        yAxisId="left"
                        tickFormatter={(value) => formatAbbreviateNumber(value)}
                    />
                    <YAxis
                        yAxisId="right"
                        orientation="right"
                        tickFormatter={(value) => formatAbbreviateNumber(value)}
                    />
                    <Legend />
                    {/* <Bar
                        dataKey="total_wip"
                        hide
                        className="hidden"
                        fill={"var(--color-primary)"}
                    /> */}
                    {bars.map((bar) => (
                        <HoverableBar
                            key={bar.dataKey}
                            bar={bar}
                            visible={visibleBars?.[bar.visibilityKey]}
                            onBarClick={onBarClick}
                            yAxisId={bar.yAxisId || "left"}
                        />
                    ))}
                </BarChart>
            )}
        </BaseChart>
    );
});
StackedBarChart.name = "StackedBarChart";
export default StackedBarChart;
