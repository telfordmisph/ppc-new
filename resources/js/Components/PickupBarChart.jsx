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

export default function PickupBarChart({ data }) {
    const isDark = localStorage.getItem("theme") === "dark";

    const brushStroke = isDark ? "#8884d8" : "#333";
    const brushFill = isDark ? "#222" : "#eee";

    return (
        <ResponsiveContainer width="100%" height="100%">
            <BarChart
                data={data}
                margin={{ top: 10, right: 50, left: 50, bottom: 5 }}
            >
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="package_name" />
                <YAxis yAxisId="left" />
                <YAxis yAxisId="right" orientation="right" />
                <Tooltip
                    labelStyle={{
                        color: isDark ? "#aaa" : "#333",
                    }}
                />
                <Legend />
                <Bar yAxisId="left" dataKey="total_quantity" fill="#422ad5" />
                <Bar yAxisId="right" dataKey="total_lots" fill="#f43098" />
                <Brush
                    dataKey="packageName"
                    height={30}
                    stroke={brushStroke}
                    fill={brushFill}
                />
            </BarChart>
        </ResponsiveContainer>
    );
}
