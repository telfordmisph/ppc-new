import { router, usePage } from "@inertiajs/react";
import { useState } from "react";
import { FaSave } from "react-icons/fa";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";
import { useF3PackagesStore } from "@/Store/f3PackageListStore";

const F3PackageUpsert = () => {
	const toast = useToast();
	const { packageName: selectedPackage } = usePage().props;
	const isEdit = !!selectedPackage;
	const { appendPackage, updatePackage } = useF3PackagesStore((state) => state);

	const [packageName, setPackageName] = useState(
		selectedPackage?.package_name || "",
	);

	const {
		mutate,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
		cancel: mutateCancel,
	} = useMutation();

	const handleUpsert = async (e) => {
		e.preventDefault();
		const formData = {
			package_name: packageName,
		};
		const url = isEdit
			? route("api.f3.package.names.update", {
					id: selectedPackage.id,
				})
			: route("api.f3.package.names.store");

		const method = isEdit ? "PATCH" : "POST";

		try {
			const response = await mutate(url, {
				method,
				body: formData,
			});

			if (isEdit) {
				updatePackage(response?.data || null);
			}

			if (!isEdit) {
				appendPackage(response?.data || null);
			}

			toast.success(
				isEdit
					? "F3 Package updated successfully!"
					: "F3 Package created successfully!",
			);

			router.visit(route("f3.package.index"));
		} catch (err) {
			console.error("Upsert failed:", err.message);
			toast.error(err.message);
		}
	};

	const handleReset = () => {
		setPackageName("");
	};

	return (
		<>
			<h1 className="text-base font-bold">
				{isEdit ? "Edit F3 Package" : "Add New F3 Package"}
			</h1>
			<div>
				<form
					onSubmit={handleUpsert}
					className="max-w-lg p-4 space-y-4 rounded-lg"
					method="POST"
				>
					<fieldset className="fieldset">
						<legend className="fieldset-legend">Package</legend>
						<input
							type="text"
							className="w-64 input input-bordered"
							placeholder="Type Package Name"
							value={packageName}
							onChange={(e) => setPackageName(e.target.value)}
							required
						/>
						<p className="label">e.g. QSOP</p>
					</fieldset>

					{/* Buttons */}
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
							{isEdit ? "Edit" : "Add"}
						</button>
					</div>
				</form>
			</div>
		</>
	);
};

export default F3PackageUpsert;
