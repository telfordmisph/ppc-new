import { router, usePage } from "@inertiajs/react";
import React, { useCallback, useEffect, useRef, useState } from "react";
import Pagination from "@/Components/Pagination";
import CheckBoxColumn from "@/Components/tanStackTable/CheckBoxColumn";
import DropdownCell from "@/Components/tanStackTable/DropdownCell";
import ReadOnlyColumns from "@/Components/tanStackTable/ReadOnlyColumn";
import TanstackTable from "@/Components/tanStackTable/TanstackTable";
import { useEditableTable } from "@/Hooks/useEditableTable";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";

const statusOptions = ["F1", "F2", "F3"];

const PickupList = () => {
	const toast = useToast();

	const {
		pickups: serverPickups,
		search: serverSearch,
		perPage: serverPerPage,
		totalEntries,
	} = usePage().props;
	console.log("🚀 ~ PickupList ~ serverPickups:", serverPickups);

	const start = serverPickups.from;
	const end = serverPickups.to;
	const filteredTotal = serverPickups.total;
	const overallTotal = totalEntries ?? filteredTotal;
	const deleteModalRef = useRef(null);
	const [originalData, setOriginalData] = useState({});
	const [searchInput, setSearchInput] = useState(serverSearch || "");
	const [maxItem, setMaxItem] = useState(serverPerPage || 10);
	const [selectedPart, setSelectedPart] = useState(null);
	const [currentPage, setCurrentPage] = useState(
		serverPickups.current_page || 1,
	);

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
		mutate,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
		cancel: mutateCancel,
	} = useMutation();

	useEffect(() => {
		const timer = setTimeout(() => {
			router.reload({
				data: { search: searchInput, perPage: maxItem, page: 1 },
				preserveState: true,
				preserveScroll: true,
			});
			setCurrentPage(1);
		}, 700);

		return () => clearTimeout(timer);
	}, [searchInput]);

	const goToPage = (page) => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, page },
			preserveState: true,
			preserveScroll: true,
		});
		setCurrentPage(page);
	};

	const changeMaxItemPerPage = (maxItem) => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, page: 1 },
			preserveState: true,
			preserveScroll: true,
		});
		setMaxItem(maxItem);
	};

	const refresh = () => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, currentPage },
			preserveState: true,
			preserveScroll: true,
		});
	};

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

	const handleCellChange = useCallback((rowIndex, columnId, value) => {
		setData((prevData) => {
			if (prevData[rowIndex][columnId] === value) {
				return prevData;
			}
			const newData = [...prevData];
			newData[rowIndex] = { ...newData[rowIndex], [columnId]: value };

			const rowId = newData[rowIndex].id;
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

	const factoryColumn = React.useMemo(
		() => ({
			accessorKey: "factory",
			header: "Factory",
			size: 100,
			cell: React.memo(({ getValue, row, column }) => {
				const value = getValue();

				return (
					<div className="w-full">
						<DropdownCell
							statusOptions={statusOptions}
							value={value}
							rowIndex={row.index}
							columnId={column.id}
							onChange={handleCellChange}
						/>
					</div>
				);
			}),
		}),
		[],
	);

	const columns = React.useMemo(
		() => [
			CheckBoxColumn,
			ReadOnlyColumns({
				accessorKey: "id",
				header: "ID",
				options: { size: 60, enableHiding: false },
			}),
			{
				accessorKey: "PARTNAME",
				header: "Part Name",
				type: "string",
			},
			{
				accessorKey: "LOTID",
				header: "Lot ID",
				type: "string",
			},
			factoryColumn,
			{
				accessorKey: "PACKAGE",
				header: "Package Name",
				type: "string",
			},
			{
				accessorKey: "QTY",
				header: "Quantity",
				type: "number",
			},
			{
				accessorKey: "LC",
				header: "Lead Count",
				type: "number",
			},
			ReadOnlyColumns({
				accessorKey: "DATE_CREATED",
				header: "Date Created",
				size: 200,
			}),
			ReadOnlyColumns({
				accessorKey: "ADDED_BY",
				header: "Added By",
				size: 80,
			}),
		],
		[],
	);

	const columnVisibility = columns.reduce((acc, col) => {
		if (col.accessorKey) {
			acc[col.accessorKey] = true;
		}
		return acc;
	}, {});

	const { table, data, setData, editedRows, setEditedRows } = useEditableTable(
		serverPickups.data || [],
		columns,
		columnVisibility,
	);

	const handleDelete = async () => {
		try {
			await mutate(route("api.pickup.massGenocide"), {
				body: {
					ids: Object.keys(table.getState().rowSelection),
				},
				method: "DELETE",
			});

			refresh();
			deleteModalRef.current.close();
			toast.success("Pickups deleted successfully!");
		} catch (error) {
			toast.error(error?.message);
			console.error(error);
		}
	};

	useEffect(() => {
		const rows = serverPickups.data || [];
		console.log("🚀 ~ rowsrowsrows ~ rows:", rows);
		setData(rows);

		const map = {};
		rows.forEach((row) => {
			map[row.id_pickup] = row;
		});
		setOriginalData(map);
		setEditedRows({});
	}, [serverPickups]);

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

	return (
		<div className="w-full px-4">
			{/* <div className="flex items-center justify-between text-center">
				<h1 className="text-base font-bold">Part Names</h1>
				<Link href={route("partname.create")} className="btn btn-primary">
					<FaPlus /> Add PartName
				</Link>
			</div>

			<div className="flex justify-between py-4">
				<MaxItemDropdown
					maxItem={maxItem}
					changeMaxItemPerPage={changeMaxItemPerPage}
				/>
				<SearchInput
					initialSearchInput={searchInput}
					onSearchChange={setSearchInput}
				/>
			</div> */}

			<TanstackTable table={table} />

			<Pagination
				links={serverPickups.links}
				currentPage={currentPage}
				goToPage={goToPage}
				filteredTotal={filteredTotal}
				overallTotal={overallTotal}
				start={start}
				end={end}
			/>
		</div>
	);
};

export default PickupList;
