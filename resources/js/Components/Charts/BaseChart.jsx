import { ResponsiveContainer, Tooltip } from "recharts";
import { useThemeStore } from "@/Store/themeStore";
import BarChartSkeleton from "./BarChartSkeleton";
import { COLORS } from "@/Constants/colors";
import clsx from "clsx";

const CustomTooltip = ({ active, payload, label, colors }) => {
    if (!active || !payload?.length) return null;

    const gradientKeys = new Set([
        colors.accent,
        colors.f1Color,
        colors.f2Color,
        colors.f3Color,
    ]);

    return (
        <div className="flex flex-col gap-1 py-2 border border-base-content/20 rounded-lg shadow-lg border-opacity-30 bg-base-100">
            <div className="px-2 font-semibold">{label}</div>
            {payload.map((item, index) => {
                const shouldGradient = gradientKeys.has(item?.color);
                const baseColor = colors[item?.name] || item.color;
                const background = shouldGradient
                    ? `linear-gradient(to right, ${baseColor}90, transparent)`
                    : "transparent";

                return (
                    <div
                        key={index}
                        className="px-2 flex items-center justify-between gap-8 py-0.5"
                        style={{ background }}
                    >
                        <div className="leading-none">
                            {(item?.name || "").replaceAll("_", " ")}
                        </div>
                        <div className="font-mono leading-none">
                            {Number(item?.value || 0).toLocaleString()}
                        </div>
                    </div>
                );
            })}
        </div>
    );
};

export default function BaseChart({ data, isLoading, children }) {
    const { theme } = useThemeStore();
    const isDark = theme === "dark";

    const colors = {
        base100: isDark ? "#1d232a" : "#ffffff",
        base200: isDark ? "#191e24" : "#f8f8f8",
        base300: isDark ? "#15191e" : "#eeeeee",
        baseContent: isDark ? "#ecf9ff" : "#18181b",
        accent: COLORS.accent,
        f1Color: COLORS.f1Color,
        f2Color: COLORS.f2Color,
        f3Color: COLORS.f3Color,
    };

    const tooltip = (
        <Tooltip
            content={<CustomTooltip colors={colors} />}
            animationEasing="ease-in-out"
            animationDuration={500}
            cursor={{ fill: colors.base300 }}
            includeHidden
        />
    );

    if (isLoading)
        return (
            <div className="h-full flex justify-center items-center w-full">
                <BarChartSkeleton />
            </div>
        );

    if (!data || data.length === 0)
        return (
            <div className="flex items-center justify-center w-full rounded-lg h-full bg-base-200">
                <p className="text-sm text-base-content/60">
                    No data available
                </p>
            </div>
        );

    return (
        <ResponsiveContainer>
            {children({ isDark, colors, tooltip })}
        </ResponsiveContainer>
    );
}
