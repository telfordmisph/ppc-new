import React, { useState, useEffect, useMemo, useRef } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import MultiSelectDropdown from "@/Components/MultiSelectDropdown";
import { useFetch } from "@/Hooks/useFetch";
import formatDate from "@/Utils/formatDate";
import StackedBarChart from "@/Components/Charts/StackedBarChart";
import TogglerButton from "@/Components/TogglerButton";
import sortObjectArray from "@/Utils/sortObjectArray";
import Modal from "@/Components/Modal";
import { useMutation } from "@/Hooks/useMutation";
import toast from "react-hot-toast";
import clsx from "clsx";
import {
    TOGGLE_FACTORY_BUTTONS,
    TOGGLE_PL_BUTTONS,
} from "@/Constants/toggleButtons";
import { sumByKey } from "@/Utils/sumByKey";
import { formatDataStatusMessage } from "@/Utils/formatStatusMessage";
import WipTrendByPackage from "@/Components/WipTrendByPackage";
import {
    summaryWipPLBarsLots,
    summaryWipPLBarsQuantity,
} from "@/Utils/chartBars";

// TODO: extract?
function buildComputeFunction(selectedTotal, visibleBars) {
    const activeKeys = Object.entries(visibleBars)
        .filter(([_, isVisible]) => isVisible)
        .map(([key]) => key);

    if (activeKeys.length === 0) return null;

    return (item) =>
        activeKeys.reduce((sum, key) => {
            const field =
                selectedTotal === "quantity"
                    ? `${key}_total_quantity`
                    : `${key}_total_lots`;
            return sum + Number(item[field] || 0);
        }, 0);
}

const WIPTrend = () => {
    const manualWIPImportRef = useRef(null);
    const [startDate, setStartDate] = useState(null);
    const [endDate, setEndDate] = useState(null);
    const today = new Date();

    const defaultStart = new Date(2025, 8, 1, 0, 0, 0, 0); // September 1, 2025 (month is 0-indexed)
    const defaultEnd = new Date(2025, 8, 1, 23, 59, 59, 999);

    const [tempStartDate, setTempStartDate] = useState(defaultStart);
    const [tempEndDate, setTempEndDate] = useState(defaultEnd);

    // const [tempStartDate, setTempStartDate] = useState(
    //     new Date(today.setHours(0, 0, 0, 0))
    // );
    // const [tempEndDate, setTempEndDate] = useState(
    //     new Date(today.setHours(23, 59, 59, 999))
    // );

    const [isWorkweek, setIsWorkWeek] = useState(false);
    const [selectedWorkWeek, setSelectedWorkWeek] = useState([]);
    const [tempSelectedWorkWeek, setTempSelectedWorkWeek] = useState([]);

    const [selectedTotal, setSelectedTotal] = useState("quantity");
    const [isTrendByPackageVisible, setIsTrendByPackageVisible] =
        useState(false);
    const [selectedPackageName, setSelectedPackageName] = useState(null);

    const [factoryVisibleBars, setFactoryVisibleBars] = useState({
        f1: true,
        f2: true,
        f3: true,
        always: true,
    });

    const [plVisibleBars, setPLVisibleBars] = useState({
        pl1: true,
        pl6: true,
    });

    const trendByPackageChartRef = useRef(null);

    const scrollToBottom = () => {
        trendByPackageChartRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    useEffect(() => {
        if (!isTrendByPackageVisible) return;

        scrollToBottom();
    }, [isTrendByPackageVisible]);

    const dateRange =
        startDate && endDate
            ? `${formatDate(startDate)} - ${formatDate(endDate)}`
            : "";

    const commonParams = {
        dateRange: startDate && endDate ? dateRange : "",
        workweek: selectedWorkWeek.join(" ") || "",
    };

    const endpoints = {
        overall: "api.wip.overall",
        overallByPackage: "api.wip.overallByPackage",
        summary: "api.wip.quantityLotTotals",
    };

    const {
        data: overallWipData,
        isLoading: isOverallWipLoading,
        errorMessage: overallWipErrorMessage,
        fetch: overallWipFetch,
    } = useFetch(route(endpoints.overall), { params: commonParams });

    const {
        data: overallSummaryWipData,
        isLoading: isOverallSummaryWipLoading,
        errorMessage: overallSummaryWipErrorMessage,
        fetch: overallSummaryWipFetch,
    } = useFetch(route(endpoints.summary), {
        params: { ...commonParams, includePL: false },
    });

    const {
        data: plSummaryWipData,
        isLoading: isPlSummaryWipLoading,
        errorMessage: plSummaryWipErrorMessage,
        fetch: plSummaryWipFetch,
    } = useFetch(route(endpoints.summary), { params: commonParams });

    const {
        isLoading: isImportWipLoading,
        errorMessage: importWipErrorMessage,
        mutate: importWip,
    } = useMutation();

    const { message } = formatDataStatusMessage({
        isLoading: isOverallWipLoading,
        label: "WIP",
        dateRange,
        startDate,
        endDate,
        selectedWorkWeek,
    });

    const handleDateChange = (dates) => {
        const [start, end] = dates;
        setTempStartDate(start);
        setTempEndDate(end);
    };

    const handleResetFilter = () => {
        setTempStartDate(null);
        setTempEndDate(null);
        setTempSelectedWorkWeek([]);
    };

    const handleDateFilterChange = (e) => {
        setIsWorkWeek(e.target.checked);
        setTempSelectedWorkWeek([]);
        setSelectedWorkWeek([]);
        setTempStartDate(null);
        setTempEndDate(null);
        setTempSelectedWorkWeek([]);
        setSelectedWorkWeek([]);
    };

    const handleRefetch = () => {
        if (!isWorkweek) {
            const newDateRange = `${formatDate(tempStartDate)} - ${formatDate(
                tempEndDate
            )}`;
            setStartDate(tempStartDate);
            setEndDate(tempEndDate);
            overallWipFetch({ dateRange: newDateRange, workweek: "" });
            overallSummaryWipFetch({
                dateRange: newDateRange,
                workweek: "",
                includePL: false,
            });
        } else {
            const newWorkweek = tempSelectedWorkWeek.join(" ");
            setSelectedWorkWeek(tempSelectedWorkWeek);
            overallWipFetch({ dateRange: "", workweek: newWorkweek });
            overallSummaryWipFetch({
                dateRange: "",
                workweek: newWorkweek,
                includePL: false,
            });
        }
    };

    const handleChangeTotalFilter = (e) => {
        if (e.target.checked) {
            setSelectedTotal("lots");
        } else {
            setSelectedTotal("quantity");
        }
    };

    const handleToggleBar = (name, key) => {
        if (name === "factory") {
            setFactoryVisibleBars((prev) => ({ ...prev, [key]: !prev[key] }));
        }

        if (name === "production_line") {
            setPLVisibleBars((prev) => ({ ...prev, [key]: !prev[key] }));
        }
    };

    const toggleAllFactory = () => {
        const allVisible = Object.values(factoryVisibleBars).every(Boolean);
        setFactoryVisibleBars({
            f1: !allVisible,
            f2: !allVisible,
            f3: !allVisible,
            always: true,
        });
    };

    const compute = useMemo(
        () => buildComputeFunction(selectedTotal, factoryVisibleBars),
        [selectedTotal, factoryVisibleBars]
    );

    // const allPackages = useMemo(
    //     () => summaryWipData?.data || [],
    //     [summaryWipData?.data?.length]
    // );

    const allPackages = useMemo(
        () => overallSummaryWipData?.data || [],
        [overallSummaryWipData?.data?.length]
    );

    const activePLs = useMemo(
        () => Object.keys(plVisibleBars).filter((pl) => plVisibleBars[pl]),
        [plVisibleBars]
    );

    const isAllPLsSelected = useMemo(
        () => activePLs.length === Object.keys(plVisibleBars).length,
        [activePLs, plVisibleBars]
    );

    const plSummed = useMemo(() => {
        if (!allPackages) return [];
        return sumByKey(allPackages, "Package_Name", ["PL"]);
    }, [allPackages]);

    const filteredData = useMemo(() => {
        if (isAllPLsSelected) return plSummed;
        return allPackages.filter((item) =>
            activePLs.some(
                (pl) => pl.toLowerCase() === String(item.PL).toLowerCase()
            )
        );
    }, [isAllPLsSelected, activePLs, plSummed, allPackages]);

    const sortKeys =
        selectedTotal === "quantity" ? ["total_quantity"] : ["total_lots"];

    // data={sortObjectArray(summaryWipData?.data || [], {
    //                     keys: ["total_lots"],
    //                     order: "desc",
    //                     compute:
    //                         selectedTotal === "quantity" ? compute : null,
    //                 })}
    const sortedAllPackageFilteredData = useMemo(
        () =>
            sortObjectArray(filteredData, {
                keys: sortKeys,
                order: "desc",
                // compute: selectedTotal === "quantity" ? compute : null,
                compute,
            }),
        [filteredData, sortKeys, compute]
    );

    const handleManualWIPImport = async () => {
        const promise = importWip(route("import.manual"));
        const toastId = "import-toast";

        const toastTransition = (t) =>
            clsx(
                "transition-all duration-300",
                t.visible
                    ? "opacity-100 translate-y-0"
                    : "opacity-0 translate-y-2"
            );

        toast.custom(
            (t) => (
                <div
                    className={clsx(
                        "flex items-center gap-2 p-4 m-2 rounded-lg shadow-lg bg-base-100",
                        toastTransition(t)
                    )}
                >
                    <span className="loading loading-spinner loading-xs" />
                    <span>Importing WIP data...</span>
                </div>
            ),
            { id: toastId, duration: Infinity, removeDelay: 400 }
        );

        try {
            const result = await promise;
            toast.dismiss(toastId);

            toast.custom(
                (t) => (
                    <div
                        className={clsx(
                            "m-2 p-4 rounded-lg shadow-lg bg-base-100",
                            toastTransition(t)
                        )}
                    >
                        <div className="mb-2 font-bold text-success">
                            Successfully imported!
                        </div>

                        <div className="flex justify-between">
                            <span className="font-light">
                                new f1/f2 entries:
                            </span>
                            <span className="font-bold">
                                {Number(result?.f1f2 ?? 0).toLocaleString()}
                            </span>
                        </div>

                        <div className="flex justify-between">
                            <span className="font-light">new f3 entries:</span>
                            <span className="font-bold">
                                {Number(result?.f3 ?? 0).toLocaleString()}
                            </span>
                        </div>

                        <button
                            className="mt-3 btn btn-wide btn-sm"
                            onClick={() => toast.dismiss(t.id)}
                        >
                            Close
                        </button>
                    </div>
                ),
                { duration: Infinity, removeDelay: 400 }
            );
        } catch (error) {
            toast.dismiss(toastId);

            toast.custom(
                (t) => (
                    <div
                        className={clsx(
                            "p-4 m-2 rounded-lg shadow-lg text-error-content bg-error",
                            toastTransition(t)
                        )}
                    >
                        <div className="mb-1 font-bold">
                            Failed to import WIP.
                        </div>
                        <div className="text-sm opacity-80">
                            {importWipErrorMessage}
                        </div>
                    </div>
                ),
                { duration: 4000, removeDelay: 400 }
            );
        }
    };

    const handleShowTrendByPackage = ({ data, dataKey }) => {
        setIsTrendByPackageVisible(true);
        setSelectedPackageName(data?.Package_Name || null);
    };

    return (
        <AuthenticatedLayout>
            <Head title="WIP Trend" />
            <div className="flex justify-between">
                <h1 className="w-3/12 text-xl font-bold mb-4">WIP Trend</h1>
                <h1 className="text-sm">{!isOverallWipLoading && message}</h1>
            </div>

            <div className="flex w-full h-full">
                <div className="flex flex-col justify-between w-3/12 pr-4">
                    <div className="flex flex-col w-full">
                        <h1>Filter</h1>
                        <div className="flex flex-col text-sm items-center mb-4 xl:flex-row lg:justify-between">
                            <label
                                className={`label cursor-pointer px-2 rounded-lg ${
                                    !isWorkweek
                                        ? "bg-accent text-accent-content"
                                        : "text-gray-500"
                                }`}
                            >
                                Date Range
                            </label>
                            <input
                                type="checkbox"
                                checked={isWorkweek}
                                onChange={(e) => handleDateFilterChange(e)}
                                className="toggle peer-focus:ring-accent/50"
                            />
                            <label
                                className={`label cursor-pointer px-2 rounded-lg ${
                                    isWorkweek
                                        ? "bg-accent text-accent-content"
                                        : "text-gray-500"
                                }`}
                            >
                                Workweek
                            </label>
                        </div>

                        {isWorkweek ? (
                            <MultiSelectDropdown
                                options={Array.from(
                                    { length: 552 - 401 + 1 },
                                    (_, i) => (401 + i).toString()
                                )}
                                value={tempSelectedWorkWeek}
                                onChange={setTempSelectedWorkWeek}
                                className="max-w-sm"
                                buttonClassName="btn border border-base-content/20 w-full justify-between"
                                dropdownClassName="dropdown-content mt-1 border border-base-content/20 max-h-60 overflow-y-auto rounded-lg menu p-2 shadow bg-base-100 w-full"
                                chipClassName="badge badge-outline badge-accent gap-1"
                                clearButtonClassName="badge badge-soft badge-secondary gap-1"
                                selectLabel={[
                                    "Select WIP Lines",
                                    "Modify WIP Lines",
                                ]}
                            />
                        ) : (
                            <DatePicker
                                className="w-full rounded-lg input"
                                selected={tempStartDate}
                                onChange={handleDateChange}
                                startDate={tempStartDate}
                                endDate={tempEndDate}
                                selectsRange
                                isClearable
                                placeholderText="Select a date range"
                                dateFormat="MMM d, yyyy"
                            />
                        )}

                        <div className="flex flex-col w-full gap-2 mt-4 lg:flex-row">
                            <button
                                className="btn btn-outline btn-error"
                                onClick={handleResetFilter}
                            >
                                Reset
                            </button>
                            <button
                                className="btn btn-primary"
                                onClick={handleRefetch}
                                disabled={
                                    !isWorkweek
                                        ? !tempStartDate || !tempEndDate
                                        : tempSelectedWorkWeek.length === 0
                                }
                            >
                                Apply Filter
                            </button>
                        </div>
                    </div>

                    <div className="">
                        <button
                            className="shadow-lg btn btm-active btn-primary btn-wide"
                            onClick={() => manualWIPImportRef.current?.open()}
                        >
                            Refresh auto daily WIP import
                        </button>
                        <Modal
                            ref={manualWIPImportRef}
                            id="deletePartModal"
                            title="Refresh auto daily WIP import"
                            onClose={() => manualWIPImportRef.current?.close()}
                            className="max-w-lg"
                        >
                            <p className="py-4">
                                Are you sure? This will start the WIP import.
                                Current import progress (if any) will block this
                                action.
                            </p>

                            <div className="flex justify-end gap-2">
                                <button
                                    className="btn btn-soft btn-warning"
                                    onClick={async () => {
                                        manualWIPImportRef.current?.close();
                                        handleManualWIPImport();
                                    }}
                                    disabled={isImportWipLoading}
                                >
                                    {isImportWipLoading && (
                                        <span className="loading loading-spinner"></span>
                                    )}
                                    Proceed
                                </button>

                                <button
                                    className="btn"
                                    onClick={() =>
                                        manualWIPImportRef.current?.close()
                                    }
                                    disabled={isImportWipLoading}
                                >
                                    Cancel
                                </button>
                            </div>
                        </Modal>
                    </div>
                </div>

                <div className="border border-base-content/10 w-9/12 h-full p-4 rounded-lg shadow-lg bg-base-300">
                    <div className="overflow-x-auto">
                        {isOverallWipLoading ? (
                            <WIPTableSkeleton message={message} />
                        ) : overallWipErrorMessage ? (
                            <div className="text-red-500">
                                {overallWipErrorMessage}
                            </div>
                        ) : (
                            <WIPTable data={overallWipData} />
                        )}
                    </div>
                </div>
            </div>

            <div className="w-full p-4 mt-4 border border-base-content/10 rounded-lg bg-base-300">
                <h1 className="text-base divider divider-start">
                    Total Quantity Graph
                </h1>

                <div className="flex flex-wrap w-full space-x-4">
                    <div>
                        <TogglerButton
                            id="factory"
                            toggleButtons={TOGGLE_FACTORY_BUTTONS}
                            visibleBars={factoryVisibleBars}
                            toggleBar={handleToggleBar}
                            toggleAll={toggleAllFactory}
                        />
                    </div>

                    <div className="divider divider-horizontal"></div>

                    <div className="flex items-center space-x-2">
                        <div>Total Quantity</div>
                        <input
                            type="checkbox"
                            checked={selectedTotal === "lots"}
                            onChange={(e) => handleChangeTotalFilter(e)}
                            className="toggle"
                        />
                        <div>Total Lots</div>
                    </div>

                    <div className="divider divider-horizontal"></div>

                    <div>
                        <div>
                            <TogglerButton
                                id="production_line"
                                toggleButtons={TOGGLE_PL_BUTTONS}
                                visibleBars={plVisibleBars}
                                toggleBar={handleToggleBar}
                            />
                        </div>
                    </div>
                </div>
                <div className="mt-4 flex justify-center h-[500px]">
                    <StackedBarChart
                        data={sortedAllPackageFilteredData}
                        isLoading={isOverallSummaryWipLoading}
                        errorMessage={overallSummaryWipErrorMessage}
                        bars={
                            selectedTotal === "quantity"
                                ? summaryWipPLBarsQuantity
                                : summaryWipPLBarsLots
                        }
                        visibleBars={factoryVisibleBars}
                        onBarClick={handleShowTrendByPackage}
                    />
                </div>
                <div ref={trendByPackageChartRef}>
                    <WipTrendByPackage
                        isVisible={isTrendByPackageVisible}
                        packageName={selectedPackageName}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

const WIPTable = ({ data }) => {
    const formatNumber = (value) => (value || 0).toLocaleString();

    const rows = [
        {
            key: "F1",
            pl1: "total_f1_pl1",
            pl6: "total_f1_pl6",
            total: "f1_total_quantity",
        },
        {
            key: "F2",
            pl1: "total_f2_pl1",
            pl6: "total_f2_pl6",
            total: "f2_total_quantity",
        },
        {
            key: "F3",
            pl1: "total_f3_pl1",
            pl6: "total_f3_pl6",
            total: "f3_total_quantity",
        },
    ];

    const overallPl1 = rows.reduce(
        (sum, row) => sum + (data?.[row.pl1] || 0),
        0
    );
    const overallPl6 = rows.reduce(
        (sum, row) => sum + (data?.[row.pl6] || 0),
        0
    );
    const overallTotal = data?.total_quantity || 0;

    return (
        <table className="table w-full rounded-lg">
            <thead>
                <tr>
                    <th></th>
                    <th>PL1 WIP</th>
                    <th>PL6 WIP</th>
                    <th className="text-right">Total WIP</th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row) => (
                    <tr key={row.key}>
                        <th className="text-right">{row.key}</th>
                        <td>{formatNumber(data?.[row.pl1])}</td>
                        <td>{formatNumber(data?.[row.pl6])}</td>
                        <td className="text-right">
                            {formatNumber(data?.[row.total])}
                        </td>
                    </tr>
                ))}
                <tr className="rounded-lg">
                    <th className="text-right rounded-l-lg bg-primary/20">
                        Overall
                    </th>
                    <td className="font-bold bg-primary/20">
                        {formatNumber(overallPl1)}
                    </td>
                    <td className="font-bold bg-primary/20">
                        {formatNumber(overallPl6)}
                    </td>
                    <td className="font-bold rounded-r-lg text-right bg-primary/20">
                        {formatNumber(overallTotal)}
                    </td>
                </tr>
            </tbody>
        </table>
    );
};

const WIPTableSkeleton = ({ message }) => {
    const rows = ["F1", "F2", "F3", "Overall"];
    const columnCount = 3;

    return (
        <table className="table w-full">
            <thead>
                <tr>
                    <th></th>
                    <th>PL1 WIP</th>
                    <th>PL6 WIP</th>
                    <th className="text-right">Total WIP</th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row, idx) => (
                    <tr key={idx}>
                        <th className="text-right">{row}</th>
                        {idx === 0 && (
                            <td
                                colSpan={columnCount}
                                rowSpan={rows.length}
                                className="text-center align-middle skeleton"
                            >
                                {message}
                            </td>
                        )}
                    </tr>
                ))}
            </tbody>
        </table>
    );
};

export default WIPTrend;
