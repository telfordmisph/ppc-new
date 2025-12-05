import { ResponsiveContainer, Tooltip } from "recharts";
import { useThemeStore } from "@/Store/themeStore";
import BarChartSkeleton from "./BarChartSkeleton";
import { DARK_THEME_NAME } from "@/Constants/colors";
import { FaDotCircle } from "react-icons/fa";
import useStateThrottle from "@/Hooks/useStateThrottle";

const CustomTooltip = ({ active, payload, label }) => {
    if (!active || !payload?.length) return null;

    return (
        <div className="flex flex-col gap-1 border border-base-content/20 rounded-lg pb-2 shadow-xs shadow-accent border-opacity-30 bg-base-300">
            <div className="px-2 pt-2 font-semibold rounded-t-lg">{label}</div>
            {payload.map((item, index) => {
                const baseColor = item.color;

                return (
                    <div
                        key={index}
                        className="px-2 flex items-center justify-between gap-8 py-0.5"
                    >
                        <div className="leading-none">
                            {(item?.name || "").replaceAll("_", " ")}
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="font-mono leading-none">
                                {Number(item?.value || 0).toLocaleString()}
                            </div>
                            <span
                                className="w-2 h-2 rounded-full"
                                style={{ background: baseColor }}
                            ></span>
                        </div>
                    </div>
                );
            })}
        </div>
    );
};

export default function BaseChart({ data, isLoading, error, children }) {
    const { theme } = useThemeStore();

    const isDark = theme === DARK_THEME_NAME;

    const tooltip = (
        <Tooltip
            offset={100}
            content={<CustomTooltip />}
            animationEasing="cubic-bezier(0, 0, 0, 1)"
            animationDuration={800}
            cursor={{ fill: "var(--color-base-content-dim)" }}
            includeHidden
        />
    );

    if (error)
        return (
            <div className="flex items-center border-error border bg-error/1 justify-center w-full rounded-lg h-full">
                <p className="text-sm text-error">{error}</p>
            </div>
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
        <ResponsiveContainer debounce={500}>
            {children({
                isDark,
                tooltip,
            })}
        </ResponsiveContainer>
    );
}
