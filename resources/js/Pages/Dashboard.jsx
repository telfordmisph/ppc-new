import BarChartSkeleton from "@/Components/Charts/BarChartSkeleton";
import BarChart from "@/Components/Charts/OverallWIPBarChart";
import { useWip } from "@/Hooks/useWip";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import { useEffect } from "react";
import { FaMinus, FaTasks, FaIndustry } from "react-icons/fa";
import { FaArrowTrendUp, FaArrowTrendDown } from "react-icons/fa6";

function StatCard({
    icon,
    title,
    desc,
    color = "primary",
    useDataHook,
    className = "",
}) {
    const { data, loading, error } = useDataHook
        ? useDataHook()
        : { data: null, loading: false, error: null };

    return (
        <div
            className={`w-full h-full rounded-lg shadow-md flex-col bg-base-200 stat ${className}`}
        >
            <div className="font-extrabold stat-title">{title}</div>

            <div className="flex items-center justify-between w-full align-baseline">
                <div className={`stat-value text-${color} text-2xl mb-1`}>
                    {loading ? (
                        <div className="w-20 h-6 rounded bg-base-content animate-pulse"></div>
                    ) : error ? (
                        <span className="text-red-500">Error</span>
                    ) : (
                        data.toLocaleString?.() || data
                    )}
                </div>

                <div className={`stat-figure text-${color} mb-2`}>
                    {loading ? (
                        <div className="w-8 h-8 rounded-full bg-base-content animate-pulse"></div>
                    ) : (
                        icon
                    )}
                </div>
            </div>

            {desc && (
                <div className="text-sm stat-desc">
                    {loading ? (
                        <div className="w-32 h-4 rounded bg-base-content animate-pulse"></div>
                    ) : (
                        desc
                    )}
                </div>
            )}
        </div>
    );
}

export default function Dashboard({ tableData, tableFilters }) {
    const props = usePage().props;

    const {
        wip: wipData,
        loading: wipLoading,
        error: wipError,
        latest: latestWip,
        yesterday: yesterdayWip,
    } = useWip();

    useEffect(() => {
        if (wipData) {
            console.log("WIP Data:", wipData);
            console.log("Yesterday WIP:", yesterdayWip);
        }
    }, [wipData]);

    useEffect(() => {
        console.log("Latest fdffWIP:", latestWip);
    }, [latestWip]);

    useEffect(() => {
        if (wipLoading) {
            console.log("Loading WIP data...");
        } else {
            console.log("WIP data loaded.");
        }
    }, [wipLoading, wipError]);

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
        trendValue > 0 ? "green-500" : trendValue < 0 ? "red-500" : "gray-500";

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <h1 className="text-2xl font-bold">Dashboard</h1>

            <div className="grid h-[800px] grid-cols-1 grid-rows-6 md:grid-rows-1 gap-4 md:h-auto md:grid-cols-12">
                <div className="flex items-center justify-center order-2 col-span-1 row-span-4 md:order-1 md:col-span-8">
                    <div className="flex flex-col items-center justify-center w-full h-full p-6 rounded-lg shadow-md bg-base-100">
                        <BarChart data={wipData} isLoading={wipLoading} />
                    </div>
                </div>

                <div className="grid items-center justify-center grid-cols-6 col-span-1 row-span-2 gap-4 md:order-2 md:col-span-4 text-accent">
                    <div className="grid w-full h-full grid-cols-2 col-span-6 gap-4 md:grid-cols-1">
                        <StatCard
                            title="Overall Factory WIP"
                            desc={
                                latestWip
                                    ? `As of ${latestWip.date}`
                                    : "No data"
                            }
                            color=""
                            className="text-white w-full bg-gradient-to-tr from-[#F43098] via-[#422AD5] to-[#00D3BB]"
                            icon={<FaTasks size={32} />}
                            useDataHook={() => ({
                                data: latestWip?.total || 0,
                                loading: wipLoading,
                                error: wipError,
                            })}
                        />

                        <StatCard
                            title="Trend Today"
                            desc="Since Yesterday"
                            color={trendColor}
                            icon={trendIcon}
                            useDataHook={() => ({
                                data: `${trendValue.toFixed(2)}%`,
                                loading: wipLoading,
                                error: wipError,
                            })}
                        />
                    </div>

                    {latestWip ? (
                        <>
                            <StatCard
                                title="F1 WIP"
                                desc={`As of ${latestWip.date}`}
                                color="primary"
                                icon={
                                    <FaIndustry
                                        size={32}
                                        className="hidden md:block"
                                    />
                                }
                                useDataHook={() => ({
                                    data: latestWip?.f1 || 0,
                                    loading: wipLoading,
                                    error: wipError,
                                })}
                                className="col-span-2 md:col-span-6"
                            />
                            <StatCard
                                title="F2 WIP"
                                desc={`As of ${latestWip.date}`}
                                color="accent"
                                icon={
                                    <FaIndustry
                                        size={32}
                                        className="hidden md:block"
                                    />
                                }
                                useDataHook={() => ({
                                    data: latestWip?.f2 || 0,
                                    loading: wipLoading,
                                    error: wipError,
                                })}
                                className="col-span-2 md:col-span-6"
                            />
                            <StatCard
                                title="F3 WIP"
                                desc={`As of ${latestWip.date}`}
                                color="secondary"
                                icon={
                                    <FaIndustry
                                        size={32}
                                        className="hidden md:block"
                                    />
                                }
                                useDataHook={() => ({
                                    data: latestWip?.f3 || 0,
                                    loading: wipLoading,
                                    error: wipError,
                                })}
                                className="col-span-2 md:col-span-6"
                            />
                        </>
                    ) : (
                        <div className="flex flex-col w-full gap-4">
                            {[1, 2, 3].map((_, i) => (
                                <div
                                    key={i}
                                    className="w-full h-32 rounded-lg bg-base-200 animate-pulse"
                                ></div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
