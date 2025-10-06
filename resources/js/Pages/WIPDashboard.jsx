import React, { useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { useState } from "react";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import MultiSelectDropdown from "@/Components/MultiSelectDropdown";
import { useFetch } from "@/Hooks/useFetch";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import formatDate from "@/Utils/formatDate";

const WIPDashboard = () => {
    const [startDate, setStartDate] = useState(null);
    const [endDate, setEndDate] = useState(null);
    const today = new Date();
    const [tempStartDate, setTempStartDate] = useState(
        new Date(today.setHours(0, 0, 0, 0))
    );
    const [tempEndDate, setTempEndDate] = useState(
        new Date(today.setHours(23, 59, 59, 999))
    );

    const [isWorkweek, setIsWorkWeek] = useState(false);

    const [selectedWorkWeek, setSelectedWorkWeek] = useState([]);
    const [tempSelectedWorkWeek, setTempSelectedWorkWeek] = useState([]);

    const [selectedFactory, setSelectedFactory] = useState("Select All");

    const dateRange =
        startDate && endDate
            ? `${formatDate(startDate)} - ${formatDate(endDate)}`
            : "";

    console.log("🚀 ~ WIPDashboard ~ tempStartDate:", tempStartDate);
    console.log("🚀 ~ WIPDashboard ~ tempEndDate:", tempEndDate);
    console.log("🚀 ~ WIPDashboard ~ dateRange:", dateRange);

    const {
        data,
        loading,
        error,
        fetch: refetch,
    } = useFetch("/api/overall-wip", {
        params: {
            dateRange: startDate && endDate ? dateRange : "",
            workweek: selectedWorkWeek.join(" ") || "",
        },
        deps: [dateRange, selectedWorkWeek],
    });

    const verb = loading ? "Loading" : "Showing";

    const filterType = dateRange
        ? "dateRange"
        : selectedWorkWeek && selectedWorkWeek.length > 0
        ? "workweek"
        : "today";

    const filter = dateRange
        ? `${formatFriendlyDate(startDate)} and ${formatFriendlyDate(endDate)}`
        : selectedWorkWeek && selectedWorkWeek.length > 0
        ? "selected workweeks: " + selectedWorkWeek.join(", ")
        : formatFriendlyDate(today);

    const message =
        filterType === "dateRange"
            ? `${verb} WIP between ${filter}`
            : filterType === "workweek"
            ? `${verb} WIP on ${filter}`
            : `${verb} WIP on ${filter}`;

    useEffect(() => {
        if (loading) {
            console.log("Loading WIP Data...");
        } else {
            console.log("WIP Data Loaded O K N A AAAAAAAAAA.");
        }
    }, [loading]);

    const handleDateChange = (dates) => {
        const [start, end] = dates;
        setTempStartDate(start);
        setTempEndDate(end);
    };

    const resetFilter = () => {
        setTempStartDate(null);
        setTempEndDate(null);
        setTempSelectedWorkWeek([]);
        setSelectedFactory("Select All");
    };

    useEffect(() => {
        console.log("date", startDate, endDate);
        console.log("workweek", selectedWorkWeek);
    }, [isWorkweek, startDate, endDate, selectedWorkWeek]);

    const handleDateFilterChange = (e) => {
        setIsWorkWeek(e.target.checked);

        if (!e.target.checked) {
            setTempSelectedWorkWeek([]);
        } else {
            setTempStartDate(null);
            setTempEndDate(null);
        }
    };

    const handleRefetch = () => {
        if (!isWorkweek) {
            const newDateRange = `${formatDate(tempStartDate)} - ${formatDate(
                tempEndDate
            )}`;
            setStartDate(tempStartDate);
            setEndDate(tempEndDate);
            refetch({ dateRange: newDateRange, workweek: "" });
        } else {
            const newWorkweek = tempSelectedWorkWeek.join(" ");
            setSelectedWorkWeek(tempSelectedWorkWeek);
            refetch({ dateRange: "", workweek: newWorkweek });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="WIP Dashboard" />
            <div className="flex justify-between">
                <h1 className="w-3/12 px-4 text-2xl font-bold">
                    WIP Dashboard
                </h1>
                <h1>{!loading && message}</h1>
            </div>

            <div className="flex">
                <div className="w-4/12">
                    <div className="flex flex-col w-full h-full p-4">
                        <h1>Filter</h1>
                        <div className="divider">
                            <label
                                className={`label cursor-pointer ${
                                    !isWorkweek
                                        ? "text-secondary"
                                        : "text-gray-500"
                                }`}
                            >
                                Date Range
                            </label>

                            <input
                                type="checkbox"
                                checked={isWorkweek}
                                onChange={(e) => handleDateFilterChange(e)}
                                className="bg-secondary toggle checked:bg-primary"
                            />

                            <label
                                className={`label cursor-pointer ${
                                    isWorkweek
                                        ? "text-primary"
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
                                buttonClassName="btn btn-outline w-full justify-between"
                                dropdownClassName="dropdown-content max-h-60 overflow-y-auto rounded-lg menu p-2 shadow bg-base-100 w-full"
                                chipClassName="badge badge-secondary gap-1"
                                clearButtonClassName="badge badge-warning gap-1"
                                selectLabel={[
                                    "Select WIP Lines",
                                    "Modify WIP Lines",
                                ]}
                            />
                        ) : (
                            <DatePicker
                                className="w-full rounded-lg input input-bordered"
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

                        <div className="flex w-full gap-2 mt-4">
                            <button
                                className="btn btn-error"
                                onClick={resetFilter}
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
                </div>

                <div className="w-9/12 p-4 rounded-lg shadow-md bg-base-200">
                    <div className="overflow-x-auto">
                        {loading ? (
                            <WIPTableSkeleton message={message} />
                        ) : error ? (
                            <div className="text-red-500">
                                Error: {error.message}
                            </div>
                        ) : (
                            <WIPTable data={data} />
                        )}
                    </div>
                </div>
            </div>

            <div>
                <div className="divider divider-start">Include WIP</div>

                <select
                    defaultValue="Select All"
                    className="w-full select"
                    onChange={(e) => setSelectedFactory(e.target.value)}
                >
                    <option>Select All</option>
                    <option>F1</option>
                    <option>F2</option>
                    <option>F3</option>
                </select>
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
                <tr>
                    <th className="text-right">Overall</th>
                    <td className="font-bold text-primary">
                        {formatNumber(overallPl1)}
                    </td>
                    <td className="font-bold text-secondary">
                        {formatNumber(overallPl6)}
                    </td>
                    <td className="font-bold text-right text-accent">
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

export default WIPDashboard;
