import React, {
	useState,
	useEffect,
	useMemo,
	useCallback,
	useRef,
} from "react";
import { Head } from "@inertiajs/react";
import clsx from "clsx";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import { useFetch } from "@/Hooks/useFetch";
import formatDate from "@/Utils/formatDate";
import StackedBarChart from "@/Components/Charts/StackedBarChart";
import TogglerButton from "@/Components/TogglerButton";
import sortObjectArray from "@/Utils/sortObjectArray";
import { useMutation } from "@/Hooks/useMutation";
import {
	TOGGLE_FACTORY_BUTTONS,
	TOGGLE_PL_BUTTONS,
} from "@/Constants/toggleButtons";
import { sumByKey } from "@/Utils/sumByKey";
import { formatDataStatusMessage } from "@/Utils/formatStatusMessage";
import TrendByPackage from "@/Components/TrendByPackage";
import { summaryLotsPLBars, summaryOutPLBars } from "@/Utils/chartBars";
import { useWorkweekStore } from "@/Store/workweekListStore";
import formatFriendlyDate from "@/Utils/formatFriendlyDate";
import { buildComputeFunction } from "@/Utils/computeTotals";
import OverallQuantityTable from "./OverallQuantityTable";
import CancellableActionButton from "@/Components/CancellableActionButton";

const OUTTrend = () => {
	const manualWIPImportRef = useRef(null);
	const [startDate, setStartDate] = useState(null);
	const [endDate, setEndDate] = useState(null);
	const today = new Date();

	const defaultStart = today;
	const defaultEnd = today;

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

	const [selectedTotal, setSelectedTotal] = useState("out");
	const [isTrendByPackageVisible, setIsTrendByPackageVisible] = useState(false);
	const [selectedPackageName, setSelectedPackageName] = useState(null);

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
		overall: "api.out.overall",
		overallByPackage: "api.wip.overallByPackage",
		summary: "api.out.outLotTotals",
	};

	const {
		data: overallOutData,
		isLoading: isOverallOutLoading,
		errorMessage: overallOutErrorMessage,
		fetch: overallOutFetch,
		abort: overallOutAbort,
	} = useFetch(route(endpoints.overall), {
		params: commonParams,
		auto: false,
	});

	const {
		data: overallSummaryOutData,
		isLoading: isOverallSummaryOutLoading,
		errorMessage: overallSummaryOutErrorMessage,
		fetch: overallSummaryOutFetch,
		abort: overallSummaryOutAbort,
	} = useFetch(route(endpoints.summary), {
		params: { ...commonParams, includePL: false },
		auto: false,
	});

	const abortAllFetch = () => {
		overallOutAbort();
		overallSummaryOutAbort();
	};

	const { message } = formatDataStatusMessage({
		isLoading: isOverallOutLoading,
		label: "OUT",
		dateRange,
		startDate,
		endDate,
		selectedWorkWeek,
	});

	const handleDateChange = useCallback((dates) => {
		const [start, end] = dates;
		setTempStartDate(start);
		setTempEndDate(end);
	}, []);

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
				tempEndDate,
			)}`;
			setStartDate(tempStartDate);
			setEndDate(tempEndDate);
			overallOutFetch({
				dateRange: newDateRange,
				workweek: "",
				period: isWorkweek ? "weekly" : null,
			});
			overallSummaryOutFetch({
				dateRange: newDateRange,
				period: isWorkweek ? "weekly" : null,
				workweek: "",
				includePL: false,
			});
		} else {
			const newWorkweek = tempSelectedWorkWeek.join(" ");
			setSelectedWorkWeek(tempSelectedWorkWeek);
			overallOutFetch({
				dateRange: "",
				workweek: newWorkweek,
				period: isWorkweek ? "weekly" : null,
			});
			overallSummaryOutFetch({
				dateRange: "",
				period: isWorkweek ? "weekly" : null,
				workweek: newWorkweek,
				includePL: false,
			});
		}
	};

	const handleChangeTotalFilter = (e) => {
		if (e.target.checked) {
			setSelectedTotal("lots");
		} else {
			setSelectedTotal("out");
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
		() => buildComputeFunction(selectedTotal, factoryVisibleBars, "out"),
		[selectedTotal, factoryVisibleBars],
	);

	const allPackages = useMemo(() => {
		const data = overallSummaryOutData?.data;
		if (!data) return [];

		if (plVisibleBars.pl1 && plVisibleBars.pl6) return data.overall ?? [];
		if (plVisibleBars.pl1) return data.overall_pl1 ?? [];
		if (plVisibleBars.pl6) return data.overall_pl6 ?? [];

		return [];
	}, [overallSummaryOutData?.data, plVisibleBars.pl1, plVisibleBars.pl6]);

	console.log("🚀 ~ OUTTrend ~ allPackages:", allPackages);

	const sortKeys = useMemo(
		() => (selectedTotal === "out" ? ["total_out"] : ["total_lots"]),
		[selectedTotal],
	);

	const sortedAllPackageFilteredData = useMemo(
		() =>
			sortObjectArray(allPackages, {
				keys: sortKeys,
				order: "desc",
				// compute: selectedTotal === "wip" ? compute : null,
				compute,
			}),
		[allPackages, sortKeys, compute],
	);

	const handleShowTrendByPackage = useCallback(({ data, dataKey }) => {
		setIsTrendByPackageVisible(true);
		setSelectedPackageName(data?.package || null);
	}, []);

	return (
		<>
			<Head title="OUT Trend" />
			<div className="flex justify-between">
				<h1 className="w-3/12 text-xl font-bold mb-4">OUT Trend</h1>
				<h1 className="text-sm">{!isOverallOutLoading && message}</h1>
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
									isWorkweek ? "bg-accent text-accent-content" : "text-gray-500"
								}`}
							>
								Workweek
							</label>
						</div>

						{isWorkweek ? (
							<MultiSelectSearchableDropdown
								options={
									workWeekData?.data.map((item) => ({
										value: String(item.cal_workweek),
										label: `${formatFriendlyDate(
											item.startDate,
										)} - ${formatFriendlyDate(item.endDate)}`,
									})) || []
								}
								defaultSelectedOptions={tempSelectedWorkWeek}
								onChange={setTempSelectedWorkWeek}
								setTempSelectedWorkWeek
								isLoading={isWorkWeekLoading}
								itemName="Workweek List"
								prompt="Select Workweek"
								debounceDelay={500}
								contentClassName="w-200 h-80"
							/>
						) : (
							<DatePicker
								className="w-full rounded-lg input z-50"
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
								type="button"
								className="btn btn-outline btn-error"
								onClick={handleResetFilter}
							>
								Reset
							</button>
							<CancellableActionButton
								abort={abortAllFetch}
								refetch={handleRefetch}
								loading={isOverallOutLoading || isOverallSummaryOutLoading}
								disabled={
									!isWorkweek
										? !tempStartDate || !tempEndDate
										: tempSelectedWorkWeek.length === 0
								}
							/>
						</div>
					</div>
				</div>

				<div className="border border-base-content/10 w-9/12 h-full p-4 rounded-lg shadow-lg bg-base-300">
					<div className="overflow-x-auto">
						<OverallQuantityTable
							data={overallOutData}
							isLoading={isOverallOutLoading}
							error={overallOutErrorMessage}
							loadingMessage={message}
							type="out"
						/>
					</div>
				</div>
			</div>

			<div className="w-full p-4 mt-4 border border-base-content/10 rounded-lg bg-base-300">
				<h1 className="text-base divider divider-start">
					Total Out Graph per Package
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
						<div>Total OUTs</div>
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
						defaultAngle={-45}
						isLoading={isOverallSummaryOutLoading}
						errorMessage={overallSummaryOutErrorMessage}
						xAxisDataKey="package"
						bars={
							selectedTotal === "out" ? summaryOutPLBars : summaryLotsPLBars
						}
						visibleBars={factoryVisibleBars}
						onBarClick={handleShowTrendByPackage}
					/>
				</div>
				<div ref={trendByPackageChartRef}>
					<TrendByPackage
						isVisible={isTrendByPackageVisible}
						packageName={selectedPackageName}
						routeApi={"api.out.overallByPackage"}
						trendType={"out"}
					/>
				</div>
			</div>
		</>
	);
};

export default OUTTrend;
