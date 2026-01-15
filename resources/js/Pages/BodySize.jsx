import React, { useState } from "react";
import { Head } from "@inertiajs/react";
import { useFetch } from "@/Hooks/useFetch";
import FloatingLabelInput from "@/Components/FloatingLabelInput";
import {
    formatPeriodLabel,
    formatPeriodTrendMessage,
} from "@/Utils/formatStatusMessage";
import { nonTrendPeriodOptions } from "@/Constants/periodOptions";
import { useF1F2PackagesStore } from "@/Store/f1f2PackageListStore";
import { useSelectedFilteredStore } from "@/Store/selectedFilterStore";
import clsx from "clsx";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import { useWorkweekStore } from "@/Store/workweekListStore";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import DatePicker from "react-datepicker";
import formatDate from "@/Utils/formatDate";
import StackedBarChart from "@/Components/Charts/StackedBarChart";
import "react-datepicker/dist/react-datepicker.css";

const BodySize = () => {
    const {
        packageNames: savedSelectedPackages,
        workWeeks: savedWorkWeeks,
        lookBack: savedLookBack,
        period: savedPeriod,
        offset: savedOffset,
        startDate: savedStartDate,
        endDate: savedEndDate,
        setSelectedPackageNames: setSavedSelectedPackageName,
        setSelectedWorkWeeks: setSavedWorkWeeks,
        setSelectedLookBack: setSavedSelectedLookBack,
        setSelectedPeriod: setSavedSelectedPeriod,
        setSelectedOffset: setSavedSelectedOffset,
        setSelectedStartDate: setSavedStartDate,
        setSelectedEndDate: setSavedEndDate,
    } = useSelectedFilteredStore();
    const [fullLabel, setFullLabel] = useState("");
    const [selectedPackageNames, setSelectedPackageNames] = useState(
        savedSelectedPackages
    );
    const [selectedWorkWeeks, setSelectedWorkWeeks] = useState(
        savedWorkWeeks || []
    );
    const [startDate, setStartDate] = useState(savedStartDate);
    const [endDate, setEndDate] = useState(savedEndDate);
    const [selectedPeriod, setSelectedPeriod] = useState(savedPeriod);
    const [selectedLookBack, setSelectedLookBack] = useState(savedLookBack);
    const [selectedOffsetPeriod, setSelectedOffsetPeriod] =
        useState(savedOffset);

    const {
        data: workWeekData,
        isLoading: isWorkWeekLoading,
        errorMessage: WorkWeekErrorMessage,
    } = useWorkweekStore();

    // const packagesData = ["LFCSP", "QFN/DFN"];

    const {
        data: packagesData,
        isLoading: isPackagesLoading,
        errorMessage: packagesErrorMessage,
    } = useF1F2PackagesStore();

    let dateRange = `${formatDate(startDate)} - ${formatDate(endDate)}`;

    const params = {
        packageName: selectedPackageNames,
        period: selectedPeriod,
        dateRange: dateRange,
        workweek:
            selectedPeriod === "weekly" ? selectedWorkWeeks.join(" ") : "",
    };

    const {
        data: bodySizeWipData,
        isLoading: isBodySizeWipLoading,
        errorMessage: bodySizeWipErrorMessage,
        fetch: bodySizeWipFetch,
        abort: bodySizeWipAbort,
    } = useFetch(route("api.wip.wipAndLotsByBodySize"), {
        params: params,
    });

    const handleSearch = () => {
        bodySizeWipAbort();
        bodySizeWipFetch();

        setFullLabel(
            formatPeriodTrendMessage(
                1,
                isBodySizeWipLoading,
                selectedPeriod,
                selectedLookBack,
                selectedOffsetPeriod,
                selectedWorkWeeks
            )
        );
    };

    const datePeriod = formatPeriodLabel(selectedPeriod);

    const handleWorkWeekChange = (selectedWorkWeek) => {
        setSelectedWorkWeeks(selectedWorkWeek);
        setSavedWorkWeeks(selectedWorkWeek);
    };

    const handlePeriodSelect = (period) => {
        setSelectedPeriod(period);
        setSavedSelectedPeriod(period);
    };

    const handleLookBackChange = (lookBack) => {
        setSelectedLookBack(lookBack);
        setSavedSelectedLookBack(lookBack);
    };

    const handleOffsetChange = (offset) => {
        setSelectedOffsetPeriod(Number(offset));
        setSavedSelectedOffset(Number(offset));
    };

    const handlePackageNamesChange = (selectedPackages) => {
        setSelectedPackageNames(selectedPackages);
        setSavedSelectedPackageName(selectedPackages);
    };

    const handleDateChange = (dates) => {
        const [start, end] = dates;
        setStartDate(start);
        setEndDate(end);

        if (!start || !end) return;
        setSavedStartDate(start);
        setSavedEndDate(end);
    };

    const commonChartProps = ({ fillWip, fillLot }) => {
        return {
            xAxisDataKey: "size_bucket",
            isLoading: isBodySizeWipLoading,
            errorMessage: bodySizeWipErrorMessage,
            bars: [
                {
                    dataKey: "total_wip",
                    fill: fillWip,
                    visibilityKey: "size_bucket",
                    yAxisId: "left",
                },
                {
                    dataKey: "total_lots",
                    fill: fillLot,
                    visibilityKey: "size_bucket",
                    yAxisId: "right",
                },
            ],
            visibleBars: {
                size_bucket: true,
            },
        };
    };

    return (
        <>
            <Head title="WIP Station" />
            <h1 className="text-base font-bold mb-4">WIP Station</h1>
            <div className="flex flex-wrap w-full items-center gap-x-2 gap-y-4 mb-2">
                <div className="join items-center">
                    <span className="join-item btn btn-disabled font-medium">
                        Package Name
                    </span>
                    <MultiSelectSearchableDropdown
                        options={
                            packagesData?.data.map((opt) => ({
                                value: opt,
                                label: null,
                            })) || []
                        }
                        onChange={handlePackageNamesChange}
                        defaultSelectedOptions={selectedPackageNames}
                        isLoading={isPackagesLoading}
                        itemName="Package List"
                        prompt="Select packages"
                        contentClassName="w-32 h-70"
                    />
                </div>

                <div className="join items-center">
                    <span className="join-item btn btn-disabled font-medium">
                        Period
                    </span>

                    <button
                        className="join-item btn rounded-r-lg border-base-content/10 w-20"
                        popoverTarget="popover-period"
                        style={{ anchorName: "--anchor-period" }}
                    >
                        {selectedPeriod}
                    </button>

                    <ul
                        className="dropdown menu w-52 rounded-box bg-base-100 shadow-sm"
                        popover="auto"
                        id="popover-period"
                        style={{ positionAnchor: "--anchor-period" }}
                    >
                        {nonTrendPeriodOptions.map((option) => (
                            <li key={option.value}>
                                <a
                                    onClick={() => {
                                        handlePeriodSelect(option.value);
                                    }}
                                >
                                    {option.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>

                <div
                    className={clsx(
                        "flex",
                        selectedPeriod === "daily" ? "" : "hidden"
                    )}
                >
                    <DatePicker
                        className="w-full rounded-lg input z-50"
                        selected={startDate}
                        onChange={handleDateChange}
                        startDate={startDate}
                        endDate={endDate}
                        selectsRange
                        isClearable
                        placeholderText="Select a date range"
                        dateFormat="MMM d, yyyy"
                    />
                </div>

                <div
                    className={clsx(
                        "flex",
                        selectedPeriod === "weekly" ||
                            selectedPeriod === "daily"
                            ? "hidden"
                            : ""
                    )}
                >
                    <FloatingLabelInput
                        id="lookBack"
                        label={`Look back ${datePeriod}`}
                        value={selectedLookBack}
                        type="number"
                        onChange={(e) => {
                            handleLookBackChange(e.target.value);
                        }}
                        className="h-9 w-44"
                        labelClassName="bg-base-200"
                    />

                    <FloatingLabelInput
                        id="offset"
                        label={`Offset days`}
                        value={selectedOffsetPeriod}
                        type="number"
                        onChange={(e) => {
                            handleOffsetChange(e.target.value);
                        }}
                        className="h-9 w-44"
                        labelClassName="bg-base-200"
                        alwaysFloatLabel
                    />
                </div>

                <div
                    className={clsx(
                        selectedPeriod === "weekly" ? "" : "hidden"
                    )}
                >
                    <MultiSelectSearchableDropdown
                        options={
                            workWeekData?.data.map((item) => ({
                                value: String(item.cal_workweek),
                                label: `${formatFriendlyDate(
                                    item.startDate
                                )} - ${formatFriendlyDate(item.endDate)}`,
                            })) || []
                        }
                        defaultSelectedOptions={selectedWorkWeeks}
                        onChange={(value) => {
                            handleWorkWeekChange(value);
                        }}
                        isLoading={isWorkWeekLoading}
                        itemName="Workweek List"
                        prompt="Select Workweek"
                        debounceDelay={500}
                        contentClassName="w-72 h-120"
                    />
                </div>

                <button
                    className="btn btn-primary h-9"
                    onClick={handleSearch}
                    disabled={
                        !selectedPackageNames.length ||
                        !selectedPeriod ||
                        (selectedPeriod === "weekly" &&
                            !selectedWorkWeeks.length)
                    }
                >
                    Get Data
                </button>
            </div>
            <div
                className={clsx(
                    "mt-4",
                    fullLabel ? "opacity-100" : "opacity-0"
                )}
            >
                {fullLabel}
            </div>
            <div
                className={clsx(
                    "text-error-content bg-error/10 border border-error rounded-lg p-4 mt-4",
                    bodySizeWipErrorMessage ? "block" : "hidden"
                )}
            >
                {bodySizeWipErrorMessage ? bodySizeWipErrorMessage : ""}
            </div>
            <div className="border border-base-content/10 rounded-lg mt-4 flex flex-col justify-center h-[500px]">
                <div className="p-2 text-center w-full border rounded-t-lg border-f1color/50 border-b-transparent">
                    F1
                </div>
                <StackedBarChart
                    data={bodySizeWipData?.data?.f1}
                    xAxisDataKey={"size_bucket"}
                    {...commonChartProps({
                        fillWip: "var(--color-f1color)",
                        fillLot: "var(--color-f1color-dim)",
                    })}
                />
            </div>
            <div className="border border-base-content/10 rounded-lg mt-4 flex flex-col justify-center h-[500px]">
                <div className="p-2 text-center w-full border rounded-t-lg border-f2color/50 border-b-transparent">
                    F2
                </div>
                <StackedBarChart
                    data={bodySizeWipData?.data?.f2}
                    xAxisDataKey={"size_bucket"}
                    {...commonChartProps({
                        fillWip: "var(--color-f2color)",
                        fillLot: "var(--color-f2color-dim)",
                    })}
                />
            </div>
            <div className="border border-base-content/10 rounded-lg mt-4 flex flex-col justify-center h-[500px]">
                <div className="p-2 text-center w-full border rounded-t-lg border-f3color/50 border-b-transparent">
                    F3
                </div>
                <StackedBarChart
                    data={bodySizeWipData?.data?.f3}
                    xAxisDataKey={"size_bucket"}
                    {...commonChartProps({
                        fillWip: "var(--color-f3color)",
                        fillLot: "var(--color-f3color-dim)",
                    })}
                />
            </div>
        </>
    );
};

export default BodySize;
