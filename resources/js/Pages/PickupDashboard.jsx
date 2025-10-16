import React, { useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { useState } from "react";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import MultiSelectDropdown from "@/Components/MultiSelectDropdown";
import { useFetch } from "@/Hooks/useFetch";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import { buildDateRange } from "@/Utils/formatDate";
import { TbFilter } from "react-icons/tb";
import PickupBarChart from "@/Components/PickupBarChart";
import clsx from "clsx";
import { FaChartBar } from "react-icons/fa";
import BarChartSkeleton from "@/Components/BarChartSkeleton";

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
    const [tempStartDate, setTempStartDate] = useState(null);
    const [tempEndDate, setTempEndDate] = useState(null);
    const dateRange = buildDateRange(startDate, endDate);
    const [selectedChartStatus, setSelectedChartStatus] = useState(null);

    const {
        data: overallPickupData,
        loading: overallPickupLoading,
        error: overallPickupError,
        fetch: overallpickupFetch,
    } = useFetch(route("api.wip.pickup"), {
        params: {
            dateRange: dateRange || "",
        },
    });

    const {
        data: pickupSummaryData,
        loading: pickupSummaryLoading,
        error: pickupSummaryError,
        fetch: pickupSummaryFetch,
    } = useFetch(route("api.wip.packagePickupSummary"), {
        auto: false,
    });

    const verb = overallPickupLoading ? "Loading" : "Showing";

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
        if (overallPickupLoading) {
            console.log("Loading Pickup Data...");
        } else {
            console.log("Pickup Data Loaded O K N A AAAAAAAAAA.");
        }
    }, [overallPickupLoading]);

    const handleViewDetails = (chartStatus) => {
        console.log("View details for:", chartStatus);
        setSelectedChartStatus(chartStatus);
        pickupSummaryFetch({
            dateRange: dateRange || "",
            chartStatus: chartStatus,
        });
    };

    const handleDateChange = (date, type) => {
        if (type === "start") {
            setTempStartDate(date);
        } else {
            setTempEndDate(date);
        }
    };

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
        if (!tempStartDate && !tempEndDate) return false;
        if (!tempStartDate || !tempEndDate) return true;
        return tempStartDate > tempEndDate;
    };

    return (
        <AuthenticatedLayout>
            <Head title="Pickup Dashboard" />
            <div className="flex items-center justify-between">
                <h1 className="w-3/12 px-4 text-2xl font-bold">
                    Pickup Dashboard
                </h1>
                <h1>{!overallPickupLoading ? message : "Empty"}</h1>
            </div>

            <div className="md:flex">
                <div className="w-full md:w-4/12">
                    <div className="flex flex-col w-full h-full mb-4 md:mb-0 md:pr-4">
                        <div className="divider">
                            <label className={`label cursor-pointer`}>
                                Filter Date Range
                            </label>
                        </div>

                        <span>from</span>
                        <DatePicker
                            selected={tempStartDate}
                            onChange={(date) => handleDateChange(date, "start")}
                            placeholderText="Select start date and time"
                            showTimeSelect
                            timeFormat="h:mm aa"
                            timeIntervals={1}
                            dateFormat="MMM d, yyyy h:mm aa"
                            className="w-full rounded-lg input input-bordered"
                        />

                        <span className="mt-2">to</span>
                        <DatePicker
                            selected={tempEndDate}
                            onChange={(date) => handleDateChange(date, "end")}
                            placeholderText="Select end date and time"
                            showTimeSelect
                            timeFormat="h:mm aa"
                            timeIntervals={1}
                            dateFormat="MMM d, yyyy h:mm aa"
                            className="w-full rounded-lg input input-bordered"
                        />

                        <div className="flex justify-end w-full gap-2 mt-4">
                            <button
                                className="flex-1 btn btn-error"
                                onClick={resetFilter}
                            >
                                Reset
                            </button>
                            <button
                                className={clsx("btn btn-primary flex-1")}
                                onClick={handleRefetch}
                                disabled={isFilterDisabled()}
                            >
                                Apply Filter
                                <TbFilter className="w-5 h-5 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>

                <div className="p-4 rounded-lg shadow-md md:w-9/12 bg-base-200">
                    <div className="overflow-x-auto text-center md:text-left">
                        {overallPickupLoading ? (
                            <PickupTableSkeleton message={message} />
                        ) : overallPickupError ? (
                            <div className="text-red-500">
                                Error: {overallPickupError.message}
                            </div>
                        ) : (
                            <PickupTable
                                data={overallPickupData}
                                onSummaryView={handleViewDetails}
                            />
                        )}
                    </div>
                </div>
            </div>
            <h1 className="mt-10 text-xl text-center divider">
                {selectedChartStatus} Pickup Summary
            </h1>

            <div className="flex justify-center w-full h-[450px] p-4 mt-4 rounded-lg shadow-md bg-base-200">
                {pickupSummaryLoading ? (
                    <BarChartSkeleton />
                ) : (
                    <PickupBarChart data={pickupSummaryData?.data || []} />
                )}
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
                    <th className="text-right">Overall</th>

                    <td className="font-bold text-primary">
                        <div className="grid grid-cols-[1fr_auto] items-center w-full gap-2">
                            {formatNumber(overallPl1)}
                            <button
                                className="h-5 transition-colors border-transparent btn-sm btn bg-base-100 hover:border-primary"
                                onClick={() => onSummaryView("PL1")}
                            >
                                <FaChartBar />
                            </button>
                        </div>
                    </td>

                    <td className="font-bold text-secondary">
                        <div className="grid grid-cols-[1fr_auto] items-center w-full gap-2">
                            {formatNumber(overallPl6)}
                            <button
                                className="h-5 transition-colors border-transparent btn-sm btn bg-base-100 hover:border-secondary"
                                onClick={() => onSummaryView("PL6")}
                            >
                                <FaChartBar />
                            </button>
                        </div>
                    </td>

                    <td className="font-bold text-accent">
                        <div className="grid grid-cols-[1fr_auto] items-center w-full gap-2">
                            {formatNumber(overallTotal)}
                            <button
                                className="h-5 transition-colors border-transparent btn-sm btn bg-base-100 hover:border-accent"
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
