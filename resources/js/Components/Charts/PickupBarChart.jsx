import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Legend,
    Brush,
} from "recharts";
import BaseChart from "./BaseChart";

export default function PickupBarChart({ data = [], isLoading = false }) {
    return (
        <BaseChart data={data} isLoading={isLoading}>
            {({ tooltip }) => (
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
                        tick={{ fill: "var(--color-primary)" }}
                    />
                    <YAxis
                        yAxisId="right"
                        orientation="right"
                        tickFormatter={(value) => formatAbbreviateNumber(value)}
                        tick={{ fill: "var(--color-secondary)" }}
                    />

                    <Legend />
                    <Bar
                        yAxisId="left"
                        dataKey="total_quantity"
                        fill={"var(--color-f1color)"}
                    />
                    <Bar
                        yAxisId="right"
                        dataKey="total_lots"
                        fill={"var(--color-f2color)"}
                    />
                    <Brush
                        dataKey="packageName"
                        height={20}
                        stroke={"var(--color-base-content)"}
                        fill={"var(--color-base-300)"}
                    />
                </BarChart>
            )}
        </BaseChart>
    );
}
