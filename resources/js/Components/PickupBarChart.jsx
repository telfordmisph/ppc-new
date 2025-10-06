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

// const data = [
//     { name: "Jan", bigValues: 50000, smallValues: 50 },
//     { name: "Feb", bigValues: 70000, smallValues: 10 },
//     { name: "Mar", bigValues: 60000, smallValues: 60 },
//     { name: "Apr", bigValues: 13500, smallValues: 77 },
//     { name: "Apr", bigValues: 2250, smallValues: 34 },
//     { name: "Apr", bigValues: 11000, smallValues: 16 },
//     { name: "Apr", bigValues: 83000, smallValues: 32 },
//     { name: "Apr", bigValues: 40400, smallValues: 120 },
//     { name: "Apr", bigValues: 30500, smallValues: 120 },
//     { name: "Apr", bigValues: 80000, smallValues: 130 },
//     { name: "Apr", bigValues: 66230, smallValues: 110 },
//     { name: "Apr", bigValues: 77200, smallValues: 100 },
//     { name: "Apr", bigValues: 10200, smallValues: 40 },
//     { name: "Apr", bigValues: 80270, smallValues: 220 },
//     { name: "Apr", bigValues: 2100, smallValues: 120 },
//     { name: "Apr", bigValues: 70500, smallValues: 15 },
//     { name: "Apr", bigValues: 80050, smallValues: 120 },
//     { name: "Apr", bigValues: 57010, smallValues: 20 },
//     { name: "Apr", bigValues: 80070, smallValues: 80 },
//     { name: "Apr", bigValues: 8200, smallValues: 120 },
//     { name: "Apr", bigValues: 2300, smallValues: 10 },
//     { name: "Apr", bigValues: 80500, smallValues: 120 },
//     { name: "Apr", bigValues: 800, smallValues: 120 },
//     { name: "Apr", bigValues: 10510, smallValues: 64 },
//     { name: "Apr", bigValues: 6560, smallValues: 80 },
//     { name: "Apr", bigValues: 50700, smallValues: 20 },
//     { name: "Apr", bigValues: 7030, smallValues: 120 },
//     { name: "Apr", bigValues: 80700, smallValues: 20 },
//     { name: "Apr", bigValues: 80310, smallValues: 40 },
//     { name: "Apr", bigValues: 40000, smallValues: 110 },
//     { name: "Apr", bigValues: 80100, smallValues: 121 },
//     { name: "Apr", bigValues: 8300, smallValues: 14 },
//     { name: "Apr", bigValues: 3010, smallValues: 13 },
//     { name: "Apr", bigValues: 84000, smallValues: 120 },
//     { name: "Apr", bigValues: 30300, smallValues: 10 },
//     { name: "Apr", bigValues: 2040, smallValues: 12 },
//     { name: "Apr", bigValues: 12300, smallValues: 120 },
//     { name: "Apr", bigValues: 1200, smallValues: 55 },
//     { name: "Apr", bigValues: 40040, smallValues: 120 },
//     { name: "Apr", bigValues: 80020, smallValues: 10 },
//     { name: "Apr", bigValues: 8200, smallValues: 120 },
//     { name: "Apr", bigValues: 2300, smallValues: 10 },
//     { name: "Apr", bigValues: 80500, smallValues: 120 },
//     { name: "Apr", bigValues: 800, smallValues: 120 },
//     { name: "Apr", bigValues: 10510, smallValues: 64 },
//     { name: "Apr", bigValues: 6560, smallValues: 80 },
//     { name: "Apr", bigValues: 50700, smallValues: 20 },
//     { name: "Apr", bigValues: 7030, smallValues: 120 },
//     { name: "Apr", bigValues: 80700, smallValues: 20 },
//     { name: "Apr", bigValues: 80310, smallValues: 40 },
//     { name: "Apr", bigValues: 40000, smallValues: 110 },
//     { name: "Apr", bigValues: 80100, smallValues: 121 },
//     { name: "Apr", bigValues: 8300, smallValues: 14 },
//     { name: "Apr", bigValues: 3010, smallValues: 13 },
//     { name: "Apr", bigValues: 84000, smallValues: 120 },
//     { name: "Apr", bigValues: 30300, smallValues: 10 },
//     { name: "Apr", bigValues: 2040, smallValues: 12 },
//     { name: "Apr", bigValues: 12300, smallValues: 120 },
//     { name: "Apr", bigValues: 1200, smallValues: 55 },
//     { name: "Apr", bigValues: 40040, smallValues: 120 },
//     { name: "Apr", bigValues: 80020, smallValues: 10 },
//     { name: "Apr", bigValues: 2300, smallValues: 10 },
//     { name: "Apr", bigValues: 80500, smallValues: 120 },
//     { name: "Apr", bigValues: 800, smallValues: 120 },
//     { name: "Apr", bigValues: 10510, smallValues: 64 },
//     { name: "Apr", bigValues: 6560, smallValues: 80 },
//     { name: "Apr", bigValues: 50700, smallValues: 20 },
//     { name: "Apr", bigValues: 7030, smallValues: 120 },
//     { name: "Apr", bigValues: 80700, smallValues: 20 },
//     { name: "Apr", bigValues: 80310, smallValues: 40 },
//     { name: "Apr", bigValues: 40000, smallValues: 110 },
//     { name: "Apr", bigValues: 80100, smallValues: 121 },
//     { name: "Apr", bigValues: 8300, smallValues: 14 },
//     { name: "Apr", bigValues: 3010, smallValues: 13 },
//     { name: "Apr", bigValues: 84000, smallValues: 120 },
//     { name: "Apr", bigValues: 30300, smallValues: 10 },
//     { name: "Apr", bigValues: 2040, smallValues: 12 },
//     { name: "Apr", bigValues: 12300, smallValues: 120 },
//     { name: "Apr", bigValues: 1200, smallValues: 55 },
//     { name: "Apr", bigValues: 40040, smallValues: 120 },
//     { name: "Apr", bigValues: 80020, smallValues: 10 },
//     { name: "Apr", bigValues: 8200, smallValues: 120 },
//     { name: "Apr", bigValues: 2300, smallValues: 10 },
//     { name: "Apr", bigValues: 80500, smallValues: 120 },
//     { name: "Apr", bigValues: 800, smallValues: 120 },
//     { name: "Apr", bigValues: 10510, smallValues: 64 },
//     { name: "Apr", bigValues: 6560, smallValues: 80 },
// ];

export default function PickupBarChart({ data }) {
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
                {/* <Tooltip /> */}
                <Tooltip
                    contentStyle={{ backgroundColor: "#111", color: "#fff" }}
                    labelStyle={{ color: "#ffcc00" }}
                />
                <Legend />
                <Bar yAxisId="left" dataKey="total_quantity" fill="#422ad5" />
                <Bar yAxisId="right" dataKey="total_lots" fill="#f43098" />
                <Brush dataKey="packageName" height={30} stroke="#444" />
            </BarChart>
        </ResponsiveContainer>
    );
}
