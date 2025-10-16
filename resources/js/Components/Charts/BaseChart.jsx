import { ResponsiveContainer, Tooltip } from "recharts";
import { useThemeStore } from "@/Store/themeStore";
import BarChartSkeleton from "./BarChartSkeleton";

export default function BaseChart({ data, isLoading, children }) {
    const { theme } = useThemeStore();
    const isDark = theme === "dark";

    const colors = {
        base100: isDark ? "#1d232a" : "#ffffff",
        base200: isDark ? "#191e24" : "#f8f8f8",
        base300: isDark ? "#15191e" : "#eeeeee",
        baseContent: isDark ? "#ecf9ff" : "#18181b",
        primary: "#422ad5",
        secondary: "#f43098",
        accent: "#00d3bb",
    };

    const tooltip = (
        <Tooltip
            animationEasing="ease-in-out"
            animationDuration="500"
            labelStyle={{
                color: colors.baseContent,
            }}
            contentStyle={{
                backgroundColor: colors.base300,
                border: `1px solid ${colors.baseContent}`,
                borderRadius: "8px",
            }}
            cursor={{ fill: colors.base300 }}
        />
    );

    if (isLoading) {
        return <BarChartSkeleton />;
    }

    if (!data || data.length === 0) {
        return (
            <div className="flex items-center justify-center w-full rounded-lg h-80 bg-base-200">
                <p className="text-sm text-base-content/60">
                    No data available
                </p>
            </div>
        );
    }

    return (
        <ResponsiveContainer width="100%" height="100%">
            {children({
                isDark,
                colors,
                tooltip,
            })}
        </ResponsiveContainer>
    );
}
