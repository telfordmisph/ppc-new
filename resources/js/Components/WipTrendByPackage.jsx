import { useFetch } from "@/Hooks/useFetch";
import React, { memo, useEffect, useMemo, useState } from "react";
import TrendLineChart from "./Charts/TrendLineChart";
import FloatingLabelInput from "./FloatingLabelInput";
import TogglerButton from "./TogglerButton";
import {
    TOGGLE_TOTAL_BUTTONS,
    TOGGLE_FACTORY_BUTTONS,
} from "@/Constants/toggleButtons";
import { FaEye, FaEyeSlash } from "react-icons/fa";
import TableChart from "./Charts/TableChart";
import { periodOptions } from "@/Constants/periodOptions";
import {
    formatPeriodLabel,
    formatPeriodTrendMessage,
} from "@/Utils/formatStatusMessage";
import { visibleLines as chartLines } from "@/Utils/chartLines";
import clsx from "clsx";
import MultiSelectSearchableDropdown from "./MultiSelectSearchableDropdown";
import { useWorkweekStore } from "@/Store/workweekListStore";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import { useSelectedFilteredStore } from "@/Store/selectedFilterStore";
import { WIP_LOTS } from "@/Constants/colors";

const WipTrendByPackage = memo(function WipTrendByPackage({
    isVisible,
    packageName = null,
    noChartTable = false,
}) {
    const {
        workWeeks: savedWorkWeeks,
        lookBack: savedLookBack,
        period: savedPeriod,
        offset: savedOffset,
        setSelectedWorkWeeks: setSavedWorkWeeks,
        setSelectedLookBack: setSavedSelectedLookBack,
        setSelectedPeriod: setSavedSelectedPeriod,
        setSelectedOffset: setSavedSelectedOffset,
    } = useSelectedFilteredStore();

    const [isChartTableVisible, setIsChartTableVisible] = useState(
        !noChartTable ? true : false
    );
    const [selectPeriod, setSelectedPeriod] = useState(savedPeriod || "weekly");
    const [selectedLookBack, setSelectedLookBack] = useState(
        savedLookBack || 20
    );
    const [selectedOffsetPeriod, setSelectedOffsetPeriod] = useState(
        savedOffset || 0
    );
    const [selectedWorkWeeks, setSelectedWorkWeek] = useState(
        savedWorkWeeks || []
    );
    const [visibleLines, setVisibleLines] = useState({
        totalLots: true,
        totalwip: true,
    });

    const {
        data: workWeekData,
        isLoading: isWorkWeekLoading,
        errorMessage: WorkWeekErrorMessage,
    } = useWorkweekStore();

    const toggleLine = (name, key) => {
        setVisibleLines((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    const params = {
        packageName: packageName,
        period: selectPeriod,
        lookBack: selectedLookBack,
        offsetDays: selectedOffsetPeriod,
        workweek: selectedWorkWeeks.join(","),
    };

    const {
        data: overallByPackageWipData,
        isLoading: isOveraByPackagellWipLoading,
        errorMessage: overallByPackageWipErrorMessage,
        fetch: overallByPackageWipFetch,
    } = useFetch(route("api.wip.overallByPackage"), {
        params: params,
        auto: false,
    });

    const xAxis = "label";

    useEffect(() => {
        if (packageName === null) return;

        const fetchData = async () => {
            await overallByPackageWipFetch();
        };

        fetchData();
    }, [
        selectPeriod,
        selectedLookBack,
        selectedOffsetPeriod,
        packageName,
        selectedWorkWeeks,
    ]);

    const datePeriod = formatPeriodLabel(selectPeriod);

    const fullLabel = formatPeriodTrendMessage(
        overallByPackageWipData,
        isOveraByPackagellWipLoading,
        selectPeriod,
        selectedLookBack,
        selectedOffsetPeriod,
        selectedWorkWeeks
    );

    const lines = useMemo(
        () =>
            chartLines({
                showQuantities: visibleLines.totalwip,
                showLots: visibleLines.totalLots,
                showFactories: { f1: true, f2: true, f3: true, overall: true },
                keyLines: WIP_LOTS,
            }),
        [visibleLines]
    );

    return (
        <div
            className={`border rounded-lg border-base-content/10 transition-all duration-300 ease-in-out transform origin-top ${
                isVisible
                    ? "opacity-100 p-4 scale-100"
                    : "opacity-0 scale-95 max-h-0 overflow-hidden"
            }`}
        >
            <div className="divider">
                <span className="text-md">
                    WIP Trend{" "}
                    <span className="bg-primary/50 px-4 rounded-lg">
                        {packageName}
                    </span>
                </span>
            </div>
            <div className="flex items-center gap-x-2 gap-y-4 flex-wrap">
                <div className="join items-center">
                    <span className="pr-2 btn btn-disabled join-item bg-red-500">
                        period
                    </span>
                    <div className="dropdown">
                        <div
                            tabIndex={0}
                            role="button"
                            className="btn border join-item"
                        >
                            {selectPeriod}
                        </div>
                        <ul
                            tabIndex="-1"
                            className="dropdown-content menu bg-base-100 rounded-box z-1 w-52 p-2 shadow-sm"
                        >
                            {periodOptions.map((option) => (
                                <li
                                    key={option.value}
                                    onClick={() => {
                                        setSelectedPeriod(option.value);
                                        setSavedSelectedPeriod(option.value);
                                    }}
                                >
                                    <a>{option.label}</a>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                <div
                    className={clsx(
                        "flex",
                        selectPeriod === "weekly" ? "hidden" : ""
                    )}
                >
                    <FloatingLabelInput
                        id="lookBack"
                        label={`Look back ${datePeriod}`}
                        value={selectedLookBack}
                        type="number"
                        onChange={(e) => {
                            setSelectedLookBack(e.target.value);
                            setSavedSelectedLookBack(e.target.value);
                        }}
                        className="h-9 m-1 w-34"
                        labelClassName="bg-base-300"
                    />
                    <FloatingLabelInput
                        id="offset"
                        label={`Offset days`}
                        value={selectedOffsetPeriod}
                        type="number"
                        onChange={(e) => {
                            setSelectedOffsetPeriod(Number(e.target.value));
                            setSavedSelectedOffset(Number(e.target.value));
                        }}
                        className="h-9 m-1 w-34"
                        labelClassName="bg-base-300"
                        alwaysFloatLabel
                    />
                </div>

                <div
                    className={clsx(selectPeriod === "weekly" ? "" : "hidden")}
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
                            setSelectedWorkWeek(value);
                            setSavedWorkWeeks(value);
                        }}
                        isLoading={isWorkWeekLoading}
                        itemName="Workweek List"
                        prompt="Select Workweek"
                        debounceDelay={500}
                        contentClassName="w-72 h-120"
                    />
                </div>

                <TogglerButton
                    toggleButtons={TOGGLE_TOTAL_BUTTONS}
                    visibleBars={visibleLines}
                    toggleBar={toggleLine}
                    buttonClassName="h-8"
                />

                {!noChartTable && (
                    <button
                        className="btn btn-sm btn-outline h-8 btn-secondary px-4"
                        onClick={() => setIsChartTableVisible((prev) => !prev)}
                    >
                        {}
                        {isChartTableVisible ? (
                            <FaEye className="mr-2" />
                        ) : (
                            <FaEyeSlash className="mr-2" />
                        )}
                        <span className="w-18">
                            {isChartTableVisible ? "Show Table" : "Hide Table"}
                        </span>
                    </button>
                )}
            </div>
            <div className="text-sm opacity-80">{fullLabel}</div>
            <div className="w-full">
                <TrendLineChart
                    data={overallByPackageWipData?.data || []}
                    xKey={xAxis}
                    isLoading={isOveraByPackagellWipLoading}
                    errorMessage={overallByPackageWipErrorMessage}
                    lines={lines}
                />
                {isChartTableVisible && !isOveraByPackagellWipLoading && (
                    <TableChart
                        data={overallByPackageWipData?.data || []}
                        exclude={["dateKey"]}
                    />
                )}
            </div>
        </div>
    );
});

export default WipTrendByPackage;
