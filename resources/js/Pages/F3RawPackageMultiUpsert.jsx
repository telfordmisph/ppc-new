import BulkErrors from "@/Components/BulkErrors";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import { createClickableCell } from "@/Components/tanStackTable/ClickableCell";
import ReadOnlyColumns from "@/Components/tanStackTable/ReadOnlyColumn";
import TanstackTable from "@/Components/tanStackTable/TanstackTable";
import { useEditableTable } from "@/Hooks/useEditableTable";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";
import { useF3PackagesStore } from "@/Store/f3PackageListStore";
import { router, usePage } from "@inertiajs/react";
import React, { useEffect, useMemo, useState } from "react";
import { FaPlus, FaSave } from "react-icons/fa";
import { MdOutlineDelete } from "react-icons/md";

const packageModalID = "package-selection-modal-f3-raw-package-multi-upsert";

const F3RawPackageMultiUpsert = () => {
	const toast = useToast();
	const { raw_packages: serverRawPackages } = usePage().props;

	const [selectedCell, setSelectedCell] = useState(null);
	const [selectedEditItem, setSelectedEditItem] = useState([[]]);

	const initialPackages = useMemo(() => {
		if (serverRawPackages?.length) {
			return serverRawPackages.map((p, index) => ({
				raw_package: p.raw_package ?? "",
				lead_count: p.lead_count ?? "",
				package_name: p.f3_package_name?.package_name ?? "",
				dimension: p.dimension ?? "",
				id: index,
			}));
		}

		return [];
	}, [serverRawPackages]);

	const {
		mutate,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
		errorData: mutateErrorData,
	} = useMutation();

	useEffect(() => {
		const fetchF3PackageNames =
			useF3PackagesStore.getState().fetchF3PackageNames;
		fetchF3PackageNames();
	}, []);

	const packageNames = useF3PackagesStore((state) => state.data);
	const isLoadingPackageNames = useF3PackagesStore((state) => state.isLoading);

	function handleEditedItemClick(row, value, column) {
		const rootKey = column?.columnDef?.accessorKey?.split(".")[0];
		setSelectedCell({ rootKey, row, value, column });
		setSelectedEditItem([value]);
	}

	const columns = React.useMemo(
		() => [
			ReadOnlyColumns({
				accessorKey: "id",
				header: "ID",
				options: { size: 60, enableHiding: false, meta: { hidden: true } },
			}),
			{
				accessorKey: "raw_package",
				header: "Raw Package",
				size: 280,
				type: "string",
			},
			{
				accessorKey: "package_name",
				accessorFn: (row) => row.package_name?.package_name ?? "",
				header: "Package Name",
				size: 150,
				cell: createClickableCell({
					modalID: packageModalID,
					deletable: false,
					handleCellClick: handleEditedItemClick,
				}),
			},
			{
				accessorKey: "dimension",
				header: "Dimension",
				size: 150,
				type: "string",
			},
			{
				accessorKey: "lead_count",
				header: "Lead Count",
				size: 150,
				type: "number",
			},
		],
		[],
	);

	const {
		table,
		data,
		handleAddNewRow,
		handleDeleteRow,
		updateData,
		handleResetChanges,
	} = useEditableTable(initialPackages || [], columns, {
		createEmptyRow: () => ({
			raw_package: "",
			package_name: "",
			dimension: "",
			LC: "",
		}),
		isMultipleSelection: true,
	});

	const handleUpsert = async (e) => {
		e.preventDefault();

		const payload = data.map((row) => {
			const { package_name, ...rest } = row;
			return {
				...rest,
				package_id: package_name?.id ?? null,
			};
		});

		try {
			await mutate(route("api.f3.raw.package.bulkUpdate"), {
				method: "PATCH",
				body: payload,
			});

			toast.success("Changes updated successfully!");
			router.visit(route("f3.raw.package.index"));
		} catch (error) {
			toast.error(error.message);
			console.error(error);
		}
	};

	const handleEditPackage = (selected) => {
		if (selectedCell === null) return;

		updateData(selectedCell?.row?.index, selectedCell?.rootKey, selected[0]);

		document.getElementById(packageModalID)?.close();
	};

	const handleDelete = async () => {
		const rowIds = Object.keys(table.getState().rowSelection);
		handleDeleteRow(rowIds);
	};

	return (
		<>
			<h1 className="text-base font-bold mb-4">Add New F3 Raw Packages</h1>
			<MultiSelectSearchableDropdown
				modalId={packageModalID}
				options={
					packageNames?.map((pkg) => ({
						value: pkg.package_name,
						label: null,
						original: pkg,
					})) || []
				}
				returnKey="original"
				controlledSelectedOptions={selectedEditItem}
				defaultSelectedOptions={[selectedEditItem]}
				onChange={handleEditPackage}
				buttonSelectorClassName="w-80 font-normal"
				itemName="Package List"
				isLoading={isLoadingPackageNames}
				prompt="Select package"
				debounceDelay={500}
				contentClassName="w-52 h-120"
				singleSelect
				disableSelectedContainer
				useModal={true}
			/>

			<form onSubmit={handleUpsert}>
				<div className="flex gap-2 mt-4">
					<button
						type="button"
						onClick={() => handleAddNewRow()}
						className="btn btn-outline btn-accent"
					>
						<FaPlus /> Add Row
					</button>
					<button
						type="button"
						onClick={handleResetChanges}
						className="btn btn-outline btn-error"
					>
						Reset
					</button>
					<button
						type="submit"
						className="btn btn-primary"
						disabled={isMutateLoading}
					>
						{isMutateLoading ? (
							<span className="loading loading-spinner"></span>
						) : (
							<FaSave />
						)}
						Save All
					</button>
					<div className="px-2 w-full">
						{<BulkErrors errors={mutateErrorData?.data || []} />}
					</div>
					<button
						type="button"
						className="btn btn-error btn-ghost btn-square"
						disabled={Object.keys(table.getState().rowSelection).length === 0}
						onClick={handleDelete}
					>
						<MdOutlineDelete className="w-full h-full" />
					</button>
				</div>
				<div className="overflow-x-auto">
					<TanstackTable table={table} />
				</div>
			</form>
		</>
	);
};

export default F3RawPackageMultiUpsert;
