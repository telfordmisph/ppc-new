import { router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { FaSave } from "react-icons/fa";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";
import { useF3PackagesStore } from "@/Store/f3PackageListStore";

const F3RawPackageUpsert = () => {
	const toast = useToast();
	const { selectedRawPackage } = usePage().props;
	const isEdit = !!selectedRawPackage;

	const [rawPackage, setRawPackage] = useState(
		selectedRawPackage?.raw_package || "",
	);
	const [packageName, setPackageName] = useState(
		selectedRawPackage?.f3_package_name.package_name || "",
	);
	const [leadCount, setLeadCount] = useState(
		selectedRawPackage?.lead_count || "",
	);
	const [dimension, setDimension] = useState(
		selectedRawPackage?.dimension || "",
	);

	useEffect(() => {
		const fetchF3PackageNames =
			useF3PackagesStore.getState().fetchF3PackageNames;
		fetchF3PackageNames();
	}, []);

	const packageNames = useF3PackagesStore((state) => state.data);
	const isLoadingPackageNames = useF3PackagesStore((state) => state.isLoading);

	const {
		mutate,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
		cancel: mutateCancel,
	} = useMutation();

	const handleUpsert = async (e) => {
		e.preventDefault();

		const formData = {
			raw_package: rawPackage,
			lead_count: leadCount,
			package_name: packageName,
			dimension: dimension,
		};

		const url = isEdit
			? route("api.f3.raw.package.update", {
					id: selectedRawPackage.id,
				})
			: route("api.f3.raw.package.store");

		const method = isEdit ? "PATCH" : "POST";

		try {
			const response = await mutate(url, {
				method,
				body: formData,
			});

			toast.success(
				isEdit
					? "F3 Raw package updated successfully!"
					: "F3 Raw package created successfully!",
			);

			router.visit(route("f3.raw.package.index"));
		} catch (err) {
			console.error("Upsert failed:", err.message);
			toast.error(err.message);
		}
	};

	const handleReset = () => {
		setRawPackage("");
		setPackageName("");
		setLeadCount("");
		setDimension("");
	};

	return (
		<>
			<h1 className="text-base font-bold">
				{isEdit ? "Edit F3 Raw Package" : "Add New F3 Raw Package"}
			</h1>
			<div>
				<form
					onSubmit={handleUpsert}
					className="max-w-lg p-4 space-y-4 rounded-lg"
					method="POST"
				>
					<fieldset className="fieldset">
						<legend className="fieldset-legend">Raw Package</legend>
						<input
							type="text"
							className="w-64 input input-bordered"
							placeholder="Type Raw Package"
							value={rawPackage}
							onChange={(e) => setRawPackage(e.target.value)}
							required
						/>
						<p className="label">e.g. 16LTQFN3X3+5Q1</p>
					</fieldset>

					<fieldset className="fieldset">
						<legend className="fieldset-legend">Lead Count</legend>
						<input
							type="number"
							className="input input-bordered w-28"
							placeholder="Lead Count"
							value={leadCount}
							onChange={(e) => setLeadCount(e.target.value)}
							required
						/>
					</fieldset>

					<fieldset className="fieldset">
						<legend className="fieldset-legend">Package Name</legend>

						<MultiSelectSearchableDropdown
							options={
								packageNames?.map((packageName) => ({
									value: packageName.package_name,
									label: null,
								})) || []
							}
							onChange={(value) => {
								setPackageName(value[0]);
							}}
							defaultSelectedOptions={packageName ? [packageName] : []}
							isLoading={isLoadingPackageNames}
							itemName="Package List"
							prompt="Select packages"
							contentClassName="w-52 h-50"
							singleSelect
						/>
					</fieldset>

					<fieldset className="fieldset">
						<legend className="fieldset-legend">Dimension</legend>
						<input
							type="text"
							className="input input-bordered w-52"
							placeholder="Type Dimension"
							value={dimension}
							onChange={(e) => setDimension(e.target.value)}
							required
						/>
						<p className="label">e.g. 10X10X2</p>
					</fieldset>

					<div className="flex mt-4 space-x-2">
						<button
							type="button"
							onClick={handleReset}
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
							{isEdit ? "Edit F3 Raw Package" : "Add F3 Raw Package"}
						</button>
					</div>
				</form>
			</div>
		</>
	);
};

export default F3RawPackageUpsert;
