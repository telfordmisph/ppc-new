import { ResponsiveContainer, Tooltip } from "recharts";
import { useThemeStore } from "@/Store/themeStore";
import BarChartSkeleton from "./BarChartSkeleton";
import { DARK_THEME_NAME } from "@/Constants/colors";

const CustomTooltip = ({ active, payload, label }) => {
    if (!active || !payload?.length) return null;

    const gradientKeys = new Set([
        colors.accent,
        colors.f1Color,
        colors.f2Color,
        colors.f3Color,
    ]);

    return (
        <div className="flex flex-col gap-1 border border-base-content/20 rounded-lg shadow-lg border-opacity-30 bg-base-300">
            <div className="px-2 pt-2 font-semibold bg-neutral/20 rounded-t-lg text-accent">
{label}
</div>
            {payload.map((item, index) => {
                                const baseColor = item.color;
                const background = `linear-gradient(to left, ${baseColor} 0%, transparent)`;

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

    const isDark = theme === DARK_THEME_NAME;

    const tooltip = (
        <Tooltip
            content={<CustomTooltip />}
            animationEasing="ease-in-out"
            animationDuration={150}
            cursor={{ fill: "var(--color-base-content-dim)" }}
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
            <div className="flex items-center justify-center w-full rounded-lg h-full">
                <p className="text-sm text-base-content/60">
                    No data available
                </p>
            </div>
        );

    return (
        <ResponsiveContainer>
            {children({ isDark, tooltip })}
        </ResponsiveContainer>
    );
}
