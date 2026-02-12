import BulkErrors from "@/Components/BulkErrors";
import ChangeReviewModal from "@/Components/ChangeReviewModal";
import DeleteModal from "@/Components/DeleteModal";
import MaxItemDropdown from "@/Components/MaxItemDropdown";
import Pagination from "@/Components/Pagination";
import SearchInput from "@/Components/SearchInput";
import Tabs from "@/Components/Tabs";
import ReadOnlyColumns from "@/Components/tanStackTable/ReadOnlyColumn";
import TanstackTable from "@/Components/tanStackTable/TanstackTable";
import { useEditableTable } from "@/Hooks/useEditableTable";
import { useMutation } from "@/Hooks/useMutation";
import { router, usePage } from "@inertiajs/react";
import React, { useEffect, useRef, useState } from "react";
import toast from "react-hot-toast";
import { FaPlus } from "react-icons/fa6";
import { MdOutlineDelete } from "react-icons/md";

const bodySizePages = {
	"Body Sizes' Capacity": route("package.body_size.capacity.index"),
	"Body Sizes": route("package.body_size.capacity.body-sizes"),
	Machines: route("package.body_size.capacity.machines"),
};

function PackageBodySizeCapacityMachineList() {
	const [selectedPage, setSelectedPage] = useState("Machines");

	const handleSelectPage = (name) => {
		setSelectedPage(name);

		router.visit(bodySizePages[name]);
	};

	const {
		machines: serverMachines,
		search: serverSearch,
		perPage: serverPerPage,
		totalEntries,
	} = usePage().props;

	const start = serverMachines.from;
	const end = serverMachines.to;
	const filteredTotal = serverMachines.total;
	const overallTotal = totalEntries ?? filteredTotal;
	const [machineSearchInput, setMachineSearchInput] = useState(
		serverSearch || "",
	);
	const [maxItem, setMaxItem] = useState(serverPerPage || 25);
	const [currentPage, setCurrentPage] = useState(
		serverMachines.current_page || 1,
	);
	const [originalData, setOriginalData] = useState({});
	console.log(
		"🚀 ~ PackageBodySizeCapacityMachineList ~ originalData:",
		originalData,
	);

	const {
		mutate: mutateMachine,
		isLoading: isMutateMachineLoading,
		errorMessage: mutateMachineErrorMessage,
		errorData: mutateMachineErrorData,
		cancel: mutateMachineCancel,
	} = useMutation(route("api.machines.bulkUpdate"));

	const {
		mutate: deleteMachine,
		isLoading: isDeleteLoading,
		errorMessage: deleteErrorMessage,
		errorData: deleteErrorData,
		cancel: deleteCancel,
	} = useMutation(route("api.machines.massGenocide"));

	useEffect(() => {
		if (!mutateMachineErrorMessage) return;

		toast.error(mutateMachineErrorMessage);
	}, [mutateMachineErrorMessage, mutateMachineErrorData]);

	const saveChangeIDModal = "save_change_modal_id";

	const columns = React.useMemo(() => {
		return [
			ReadOnlyColumns({
				accessorKey: "id",
				header: "ID",
				options: { size: 60, enableHiding: false },
			}),
			{
				accessorKey: "name",
				header: "Machine Name",
				type: "string",
			},
			ReadOnlyColumns({
				accessorKey: "modified_at",
				header: "Modified At",
				options: { size: 140, enableHiding: false },
			}),
			ReadOnlyColumns({
				accessorKey: "modified_by",
				header: "Modified By",
				options: { size: 140, enableHiding: false },
			}),
		];
	}, []);

	const {
		table,
		editedRows,
		handleResetChanges,
		handleAddNewRow,
		getChanges,
		changes,
	} = useEditableTable(serverMachines.data || [], columns, {
		createEmptyRow: () => ({
			name: "",
		}),
		isMultipleSelection: true,
	});

	console.log(
		"🚀 ~ PackageBodySizeCapacityMachineList ~ editedRows:",
		editedRows,
	);

	const handleSaveClick = () => {
		const computedChanges = getChanges();
		if (computedChanges.length === 0) {
			alert("No changes to save.");
			return;
		}
		document.getElementById(saveChangeIDModal).showModal();
	};

	useEffect(() => {
		const timer = setTimeout(() => {
			router.reload({
				data: {
					search: machineSearchInput,
					perPage: maxItem,
					page: 1,
				},
				preserveState: true,
				preserveScroll: true,
			});
			setCurrentPage(1);
		}, 700);

		return () => clearTimeout(timer);
	}, [machineSearchInput]);

	const goToPageMachineList = (page) => {
		router.reload({
			data: { search: machineSearchInput, perPage: maxItem, page },
			preserveState: true,
			preserveScroll: true,
		});
		setCurrentPage(page);
	};

	const deleteModalRef = useRef(null);

	const changeMaxItemPerPage = (maxItem) => {
		router.reload({
			data: { search: machineSearchInput, perPage: maxItem, page: 1 },
			preserveState: true,
			preserveScroll: true,
		});
		setMaxItem(maxItem);
	};

	const refresh = () => {
		router.reload({
			data: {
				search: machineSearchInput,
				perPage: maxItem,
				page: currentPage,
			},
			preserveState: true,
			preserveScroll: true,
		});
	};

	const handleDelete = async () => {
		try {
			await deleteMachine(route("api.machines.massGenocide"), {
				body: {
					ids: Object.keys(table.getState().rowSelection),
				},
				method: "DELETE",
			});

			refresh();
			deleteModalRef.current.close();
			toast.success("Machines deleted successfully!");
		} catch (error) {
			toast.error(error?.message);
			console.error(error);
		}
	};

	return (
		<div className="relative overflow-auto">
			<Tabs
				options={Object.keys(bodySizePages)}
				selectedFactory={selectedPage}
				handleFactoryChange={handleSelectPage}
				tabClassName={"mb-2"}
			/>

			<div className="w-full">
				<div className="shadow-lg w-full shadow-black/20 rounded-lg inline-block relative">
					{/* Header */}
					<div className="rounded-lg z-100 flex flex-col gap-2 sticky -top-8 bg-base-200">
						<div className="flex justify-between items-center gap-2 px-2 pt-4">
							<div className="flex gap-2 sticky left-0 items-center">
								<div className="w-full flex gap-1 justify-between">
									<SearchInput
										initialSearchInput={machineSearchInput}
										onSearchChange={setMachineSearchInput}
									/>
									<button
										type="button"
										className="btn btn-primary"
										onClick={() => handleAddNewRow()}
									>
										<FaPlus className="mr-2" />
										Add New Machine
									</button>
								</div>
							</div>
						</div>

						<div className="px-2 w-full">
							{<BulkErrors errors={mutateMachineErrorData?.data || []} />}
						</div>

						<div className="flex px-2 justify-between items-center gap-2">
							<div className="flex gap-2 sticky left-0 items-center">
								<MaxItemDropdown
									maxItem={maxItem}
									changeMaxItemPerPage={changeMaxItemPerPage}
								/>
							</div>

							<div className="flex gap-2 sticky right-0">
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
								</div>

								<ChangeReviewModal
									modalID={saveChangeIDModal}
									changes={changes}
									onClose={() =>
										document.getElementById(saveChangeIDModal).close()
									}
									onSave={async () => {
										await mutateMachine(route("api.machines.bulkUpdate"), {
											method: "PATCH",
											body: editedRows,
										});

										document.getElementById(saveChangeIDModal).close();

										toast.success("Machine updated successfully!");
										refresh();
									}}
									isLoading={isMutateMachineLoading}
								/>
							</div>
						</div>
					</div>
					<Pagination
						links={serverMachines.links}
						currentPage={currentPage}
						goToPage={goToPageMachineList}
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
				id="machineDeleteModal"
				message="Are you sure you want to delete these body size/s?"
				errorMessage={deleteErrorMessage}
				isLoading={isDeleteLoading}
				onDelete={handleDelete}
				onCancel={deleteCancel}
				onClose={() => deleteModalRef.current?.close()}
			/>
		</div>
	);
}

export default PackageBodySizeCapacityMachineList;
