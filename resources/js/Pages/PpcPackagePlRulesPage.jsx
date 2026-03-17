import BulkErrors from "@/Components/BulkErrors";
import ChangeReviewModal from "@/Components/ChangeReviewModal";
import DeleteModal from "@/Components/DeleteModal";
import MaxItemDropdown from "@/Components/MaxItemDropdown";
import Pagination from "@/Components/Pagination";
import SearchInput from "@/Components/SearchInput";
import Tabs from "@/Components/Tabs";
import DropdownCell from "@/Components/tanStackTable/DropdownCell";
import ReadOnlyColumns from "@/Components/tanStackTable/ReadOnlyColumn";
import TanstackTable from "@/Components/tanStackTable/TanstackTable";
import { useEditableTable } from "@/Hooks/useEditableTable";
import { useMutation } from "@/Hooks/useMutation";
import { router, usePage } from "@inertiajs/react";
import React, { useCallback, useEffect, useRef, useState } from "react";
import toast from "react-hot-toast";
import { FaPlus } from "react-icons/fa6";
import { MdOutlineDelete } from "react-icons/md";

const ppcPages = {
	"PL Reference": route("pl-ref.master.index"),
	"PL Rules": route("pl-ref.rules.index"),
};

const PL_OPTIONS = ["PL1", "PL6"];
const FACTORY_OPTIONS = [null, "F1", "F2", "F3"];
const BOOL_OPTIONS = ["true", "false"];

function PpcPackagePlRulesPage() {
	const [selectedPage, setSelectedPage] = useState("PL Rules");

	const handleSelectPage = (name) => {
		setSelectedPage(name);
		router.visit(ppcPages[name]);
	};

	const {
		rules: serverRules,
		search: serverSearch,
		perPage: serverPerPage,
		totalEntries,
	} = usePage().props;

	const start = serverRules.from;
	const end = serverRules.to;
	const filteredTotal = serverRules.total;
	const overallTotal = totalEntries ?? filteredTotal;

	const [searchInput, setSearchInput] = useState(serverSearch || "");
	const [maxItem, setMaxItem] = useState(serverPerPage || 25);
	const [currentPage, setCurrentPage] = useState(serverRules.current_page || 1);

	const saveChangeIDModal = "ppc_rules_save_modal";
	const deleteModalRef = useRef(null);

	const {
		mutate: mutateRule,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
		errorData: mutateErrorData,
	} = useMutation();

	const {
		mutate: deleteRule,
		isLoading: isDeleteLoading,
		errorMessage: deleteErrorMessage,
		cancel: deleteCancel,
	} = useMutation();

	useEffect(() => {
		if (!mutateErrorMessage) return;
		toast.error(mutateErrorMessage);
	}, [mutateErrorMessage, mutateErrorData]);

	const productionLineColumn = React.useMemo(
		() => ({
			accessorKey: "production_line",
			header: "Production Line",
			size: 130,
			cell: React.memo(({ getValue, row, column, table }) => (
				<DropdownCell
					value={getValue()}
					rowIndex={row.index}
					columnId={column.id}
					statusOptions={PL_OPTIONS}
					onChange={(rowIndex, columnId, value) =>
						table.options.meta.updateData(rowIndex, columnId, value)
					}
				/>
			)),
		}),
		[],
	);

	const factoryColumn = React.useMemo(
		() => ({
			accessorKey: "factory",
			header: "Factory",
			size: 100,
			cell: React.memo(({ getValue, row, column, table }) => (
				<DropdownCell
					value={getValue()}
					rowIndex={row.index}
					columnId={column.id}
					statusOptions={FACTORY_OPTIONS}
					onChange={(rowIndex, columnId, value) =>
						table.options.meta.updateData(rowIndex, columnId, value)
					}
				/>
			)),
		}),
		[],
	);

	const isActiveColumn = React.useMemo(
		() => ({
			accessorKey: "is_active",
			header: "Active",
			size: 90,
			cell: React.memo(({ getValue, row, column, table }) => (
				<DropdownCell
					value={Number(getValue()) ? "true" : "false"}
					rowIndex={row.index}
					columnId={column.id}
					statusOptions={BOOL_OPTIONS}
					onChange={(rowIndex, columnId, value) => {
						value = value === "true" ? 1 : 0;
						table.options.meta.updateData(rowIndex, columnId, value);
					}}
				/>
			)),
		}),
		[],
	);

	const columns = React.useMemo(
		() => [
			ReadOnlyColumns({
				accessorKey: "id",
				header: "ID",
				options: { size: 60, enableHiding: false },
			}),
			{
				accessorKey: "package",
				header: "Package",
				size: 140,
				type: "string",
			},
			productionLineColumn,
			factoryColumn,
			{
				accessorKey: "lead_count",
				header: "Lead Count",
				size: 110,
				type: "number",
			},
			{
				accessorKey: "partname_like",
				header: "Part Name",
				cell: ({ getValue, row, column, table }) => {
					const raw = getValue() ?? "";
					const display = raw.replace(/%$/, "");

					return (
						<input
							value={display}
							onChange={(e) => {
								table.options.meta.updateData(
									row.index,
									column.id,
									e.target.value + "%",
								);
							}}
						/>
					);
				},
			},
			{
				accessorKey: "priority",
				header: "Priority",
				size: 90,
				type: "number",
			},
			isActiveColumn,
			// {
			// 	accessorKey: "valid_from",
			// 	header: "Valid From",
			// 	size: 130,
			// 	type: "date",
			// },
			// {
			// 	accessorKey: "valid_to",
			// 	header: "Valid To",
			// 	size: 130,
			// 	type: "date",
			// },
			{
				accessorKey: "note",
				header: "Note",
				size: 220,
				type: "string",
			},
			ReadOnlyColumns({
				accessorKey: "updated_at",
				header: "Updated At",
				options: { size: 200 },
			}),
		],
		[],
	);

	const {
		table,
		data,
		setData,
		setEditedRows,
		editedRows,
		handleResetChanges,
		handleAddNewRow,
		getChanges,
		changes,
	} = useEditableTable(serverRules.data || [], columns, {
		createEmptyRow: () => ({
			package: "",
			production_line: "PL1",
			factory: null,
			lead_count: null,
			partname_like: null,
			priority: 100,
			is_active: 1,
			valid_from: null,
			valid_to: null,
			note: "",
		}),
		isMultipleSelection: true,
	});

	const refresh = () => {
		router.reload({
			data: { search: searchInput, perPage: maxItem, page: currentPage },
			preserveState: true,
			preserveScroll: true,
		});
	};

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

	const changeMaxItemPerPage = (max) => {
		router.reload({
			data: { search: searchInput, perPage: max, page: 1 },
			preserveState: true,
			preserveScroll: true,
		});
		setMaxItem(max);
	};

	const handleSaveClick = () => {
		const computedChanges = getChanges();
		if (computedChanges.length === 0) {
			alert("No changes to save.");
			return;
		}
		document.getElementById(saveChangeIDModal).showModal();
	};

	const handleDelete = async () => {
		try {
			await deleteRule(route("api.pl-ref.rules.massDelete"), {
				body: { ids: Object.keys(table.getState().rowSelection) },
				method: "DELETE",
			});
			refresh();
			deleteModalRef.current.close();
			toast.success("Rules deleted successfully!");
		} catch (error) {
			toast.error(error?.message);
		}
	};

	return (
		<div className="relative overflow-auto">
			<Tabs
				options={Object.keys(ppcPages)}
				selectedFactory={selectedPage}
				handleFactoryChange={handleSelectPage}
				tabClassName={"mb-2"}
			/>

			<div className="w-full">
				<div className="shadow-lg w-full shadow-black/20 rounded-lg inline-block relative">
					<div className="rounded-lg z-100 flex flex-col gap-2 sticky -top-8 bg-base-200">
						<div className="text-sm text-base-content/70 space-y-1">
							<div>
								<span className="font-semibold">Package</span> — the package
								this rule applies to. Must exist in the Package Master.
							</div>
							<div>
								<span className="font-semibold">Production Line</span> — the PL
								to assign when this rule matches, overriding the package's
								default PL.
							</div>
							<div>
								<span className="font-semibold">Factory</span> — restricts the
								rule to a specific factory (F1, F2, F3). Leave empty to match
								any factory.
							</div>
							<div>
								<span className="font-semibold">Lead Count</span> — restricts
								the rule to parts with a specific lead count (e.g. 28 for PLCC
								28L). Leave empty to match any lead count.
							</div>
							<div>
								<span className="font-semibold">Part Name (LIKE)</span> —
								restricts the rule to parts whose name matches a SQL LIKE
								pattern (e.g.{" "}
								<code className="bg-base-300 px-1 rounded">ADXRS910A</code>{" "}
								matches any part starting with ADXRS910A). Leave empty to match
								any part name.
							</div>
							<div>
								<span className="font-semibold">Priority</span> — when multiple
								rules match the same part, the rule with the lowest number wins.
								Use gaps (10, 20, 50) to leave room for future rules between
								existing ones.
							</div>
							<div>
								<span className="font-semibold">Active</span> — inactive rules
								are ignored by the resolver but kept for reference.
							</div>
							{/* <div>
								<span className="font-semibold">Valid From / Valid To</span> —
								optional date range for the rule. Leave blank for no
								restriction. Useful for scheduling future rule changes or
								retiring rules at a known date.
							</div> */}
							<div className="bg-warning text-warning-content">
								Rules only affect future WIP imports. Existing WIP rows retain
								their resolved production line at the time of insertion.
							</div>
						</div>
						<div className="flex justify-between items-center gap-2 px-2 pt-4">
							<div className="flex gap-2 sticky left-0 items-center w-full">
								<div className="flex px-2 justify-between items-center gap-2">
									<div className="flex gap-2 sticky left-0 items-center">
										<MaxItemDropdown
											maxItem={maxItem}
											changeMaxItemPerPage={changeMaxItemPerPage}
											buttomClassName="w-35"
										/>
									</div>

									<div className="flex gap-2 sticky right-0">
										<button
											type="button"
											className="btn btn-error btn-ghost btn-square"
											disabled={
												Object.keys(table.getState().rowSelection).length === 0
											}
											onClick={() => deleteModalRef.current.open()}
										>
											<MdOutlineDelete className="w-full h-full" />
										</button>
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

										<ChangeReviewModal
											modalID={saveChangeIDModal}
											changes={changes}
											onClose={() =>
												document.getElementById(saveChangeIDModal).close()
											}
											onSave={async () => {
												await mutateRule(route("api.pl-ref.rules.bulkUpdate"), {
													method: "PATCH",
													body: editedRows,
												});
												document.getElementById(saveChangeIDModal).close();
												toast.success("Rules updated successfully!");
												refresh();
											}}
											isLoading={isMutateLoading}
										/>
									</div>
								</div>
								<div className="w-full flex gap-1 justify-between">
									<SearchInput
										initialSearchInput={searchInput}
										onSearchChange={setSearchInput}
									/>
									<button
										type="button"
										className="btn btn-primary"
										onClick={() => handleAddNewRow()}
									>
										<FaPlus className="mr-2" />
										Add Rule
									</button>
								</div>
							</div>
						</div>

						<div className="px-2 w-full">
							<BulkErrors errors={mutateErrorData?.data || []} />
						</div>
					</div>

					<Pagination
						links={serverRules.links}
						currentPage={currentPage}
						goToPage={goToPage}
						filteredTotal={filteredTotal}
						overallTotal={overallTotal}
						start={start}
						end={end}
					/>
					<TanstackTable table={table} />
				</div>
			</div>

			<DeleteModal
				ref={deleteModalRef}
				id="ppcRulesDeleteModal"
				message="Are you sure you want to delete these rule/s?"
				errorMessage={deleteErrorMessage}
				isLoading={isDeleteLoading}
				onDelete={handleDelete}
				onCancel={deleteCancel}
				onClose={() => deleteModalRef.current?.close()}
			/>
		</div>
	);
}

export default PpcPackagePlRulesPage;
