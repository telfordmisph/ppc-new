import CancellableActionButton from "@/Components/CancellableActionButton";
import StackedBarChart from "@/Components/Charts/StackedBarChart";
import TrendLineChart from "@/Components/Charts/TrendLineChart";
import FloatingLabelInput from "@/Components/FloatingLabelInput";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import Tabs from "@/Components/Tabs";
import TogglerButton from "@/Components/TogglerButton";
import { nonTrendPeriodOptions } from "@/Constants/periodOptions";
import { TOGGLE_FACTORY_BUTTONS } from "@/Constants/toggleButtons";
import { useFetch } from "@/Hooks/useFetch";
import { useF1F2PackagesStore } from "@/Store/f1f2PackageListStore";
import { useSelectedFilteredStore } from "@/Store/selectedFilterStore";
import { useWorkweekStore } from "@/Store/workweekListStore";
import { summaryLotsPLBars, summaryWipPLBarswip } from "@/Utils/chartBars";
import { visibleLines } from "@/Utils/chartLines";
import { buildComputeFunction } from "@/Utils/computeTotals";
import formatDate from "@/Utils/formatDate";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import {
	formatPeriodLabel,
	formatPeriodTrendMessage,
} from "@/Utils/formatStatusMessage";
import generateDistinctColors from "@/Utils/generateDistinctColors";
import sortObjectArray from "@/Utils/sortObjectArray";
import { Head } from "@inertiajs/react";
import clsx from "clsx";
import { useMemo, useState } from "react";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

const factoryFilter = ["F1", "F2", "F3", "Overall"];

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
	const [selectedFactoryFilterTrend, setSelectedFactoryFilterTrend] = useState("overall");

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
		workweek: selectedPeriod === "weekly" ? selectedWorkWeeks.join(" ") : "",
	};

	const [selectedTotal, setSelectedTotal] = useState("wip");
	const [selectedMetric, setSelectedMetric] = useState('total_wip');

	const {
		data: bodySizeWipData,
		isLoading: isBodySizeWipLoading,
		errorMessage: bodySizeWipErrorMessage,
		fetch: bodySizeWipFetch,
		abort: bodySizeWipAbort,
	} = useFetch(route("api.wip.wipAndLotsByBodySize"), {
		auto: false,
		params: params,
	});

	const {
		data: bodySizeTrend,
		isLoading: isBodySizeTrendLoading,
		errorMessage: bodySizeTrendErrorMessage,
		fetch: bodySizeTrendFetch,
		abort: bodySizeTrendAbort,
	} = useFetch(route("api.wip.wipAndLotsByBodySizeTrend"), {
		params: params,
	});

	const handleFactoryChange = (selectedFactory) => {
		setSelectedFactoryFilterTrend(selectedFactory);
	};

	const handleSearch = () => {
		bodySizeWipAbort();
		bodySizeWipFetch();

		bodySizeTrendAbort();
		bodySizeTrendFetch();

		setFullLabel(
			formatPeriodTrendMessage(
				1,
				isBodySizeWipLoading,
				selectedPeriod,
				selectedLookBack,
				selectedOffsetPeriod,
				selectedWorkWeeks,
			),
		);
	};

	const handleChangeTotalFilter = (e) => {
		if (e.target.checked) {
			setSelectedTotal("lots");
		} else {
			setSelectedTotal("wip");
		}
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

	const toggleAllFactory = () => {
		const allVisible = Object.values(factoryVisibleBars).every(Boolean);
		setFactoryVisibleBars({
			f1: !allVisible,
			f2: !allVisible,
			f3: !allVisible,
			always: true,
		});
	};

	const handleToggleBar = (name, key) => {
		setFactoryVisibleBars((prev) => ({ ...prev, [key]: !prev[key] }));
	};

	const handleDateChange = (dates) => {
		const [start, end] = dates;
		setStartDate(start);
		setEndDate(end);

		if (!start || !end) return;
		setSavedStartDate(start);
		setSavedEndDate(end);
	};

	const lineKeys = useMemo(() => {
    const keys = new Map();
    (bodySizeTrend?.data ?? []).forEach(row => {
        Object.keys(row).forEach(key => {
            if (key.startsWith(`${selectedFactoryFilterTrend.toLowerCase()}_`) && key.endsWith(`_${selectedMetric}`) && !keys.has(key)) {
                keys.set(key, key);
            }
        });
    });

    const uniqueKeys = [...keys.keys()].sort();
    const colors = generateDistinctColors(uniqueKeys.length);

    return uniqueKeys.map((key, i) => ({
        dataKey: key,
        name: key
            .replace(`${selectedFactoryFilterTrend.toLowerCase()}_`, '')
            .replace(`_${selectedMetric}`, ''),
        yAxisId: 'left',
        stroke: colors[i],
        fill: colors[i],
        connectNulls: true,
        type: 'basis',
        dot: false,
    }));
}, [bodySizeTrend?.data, selectedFactoryFilterTrend, selectedMetric]);

	const commonChartProps = ({ key, fillWip, fillLot }) => {
		return {
			xAxisDataKey: "size_bucket",
			isLoading: isBodySizeWipLoading,
			errorMessage: bodySizeWipErrorMessage,
			bars: [
				{
					dataKey: `${key}_total_wip`,
					fill: fillWip,
					visibilityKey: "size_bucket",
					yAxisId: "left",
				},
				{
					dataKey: `${key}_total_lots`,
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

	const f1ChartProps = useMemo(
		() =>
			commonChartProps({
				key: "f1",
				fillWip: "var(--color-f1color)",
				fillLot: "var(--color-f1color-dim)",
			}),
		[],
	);

	const f2ChartProps = useMemo(
		() =>
			commonChartProps({
				key: "f2",
				fillWip: "var(--color-f2color)",
				fillLot: "var(--color-f2color-dim)",
			}),
		[],
	);

	const f3ChartProps = useMemo(
		() =>
			commonChartProps({
				key: "f3",
				fillWip: "var(--color-f3color)",
				fillLot: "var(--color-f3color-dim)",
			}),
		[],
	);

	const bodySizeWipDataOverall = useMemo(
		() => bodySizeWipData?.data?.result || [],
		[bodySizeWipData?.data?.result?.length],
	);

	const compute = useMemo(
		() => buildComputeFunction(selectedTotal, factoryVisibleBars),
		[selectedTotal, factoryVisibleBars],
	);

	const sortKeys = useMemo(
		() => (selectedTotal === "wip" ? ["total_wip"] : ["total_lots"]),
		[selectedTotal],
	);

	const sortedAllPackageFilteredData = useMemo(
		() =>
			sortObjectArray(bodySizeWipDataOverall, {
				keys: sortKeys,
				order: "desc",
				compute,
			}),
		[bodySizeWipDataOverall, sortKeys, compute],
	);

	const f1BodySize = useMemo(
		() => bodySizeWipData?.data?.f1,
		[bodySizeWipData],
	);
	const f2BodySize = useMemo(
		() => bodySizeWipData?.data?.f2,
		[bodySizeWipData],
	);
	const f3BodySize = useMemo(
		() => bodySizeWipData?.data?.f3,
		[bodySizeWipData],
	);

	const chartTitle = (suffix) => {
		const packages = selectedPackageNames.length > 3
    ? `${selectedPackageNames.slice(0, 3).join(", ")} +${selectedPackageNames.length - 3} more`
    : selectedPackageNames.join(", ") || "All Packages";
		return `${packages} · ${selectedFactoryFilterTrend} ${suffix}`;
	};

	return (
		<>
			<Head title="WIP Body Size" />
			<h1 className="text-base font-bold mb-4">WIP Body Size</h1>
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

				<CancellableActionButton
					abort={bodySizeWipAbort}
					refetch={handleSearch}
					loading={isBodySizeWipLoading}
					disabled={
						!selectedPackageNames.length ||
						!selectedPeriod ||
						(selectedPeriod === "weekly" && !selectedWorkWeeks.length)
					}
				/>
			</div>
			<div className={clsx("mt-4", fullLabel ? "opacity-100" : "opacity-0")}>
				{fullLabel}
			</div>
			<div
				className={clsx(
					"text-error-content bg-error/10 border border-error rounded-lg p-4 mt-4",
					bodySizeWipErrorMessage ? "block" : "hidden",
				)}
			>
				{bodySizeWipErrorMessage ? bodySizeWipErrorMessage : ""}
			</div>

			<div className="flex gap-2 items-center">
				<Tabs
					options={factoryFilter}
					selectedFactory={selectedFactoryFilterTrend}
					handleFactoryChange={handleFactoryChange}
				/>

				<div className="flex gap-2">
					{['total_wip', 'total_lots'].map(metric => (
						<button
							key={metric}
							type="button"
							className={clsx('btn btn-sm', selectedMetric === metric ? 'btn-primary' : 'btn-ghost')}
							onClick={() => setSelectedMetric(metric)}
						>
							{metric === 'total_wip' ? 'WIP Qty' : 'Lots'}
						</button>
					))}
				</div>
			</div>

			<TrendLineChart
				data={bodySizeTrend?.data || []}
				xKey={"label"}
				height={400}
				isLoading={isBodySizeTrendLoading}
				errorMessage={bodySizeTrendErrorMessage}
				title={chartTitle(`Body Size ${selectedMetric.replace("_", " ")}`)}
				lines={lineKeys}
			/>

			<OverallChart
				data={sortedAllPackageFilteredData || []}
				selectedTotal={selectedTotal}
				isBodySizeWipLoading={isBodySizeWipLoading}
				bodySizeWipErrorMessage={bodySizeWipErrorMessage}
				handleChangeTotalFilter={handleChangeTotalFilter}
				factoryVisibleBars={factoryVisibleBars}
				handleToggleBar={handleToggleBar}
				toggleAllFactory={toggleAllFactory}
			/>

			<div className="border border-base-content/10 rounded-lg mt-4 flex flex-col justify-center h-[500px]">
				<div className="font-semibold text-lg p-2 text-center w-full border rounded-t-lg border-f1color/50 border-b-transparent">
					F1
				</div>
				<StackedBarChart data={f1BodySize} {...f1ChartProps} />
			</div>
			<div className="border border-base-content/10 rounded-lg mt-4 flex flex-col justify-center h-[500px]">
				<div className="font-semibold text-lg p-2 text-center w-full border rounded-t-lg border-f2color/50 border-b-transparent">
					F2
				</div>
				<StackedBarChart data={f2BodySize} {...f2ChartProps} />
			</div>
			<div className="border border-base-content/10 rounded-lg mt-4 flex flex-col justify-center h-[500px]">
				<div className="font-semibold text-lg p-2 text-center w-full border rounded-t-lg border-f3color/50 border-b-transparent">
					F3
				</div>
				<StackedBarChart
					data={f3BodySize}
					xAxisDataKey={"size_bucket"}
					{...f3ChartProps}
				/>
			</div>
		</>
	);
};

const OverallChart = ({
	data = [],
	selectedTotal,
	isBodySizeWipLoading,
	bodySizeWipErrorMessage,
	handleChangeTotalFilter,
	factoryVisibleBars,
	handleToggleBar,
	toggleAllFactory,
}) => {
	return (
		<div className="border border-base-content/10 rounded-lg mt-4 flex flex-col justify-center h-[500px]">
			<div className="font-semibold text-lg p-2 text-center w-full border rounded-t-lg border-f1color/50 border-b-transparent">
				Overall
			</div>
			{data?.length > 0 && (
				<div className="px-4 flex gap-4">
					<div>
						<TogglerButton
							id="factory"
							toggleButtons={TOGGLE_FACTORY_BUTTONS}
							visibleBars={factoryVisibleBars}
							toggleBar={handleToggleBar}
							toggleAll={toggleAllFactory}
						/>
					</div>
					<div className="flex items-center space-x-2">
						<div>Total wip</div>
						<input
							type="checkbox"
							checked={selectedTotal === "lots"}
							onChange={(e) => handleChangeTotalFilter(e)}
							className="toggle"
						/>
						<div>Total Lots</div>
					</div>
				</div>
			)}

			<StackedBarChart
				data={data}
				bars={selectedTotal === "wip" ? summaryWipPLBarswip : summaryLotsPLBars}
				xAxisDataKey={"size_bucket"}
				isLoading={isBodySizeWipLoading}
				errorMessage={bodySizeWipErrorMessage}
				visibleBars={factoryVisibleBars}
			>
			</StackedBarChart>
		</div>
	);
};

export default BodySize;
