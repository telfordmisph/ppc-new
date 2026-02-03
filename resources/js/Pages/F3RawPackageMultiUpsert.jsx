import React, { useMemo, useState, useEffect } from "react";
import { FaSave, FaTimes, FaPlus } from "react-icons/fa";
import { router, usePage } from "@inertiajs/react";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";
import { useF3PackagesStore } from "@/Store/f3PackageListStore";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";

const F3RawPackageMultiUpsert = () => {
	const toast = useToast();
	const { raw_packages: serverRawPackages } = usePage().props; // array of packages if editing

	const emptyPackage = {
		raw_package: "",
		lead_count: "",
		package_name: "",
		dimension: "",
	};

	const initialPackages = useMemo(() => {
		if (serverRawPackages?.length) {
			return serverRawPackages.map((p) => ({
				raw_package: p.raw_package ?? "",
				lead_count: p.lead_count ?? "",
				package_name: p.f3_package_name?.package_name ?? "",
				dimension: p.dimension ?? "",
			}));
		}

		return [{ ...emptyPackage }];
	}, [serverRawPackages]);

	const [packages, setPackages] = useState(initialPackages);

	const {
		mutate,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
	} = useMutation();

	useEffect(() => {
		const fetchF3PackageNames =
			useF3PackagesStore.getState().fetchF3PackageNames;
		fetchF3PackageNames();
	}, []);

	const packageNames = useF3PackagesStore((state) => state.data);
	const isLoadingPackageNames = useF3PackagesStore((state) => state.isLoading);

	const handleInputChange = (index, field, value) => {
		setPackages((prev) =>
			prev.map((part, i) => (i === index ? { ...part, [field]: value } : part)),
		);
	};

	const handleAddRow = () => setPackages([...packages, { ...emptyPackage }]);

	const handleRemoveRow = (index) =>
		setPackages(packages.filter((_, i) => i !== index));

	const handleReset = () => setPackages(initialPackages);

	const handleUpsert = async (e) => {
		e.preventDefault();

		const url = route("api.f3.raw.package.store");
		const method = "POST";

		try {
			await mutate(url, { method, body: packages });
			toast.success("Packages created!");
			router.visit(route("f3.raw.package.index"));
		} catch (err) {
			console.error("Upsert failed:", err?.message);
			toast.error(err?.message);
		}
	};

	return (
		<>
			<h1 className="text-base font-bold mb-4">Add New F3 Raw Packages</h1>
			<form onSubmit={handleUpsert}>
				<div className="overflow-x-auto">
					<table className="table table-zebra w-full">
						<thead>
							<tr>
								<th className="w-10">#</th>
								<th className="w-[200px]">Raw Package</th>
								<th className="w-[20px]">Lead Count</th>
								<th className="w-[200px]">Package Name</th>
								<th className="w-[200px]">Dimension</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							{packages.map((p, idx) => (
								<tr key={idx}>
									<th>{idx + 1}</th>
									<td>
										<input
											type="text"
											className="input input-bordered w-full"
											value={p.raw_package}
											onChange={(e) =>
												handleInputChange(idx, "raw_package", e.target.value)
											}
											required
										/>
									</td>
									<td>
										<input
											type="number"
											className="input input-bordered w-full"
											value={p.lead_count}
											onChange={(e) =>
												handleInputChange(idx, "lead_count", e.target.value)
											}
											required
										/>
									</td>
									<td>
										<MultiSelectSearchableDropdown
											options={
												packageNames?.map((pkg) => ({
													value: pkg.package_name,
													label: null,
												})) || []
											}
											onChange={(value) =>
												handleInputChange(idx, "package_name", value[0])
											}
											defaultSelectedOptions={
												p.package_name ? [p.package_name] : []
											}
											isLoading={isLoadingPackageNames}
											itemName="Package List"
											prompt="Select packages"
											contentClassName="w-52 h-50"
											singleSelect
										/>
									</td>
									<td>
										<input
											type="text"
											className="input input-bordered w-full"
											value={p.dimension}
											onChange={(e) =>
												handleInputChange(idx, "dimension", e.target.value)
											}
											required
										/>
									</td>
									<td className="">
										<button
											type="button"
											onClick={() => handleRemoveRow(idx)}
											className="btn btn-error btn-sm"
										>
											<FaTimes />
										</button>
									</td>
								</tr>
							))}
						</tbody>
					</table>
				</div>

				<div className="flex gap-2 mt-4">
					<button
						type="button"
						onClick={handleAddRow}
						className="btn btn-outline btn-accent"
					>
						<FaPlus /> Add Row
					</button>
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
						Save All
					</button>
				</div>
			</form>
		</>
	);
};

export default F3RawPackageMultiUpsert;
