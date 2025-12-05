import React, { memo, useState } from "react";
import {
    LineChart as ReLineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Legend,
} from "recharts";
import BaseChart from "./BaseChart";
import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";

const TrendLineChart = memo(function TrendLineChart({
    data,
    xKey = "name",
    isLoading = false,
    errorMessage = null,
    lines = [],
    height = 300,
}) {
    const [lineProps, setLineProps] = useState(
        lines.reduce(
            (a, { dataKey }) => {
                a[dataKey] = false;
                return a;
            },
            { hover: null }
        )
    );

    const handleLegendMouseEnter = (e) => {
        if (!lineProps[e.dataKey]) {
            setLineProps({ ...lineProps, hover: e.dataKey });
        }
    };

    const handleLegendMouseLeave = (e) => {
        setLineProps({ ...lineProps, hover: null });
    };

    const selectLine = (e) => {
        setLineProps({
            ...lineProps,
            [e.dataKey]: !lineProps[e.dataKey],
            hover: null,
        });
    };

    return (
        <div style={{ width: "100%", height }}>
            <BaseChart data={data} isLoading={isLoading} error={errorMessage}>
                {({ tooltip }) => (
                    <ReLineChart
                        data={data}
                        margin={{
                            top: 20,
                            right: 20,
                            left: 20,
                            bottom: 20,
                        }}
                    >
                        <CartesianGrid
                            stroke={"var(--color-base-content-dim)"}
                            strokeDasharray="2 3"
                        />
                        <XAxis dataKey={xKey} />

                        <YAxis
                            tickFormatter={(value) =>
                                formatAbbreviateNumber(value)
                            }
                            yAxisId="left"
                        />
                        <YAxis
                            tickFormatter={(value) =>
                                formatAbbreviateNumber(value)
                            }
                            yAxisId="right"
                            orientation="right"
                        />

                        <Legend
                            wrapperStyle={{ cursor: "pointer" }}
                            onClick={selectLine}
                            onMouseOver={handleLegendMouseEnter}
                            onMouseOut={handleLegendMouseLeave}
                        />

                        {tooltip}
                        {lines.map((line, index) => (
                            <Line
                                key={index}
                                hide={lineProps[line.dataKey] === true}
                                opacity={Number(
                                    lineProps.hover === line.dataKey ||
                                        !lineProps.hover
                                        ? 1
                                        : 0.1
                                )}
                                {...line}
                            />
                        ))}
                    </ReLineChart>
                )}
            </BaseChart>
        </div>
    );
});

export default TrendLineChart;
