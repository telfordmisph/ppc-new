import React, { useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import { useFetch } from "@/Hooks/useFetch";

function StatCard({ title, data, isLoading, errorMessage }) {
    const renderContent = () => {
        if (isLoading) {
            return (
                <div className="flex items-center justify-center flex-1">
                    <span className="loading loading-spinner loading-xs"></span>
                </div>
            );
        }

        if (errorMessage) {
            return (
                <div className="flex-1 text-sm text-center text-error">
                    {errorMessage || "Error"}
                </div>
            );
        }

        if (data && (data.final_total_qty || data.final_total_lots)) {
            return (
                <>
                    <div className="flex flex-col items-center justify-center flex-1">
                        <div className="text-base font-bold text-primary">
                            {(
                                Number(data.final_total_qty) || 0
                            ).toLocaleString()}
                        </div>
                        <div className="text-xs opacity-70">total quantity</div>
                    </div>
                    <div className="flex flex-col items-center justify-center flex-1">
                        <div className="text-base font-bold text-secondary">
                            {(
                                Number(data.final_total_lots) || 0
                            ).toLocaleString()}
                        </div>
                        <div className="text-xs opacity-70">total lots</div>
                    </div>
                </>
            );
        }

        return (
            <div className="flex items-center justify-center flex-1 text-sm text-center opacity-50">
                N/A
            </div>
        );
    };

    return (
        <div className="flex flex-col justify-between h-32 p-4 rounded-lg bg-base-200 border border-base-content/10">
            {title && (
                <div className="mb-2 text-sm font-semibold opacity-70">
                    {title}
                </div>
            )}
            <div className="flex items-center justify-between flex-1">
                {renderContent()}
            </div>
        </div>
    );
}

const WIPTable = () => {
    const [selectedFilterType, setSelectedFilterType] = useState("F1");
    const [selectedDate, setSelectedDate] = useState(() => {
        const today = new Date();
        return today.toISOString().split("T")[0];
    });

    const filteringConditions = [
        "All",
        "Hold",
        "Pipeline",
        "Bake",
        "Processable",
        "Detapesegregation",
        "Lpi",
        "Brand",
        "Lli",
        "Sort",
    ];

    const fetches = filteringConditions.reduce((acc, type) => {
        const commonParams = {
            date: selectedDate,
            filterType: selectedFilterType,
            filteringCondition: type,
        };

        acc[type] = useFetch(route("api.wip.filterSummary"), {
            params: commonParams,
        });
        return acc;
    }, {});
    console.log("🚀 ~ WIPTable ~ fetches:", fetches);

    const handleSearch = () => {
        Object.values(fetches).forEach(({ abort: abort, fetch: refetch }) => {
            abort();
            refetch();
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="WIP Table" />
            <h1 className="text-base font-bold mb-4">WIP Table</h1>
            <div className="flex items-center gap-2 mb-2">
                <div className="w-20 dropdown dropdown-hover">
                    <div tabIndex={0} role="button" className="m-1 btn">
                        {selectedFilterType}
                    </div>
                    <ul
                        tabIndex={-1}
                        className="p-2 shadow-lg dropdown-content menu bg-base-100 rounded-lg z-1 w-52"
                    >
                        {["F1", "F2", "PL1", "PL6"].map((type) => (
                            <li key={type}>
                                <a onClick={() => setSelectedFilterType(type)}>
                                    {type}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
                <input
                    type="date"
                    className="input"
                    value={selectedDate || ""}
                    onChange={(e) => setSelectedDate(e.target.value)}
                />
                <button
                    className="btn btn-outline btn-primary"
                    onClick={handleSearch}
                >
                    Search
                </button>
            </div>
            <div className="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2 lg:grid-cols-4">
                {filteringConditions.map((type) => {
                    const { data, isLoading, errorMessage } = fetches[type];
                    return (
                        <StatCard
                            key={type}
                            title={type}
                            data={data}
                            isLoading={isLoading}
                            errorMessage={errorMessage}
                        />
                    );
                })}
            </div>
        </AuthenticatedLayout>
    );
};

export default WIPTable;
