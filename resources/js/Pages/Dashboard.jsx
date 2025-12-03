import BarChart from "@/Components/Charts/OverallWIPBarChart";
import { Head } from "@inertiajs/react";
import { useState, memo } from "react";
import { FaMinus, FaTasks, FaIndustry } from "react-icons/fa";
import { FaArrowTrendUp, FaArrowTrendDown } from "react-icons/fa6";
import clsx from "clsx";
import { useWipStore } from "@/Store/overallDailyWipTrendStore";
import WipOutTrendByPackage from "@/Components/WipOutTrendByPackage";

const StatCard = memo(function StatCard({
    icon,
    title,
    desc,
    textColor = "text-primary",
    data,
    loading,
    error,
    className = "",
}) {
    return (
        <div
            className={`border border-base-content/10 rounded-lg shadow-lg bg-base-300 stat ${className}`}
        >
            <div className="font-extrabold text-primary stat-title">
                {title}
            </div>

            <div className="flex items-center justify-between w-full align-baseline">
                <div className={clsx(`stat-value text-2xl mb-1`, textColor)}>
                    {loading ? (
                        <div className="w-20 h-6 rounded-lg bg-base-300 animate-pulse"></div>
                    ) : error ? (
                        <span className="text-red-500">{error}</span>
                    ) : (
                        data.toLocaleString?.() || data
                    )}
                </div>

                <div className={clsx(`stat-figure mb-2`, textColor)}>
                    {loading ? (
                        <div className="w-8 h-8 rounded-full bg-base-300 animate-pulse"></div>
                    ) : (
                        icon
                    )}
                </div>
            </div>

            {desc && (
                <div className="text-sm stat-desc">
                    {loading ? (
                        <div className="w-32 h-4 rounded-lg bg-base-300 animate-pulse"></div>
                    ) : (
                        desc
                    )}
                </div>
            )}
        </div>
    );
});

export default function Dashboard({ tableData, tableFilters }) {
    const [windowSize, setWindowSize] = useState(2);

    const {
        wip: wipData,
        isLoading: isWipLoading,
        errorMessage: wipErrorMessage,
        latest: latestWip,
    } = useWipStore();

    const trendValue = latestWip?.trend ?? 0;
    const trendIcon =
        trendValue > 0 ? (
            <FaArrowTrendUp size={32} />
        ) : trendValue < 0 ? (
            <FaArrowTrendDown size={32} />
        ) : (
            <FaMinus size={32} />
        );

    const trendColor =
        trendValue > 0
            ? "text-green-500"
            : trendValue < 0
            ? "text-red-500"
            : "text-gray-500";

    return (
        <>
            <Head title="Dashboard" />

            <h1 className="text-2xl font-bold mb-4">Dashboard</h1>

            <div className="w-full">
                <WipOutTrendByPackage
                    isVisible
                    title="Wip/Out/Capacity Trend by Packages"
                    dataAPI={route("api.wip.out.trend")}
                    showLines={{
                        showQuantities: true,
                        showLots: false,
                        showOuts: true,
                        showCapacities: true,
                    }}
                />
            </div>

            <div className="mt-4 grid md:h-[600px] grid-cols-2 grid-rows-12 md:grid-rows-1 gap-4 h-[800px] md:grid-cols-12">
                <div className="order-2 col-span-2 row-span-8 md:order-1 md:row-span-1 md:col-span-8 border border-base-content/10 items-center justify-center w-full h-full p-4 rounded-lg shadow-lg bg-base-300">
                    <BarChart
                        data={wipData}
                        isLoading={isWipLoading}
                        errorMessage={wipErrorMessage}
                        windowSize={windowSize}
                    />
                </div>

                <div className="grid h-full md:grid-rows-5 grid-rows-2 md:row-span-1 grid-cols-12 col-span-2 row-span-4 gap-4 order-1 md:order-2 md:col-span-4">
                    <StatCard
                        title="Overall Factory WIP"
                        desc={latestWip ? `As of ${latestWip.date}` : "No data"}
                        textColor=""
                        className="row-span-1 col-span-6 md:col-span-12 text-white w-full bg-linear-to-tr from-f1color/60 via-f1color/75 to-f1color"
                        icon={<FaTasks size={32} />}
                        data={latestWip?.total || 0}
                        loading={isWipLoading}
                        error={wipErrorMessage}
                    />
                    <StatCard
                        title="Trend Today"
                        desc="Since Yesterday"
                        textColor={trendColor}
                        icon={trendIcon}
                        data={`${trendValue.toFixed(2)}%`}
                        loading={isWipLoading}
                        error={wipErrorMessage}
                        className="row-span-1 col-span-6 md:col-span-12"
                    />
                    <StatCard
                        title="F1 WIP"
                        desc={`As of ${latestWip?.date || "No data"}`}
                        textColor="text-f1color"
                        icon={
                            <FaIndustry size={32} className="hidden md:block" />
                        }
                        data={latestWip?.f1 || 0}
                        loading={isWipLoading}
                        error={wipErrorMessage}
                        className="row-span-1 col-span-4 md:col-span-12"
                    />
                    <StatCard
                        title="F2 WIP"
                        desc={`As of ${latestWip?.date || "No data"}`}
                        textColor="text-f2color"
                        icon={
                            <FaIndustry size={32} className="hidden md:block" />
                        }
                        data={latestWip?.f2 || 0}
                        loading={isWipLoading}
                        error={wipErrorMessage}
                        className="row-span-1 col-span-4 md:col-span-12"
                    />
                    <StatCard
                        title="F3 WIP"
                        desc={`As of ${latestWip?.date || "No data"}`}
                        textColor="text-f3color"
                        icon={
                            <FaIndustry size={32} className="hidden md:block" />
                        }
                        data={latestWip?.f3 || 0}
                        loading={isWipLoading}
                        error={wipErrorMessage}
                        className="row-span-1 col-span-4 md:col-span-12"
                    />
                </div>
            </div>
        </>
    );
}
