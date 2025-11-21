import React, { useEffect, useState, useMemo } from "react";
import { Head, usePage } from "@inertiajs/react";
import { useFetch } from "@/Hooks/useFetch";
import SearchableDropdown from "@/Components/SearchableDropdown";
import TrendLineChart from "@/Components/Charts/TrendLineChart";
import FloatingLabelInput from "@/Components/FloatingLabelInput";
import TogglerButton from "@/Components/TogglerButton";
import {
    formatPeriodLabel,
    formatPeriodTrendMessage,
} from "@/Utils/formatStatusMessage";
import { periodOptions } from "@/Constants/periodOptions";
import {
    TOGGLE_F1F2_BUTTONS,
    TOGGLE_FACTORY_BUTTONS,
} from "@/Constants/toggleButtons";
import { useSelectedFilteredStore } from "@/Store/selectedFilterStore";
import { visibleLines } from "@/Utils/chartLines";
import clsx from "clsx";

const WIPStation = () => {
    const {
        packageName: savedSelectedPackage,
        lookBack: savedLookBack,
        period: savedPeriod,
        offset: savedOffset,
        setSelectedPackageName: setSavedSelectedPackageName,
        setSelectedLookBack: setSavedSelectedLookBack,
        setSelectedPeriod: setSavedSelectedPeriod,
        setSelectedOffset: setSavedSelectedOffset,
    } = useSelectedFilteredStore();
    const [fullLabel, setFullLabel] = useState("");
    const [selectedPackageName, setSelectedPackageName] =
        useState(savedSelectedPackage);
    const [selectedPeriod, setSelectedPeriod] = useState(savedPeriod);
    const [selectedLookBack, setSelectedLookBack] = useState(savedLookBack);
    const [selectedOffsetPeriod, setSelectedOffsetPeriod] =
        useState(savedOffset);
    const [factoryVisibleBars, setFactoryVisibleBars] = useState({
        f1: true,
        f2: true,
        f3: true,
        always: true,
    });

    const {
        data: packagesData,
        isLoading: packagesryLoading,
        errorMessage: packagesErrorMessage,
        // fetch: packagesFetch,
    } = useFetch(route("api.wip.distinctPackages"));

    const commonBaseParams = useMemo(
        () => ({
            packageName: selectedPackageName,
            period: selectedPeriod,
            lookBack: selectedLookBack,
            offsetDays: selectedOffsetPeriod,
        }),
        [
            selectedPackageName,
            selectedPeriod,
            selectedLookBack,
            selectedOffsetPeriod,
        ]
    );

    const makeFetch = (condition) =>
        useFetch(route("api.wip.filterSummaryTrend"), {
            params: { ...commonBaseParams, filteringCondition: condition },
            auto: false,
        });

    const fetches = {
        All: makeFetch("All"),
        Hold: makeFetch("Hold"),
        Pipeline: makeFetch("Pipeline"),
        Bake: makeFetch("Bake"),
        Processable: makeFetch("Processable"),
        Detapesegregation: makeFetch("Detapesegregation"),
        Lpi: makeFetch("Lpi"),
        Brand: makeFetch("Brand"),
        Lli: makeFetch("Lli"),
        Sort: makeFetch("Sort"),
    };

    const handleSearch = () => {
        Object.values(fetches).forEach(({ abort, fetch }) => {
            abort();
            fetch();
        });

        setFullLabel(
            formatPeriodTrendMessage(
                anyLoading,
                selectedPeriod,
                selectedLookBack,
                selectedOffsetPeriod
            )
        );
    };

    const xAxis = "label";

    const datePeriod = formatPeriodLabel(selectedPeriod);

    const allFetchStates = Object.keys(fetches).map((type) => {
        const { data, isLoading, errorMessage } = fetches[type];
        return { type, data, isLoading, errorMessage };
    });

    useEffect(() => {
        console.log(
            "---------------------------------------------------------"
        );
        setFullLabel("");
    }, [
        selectedPackageName,
        selectedPeriod,
        selectedLookBack,
        selectedOffsetPeriod,
    ]);

    const anyLoading = allFetchStates.some((f) => f.isLoading);
    const anyError = allFetchStates.find((f) => f.errorMessage);

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
            always: true,
        });
    };

    const handleSearchableDropdownSelect = (packageName) => {
        setSelectedPackageName(packageName);
        setSavedSelectedPackageName(packageName);
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

    return (
        <>
            <Head title="WIP Station" />
            <h1 className="text-base font-bold mb-4">WIP Station</h1>
            <div className="flex flex-wrap w-full items-center gap-x-2 gap-y-4 mb-2">
                <div className="join items-center">
                    <span className="join-item btn btn-disabled font-medium">
                        Package Name
                    </span>
                    <SearchableDropdown
                        selectedItem={selectedPackageName}
                        onSelectItem={(packageName) => {
                            handleSearchableDropdownSelect(packageName);
                        }}
                        items={packagesData?.data || []}
                        isLoading={packagesryLoading}
                        errorMessage={packagesErrorMessage}
                        buttonClassName="rounded-r-lg m-0 border-base-content/10 w-30"
                    />
                </div>

                <div className="join items-center">
                    <span className="join-item btn btn-disabled font-medium">
                        Period
                    </span>
                    <div className="dropdown dropdown-hover">
                        <div
                            tabIndex={0}
                            role="button"
                            className="join-item btn rounded-r-lg border-base-content/10 w-20"
                        >
                            {selectedPeriod}
                        </div>
                        <ul
                            tabIndex="-1"
                            className="dropdown-content menu bg-base-100 rounded-box z-1 w-52 p-2 shadow-sm"
                        >
                            {periodOptions.map((option) => (
                                <li
                                    key={option.value}
                                    onClick={() => {
                                        handlePeriodSelect(option.value);
                                    }}
                                >
                                    <a>{option.label}</a>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

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

                <TogglerButton
                    id="factory"
                    toggleButtons={TOGGLE_F1F2_BUTTONS}
                    visibleBars={factoryVisibleBars}
                    toggleBar={handleToggleBar}
                    toggleAll={toggleAllFactory}
                />

                <button className="btn btn-primary h-9" onClick={handleSearch}>
                    Search
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
                    anyError ? "block" : "hidden"
                )}
            >
                {anyError ? anyError.errorMessage : ""}
            </div>
            <div className="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2">
                {allFetchStates.map(
                    ({ type, data, isLoading, errorMessage }) => {
                        return (
                            <div>
                                <div className="font-semibold pt-4 pb-2 pl-2">
                                    {type}
                                </div>
                                <div className="p-4 border bg-base-300 border-base-content/10 rounded-lg">
                                    <TrendLineChart
                                        key={type}
                                        data={data?.data || []}
                                        xKey={xAxis}
                                        isLoading={isLoading}
                                        errorMessage={errorMessage}
                                        lines={visibleLines({
                                            showQuantities: true,
                                            showLots: false,
                                            showFactories: {
                                                f1: factoryVisibleBars.f1,
                                                f2: factoryVisibleBars.f2,
                                                f3: factoryVisibleBars.f3,
                                            },
                                        })}
                                    />
                                </div>
                            </div>
                        );
                    }
                )}
            </div>
        </>
    );
};

export default WIPStation;
