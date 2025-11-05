import React from "react";
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

export default function TrendLineChart({
    data,
    xKey = "name",
    isLoading = false,
    errorMessage = null,
    lines = [],
    height = 300,
}) {
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

                        <Legend />
                        {tooltip}
                        {lines.map((line, index) => (
                            <Line key={index} {...line} />
                        ))}
                    </ReLineChart>
                )}
            </BaseChart>
        </div>
    );
}
