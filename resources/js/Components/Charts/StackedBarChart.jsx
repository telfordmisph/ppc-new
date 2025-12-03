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

const HoverableBar = memo(({ bar, visible, onBarClick }) => {
    if (!visible) return null;

    return (
        <Bar
            key={bar.dataKey}
            radius={4}
            unit={10}
            dataKey={bar.dataKey}
            stackId={bar.stackId || null}
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
    width = 500,
    height = 300,
    margin,
}) {
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
                    <Bar
                        dataKey="total_quantity"
                        hide
                        className="hidden"
                        fill={"var(--color-neutral-content)"}
                    />
                    {bars.map((bar) => (
                        <HoverableBar
                            key={bar.dataKey}
                            bar={bar}
                            visible={visibleBars?.[bar.visibilityKey]}
                            onBarClick={onBarClick}
                        />
                    ))}
                </BarChart>
            )}
        </BaseChart>
    );
});
StackedBarChart.name = "StackedBarChart";
export default StackedBarChart;
