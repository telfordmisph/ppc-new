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
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import { useF1F2PackagesStore } from "@/Store/f1f2PackageListStore";
import { useWorkweekStore } from "@/Store/workweekListStore";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import { FACTORY_COLORS } from "@/Constants/colors";
import DatePicker from "react-datepicker";
import formatDate from "@/Utils/formatDate";
import "react-datepicker/dist/react-datepicker.css";
import CancellableActionButton from "@/Components/CancellableActionButton";

function useFetchByType(type, commonBaseParams) {
	return useFetch(route("api.wip.filterSummaryTrend"), {
		params: { ...commonBaseParams, filteringCondition: type },
		auto: false,
	});
}

const WIPStation = () => {
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
		savedSelectedPackages,
	);
	const [selectedWorkWeeks, setSelectedWorkWeeks] = useState(
		savedWorkWeeks || [],
	);
	const [startDate, setStartDate] = useState(savedStartDate);
	const [endDate, setEndDate] = useState(savedEndDate);
	const [selectedPeriod, setSelectedPeriod] = useState(savedPeriod);
	const [selectedLookBack, setSelectedLookBack] = useState(savedLookBack);
	const [selectedOffsetPeriod, setSelectedOffsetPeriod] = useState(savedOffset);
	const [factoryVisibleBars, setFactoryVisibleBars] = useState({
		f1: true,
		f2: true,
		f3: true,
		always: true,
	});

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

	let dateRange = `${formatDate(startDate)} - ${formatDate(endDate)}`;
	// const {
	//     data: packagesData,
	//     isLoading: packagesryLoading,
	//     errorMessage: packagesErrorMessage,
	//     // fetch: packagesFetch,
	// } = useFetch(route("api.wip.distinctPackages"));

	const commonBaseParams = useMemo(
		() => ({
			packageName: selectedPackageNames,
			period: selectedPeriod,
			dateRange: dateRange,
			lookBack: selectedLookBack,
			offsetDays: selectedOffsetPeriod,
			workweek: selectedWorkWeeks.join(","),
		}),
		[
			selectedPackageNames,
			selectedPeriod,
			selectedLookBack,
			selectedWorkWeeks,
			dateRange,
			selectedOffsetPeriod,
		],
	);

	const allFetch = useFetchByType("All", commonBaseParams);
	const holdFetch = useFetchByType("Hold", commonBaseParams);
	const pipelineFetch = useFetchByType("Pipeline", commonBaseParams);
	const bakeFetch = useFetchByType("Bake", commonBaseParams);
	const processableFetch = useFetchByType("Processable", commonBaseParams);
	const detapesegregationFetch = useFetchByType(
		"Detapesegregation",
		commonBaseParams,
	);
	const lpiFetch = useFetchByType("Lpi", commonBaseParams);
	const brandFetch = useFetchByType("Brand", commonBaseParams);
	const lliFetch = useFetchByType("Lli", commonBaseParams);
	const sortFetch = useFetchByType("Sort", commonBaseParams);

	const allFetchStates = [
		{ type: "All", ...allFetch },
		{ type: "Hold", ...holdFetch },
		{ type: "Pipeline", ...pipelineFetch },
		{ type: "Bake", ...bakeFetch },
		{ type: "Processable", ...processableFetch },
		{ type: "Detapesegregation", ...detapesegregationFetch },
		{ type: "Lpi", ...lpiFetch },
		{ type: "Brand", ...brandFetch },
		{ type: "Lli", ...lliFetch },
		{ type: "Sort", ...sortFetch },
	];

	const handleSearch = () => {
		allFetchStates.forEach(({ abort, fetch }) => {
			abort();
			fetch();
		});

		setFullLabel(
			formatPeriodTrendMessage(
				1,
				anyLoading,
				selectedPeriod,
				selectedLookBack,
				selectedOffsetPeriod,
				selectedWorkWeeks,
			),
		);
	};

	const abortAll = () => {
		allFetchStates.forEach(({ abort }) => {
			abort();
		});
	};

	const xAxis = "label";

	const datePeriod = formatPeriodLabel(selectedPeriod);
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

	const lines = useMemo(
		() =>
			visibleLines({
				showQuantities: true,
				showLots: false,
				showFactories: {
					f1: factoryVisibleBars.f1,
					f2: factoryVisibleBars.f2,
					f3: factoryVisibleBars.f3,
				},
				keyLines: FACTORY_COLORS,
			}),
		[factoryVisibleBars.f1, factoryVisibleBars.f2, factoryVisibleBars.f3],
	);

	const handleDateChange = (dates) => {
		const [start, end] = dates;
		setStartDate(start);
		setEndDate(end);

		if (!start || !end) return;
		setSavedStartDate(start);
		setSavedEndDate(end);
	};

	const disableSearch =
		!selectedPackageNames.length || startDate === null || endDate === null;

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
						contentClassName="w-52 h-70"
						// singleSelect
						// disableSelectedContainer
					/>
				</div>

				<div className="join items-center">
					<span className="join-item btn btn-disabled font-medium">Period</span>

					<button
						type="button"
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
						{periodOptions.map((option) => (
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
					className={clsx("flex", selectedPeriod === "daily" ? "" : "hidden")}
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
						selectedPeriod === "weekly" || selectedPeriod === "daily"
							? "hidden"
							: "",
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

				<div className={clsx(selectedPeriod === "weekly" ? "" : "hidden")}>
					<MultiSelectSearchableDropdown
						options={
							workWeekData?.data.map((item) => ({
								value: String(item.cal_workweek),
								label: `${formatFriendlyDate(
									item.startDate,
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

				<TogglerButton
					id="factory"
					toggleButtons={TOGGLE_F1F2_BUTTONS}
					visibleBars={factoryVisibleBars}
					toggleBar={handleToggleBar}
					toggleAll={toggleAllFactory}
				/>
				{/* 
				<button className="btn btn-primary h-9" onClick={handleSearch}>
					Search
				</button> */}

				<CancellableActionButton
					abort={abortAll}
					refetch={handleSearch}
					loading={anyLoading}
					disabled={disableSearch}
				/>
			</div>
			<div className={clsx("mt-4", fullLabel ? "opacity-100" : "opacity-0")}>
				{fullLabel}
			</div>
			<div
				className={clsx(
					"text-error-content bg-error/10 border border-error rounded-lg p-4 mt-4",
					anyError ? "block" : "hidden",
				)}
			>
				{anyError ? anyError.errorMessage : ""}
			</div>
			<div className="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2">
				{allFetchStates.map(({ type, data, isLoading, errorMessage }) => {
					return (
						<div key={type}>
							<div className="font-semibold pt-4 pb-2 pl-2">{type}</div>
							<div className="p-4 border bg-base-300 border-base-content/10 rounded-lg">
								<TrendLineChart
									key={type}
									data={data?.data || []}
									xKey={xAxis}
									isLoading={isLoading}
									errorMessage={errorMessage}
									lines={lines}
								/>
							</div>
						</div>
					);
				})}
			</div>
		</>
	);
};

export default WIPStation;
