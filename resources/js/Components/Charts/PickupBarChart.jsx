import { Bar, BarChart, CartesianGrid, Legend, XAxis, YAxis } from "recharts";
import formatAbbreviateNumber from "@/Utils/formatAbbreviateNumber";
import BaseChart from "./BaseChart";

export default function PickupBarChart({
	data = [],
	isLoading = false,
	errorMessage,
}) {
	return (
		<BaseChart data={data} isLoading={isLoading} error={errorMessage}>
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
						dataKey="total_wip"
						fill={"var(--color-f1color)"}
						radius={4}
					/>
					<Bar
						yAxisId="right"
						dataKey="total_lots"
						fill={"var(--color-f2color)"}
						radius={4}
					/>
					{/* <Brush
                        dataKey="packageName"
                        height={20}
                        stroke={"var(--color-base-content)"}
                        fill={"var(--color-base-300)"}
                    /> */}
				</BarChart>
			)}
		</BaseChart>
	);
}
