import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    Brush,
} from "recharts";
import BaseChart from "./BaseChart";

export default function PickupBarChart({ data, isLoading }) {
    return (
        <BaseChart data={data} isLoading={isLoading}>
            {({ colors, tooltip }) => (
                <BarChart
                    data={data}
                    margin={{ top: 10, right: 50, left: 50, bottom: 5 }}
                >
                    <CartesianGrid strokeDasharray="3 3" />
                    {tooltip}

                    <XAxis dataKey="PACKAGE" />
                    <YAxis
                        yAxisId="left"
                        tickFormatter={(value) => formatAbbreviateNumber(value)}
                        tick={{ fill: colors.primary }}
                    />
                    <YAxis
                        yAxisId="right"
                        orientation="right"
                        tickFormatter={(value) => formatAbbreviateNumber(value)}
                        tick={{ fill: colors.secondary }}
                    />

                    <Legend />
                    <Bar
                        yAxisId="left"
                        dataKey="total_quantity"
                        fill={colors.primary}
                    />
                    <Bar
                        yAxisId="right"
                        dataKey="total_lots"
                        fill={colors.secondary}
                    />
                    <Brush
                        dataKey="packageName"
                        height={20}
                        stroke={colors.baseContent}
                        fill={colors.base300}
                    />
                </BarChart>
            )}
        </BaseChart>
    );
}
