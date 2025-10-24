import React, { useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { useState } from "react";
import "react-datepicker/dist/react-datepicker.css";
import { useFetch } from "@/Hooks/useFetch";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import { buildDateRange } from "@/Utils/formatDate";
import { TbFilter } from "react-icons/tb";
import PickupBarChart from "@/Components/Charts/PickupBarChart";
import clsx from "clsx";
import { FaChartBar } from "react-icons/fa";
import formatDateToLocalInput from "@/Utils/formatDateToLocalInput";
import FloatingLabelInput from "@/Components/FloatingLabelInput";

const PickupDashboard = () => {
    const [startDate, setStartDate] = useState(() => {
        const date = new Date();
        date.setHours(0, 0, 0, 0);
        return date;
    });

    const [endDate, setEndDate] = useState(() => {
        const date = new Date();
        date.setHours(23, 59, 59, 999);
        return date;
    });
    const [tempStartDate, setTempStartDate] = useState(startDate);
    const [tempEndDate, setTempEndDate] = useState(endDate);
    const dateRange = buildDateRange(startDate, endDate);
    const [selectedChartStatus, setSelectedChartStatus] = useState(null);

    const {
        data: overallPickupData,
        isLoading: isOverallPickupLoading,
        errorMessage: overallPickupErrorMessage,
        fetch: overallpickupFetch,
    } = useFetch(route("api.wip.pickup"), {
        params: {
            dateRange: dateRange || "",
        },
    });

    const {
        data: pickupSummaryData,
        isLoading: isPickupSummaryLoading,
        errorMessage: pickupSummaryErrorMessage,
        fetch: pickupSummaryFetch,
    } = useFetch(route("api.wip.packagePickupSummary"), {
        auto: false,
    });

    const verb = isOverallPickupLoading ? "Loading" : "Showing";

    const filterType = dateRange ? "dateRange" : "today";

    const filter = dateRange
        ? `${formatFriendlyDate(startDate, true)} and ${formatFriendlyDate(
              endDate,
              true
          )}`
        : formatFriendlyDate(new Date());

    const message =
        filterType === "dateRange"
            ? `${verb} Pickup between ${filter}`
            : filterType === "workweek"
            ? `${verb} Pickup on ${filter}`
            : `${verb} Pickup on ${filter}`;

    useEffect(() => {
        if (isOverallPickupLoading) {
            console.log("Loading Pickup Data...");
        } else {
            console.log("Pickup Data Loaded O K N A AAAAAAAAAA.");
        }
    }, [isOverallPickupLoading]);

    const handleViewDetails = (chartStatus) => {
        console.log("View details for:", chartStatus);
        setSelectedChartStatus(chartStatus);
        pickupSummaryFetch({
            dateRange: dateRange || "",
            chartStatus: chartStatus,
        });
    };

    function handleDateChange(date, type) {
        if (!date) {
            if (type === "start") {
                setTempStartDate(date);

                if (tempStartDate > tempEndDate) {
                    setTempEndDate(tempStartDate);
                }
            } else {
                setTempEndDate(date);
            }

            return;
        }

        if (type === "start") setTempStartDate(date);
        else setTempEndDate(date);
    }

    const resetFilter = () => {
        setTempStartDate(null);
        setTempEndDate(null);
    };

    useEffect(() => {
        console.log("date", startDate, endDate);
    }, [startDate, endDate]);

    const handleRefetch = () => {
        const newDateRange = buildDateRange(tempStartDate, tempEndDate);
        setStartDate(tempStartDate);
        setEndDate(tempEndDate);
        overallpickupFetch({ dateRange: newDateRange });
    };

    const isFilterDisabled = () => {
        const start = new Date(tempStartDate);
        const end = new Date(tempEndDate);

        if (!tempStartDate && !tempEndDate) return true;
        if (!tempStartDate || !tempEndDate) return true;
        if (isNaN(start.getTime()) || isNaN(end.getTime())) return true;

        return start > end;
    };

    return (
        <AuthenticatedLayout>
            <Head title="Pickup Dashboard" />
            <div className="flex items-center justify-between">
                <h1 className="w-3/12 text-base font-bold">Pickup Dashboard</h1>
                <h1 className="text-sm text-right sm:text-md">
                    {!isOverallPickupLoading ? message : "Empty"}
                </h1>
            </div>

            <div className="md:flex">
                <div className="w-full md:w-4/12">
                    <div className="justify-between flex flex-col w-full h-full md:mb-0 md:pr-4">
                        <div>
                            <label className={`label cursor-pointer`}>
                                Filter Date Range
                            </label>

                            <div className="flex mt-4 flex-col gap-4">
                                <FloatingLabelInput
                                    id="pickup_start_date_input"
                                    label="Start date and time"
                                    type="datetime-local"
                                    value={formatDateToLocalInput(
                                        tempStartDate
                                    )}
                                    onChange={(e) => {
                                        const val = e.target.value;
                                        handleDateChange(
                                            val ? new Date(val) : null,
                                            "start"
                                        );
                                    }}
                                    labelClassName="bg-base-300"
                                />

                                <FloatingLabelInput
                                    id="pickup_end_date_input"
                                    label="End date and time"
                                    type="datetime-local"
                                    value={formatDateToLocalInput(tempEndDate)}
                                    onChange={(e) => {
                                        const val = e.target.value;
                                        handleDateChange(
                                            val ? new Date(val) : null,
                                            "end"
                                        );
                                    }}
                                    labelClassName="bg-base-300"
                                    min={
                                        tempStartDate instanceof Date &&
                                        !isNaN(tempStartDate)
                                            ? tempStartDate
                                                  .toISOString()
                                                  .slice(0, 16)
                                            : ""
                                    }
                                    disabled={!tempStartDate}
                                    helperText={
                                        !tempStartDate
                                            ? "Pick your start date first"
                                            : tempStartDate instanceof Date &&
                                              isNaN(tempStartDate)
                                            ? "Pick your valid start date first"
                                            : ""
                                    }
                                    errorText={
                                        tempStartDate > tempEndDate
                                            ? "Start date must be less than End Date"
                                            : ""
                                    }
                                />
                            </div>
                        </div>

                        <div className="flex w-full gap-2 lg:flex-row">
                            <button
                                className="flex-1 btn-outline btn btn-error"
                                onClick={resetFilter}
                            >
                                Reset
                            </button>
                            <button
                                className={clsx(
                                    "btn  btn-soft btn-primary flex-1"
                                )}
                                onClick={handleRefetch}
                                disabled={isFilterDisabled()}
                            >
                                Apply Filter
                                <TbFilter className="w-5 h-5 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>

                <div className="p-4 rounded-lg shadow-lg md:w-9/12 bg-base-200">
                    <div className="overflow-x-auto text-center md:text-left">
                        {isOverallPickupLoading ? (
                            <PickupTableSkeleton message={message} />
                        ) : overallPickupErrorMessage ? (
                            <div className="text-red-500">
                                Error: {overallPickupErrorMessage.message}
                            </div>
                        ) : (
                            <>
                                <PickupTable
                                    data={overallPickupData}
                                    onSummaryView={handleViewDetails}
                                />
                                <div className="flex items-center w-full px-4 space-x-2 text-sm opacity-50">
                                    <span>Preview summary by clicking </span>
                                    <button className="h-6 pointer-events-none btn btn-sm">
                                        <FaChartBar />
                                    </button>
                                    <span>.</span>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
            <h1 className="mt-10 text-base text-center divider">
                {selectedChartStatus} Pickup Summary
            </h1>

            <div className="flex justify-center w-full h-[450px] p-4 mt-4 rounded-lg shadow-lg bg-base-200">
                <PickupBarChart
                    data={pickupSummaryData?.data || []}
                    isLoading={isPickupSummaryLoading}
                />
            </div>
        </AuthenticatedLayout>
    );
};

const PickupTable = ({ data, onSummaryView }) => {
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
        <table className="table w-full">
            <thead>
                <tr>
                    <th></th>
                    <th>PL1 Pickup</th>
                    <th>PL6 Pickup</th>
                    <th>Total Pickup</th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row) => (
                    <tr className="hover:bg-base-100" key={row.key}>
                        <th className="w-10 h-10 text-right">{row.key}</th>

                        <td>
                            <div className="grid grid-cols-[1fr_auto] items-center w-full group">
                                <span className="transition-colors">
                                    {formatNumber(data?.[row.pl1])}
                                </span>
                            </div>
                        </td>

                        <td>
                            <div className="grid grid-cols-[1fr_auto] items-center w-full group">
                                <span className="transition-colors">
                                    {formatNumber(data?.[row.pl6])}
                                </span>
                            </div>
                        </td>

                        <td className="h-10">
                            <div className="flex justify-between w-full h-full align-middle group">
                                <span className="content-center h-full transition-colors group-hover:text-accent">
                                    {formatNumber(data?.[row.total])}
                                </span>
                                <div className="tooltip" data-tip="">
                                    <button
                                        className="h-5 transition-colors border-transparent btn-sm btn hover:border-accent"
                                        onClick={() => onSummaryView(row.key)}
                                    >
                                        <FaChartBar />
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                ))}

                <tr className="hover:bg-base-100">
                    <th className="text-right rounded-l-lg bg-primary/20">
                        Overall
                    </th>

                    <td className="font-bold bg-primary/20">
                        <div className="grid grid-cols-[1fr_auto] items-center w-full gap-2">
                            {formatNumber(overallPl1)}
                            <button
                                className="h-5 transition-colors border-transparent btn-sm btn bg-base-100"
                                onClick={() => onSummaryView("PL1")}
                            >
                                <FaChartBar />
                            </button>
                        </div>
                    </td>

                    <td className="font-bold bg-primary/20">
                        <div className="grid grid-cols-[1fr_auto] items-center w-full gap-2">
                            {formatNumber(overallPl6)}
                            <button
                                className="h-5 transition-colors border-transparent btn-sm btn bg-base-100"
                                onClick={() => onSummaryView("PL6")}
                            >
                                <FaChartBar />
                            </button>
                        </div>
                    </td>

                    <td className="font-bold rounded-r-lg bg-primary/20">
                        <div className="grid grid-cols-[1fr_auto] items-center w-full gap-2">
                            {formatNumber(overallTotal)}
                            <button
                                className="h-5 transition-colors border-transparent btn-sm btn bg-base-100"
                                onClick={() => onSummaryView("all")}
                            >
                                <FaChartBar />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    );
};

const PickupTableSkeleton = ({ message }) => {
    const rows = ["F1", "F2", "F3", "Overall"];
    const columnCount = 3;

    return (
        <table className="table w-full">
            <thead>
                <tr>
                    <th></th>
                    <th>PL1 Pickup</th>
                    <th>PL6 Pickup</th>
                    <th>Total Pickup</th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row, idx) => (
                    <tr key={idx}>
                        <th className="w-10 h-10 text-right">{row}</th>
                        {idx === 0 && (
                            <td
                                colSpan={columnCount}
                                rowSpan={rows.length}
                                className="w-full text-center align-middle skeleton"
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

export default PickupDashboard;
