import BulkErrors from "@/Components/BulkErrors";
import ChangeReviewModal from "@/Components/ChangeReviewModal";
import MaxItemDropdown from "@/Components/MaxItemDropdown";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import Pagination from "@/Components/Pagination";
import SearchInput from "@/Components/SearchInput";
import DateCell from "@/Components/tanStackTable/DateColumn";
import DropdownCell from "@/Components/tanStackTable/DropdownCell";
import ReadOnlyColumns from "@/Components/tanStackTable/ReadOnlyColumn";
import TanstackTable from "@/Components/tanStackTable/TanstackTable";
import { useEditableTable } from "@/Hooks/useEditableTable";
import { useFetch } from "@/Hooks/useFetch";
import { useMutation } from "@/Hooks/useMutation";
import { router, usePage } from "@inertiajs/react";
import React, { useCallback, useEffect, useState } from "react";
import toast from "react-hot-toast";
import { MdSchedule } from "react-icons/md";

const statusOptions = [
	"SHIPPED",
	"IQA",
	"For Process",
	"In-process",
	"Hold",
	"FVI",
	"Boxing",
	"OQA",
	"QA Buy-off",
];

const fullF3Roles = [
	"Production Supervisor",
	"Senior Production Supervisor",
	"Production Section Head",
	"Section Head",
	"programmer 1",
	"PPC Manager",
	"Trainee PPC Planner",
	"PPC",
	"PPC Planner",
	"PPC Engineer",
	"PPC Planner 2",
	"PPC Expediter 1",
	"PPC Expediter 2",
	"Planner 2",
	"Planner",
	"PPC Senior Supervisor",
	"ppc supervisor",
	"Residual Controller 1",
];

export default function F3List() {
	const {
		f3WipAndOut: serverF3WipAndOut,
		search: serverSearch,
		perPage: serverPerPage,
		dateLoaded: serverDateLoaded,
		totalEntries,
		emp_data,
	} = usePage().props;

	const hasFullF3Access = fullF3Roles.some(
		(role) => role.toLowerCase() === emp_data.emp_jobtitle.toLowerCase(),
	);
	const start = serverF3WipAndOut.from;
	const end = serverF3WipAndOut.to;
	const filteredTotal = serverF3WipAndOut.total;
	const overallTotal = totalEntries ?? filteredTotal;
	// const [data, setData] = React.useState(serverF3WipAndOut.data || []);
	const [f3SearchInput, setF3SearchInput] = useState(serverSearch || "");
	const [f3DateInput, setF3DateInput] = useState(serverDateLoaded || null);
	const [maxItem, setMaxItem] = useState(serverPerPage || 25);
	// const [editedRows, setEditedRows] = React.useState({});
	const [currentPage, setCurrentPage] = useState(
		serverF3WipAndOut.current_page || 1,
	);
	const perPageF3RawPackage = 50;

	const [selectedRowIndex, setSelectedRowIndex] = useState(null);
	const [originalF3RawPackage, setSelectedOriginalF3RawPackage] = useState([
		[],
	]);
	const [originalData, setOriginalData] = useState({});
	const [showChangeModal, setShowChangeModal] = useState(false);
	const [changesToReview, setChangesToReview] = useState([]);
	const [f3RawPackageSearchInput, setF3RawPackageSearchInput] = useState("");

	const getChanges = () => {
		const changes = [];

		for (const rowId in editedRows) {
			const original = originalData[rowId];
			const edited = editedRows[rowId];

			for (const field in edited) {
				const before = original[field];
				const after = edited[field];

				if (before !== after) {
					changes.push({
						rowId,
						field,
						before,
						after,
					});
				}
			}
		}

		return changes;
	};

	const {
		mutate: mutateF3,
		isLoading: isMutateF3Loading,
		errorMessage: mutateF3ErrorMessage,
		errorData: mutateF3ErrorData,
		cancel: mutateF3Cancel,
	} = useMutation(route("api.f3.bulkUpdate"));

	useEffect(() => {
		if (!mutateF3ErrorMessage) return;

		toast.error(mutateF3ErrorMessage);
	}, [mutateF3ErrorMessage, mutateF3ErrorData]);

	const saveChangeIDModal = "save_change_modal_id";

	const handleSaveClick = () => {
		const changes = getChanges();
		if (changes.length === 0) {
			alert("No changes to save.");
			return;
		}
		document.getElementById(saveChangeIDModal).showModal();
		setChangesToReview(changes);
		setShowChangeModal(true);
	};

	useEffect(() => {
		const rows = serverF3WipAndOut.data || [];
		console.log("🚀 ~ F3List ~ rows:", rows);
		setData(rows);

		const map = {};
		rows.forEach((row) => {
			map[row.id] = row;
		});
		setOriginalData(map);
		setEditedRows({});
	}, [serverF3WipAndOut]);

	const openF3RawPackageSelectionModal = (rowIndex, originalF3RawPackage) => {
		setSelectedRowIndex(rowIndex);
		setSelectedOriginalF3RawPackage([originalF3RawPackage]);
		// modalDropdownRef.current?.showModal();
		document.getElementById("multiSelectSearchableDropdown-modal").showModal();
	};

	const {
		data: f3RawPackages,
		isLoading: isLoadingF3RawPackages,
		errorMessage: errorMessageF3RawPackages,
		errorData: errorDataF3RawPackages,
		cancel: cancelF3RawPackages,
		fetch: fetchF3RawPackages,
	} = useFetch(route("api.f3.raw.package.index"), {
		auto: false,
	});

	const handleCellChange = useCallback((rowIndex, columnId, value) => {
		setData((prevData) => {
			if (prevData[rowIndex][columnId] === value) {
				return prevData;
			}
			const newData = [...prevData];
			newData[rowIndex] = { ...newData[rowIndex], [columnId]: value };

			const rowId = newData[rowIndex].id;

			// setEditedRows((prevEdited) => ({
			//     ...prevEdited,
			//     [newData[rowIndex].id]: { ...newData[rowIndex] },
			// }));

			setEditedRows((prev) => ({
				...prev,
				[rowId]: {
					...prev[rowId],
					[columnId]: value,
				},
			}));

			return newData;
		});
	}, []);

	const statusColumn = React.useMemo(
		() => ({
			accessorKey: "status",
			header: "Status",
			size: 200,
			cell: React.memo(({ getValue, row, column }) => {
				const value = getValue();
				const isShipped = value === "SHIPPED";

				return (
					<div className="w-full">
						<DropdownCell
							statusOptions={statusOptions}
							value={value}
							rowIndex={row.index}
							columnId={column.id}
							onChange={handleCellChange}
							buttonClassname={
								isShipped ? "text-neutral bg-green-300 rounded-none" : ""
							}
						/>
					</div>
				);
			}),
		}),
		[],
	);

	const packageColumn = React.useMemo(
		() => ({
			accessorFn: (row) => row.package?.raw_package ?? "-",
			accessorKey: "package",
			header: "Package",
			size: 350,
			cell: ({ getValue, row, column, table }) => {
				return (
					<button
						type="button"
						className="btn w-full border-base-100"
						onClick={() =>
							openF3RawPackageSelectionModal(row.index, getValue())
						}
					>
						{getValue()}
					</button>
				);
			},
		}),
		[],
	);

	const columns = React.useMemo(() => {
		return [
			ReadOnlyColumns({
				accessorKey: "id",
				header: "ID",
				options: { size: 60, enableHiding: false },
			}),
			// {
			// 	accessorKey: "running_ct",
			// 	header: "Running CT",
			// 	inputOptions: { type: "number", step: 0.01 },
			// 	size: 100,
			// },
			{
				accessorKey: "date_received",
				header: "Date Received",
				size: 260,
				cell: ({ getValue, row, column }) => (
					<DateCell
						dateType={"datetime"}
						value={getValue()}
						rowIndex={row.index}
						columnId={column.id}
						onChange={handleCellChange}
					/>
				),
			},
			{
				accessorKey: "qty",
				header: "Quantity",
				type: "number",
			},
			{
				accessorKey: "good",
				header: "Good",
				type: "number",
			},
			{
				accessorKey: "rej",
				header: "Rejected",
				type: "number",
			},
			{
				accessorKey: "res",
				header: "Residual",
				type: "number",
			},
			{
				accessorKey: "packing_list_srf",
				header: "Packing List SRF",
			},
			{ accessorKey: "po_number", header: "PO Number" },
			{
				accessorKey: "machine_number",
				header: "Machine Number",
			},
			{
				accessorKey: "part_number",
				header: "Part Number",
			},
			{
				accessorKey: "package_code",
				header: "Package Code",
			},
			statusColumn,
			{ accessorKey: "do_number", header: "DO Number" },
			{
				accessorKey: "remarks",
				header: "Remarks",
				size: 600,
			},
			{
				accessorKey: "doable",
				header: "Doable",
				type: "number",
				size: 90,
			},
			{
				accessorKey: "focus_group",
				header: "Focus Group",
				size: 100,
			},
			packageColumn,
			{ accessorKey: "lot_number", header: "Lot Number" },
			{
				accessorKey: "process_req",
				header: "Process Req.",
				size: 100,
			},
			{
				accessorKey: "date_commit",
				header: "Date Commit",
				cell: ({ getValue, row, column }) => (
					<DateCell
						dateType={"date"}
						value={getValue()}
						rowIndex={row.index}
						columnId={column.id}
						onChange={handleCellChange}
						options={{
							dateFormat: "yyyy-MM-dd",
							showTimeSelect: false,
							timeFormat: null,
							timeIntervals: null,
						}}
					/>
				),
				size: 160,
			},
			{
				accessorKey: "actual_date_time",
				header: "Actual Date/Time",
				cell: ({ getValue, row, column }) => (
					<DateCell
						dateType={"datetime"}
						value={getValue()}
						rowIndex={row.index}
						columnId={column.id}
						onChange={handleCellChange}
						options={{
							dateFormat: "yyyy-MM-dd",
							showTimeSelect: false,
							timeFormat: null,
							timeIntervals: null,
						}}
					/>
				),
				size: 260,
			},
			{
				accessorKey: "gap_analysis",
				header: "Gap Analysis",
				size: 150,
			},
			{
				accessorKey: "cycle_time",
				header: "Cycle Time",
				size: 100,
			},
			ReadOnlyColumns({
				accessorKey: "imported_by",
				header: "Imported By",
				size: 80,
			}),
			ReadOnlyColumns({
				accessorKey: "date_loaded",
				header: "Date Loaded",
			}),
			ReadOnlyColumns({
				accessorKey: "modified_by",
				header: "Modified By",
				size: 80,
			}),
			ReadOnlyColumns({
				accessorKey: "modified_at",
				header: "Modified At",
				size: 120,
			}),
		];

		// if (!hasFullF3Access) {
		// 	return [
		// 		ReadOnlyColumns({
		// 			accessorKey: "id",
		// 			header: "ID",
		// 			options: { size: 60, enableHiding: false },
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "running_ct",
		// 			header: "Running CT",
		// 			inputOptions: { type: "number", step: 0.01 },
		// 			options: { size: 80 },
		// 		}),

		// 		ReadOnlyColumns({
		// 			accessorKey: "date_received",
		// 			header: "Date Received",
		// 			options: {
		// 				size: 520,
		// 			},
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "packing_list_srf",
		// 			header: "Packing List SRF",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "po_number",
		// 			header: "PO Number",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "machine_number",
		// 			header: "Machine Number",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "part_number",
		// 			header: "Part Number",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "package_code",
		// 			header: "Package Code",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "package",
		// 			header: "Package",
		// 			options: {
		// 				accessorFn: (row) => row.package?.raw_package ?? "-",
		// 			},
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "lot_number",
		// 			header: "Lot Number",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "process_req",
		// 			header: "Process Req.",
		// 			options: {
		// 				size: 100,
		// 			},
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "qty",
		// 			header: "Quantity",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "good",
		// 			header: "Good",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "rej",
		// 			header: "Rejected",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "res",
		// 			header: "Residual",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "date_commit",
		// 			header: "Date Commit",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "actual_date_time",
		// 			header: "Actual Date/Time",
		// 		}),
		// 		statusColumn,
		// 		ReadOnlyColumns({
		// 			accessorKey: "do_number",
		// 			header: "DO Number",
		// 		}),
		// 		{
		// 			accessorKey: "remarks",
		// 			header: "Remarks",
		// 			size: 600,
		// 		},
		// 		ReadOnlyColumns({
		// 			accessorKey: "doable",
		// 			header: "Doable",
		// 			options: { size: 90 },
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "focus_group",
		// 			header: "Focus Group",
		// 			options: { size: 100 },
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "gap_analysis",
		// 			header: "Gap Analysis",
		// 			options: { size: 150 },
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "cycle_time",
		// 			header: "Cycle Time",
		// 			options: { size: 100 },
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "imported_by",
		// 			header: "Imported By",
		// 			options: { size: 80 },
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "date_loaded",
		// 			header: "Date Loaded",
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "modified_by",
		// 			header: "Modified By",
		// 			options: { size: 80 },
		// 		}),
		// 		ReadOnlyColumns({
		// 			accessorKey: "modified_at",
		// 			header: "Modified At",
		// 			options: { size: 120 },
		// 		}),
		// 	];
		// } else {
		// return allColumns;
		// }
	}, [emp_data]);

	const initialColumnVisibility = columns.reduce((acc, col) => {
		if (col.accessorKey) {
			acc[col.accessorKey] = true;
		}
		return acc;
	}, {});

	const [columnVisibility, setColumnVisibility] = React.useState(
		initialColumnVisibility,
	);

	const { table, data, setData, editedRows, setEditedRows } = useEditableTable(
		serverF3WipAndOut.data || [],
		columns,
		columnVisibility,
		setColumnVisibility,
	);

	const handleResetChanges = () => {
		if (Object.keys(editedRows).length === 0) {
			alert("No changes to reset.");
			return;
		}

		if (!confirm("Are you sure you want to discard all changes?")) return;

		setEditedRows({});
		setChangesToReview([]);
		const originalRows = Object.values(originalData);
		setData(originalRows);
	};

	// const table = useReactTable({
	// 	data,
	// 	columns,
	// 	state: {
	// 		columnVisibility,
	// 		rowSelection,
	// 	},
	// 	enableRowVirtualization: true,
	// 	meta: { onCellChange: handleCellChange },
	// 	enableRowSelection: true,
	// 	enableMultiRowSelection: false,
	// 	onRowSelectionChange: setRowSelection,
	// 	defaultColumn: { minSize: 10, maxSize: 1200 },
	// 	columnResizeMode: "onChange",
	// 	getCoreRowModel: getCoreRowModel(),
	// });

	useEffect(() => {
		const timer = setTimeout(() => {
			router.reload({
				data: {
					search: f3SearchInput,
					perPage: maxItem,
					page: 1,
					dateLoaded: f3DateInput,
				},
				preserveState: true,
				preserveScroll: true,
			});
			setCurrentPage(1);
		}, 700);

		return () => clearTimeout(timer);
	}, [f3SearchInput, f3DateInput]);

	const handleF3DateChange = useCallback((event) => {
		const dateStr = event.target.value;
		if (!dateStr) return;

		setF3DateInput(dateStr);
	}, []);

	const goToPageF3List = (page) => {
		router.reload({
			data: { search: f3SearchInput, perPage: maxItem, page },
			preserveState: true,
			preserveScroll: true,
		});
		setCurrentPage(page);
	};

	const goToPageF3RawPackage = (page) => {
		fetchF3RawPackages({
			search: f3RawPackageSearchInput,
			page: page,
			perPage: perPageF3RawPackage,
		});
	};

	const changeMaxItemPerPage = (maxItem) => {
		router.reload({
			data: { search: f3SearchInput, perPage: maxItem, page: 1 },
			preserveState: true,
			preserveScroll: true,
		});
		setMaxItem(maxItem);
	};

	const refresh = () => {
		router.reload({
			data: {
				search: f3SearchInput,
				perPage: maxItem,
				page: currentPage,
			},
			preserveState: true,
			preserveScroll: true,
		});
	};

	const columnOptions = columns.map((col) => ({
		value: col.accessorKey,
		label: col.header,
	}));

	const selectedColumns = Object.keys(columnVisibility).filter(
		(key) => columnVisibility[key],
	);

	const handleColumnVisibilityChange = (selected) => {
		const newVisibility = {};
		columns.forEach((col) => {
			if (col.accessorKey) {
				newVisibility[col.accessorKey] = selected.includes(col.accessorKey);
			}
		});
		setColumnVisibility(newVisibility);
	};

	const totalHiddenColumns = columns.reduce((count, col) => {
		if (col.accessorKey && !columnVisibility[col.accessorKey]) {
			return count + 1;
		}
		return count;
	}, 0);

	const handleF3RawPackageSearchChange = useCallback((searchValue) => {
		fetchF3RawPackages({
			search: searchValue,
			page: 1,
			perPage: perPageF3RawPackage,
		});
		setF3RawPackageSearchInput(searchValue);
	}, []);

	const columnSizeVars = React.useMemo(() => {
		const headers = table.getFlatHeaders();
		const colSizes = {};
		for (const header of headers) {
			colSizes[`--header-${header.id}-size`] = header.getSize();
			colSizes[`--col-${header.column.id}-size`] = header.column.getSize();
		}
		return colSizes;
	}, [table.getState().columnSizing, table.getState().columnSizingInfo]);

	const resetAllColumns = () => {
		table.getAllColumns().forEach((col) => col.resetSize());
	};

	useEffect(() => {
		resetAllColumns();
	}, [columnVisibility]);

	const handleF3RawPackageModalSelect = (selectedF3RawPackage) => {
		if (selectedRowIndex === null) return;
		setSelectedOriginalF3RawPackage(
			[selectedF3RawPackage[0]?.raw_package] || [],
		);
		// rowSelection
		setData((prevData) => {
			if (prevData[selectedRowIndex]["package"] === selectedF3RawPackage[0]) {
				return prevData;
			}
			const newData = [...prevData];
			newData[selectedRowIndex] = {
				...newData[selectedRowIndex],
				["package"]: selectedF3RawPackage[0],
			};

			// setEditedRows((prevEdited) => ({
			//     ...prevEdited,
			//     []: {
			//         ...newData[selectedRowIndex],
			//     },
			// }));

			const rowId = newData[selectedRowIndex].id;

			setEditedRows((prev) => ({
				...prev,
				[rowId]: {
					...prev[rowId],
					["package"]: selectedF3RawPackage[0],
				},
			}));

			return newData;
		});
	};

	return (
		<div className="p-4">
			<h1 className="text-lg font-semibold">F3 Wip & Out List</h1>
			<MultiSelectSearchableDropdown
				options={
					f3RawPackages?.f3RawPackages?.data?.map((item) => ({
						value: String(item.raw_package),
						label: String(
							`${item.f3_package_name?.package_name} : ${item.dimension} : ${item.lead_count}` ||
								"",
						),
						original: item,
					})) || []
				}
				returnKey="original"
				defaultSelectedOptions={[originalF3RawPackage]}
				controlledSelectedOptions={originalF3RawPackage}
				onChange={handleF3RawPackageModalSelect}
				buttonSelectorClassName="w-80 font-normal"
				itemName="F3 Raw Package list"
				isLoading={isLoadingF3RawPackages}
				prompt="Select F3 Raw Package"
				debounceDelay={500}
				contentClassName={"w-250 h-100"}
				onSearchChange={handleF3RawPackageSearchChange}
				singleSelect
				disableTooltip
				disableClearSelection={true}
				useModal={true}
				disableSelectedContainer
				paginated={true}
				// paginated={false}
				links={f3RawPackages?.f3RawPackages?.links || null}
				currentPage={f3RawPackages?.f3RawPackages?.current_page || 1}
				goToPage={goToPageF3RawPackage}
			/>

			<div className="w-full">
				<div className="shadow-lg w-full shadow-black/20 rounded-lg inline-block relative">
					{/* Header */}
					<div className="rounded-lg z-100 flex flex-col gap-2 sticky -top-8 bg-base-200">
						<div className="flex justify-between items-center gap-2 px-2 pt-4">
							<div className="flex gap-2 sticky left-0 items-center">
								<div className="w-70">
									<SearchInput
										initialSearchInput={f3SearchInput}
										onSearchChange={setF3SearchInput}
									/>
								</div>

								<label className="input">
									<MdSchedule className="h-4 w-4" />
									<input
										type="date"
										className="input"
										onChange={handleF3DateChange}
										defaultValue={f3DateInput}
									/>
								</label>
							</div>
						</div>

						<div className="px-2 w-full">
							{<BulkErrors errors={mutateF3ErrorData?.data || []} />}
						</div>

						<div className="flex px-2 justify-between items-center gap-2">
							<div className="flex gap-2 sticky left-0 items-center">
								<MaxItemDropdown
									maxItem={maxItem}
									changeMaxItemPerPage={changeMaxItemPerPage}
								/>

								<MultiSelectSearchableDropdown
									formFieldName="columns"
									options={columnOptions}
									defaultSelectedOptions={selectedColumns}
									onChange={handleColumnVisibilityChange}
									itemName="columns"
									prompt="Select columns to show"
									disableSearch
									contentClassName={"h-72"}
								/>

								{totalHiddenColumns > 0 && (
									<p>Hiding {totalHiddenColumns} column(s)</p>
								)}
							</div>

							<div className="flex gap-2 sticky right-0">
								<div className="flex gap-2 sticky right-0">
									<button
										type="button"
										className="btn btn-primary"
										onClick={handleSaveClick}
										disabled={Object.keys(editedRows).length === 0}
									>
										Save Changes
									</button>
									<button
										type="button"
										className="btn btn-secondary"
										onClick={handleResetChanges}
									>
										Reset
									</button>
								</div>

								<ChangeReviewModal
									modalID={saveChangeIDModal}
									changes={changesToReview}
									onClose={() =>
										document.getElementById(saveChangeIDModal).close()
									}
									onSave={async () => {
										await mutateF3(route("api.f3.bulkUpdate"), {
											method: "PATCH",
											body: editedRows,
										});

										document.getElementById(saveChangeIDModal).close();

										toast.success("F3 updated successfully!");
										refresh();
									}}
									isLoading={isMutateF3Loading}
								/>
							</div>
						</div>
					</div>
					{/* Body */}

					<TanstackTable table={table} />

					<Pagination
						links={serverF3WipAndOut.links}
						currentPage={currentPage}
						goToPage={goToPageF3List}
						filteredTotal={filteredTotal}
						overallTotal={overallTotal}
						start={start}
						end={end}
					/>
				</div>
			</div>
		</div>
	);
}
