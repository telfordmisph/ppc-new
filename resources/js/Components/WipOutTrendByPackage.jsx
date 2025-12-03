import { useFetch } from "@/Hooks/useFetch";
import React, { useEffect, useState, useMemo } from "react";
import TrendLineChart from "./Charts/TrendLineChart";
import FloatingLabelInput from "./FloatingLabelInput";
import { FaEye, FaEyeSlash } from "react-icons/fa";
import TableChart from "./Charts/TableChart";
import { periodOptions } from "@/Constants/periodOptions";
import {
    formatPeriodLabel,
    formatPeriodTrendMessage,
} from "@/Utils/formatStatusMessage";
import { useWorkweekStore } from "@/Store/workweekListStore";
import clsx from "clsx";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import MultiSelectSearchableDropdown from "./MultiSelectSearchableDropdown";
import { useF1F2PackagesStore } from "@/Store/f1f2PackageListStore";
import Tabs from "./Tabs";
import { useSelectedFilteredStore } from "@/Store/selectedFilterStore";
import { visibleLines } from "@/Utils/chartLines";

const test = ["F1", "F2", "F3", "Overall"];

const WipOutTrendByPackage = ({
    isVisible,
    title = "",
    dataAPI = null,
    showLines = {},
    noChartTable = false,
}) => {
    const [isChartTableVisible, setIsChartTableVisible] = useState(
        !noChartTable ? true : false
    );
    const {
        packageNames: savedSelectedPackageNames,
        workWeeks: savedWorkWeeks,
        lookBack: savedLookBack,
        period: savedPeriod,
        offset: savedOffset,
        factory: savedFactory,
        setSelectedPackageNames: setSavedSelectedPackage,
        setSelectedWorkWeeks: setSavedWorkWeeks,
        setSelectedLookBack: setSavedSelectedLookBack,
        setSelectedPeriod: setSavedSelectedPeriod,
        setSelectedOffset: setSavedSelectedOffset,
        setSelectedFactory: setSavedSelectedFactory,
    } = useSelectedFilteredStore();

    const [selectedPackageNames, setSelectedPackageNames] = useState(
        savedSelectedPackageNames || []
    );
    const [selectedWorkWeeks, setSelectedWorkWeeks] = useState(
        savedWorkWeeks || []
    );
    const [selectedLookBack, setSelectedLookBack] = useState(
        savedLookBack || 20
    );
    const [selectedOffsetPeriod, setSelectedOffsetPeriod] = useState(
        savedOffset || 0
    );
    const [selectPeriod, setSelectedPeriod] = useState(savedPeriod || "weekly");
    const [selectedFactory, setSelectedFactory] = useState(
        savedFactory || "Overall"
    );

    const params = {
        packageName: selectedPackageNames.join(","),
        period: selectPeriod,
        lookBack: selectedLookBack,
        offsetDays: selectedOffsetPeriod,
        workweek: selectedWorkWeeks.join(","),
    };

    const {
        data: workWeekData,
        isLoading: isWorkWeekLoading,
        errorMessage: WorkWeekErrorMessage,
    } = useWorkweekStore();

    const {
        data: packagesData,
        isLoading: isPackagesLoading,
        errorMessage: packagesErrorMessage,
    } = useF1F2PackagesStore();

    const {
        data: overallByPackageWipData,
        isLoading: isOveraByPackagellWipLoading,
        errorMessage: overallByPackageWipErrorMessage,
        fetch: overallByPackageWipFetch,
    } = useFetch(dataAPI, {
        params: params,
        auto: false,
    });

    const handlePackageNamesChange = (selectedPackages) => {
        setSelectedPackageNames(selectedPackages);
        setSavedSelectedPackage(selectedPackages);
    };

    const handleFactoryChange = (selectedFactory) => {
        setSelectedFactory(selectedFactory);
        setSavedSelectedFactory(selectedFactory);
    };

    const handleWorkWeekChange = (selectedWorkWeek) => {
        setSelectedWorkWeeks(selectedWorkWeek);
        setSavedWorkWeeks(selectedWorkWeek);
    };

    const handleLookBackChange = (selectedLookBack) => {
        setSelectedLookBack(selectedLookBack);
        setSavedSelectedLookBack(selectedLookBack);
    };

    const handlePeriodChange = (selectedPeriod) => {
        setSelectedPeriod(selectedPeriod);
        setSavedSelectedPeriod(selectedPeriod);
    };

    const handleOffsetChange = (selectedOffsetPeriod) => {
        setSelectedOffsetPeriod(selectedOffsetPeriod);
        setSavedSelectedOffset(selectedOffsetPeriod);
    };

    const xAxis = "label";

    const handleSearch = async () => {
        if (selectedPackageNames === null) return;

        await overallByPackageWipFetch(params);
    };

    const datePeriod = formatPeriodLabel(selectPeriod);

    const fullLabel = formatPeriodTrendMessage(
        isOveraByPackagellWipLoading,
        selectPeriod,
        selectedLookBack,
        selectedOffsetPeriod,
        selectedWorkWeeks
    );

    const disableSearch =
        (selectPeriod === "weekly" && selectedWorkWeeks.length === 0) ||
        isOveraByPackagellWipLoading ||
        selectedLookBack === 0;

    const lines = useMemo(
        () =>
            visibleLines({
                showFactories: {
                    f1: selectedFactory === "F1",
                    f2: selectedFactory === "F2",
                    f3: selectedFactory === "F3",
                    overall: selectedFactory === "Overall",
                },
                ...showLines,
            }),
        [selectedFactory]
    );

    return (
        <>
            <div className="mb-2">{title}</div>
            <Tabs
                options={test}
                selectedFactory={selectedFactory}
                handleFactoryChange={handleFactoryChange}
            />

            <div
                className={`border rounded-lg border-base-content/10 transition-all duration-300 ease-in-out transform origin-top ${
                    isVisible
                        ? "opacity-100 p-4 scale-100"
                        : "opacity-0 scale-95 max-h-0 overflow-hidden"
                }`}
            >
                <div className="flex items-center gap-x-2 gap-y-4 flex-wrap">
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
                        contentClassName="w-52 h-70"
                    />

                    <div className="join items-center">
                        <span className="pr-2 btn btn-disabled join-item bg-red-500">
                            period
                        </span>
                        <div className="dropdown dropdown-hover">
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
                                        onClick={() =>
                                            handlePeriodChange(option.value)
                                        }
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
                            onChange={(e) =>
                                handleLookBackChange(Number(e.target.value))
                            }
                            className="h-9 m-1 w-34"
                            labelClassName="bg-base-200"
                        />
                        <FloatingLabelInput
                            id="offset"
                            label={`Offset days`}
                            value={selectedOffsetPeriod}
                            type="number"
                            onChange={(e) =>
                                handleOffsetChange(Number(e.target.value))
                            }
                            className="h-9 m-1 w-34"
                            labelClassName="bg-base-200"
                            alwaysFloatLabel
                        />
                    </div>

                    <div
                        className={clsx(
                            selectPeriod === "weekly" ? "" : "hidden"
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
                        className="btn btn-primary"
                        onClick={handleSearch}
                        disabled={disableSearch}
                    >
                        Get Data{" "}
                        {isOveraByPackagellWipLoading && (
                            <span className="loading loading-spinner loading-xs"></span>
                        )}
                    </button>

                    {!noChartTable && (
                        <button
                            className="btn btn-sm btn-outline h-8 btn-secondary px-4"
                            onClick={() =>
                                setIsChartTableVisible((prev) => !prev)
                            }
                        >
                            {}
                            {isChartTableVisible ? (
                                <FaEye className="mr-2" />
                            ) : (
                                <FaEyeSlash className="mr-2" />
                            )}
                            <span className="w-18">
                                {isChartTableVisible
                                    ? "Show Table"
                                    : "Hide Table"}
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
        </>
    );
};

export default WipOutTrendByPackage;
