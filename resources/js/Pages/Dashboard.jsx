import BarChartSkeleton from "@/Components/BarChartSkeleton";
import BarChart from "@/Components/OverallWIPBarChart";
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

            <div className="flex h-[500px]">
                <div className="flex items-center justify-center w-8/12">
                    {/* <BarChartSkeleton /> */}
                    {wipLoading || !wipData ? (
                        <BarChartSkeleton />
                    ) : (
                        <BarChart data={wipData} />
                    )}
                </div>

                <div className="flex flex-col items-center justify-center w-4/12 gap-4 px-4 text-accent">
                    <div className="flex w-full h-full gap-4">
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
                                icon={<FaIndustry size={32} />}
                                useDataHook={() => ({
                                    data: latestWip?.f1 || 0,
                                    loading: wipLoading,
                                    error: wipError,
                                })}
                            />
                            <StatCard
                                title="F2 WIP"
                                desc={`As of ${latestWip.date}`}
                                color="accent"
                                icon={<FaIndustry size={32} />}
                                useDataHook={() => ({
                                    data: latestWip?.f2 || 0,
                                    loading: wipLoading,
                                    error: wipError,
                                })}
                            />
                            <StatCard
                                title="F3 WIP"
                                desc={`As of ${latestWip.date}`}
                                color="secondary"
                                icon={<FaIndustry size={32} />}
                                useDataHook={() => ({
                                    data: latestWip?.f3 || 0,
                                    loading: wipLoading,
                                    error: wipError,
                                })}
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
